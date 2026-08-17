---
name: db-sync-from-live
description: Pull the production/staging Drupal database down over SSH into the local DDEV environment, safely. Use this whenever the user asks to sync, refresh, pull, or copy the live/production/remote database to local — phrases like "sync my local db with live", "get a fresh copy of prod", "my local data is stale, pull it from the server", or "refresh the ddev db from staging" should all trigger this, even if they don't say "DDEV" or name this skill directly. Also use it if the user asks to set up or reconfigure this sync process itself (e.g. "where do I put my SSH details for the db sync").
---

# Syncing the local DDEV database from a remote site

This automates a specific, previously-manual process: get a current copy of a
remote Drupal database onto a local DDEV site, without ever writing to the
remote site and without leaving anything behind on it.

## Why the steps are ordered this way

Each step exists because of something that has actually gone wrong before,
not out of caution for its own sake:

1. **Back up local before importing anything.** The import overwrites the
   local database wholesale. If the remote dump turns out to be wrong, or
   the sync was a mistake, the only way back is a backup taken *before* the
   import ran — one taken after is too late.
2. **The remote host's default `php` may be too old for `drush`.** Shared
   hosting in particular often keeps the "default" PHP a version or two
   behind what a current Drupal's Composer dependencies require, alongside
   several newer alternates. If `vendor/bin/drush status` fails with a
   platform/PHP-version complaint, that's the symptom — go looking for a
   newer binary on that host rather than assuming drush itself is broken.
3. **Dump into a throwaway temp directory, not the site's own backups
   folder.** A site may already have its own backup convention (pre-deploy
   dumps, scheduled snapshots, etc.) — mixing an ad-hoc sync dump into that
   directory makes it unclear later which files are which, and risks a
   accidentally different retention/cleanup expectation. A dedicated
   `/tmp/db-sync-<stamp>-<pid>` directory keeps this sync's footprint
   obviously separate and obviously disposable.
4. **Delete the remote temp file and confirm it's gone.** The whole point of
   step 3 is that nothing from this process should persist on the remote
   host once the dump has been downloaded. Don't just issue the `rm` —
   check afterward that the path is actually gone, the same way you'd
   verify a destructive step anywhere else.
5. **Verify after importing.** `drush config:status` after the import tells
   you whether the sync introduced config drift relative to `config/sync` —
   if it reports differences, that's worth surfacing to the user before
   they assume the sync is a clean success. `drush status` confirms the
   site bootstraps at all.

None of this ever writes to the remote database or remote filesystem beyond
the throwaway temp dir it cleans up itself. If a task genuinely calls for
changing something on the remote host, that's a different, much more
sensitive operation — stop and confirm explicitly with the user rather than
folding it into this flow.

## First-time setup — do this yourself, don't hand it to the user

If `.claude/db-sync.local.env` doesn't already exist, that means nobody has
run this on this machine/checkout yet. Set it up rather than telling the
user to go copy a template and fill it in by hand — that's exactly the kind
of manual step this skill exists to remove, and asking them to hand-edit an
env file is worse UX than just asking the questions directly.

1. Check for values already sitting in the conversation (the user may have
   just given you an SSH command, or been troubleshooting on the box) before
   asking anything twice.
2. Ask for whatever's still missing: SSH host, port (default 22), SSH user,
   and the path to the Drupal app root on the server (the directory holding
   `vendor/bin/drush`). Leave `REMOTE_PHP_BIN` blank for now — the script
   detects a PHP-version problem itself on the first run, and it's easier to
   fill that in once you know it's actually needed than to ask the user to
   go hunt for alternate PHP paths speculatively.
3. Write `.claude/db-sync.local.env` yourself (copy `config.example.env`'s
   structure and fill in the values), then `chmod 600` it — it holds
   connection details, so it shouldn't be casually world-readable the way
   the rest of the repo is.
4. Confirm it's actually ignored before doing anything else with it:
   `git check-ignore .claude/db-sync.local.env`. If that prints nothing, stop
   and fix the `.gitignore` situation before continuing — see **Config and
   what never gets committed** below.
5. Do a cheap connectivity check before the full sync — e.g.
   `ssh -p <port> <user>@<host> 'cd <app-path> && vendor/bin/drush status'`
   — so a typo in the host/path surfaces immediately instead of partway
   through a dump.

Once the config file exists (whether you just wrote it or a previous session
did), every future ask on this project is just "run the sync" — you already
have what you need.

## Running it

The logic lives in `scripts/db-sync.sh` — read it once so you know what it's
about to do, then run it rather than re-deriving each SSH/scp/drush call by
hand. It's re-runnable and safe to interrupt: nothing is a partial write.

```bash
.claude/skills/db-sync-from-live/scripts/db-sync.sh
```

It reads connection details from `.claude/db-sync.local.env` (or a path
passed via `--config`, or `$DB_SYNC_CONFIG`). See **Config and what never
gets committed** below before writing real values anywhere.

The script prompts before the actual `ddev import-db` (pass `--yes` to skip
the prompt if the user has already clearly asked for the import to happen
unattended). Everything before that point — remote dump, download, remote
cleanup — runs without touching anything local, so it's safe to let those
steps run and then decide about the import based on what happened.

If `db-sync.sh` fails at the PHP-detection step, that's the cue to go
looking by hand: SSH in and check what's actually on the host (e.g. list
`/opt/alt/php*/usr/bin/php`-style paths, or however that host's control
panel exposes alternate versions) rather than guessing a path. Once you find
one that works, either set `REMOTE_PHP_BIN` in the config file for next time
or pass it through directly.

## Config and what never gets committed

This project's root `.gitignore` already ignores everything under `.claude/`
except `.claude/skills/`, so `.claude/db-sync.local.env` is untracked by
default — that's *why* this config lives there instead of inside the skill
folder. The script itself also refuses to run against a config file that
`git ls-files` shows as tracked, as a second check in case that ignore rule
ever changes.

Before this skill (or anything it produces) is committed or pushed anywhere
public, double check:

- [ ] `.claude/db-sync.local.env` — or any file holding a real SSH host,
      port, username, remote path, or database credential — is not staged.
      Run `git status` and look for it.
- [ ] Nothing in this skill's own files (`SKILL.md`, `scripts/db-sync.sh`,
      `config.example.env`) has been edited to contain a real value in place
      of a placeholder — it's easy to paste a working example while
      debugging and forget to revert it before committing.
- [ ] Downloaded dumps (`/tmp/db-sync-local-backup-*.sql.gz`,
      `/tmp/db-sync-live-*.sql.gz`) live under `/tmp`, not inside the repo,
      and aren't referenced from anywhere that gets committed.
- [ ] Terminal transcripts, commit messages, or PR descriptions written
      while using this skill don't quote the real host/user/path either.

If any of this has already leaked into git history, treat it like a leaked
credential — rotating the SSH key/password is more reliable than trying to
scrub history.
