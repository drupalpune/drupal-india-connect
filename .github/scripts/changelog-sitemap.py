#!/usr/bin/env python3
"""Lists one day's public-facing content changes on drupalasia.org.

Reads only the public sitemap (Simple XML Sitemap, regenerated on every cron
run), so it sees exactly what an anonymous visitor can see: published pages,
never drafts. No server access.

  UPDATED  lastmod falls on the day (a new page also shows up here)
  NEW      in the sitemap but not in the previous snapshot
  REMOVED  in the previous snapshot but gone from the sitemap
           (unpublished, deleted or moved)

NEW/REMOVED need the snapshot of the previous run's URLs, so they only make
sense for the latest day. Pass --write-snapshot to save today's URL list.

Usage: changelog-sitemap.py YYYY-MM-DD SNAPSHOT_FILE [--write-snapshot]
Output: one "- [...]" line per change on stdout.
"""
import datetime
import html
import re
import sys
import urllib.request
import xml.etree.ElementTree as ET

SITEMAP = "https://drupalasia.org/sitemap.xml"
NS = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
IST = datetime.timezone(datetime.timedelta(hours=5, minutes=30))


def fetch(url):
    req = urllib.request.Request(url, headers={"User-Agent": "drupalasia-changelog"})
    with urllib.request.urlopen(req, timeout=30) as resp:
        return resp.read()


def title_of(url):
    try:
        page = fetch(url).decode("utf-8", "replace")
    except Exception:
        return ""
    m = re.search(r"<title[^>]*>(.*?)</title>", page, re.S | re.I)
    if not m:
        return ""
    # "Page | DrupalAsia Connect" -> "Page"
    return html.unescape(m.group(1)).split(" | ")[0].strip()


def main():
    day = datetime.date.fromisoformat(sys.argv[1])
    snapshot_path = sys.argv[2]
    write_snapshot = "--write-snapshot" in sys.argv[3:]

    root = ET.fromstring(fetch(SITEMAP))
    pages = {}
    for url in root.findall("sm:url", NS):
        loc = url.findtext("sm:loc", "", NS).strip()
        lastmod = url.findtext("sm:lastmod", "", NS).strip()
        if loc:
            pages[loc] = lastmod

    try:
        with open(snapshot_path) as f:
            previous = {line.strip() for line in f if line.strip()}
    except FileNotFoundError:
        previous = None  # First run: nothing to compare against.

    lines = []
    for loc, lastmod in sorted(pages.items()):
        changed_today = False
        if lastmod:
            changed = datetime.datetime.fromisoformat(lastmod).astimezone(IST).date()
            changed_today = changed == day
        is_new = previous is not None and write_snapshot and loc not in previous
        if changed_today or is_new:
            kind = "NEW" if is_new else "UPDATED"
            title = title_of(loc)
            lines.append(f'- [{kind}] "{title}" {loc}' if title else f"- [{kind}] {loc}")

    if previous is not None and write_snapshot:
        for loc in sorted(previous - pages.keys()):
            lines.append(f"- [REMOVED] {loc}")

    print("\n".join(lines))

    if write_snapshot:
        with open(snapshot_path, "w") as f:
            f.write("\n".join(sorted(pages)) + "\n")


if __name__ == "__main__":
    main()
