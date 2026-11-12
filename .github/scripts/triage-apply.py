#!/usr/bin/env python3
"""Applies Claude's triage decision to a new issue.

Claude only writes the decision file. Everything that touches GitHub happens
here, and every value is checked against the allowed set first, so nothing in
an issue body can make the workflow do more than set these fields, assign one
person from the pool and post one comment.

What it does:
- Fills Type, Priority, Effort and Target date, but only where they are empty:
  anything a person already set is kept.
- Assigns the pool member with the least open work (Effort High=3, Medium=2,
  Low=1, unset=2), unless the issue already has an assignee.
- Comments with the deadline and the reason, or, when no deadline could be
  worked out, tags the author and asks for one.

Usage: triage-apply.py ISSUE_NUMBER DECISION_JSON
Env: GH_TOKEN, GITHUB_REPOSITORY, TRIAGE_POOL (space-separated logins),
     TRIAGE_DRY_RUN=1 to print instead of writing.
"""
import datetime
import json
import os
import re
import sys
import urllib.request
import zoneinfo

TYPES = {'Task', 'Bug', 'Feature'}
LEVELS = {'Urgent', 'High', 'Medium', 'Low'}
EFFORTS = {'High', 'Medium', 'Low'}
WEIGHT = {'High': 3, 'Medium': 2, 'Low': 1}
LAST_DAY = datetime.date(2027, 1, 30)  # last day of the event

REPO = os.environ.get('GITHUB_REPOSITORY', 'drupalpune/drupal-india-connect')
OWNER, NAME = REPO.split('/')
POOL = os.environ.get('TRIAGE_POOL', '').split()
DRY = os.environ.get('TRIAGE_DRY_RUN') == '1'

FIELDS = '''issueFieldValues(first: 20) { nodes {
  ... on IssueFieldDateValue { value field { ... on IssueFieldDate { name } } }
  ... on IssueFieldSingleSelectValue { name field { ... on IssueFieldSingleSelect { name } } }
} }'''


def gql(query, **variables):
    req = urllib.request.Request(
        'https://api.github.com/graphql',
        data=json.dumps({'query': query, 'variables': variables}).encode(),
        headers={'Authorization': 'bearer ' + os.environ['GH_TOKEN'],
                 'Content-Type': 'application/json'})
    with urllib.request.urlopen(req, timeout=30) as r:
        out = json.load(r)
    if out.get('errors'):
        raise RuntimeError(json.dumps(out['errors']))
    return out['data']


def field_values(node):
    return {v['field']['name']: v.get('value') or v.get('name')
            for v in node['issueFieldValues']['nodes'] if v.get('field')}


def clean(text, limit=300):
    """One line of plain text: no mentions or markdown links from the model."""
    text = re.sub(r'\s+', ' ', str(text or '')).strip()
    return text.replace('@', '').replace('](', '] (')[:limit]


def open_load():
    load = {}
    for who in POOL:
        data = gql(f'''query($q: String!) {{ search(type: ISSUE, query: $q, first: 100) {{
          nodes {{ ... on Issue {{ {FIELDS} }} }} }} }}''', q=f'repo:{REPO} is:issue is:open assignee:{who}')
        load[who] = sum(WEIGHT.get(field_values(i).get('Effort'), 2) for i in data['search']['nodes'])
    return load


def main(num, decision_path):
    num = int(num)
    with open(decision_path) as f:
        d = json.load(f)

    org = gql('''query($o: String!) { organization(login: $o) {
      issueTypes(first: 20) { nodes { id name } }
      issueFields(first: 20) { nodes {
        ... on IssueFieldSingleSelect { id name options { id name } }
        ... on IssueFieldDate { id name } } } } }''', o=OWNER)['organization']
    types = {t['name']: t['id'] for t in org['issueTypes']['nodes']}
    fields = {f['name']: f for f in org['issueFields']['nodes'] if f}

    issue = gql(f'''query($o: String!, $n: String!, $num: Int!) {{
      repository(owner: $o, name: $n) {{ issue(number: $num) {{
        id author {{ login }} issueType {{ name }}
        assignees(first: 10) {{ nodes {{ login }} }} {FIELDS} }} }} }}''',
                o=OWNER, n=NAME, num=num)['repository']['issue']
    have = field_values(issue)
    author = issue['author']['login'] if issue['author'] else None

    today = datetime.datetime.now(zoneinfo.ZoneInfo('Asia/Kolkata')).date()
    target = None
    if re.fullmatch(r'\d{4}-\d{2}-\d{2}', str(d.get('target_date') or '')):
        target = datetime.date.fromisoformat(d['target_date'])
        if not today <= target <= LAST_DAY:
            target = None  # in the past or after the event: treat as unknown

    updates = []
    want = {'Priority': d.get('priority') if d.get('priority') in LEVELS else None,
            'Effort': d.get('effort') if d.get('effort') in EFFORTS else None}
    for name, value in want.items():
        if value and not have.get(name) and name in fields:
            opt = next(o['id'] for o in fields[name]['options'] if o['name'] == value)
            updates.append({'fieldId': fields[name]['id'], 'singleSelectOptionId': opt})
    if target and not have.get('Target date') and 'Target date' in fields:
        updates.append({'fieldId': fields['Target date']['id'], 'dateValue': target.isoformat()})
    new_type = d.get('type') if d.get('type') in TYPES and not issue['issueType'] else None

    assignees = [a['login'] for a in issue['assignees']['nodes']]
    pick, load = None, {}
    if not assignees and POOL:
        load = open_load()
        pick = min(POOL, key=lambda who: load[who])

    # The comment.
    lines = []
    deadline = have.get('Target date') or (target and target.isoformat())
    if deadline:
        reason = '' if have.get('Target date') else f" {clean(d.get('date_reason'))}"
        lines.append(f'**Target date: {deadline}.**{reason}')
    elif author:
        ask = clean(d.get('question')) or 'Nothing in the issue or on the site ties this to a date.'
        lines.append(f'@{author} when does this need to be done by? {ask} '
                     'Reply with a date, or set the Target date field on this issue.')
    if pick:
        others = ', '.join(f'{w} {load[w]}' for w in POOL if w != pick)
        lines.append(f'Assigned to @{pick} (open work: {pick} {load[pick]}, {others}).')
    summary = ', '.join(f'{k} {v}' for k, v in
                        (('Type', new_type), ('Priority', want['Priority']), ('Effort', want['Effort']))
                        if v and (k == 'Type' or not have.get(k)))
    if summary:
        lines.append(f'Set {summary}. Change any of these if they are wrong.')
    lines.append('<sub>Automatic triage, see `.github/workflows/issue-triage.yml`. '
                 'Key dates live in `.github/triage/key-dates.md`.</sub>')
    body = '\n\n'.join(lines)

    if DRY:
        print(json.dumps({'type': new_type, 'fields': updates, 'assign': pick}, indent=2))
        print(body)
        return

    # Fields need org issue-field permissions that the workflow token may not
    # have; report a failure in the comment instead of losing the comment.
    problems = []
    try:
        if new_type:
            gql('mutation($i: ID!, $t: ID!) { updateIssueIssueType(input: {issueId: $i, issueTypeId: $t}) { clientMutationId } }',
                i=issue['id'], t=types[new_type])
        if updates:
            gql('mutation($i: ID!, $f: [IssueFieldCreateOrUpdateInput!]!) { setIssueFieldValue(input: {issueId: $i, issueFields: $f}) { clientMutationId } }',
                i=issue['id'], f=updates)
    except RuntimeError as e:
        print('Setting fields failed:', e, file=sys.stderr)
        problems.append('could not set the issue fields (see the workflow run log), please set them by hand')
    if pick:
        try:
            gql('''mutation($i: ID!, $u: [ID!]!) { addAssigneesToAssignable(input: {assignableId: $i, assigneeIds: $u}) { clientMutationId } }''',
                i=issue['id'], u=[gql('query($l: String!) { user(login: $l) { id } }', l=pick)['user']['id']])
        except RuntimeError as e:
            print('Assigning failed:', e, file=sys.stderr)
            problems.append(f'could not assign {pick}')
    if problems:
        body += '\n\n**Triage problems:** ' + '; '.join(problems) + '.'
    gql('mutation($i: ID!, $b: String!) { addComment(input: {subjectId: $i, body: $b}) { clientMutationId } }',
        i=issue['id'], b=body)
    print(body)


if __name__ == '__main__':
    main(*sys.argv[1:3])
