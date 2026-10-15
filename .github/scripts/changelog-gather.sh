#!/usr/bin/env bash
# Collects one day's activity (IST) into a plain-text brief for the daily
# changelog workflow. Claude reads the brief and writes the CHANGELOG.md entry;
# it never calls git or gh itself.
#
# Usage: changelog-gather.sh YYYY-MM-DD OUTFILE [CONTENT_FILE]
# CONTENT_FILE is the output of changelog-sitemap.py (public content changes
# on the live site); omit it if unavailable.
# Needs: git history of origin/main + tags, and `gh` authenticated (GH_TOKEN).
set -euo pipefail

DAY="$1"
OUT="$2"
CONTENT="${3:-}"
REPO="${GITHUB_REPOSITORY:-drupalpune/drupal-india-connect}"
START="${DAY}T00:00:00+05:30"
END="$(TZ=Asia/Kolkata date -d "$DAY + 1 day" +%F 2>/dev/null || TZ=Asia/Kolkata date -j -v+1d -f %F "$DAY" +%F)T00:00:00+05:30"

{
  echo "# Activity for $DAY (IST, Asia/Kolkata)"
  echo
  echo "## Commits on main"
  echo "Format: sha | release tag that first contains it (or UNRELEASED) | subject"
  echo "UNRELEASED means merged but not deployed yet: deploys happen only on a release tag."
  # Changelog commits are excluded so the bot doesn't log itself.
  git log origin/main --no-merges --since="$START" --until="$END" \
      --invert-grep --grep='^Changelog:' --format='%h%x09%s' |
  while IFS=$'\t' read -r sha subject; do
    tag="$(git tag --contains "$sha" --sort=creatordate 2>/dev/null | head -1)"
    echo "- $sha | ${tag:-UNRELEASED} | $subject"
  done
  echo

  echo "## Release tags created (each one is a production deploy)"
  git for-each-ref --sort=creatordate --format='%(refname:short) %(creatordate:iso-strict)' refs/tags |
  while read -r tag date; do
    d="$(TZ=Asia/Kolkata date -d "$date" +%F 2>/dev/null || echo "${date%%T*}")"
    if [ "$d" = "$DAY" ]; then echo "- $tag"; fi
  done
  echo

  echo "## Pull requests merged"
  gh pr list --repo "$REPO" --state merged --limit 100 \
     --search "merged:${START}..${END}" \
     --json number,title,url,body \
     -q '.[] | "### PR #\(.number): \(.title)\n\(.url)\n\(.body // "" | .[0:1200])\n"'
  echo

  echo "## Issues closed"
  echo "Some work is content-only and done directly on the live site, so it has no commit."
  echo "The closing comment usually says what was actually done."
  gh issue list --repo "$REPO" --state closed --limit 100 \
     --search "closed:${START}..${END}" \
     --json number,title,url,stateReason |
  jq -r '.[] | "\(.number)\t\(.title)\t\(.url)\t\(.stateReason)"' |
  while IFS=$'\t' read -r num title url reason; do
    echo "### Issue #$num: $title"
    echo "$url"
    echo "Closed as: $reason"
    last="$(gh issue view "$num" --repo "$REPO" --json comments \
            -q '.comments[-1].body // ""' | head -c 1500)"
    if [ -n "$last" ]; then echo "Last comment:"; echo "$last"; fi
    echo
  done
  echo

  echo "## Content changes on the live site"
  echo "From the public sitemap: pages NEW, UPDATED (edited that day) or REMOVED (no longer public)."
  echo "Usually edits made in the CMS, so they have no commit. Says that a page changed, not what."
  if [ -z "$CONTENT" ] || [ ! -f "$CONTENT" ]; then
    echo "(Not available for this run.)"
  elif [ -s "$CONTENT" ]; then
    cat "$CONTENT"
  else
    echo "(None.)"
  fi
} > "$OUT"

# Tell the workflow whether there is anything to write about.
if grep -qE '^- [0-9a-f]{7,} \||^### (PR|Issue) #|^- \[' "$OUT"; then
  echo "has_activity=true"
else
  echo "has_activity=false"
fi
