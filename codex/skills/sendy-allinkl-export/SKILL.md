---
name: sendy-allinkl-export
description: "Use when exporting Sendy contacts or newsletter recipients from pirate.arrr.co on the all-inkl server."
metadata:
  short-description: Export Sendy contacts from all-inkl DB
---

# Sendy all-inkl Export

This skill is scoped to this device and this Sendy installation:

- Local project dir: `/home/redbeard/pirate/projects/sendy-contacts`
- SSH target: `ssh-w010c8ea@w010c8ea.kasserver.com`
- SSH key: `/home/redbeard/.ssh/id_ed25519_gic_allinkl`
- Sendy install: `/www/htdocs/w010c8ea/sendy`
- Sendy config: `/www/htdocs/w010c8ea/sendy/includes/config.php`
- Preferred execution location on server: `/tmp`

Use this skill when the user gives a non-technical request like “give me a CSV of all Sendy contacts”, “export OMClub sponsors”, or “include unsubscribed/bounced too”.

## Output

Produce nice CSV files on this device in:

```text
/home/redbeard/pirate/projects/sendy-contacts/
```

Default all-brand DB export files:

```text
sendy_contacts_db.csv
sendy_contacts_db_omclub.csv
```

`sendy_contacts_db.csv` contains all brands. `sendy_contacts_db_omclub.csv` contains only contacts whose discovered `source_brand_name` includes `OMClub` or `OMClub Sponsors`.

For OMClub-specific requests, use the dedicated OMClub exporter instead. It queries only the target brands from the database and writes two separate files:

```text
sendy_contacts_db_omclub.csv
sendy_contacts_db_omclub_sponsors.csv
```

Contacts that exist in both brands must appear in both files. Do not filter these files from an all-brand artifact.

Columns must match the curated handoff table:

```text
full_name, first_name, last_name, company, email, languages, source_brand_name, source_list_names, status, joined
```

For the dedicated OMClub exporter, drop `source_brand_name` because the file itself indicates the brand:

```text
full_name, first_name, last_name, company, email, languages, source_list_names, status, joined
```

These are generally the end-user fields of interest: who the person is, company, email, inferred language, where in Sendy they came from, subscription status, and when they joined.

## Rules

Use direct DB export, not Sendy API or web scraping, when SSH works. The API/web exporter remains historical fallback only.

The DB export includes all subscriber statuses available in the database, not only active contacts. Status derivation:

- `bounced` if Sendy bounced flag is set
- `unsubscribed` if Sendy unsubscribed flag is set
- `complained` if complaint flag is set
- `unconfirmed` if confirmed flag exists and is false
- otherwise `active`

Duplicates are handled by email address within the exported scope:

- lower-case email is the dedupe key
- one output row per email
- `languages`, `source_brand_name` when present, `source_list_names`, and `status` are merged with `;`
- for other fields, keep the first non-empty value found
- for the dedicated OMClub exporter, dedupe separately inside `OMClub` and `OMClub Sponsors`; the same email may appear in both output files

Language is inferred from list names and email domains:

- any `DE` or `GER` context means `DE`
- any `EN` or `ENG` context means `EN`
- email domains ending in `.de`, `.at`, or `.ch` add `DE`
- email domains ending in `.uk`, `.co.uk`, `.us`, `.ie`, `.au`, `.nz`, or `.ca` add `EN`
- if both are found for the same email, output `DE;EN`
- only if neither is found, output `unknown`

Name/company normalization:

- `first_name`: prefer `first_name`, then `firstname`, then split from `name`
- `last_name`: prefer `last_name`, `lastname`, `surname`, then `nachname`
- `full_name`: join normalized first and last name
- `company`: prefer `company`, then `company_name`, then `companyname`

## Workflow

1. For all-brand exports, use `scripts/export_sendy_contacts_db.php`.
2. For OMClub/OMClub Sponsors exports, use `scripts/export_sendy_omclub_contacts_db.php`.
2. Copy it to the server `/tmp`, not the web root.
3. Run it with `SENDY_CONFIG` pointing to the real Sendy config.
4. Copy the two CSV files back to the local project dir.
5. Verify headers and row counts locally.
6. Report local file paths and counts.

Commands:

```bash
scp -i /home/redbeard/.ssh/id_ed25519_gic_allinkl scripts/export_sendy_contacts_db.php ssh-w010c8ea@w010c8ea.kasserver.com:/tmp/export_sendy_contacts_db.php
ssh -i /home/redbeard/.ssh/id_ed25519_gic_allinkl ssh-w010c8ea@w010c8ea.kasserver.com 'php -l /tmp/export_sendy_contacts_db.php'
ssh -i /home/redbeard/.ssh/id_ed25519_gic_allinkl ssh-w010c8ea@w010c8ea.kasserver.com 'SENDY_CONFIG=/www/htdocs/w010c8ea/sendy/includes/config.php php /tmp/export_sendy_contacts_db.php /tmp/sendy_contacts_db.csv /tmp/sendy_contacts_db_omclub.csv'
scp -i /home/redbeard/.ssh/id_ed25519_gic_allinkl ssh-w010c8ea@w010c8ea.kasserver.com:/tmp/sendy_contacts_db.csv /home/redbeard/pirate/projects/sendy-contacts/sendy_contacts_db.csv
scp -i /home/redbeard/.ssh/id_ed25519_gic_allinkl ssh-w010c8ea@w010c8ea.kasserver.com:/tmp/sendy_contacts_db_omclub.csv /home/redbeard/pirate/projects/sendy-contacts/sendy_contacts_db_omclub.csv
```

Dedicated OMClub commands:

```bash
scp -i /home/redbeard/.ssh/id_ed25519_gic_allinkl scripts/export_sendy_omclub_contacts_db.php ssh-w010c8ea@w010c8ea.kasserver.com:/tmp/export_sendy_omclub_contacts_db.php
ssh -i /home/redbeard/.ssh/id_ed25519_gic_allinkl ssh-w010c8ea@w010c8ea.kasserver.com 'php -l /tmp/export_sendy_omclub_contacts_db.php'
ssh -i /home/redbeard/.ssh/id_ed25519_gic_allinkl ssh-w010c8ea@w010c8ea.kasserver.com 'SENDY_CONFIG=/www/htdocs/w010c8ea/sendy/includes/config.php php /tmp/export_sendy_omclub_contacts_db.php /tmp/sendy_contacts_db_omclub.csv /tmp/sendy_contacts_db_omclub_sponsors.csv'
scp -i /home/redbeard/.ssh/id_ed25519_gic_allinkl ssh-w010c8ea@w010c8ea.kasserver.com:/tmp/sendy_contacts_db_omclub.csv /home/redbeard/pirate/projects/sendy-contacts/sendy_contacts_db_omclub.csv
scp -i /home/redbeard/.ssh/id_ed25519_gic_allinkl ssh-w010c8ea@w010c8ea.kasserver.com:/tmp/sendy_contacts_db_omclub_sponsors.csv /home/redbeard/pirate/projects/sendy-contacts/sendy_contacts_db_omclub_sponsors.csv
```

Use escalated execution approval for SSH/SCP. Be careful on the server: no deletes, no writes to the web root unless explicitly requested. Leaving temp files in `/tmp` is acceptable unless the user asks for cleanup.

Verification:

```bash
python3 - <<'PY'
import csv
for path in [
    '/home/redbeard/pirate/projects/sendy-contacts/sendy_contacts_db.csv',
    '/home/redbeard/pirate/projects/sendy-contacts/sendy_contacts_db_omclub.csv',
]:
    with open(path, newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        rows = sum(1 for _ in reader)
        print(path)
        print(reader.fieldnames)
        print(rows)
PY
```

Expected headers:

```text
['full_name', 'first_name', 'last_name', 'company', 'email', 'languages', 'source_brand_name', 'source_list_names', 'status', 'joined']
```

Dedicated OMClub expected headers:

```text
['full_name', 'first_name', 'last_name', 'company', 'email', 'languages', 'source_list_names', 'status', 'joined']
```
