#!/usr/bin/env bash
# Collects everything the issue triage workflow needs into one brief: the
# issue itself, the key dates file, and the date lines from the live site's
# main pages. Claude reads the brief and writes a decision file; it never
# calls gh or the network itself.
#
# Usage: triage-gather.sh ISSUE_NUMBER OUTFILE
# Needs: `gh` authenticated (GH_TOKEN).
set -euo pipefail

NUM="$1"
OUT="$2"
REPO="${GITHUB_REPOSITORY:-drupalpune/drupal-india-connect}"
SITE="https://drupalasia.org"
PAGES="/ /ticket-rates-and-details /program-at-a-glance /ai-summit /become-a-sponsor /sponsors-and-partners"
MONTHS='jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec'

{
  echo "# Triage brief for issue #$NUM"
  echo
  echo "Today (IST): $(TZ=Asia/Kolkata date '+%F (%A)')"
  echo
  echo "## The issue"
  gh issue view "$NUM" --repo "$REPO" --json title,author,body,comments --template \
'Title: {{.title}}
Author: {{.author.login}}

{{.body}}

{{range .comments}}--- Comment by {{.author.login}}:
{{.body}}

{{end}}'
  echo
  echo "## Key dates shared by the organising team"
  sed -n '/^- /p' .github/triage/key-dates.md
  echo
  echo "## Date lines on the live site"
  for p in $PAGES; do
    echo "### $SITE$p"
    # A page that fails to load is reported, not silently treated as empty.
    if html="$(curl -sfL -m 20 -A 'drupalasia-triage' "$SITE$p")"; then
      printf '%s' "$html" |
        perl -0pe 's/<(script|style)[^>]*>.*?<\/\1>//gs; s/<[^>]+>/\n/g; s/&nbsp;|&#160;/ /g; s/&amp;/&/g' |
        perl -ne 's/^\s+|\s+$//g; print "$_\n" if length' |
        grep -iE "\\b($MONTHS)[a-z]*\\b.*[0-9]|[0-9]+ ($MONTHS)|deadline|early bird|last date|closes" |
        sort -u | head -20 | sed 's/^/- /' || true
    else
      echo "- (page could not be loaded)"
    fi
  done
} > "$OUT"
