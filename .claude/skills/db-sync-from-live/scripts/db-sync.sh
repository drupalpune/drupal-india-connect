#!/usr/bin/env bash
# Syncs the local DDEV database from a remote Drupal site over SSH.
#
# This script never writes to the remote database or leaves files behind on
# the remote host — it only runs `drush sql:dump`, downloads the result, and
# deletes its own temp file. See SKILL.md for the full safety rationale.
#
# Usage:
#   scripts/db-sync.sh [--config PATH] [--yes]
#
# Config is read from --config, or $DB_SYNC_CONFIG, or ./.claude/db-sync.local.env.
# See config.example.env for the variables it needs.

set -euo pipefail

SKILL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_ROOT="$(cd "$SKILL_DIR/../../.." && pwd)"
CONFIG_PATH="${DB_SYNC_CONFIG:-$PROJECT_ROOT/.claude/db-sync.local.env}"
ASSUME_YES=0

while [ $# -gt 0 ]; do
  case "$1" in
    --config) CONFIG_PATH="$2"; shift 2 ;;
    --yes) ASSUME_YES=1; shift ;;
    *) echo "Unknown argument: $1" >&2; exit 1 ;;
  esac
done

log()  { printf '\n== %s ==\n' "$1"; }
fail() { printf 'ERROR: %s\n' "$1" >&2; exit 1; }

# --- 1. Load config, and refuse to run with a config file that git can see. ---

[ -f "$CONFIG_PATH" ] || fail "No config file at $CONFIG_PATH. Copy config.example.env there and fill it in."

if git -C "$PROJECT_ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  if git -C "$PROJECT_ROOT" check-ignore -q "$CONFIG_PATH" 2>/dev/null; then
    : # good, git ignores it
  elif git -C "$PROJECT_ROOT" ls-files --error-unmatch "$CONFIG_PATH" >/dev/null 2>&1; then
    fail "$CONFIG_PATH is TRACKED by git. This file holds your SSH host/user and remote paths — untrack it (git rm --cached) and add it to .gitignore before running this again."
  else
    printf 'WARNING: %s is not covered by any .gitignore rule Claude could find.\n' "$CONFIG_PATH" >&2
    printf 'It will not be committed by this script, but double check before you `git add -A`.\n' >&2
  fi
fi

# shellcheck disable=SC1090
source "$CONFIG_PATH"

: "${SSH_HOST:?Set SSH_HOST in $CONFIG_PATH}"
: "${SSH_PORT:=22}"
: "${SSH_USER:?Set SSH_USER in $CONFIG_PATH}"
: "${REMOTE_APP_PATH:?Set REMOTE_APP_PATH in $CONFIG_PATH}"
REMOTE_PHP_BIN="${REMOTE_PHP_BIN:-}"
LOCAL_DDEV_PROJECT="${LOCAL_DDEV_PROJECT:-}"

SSH_TARGET="$SSH_USER@$SSH_HOST"
SSH_OPTS=(-p "$SSH_PORT")
SCP_OPTS=(-P "$SSH_PORT")
DDEV_ARGS=()
[ -n "$LOCAL_DDEV_PROJECT" ] && DDEV_ARGS=(--project="$LOCAL_DDEV_PROJECT")

STAMP="$(date +%Y%m%d-%H%M%S 2>/dev/null || echo sync)"
LOCAL_BACKUP="/tmp/db-sync-local-backup-$STAMP.sql.gz"
LOCAL_DOWNLOAD="/tmp/db-sync-live-$STAMP.sql.gz"
REMOTE_TMP_DIR="/tmp/db-sync-$STAMP-$$"

# --- 2. Back up the local DB before anything can overwrite it. ---

log "Backing up local DDEV database"
ddev "${DDEV_ARGS[@]}" export-db --file="$LOCAL_BACKUP"
echo "Local backup saved to $LOCAL_BACKUP (keep this until you've confirmed the sync looks right)."

# --- 3. Find a PHP binary on the remote host that drush can actually run with. ---

log "Checking remote PHP/drush"
remote_php="$REMOTE_PHP_BIN"
if [ -z "$remote_php" ]; then
  remote_php="$(ssh "${SSH_OPTS[@]}" "$SSH_TARGET" \
    "cd '$REMOTE_APP_PATH' && PATH=\$PATH vendor/bin/drush status >/dev/null 2>&1 && echo php" || true)"
  [ "$remote_php" = php ] && remote_php="php" || remote_php=""
fi

if [ -z "$remote_php" ]; then
  fail "Could not run drush with the default remote PHP. If this host keeps multiple PHP versions (shared hosting often does), find a newer binary yourself — e.g. \`ssh ... 'ls /opt/alt/php*/usr/bin/php 2>/dev/null'\` — and set REMOTE_PHP_BIN to it in $CONFIG_PATH."
fi

# --- 4. Dump the remote DB into a throwaway temp dir, never the site's own backups folder. ---

log "Dumping remote database"
ssh "${SSH_OPTS[@]}" "$SSH_TARGET" bash -s <<EOF
set -euo pipefail
mkdir -p '$REMOTE_TMP_DIR'
cd '$REMOTE_APP_PATH'
PATH=\$(dirname '$remote_php'):\$PATH vendor/bin/drush sql:dump --gzip --result-file='$REMOTE_TMP_DIR/dump.sql'
EOF

log "Downloading dump"
scp "${SCP_OPTS[@]}" "$SSH_TARGET:$REMOTE_TMP_DIR/dump.sql.gz" "$LOCAL_DOWNLOAD"

# --- 5. Clean up the remote temp file — never leave dumps sitting on production. ---

log "Cleaning up remote temp file"
ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "rm -rf '$REMOTE_TMP_DIR'"
if ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "[ -e '$REMOTE_TMP_DIR' ]" 2>/dev/null; then
  fail "Remote temp dir $REMOTE_TMP_DIR still exists after cleanup — check it manually."
fi
echo "Confirmed $REMOTE_TMP_DIR no longer exists on the remote host."

# --- 6. Import locally. ---

if [ "$ASSUME_YES" -ne 1 ]; then
  read -r -p "Import $LOCAL_DOWNLOAD into local DDEV database now? [y/N] " reply
  case "$reply" in
    [yY]*) ;;
    *) echo "Skipping import. Dump kept at $LOCAL_DOWNLOAD."; exit 0 ;;
  esac
fi

log "Importing into local DDEV database"
ddev "${DDEV_ARGS[@]}" import-db --file="$LOCAL_DOWNLOAD"

# --- 7. Verify. ---

log "Rebuilding cache and checking for config drift"
ddev "${DDEV_ARGS[@]}" drush cr
ddev "${DDEV_ARGS[@]}" drush config:status || true
ddev "${DDEV_ARGS[@]}" drush status

echo
echo "Done. Local backup (pre-sync): $LOCAL_BACKUP"
echo "      Downloaded live dump:     $LOCAL_DOWNLOAD"
echo "Delete these once you're confident the sync is good."
