---
name: spreadsheet-handoff
description: "Use for CSV/XLSX/Sheets handoffs, lead lists, CRM imports, evidence columns, formula safety, or readable exports."
---

# Spreadsheet Handoff

Create spreadsheets that are safe to open, easy to review, and useful to nontechnical recipients. Favor practical handoff quality over clever formatting.

## Default Workflow

1. Identify the target: CSV, XLSX, Google Sheets, CRM import, or human review.
2. Design columns before exporting. Put review/action columns first, evidence and machine/debug columns later.
3. Write files with a real CSV/spreadsheet library. Never build CSV rows by string concatenation.
4. Escape formula-like cell values before export.
5. Validate by importing or round-tripping the file with a parser.
6. Report the file path, row count, column count, import assumptions, and any remaining uncertainty.

If the target is unknown, default to UTF-8 CSV with comma delimiter, double-quote escaping, one header row, and no explanatory rows before the header.

## CSV Safety

Use structured writers such as Python `csv`, Ruby `CSV`, pandas, `openpyxl`, or another proper spreadsheet library.

Formula-escape user-controlled or source-derived text before writing CSV/XLSX cells. Treat a value as dangerous if its first non-space character is one of:

```text
= + - @
```

Also treat leading tab or carriage return as dangerous. Prefix dangerous text with a single quote unless the destination has a stricter known convention.

Keep canonical data intact when exact values matter. For example, use both `website` and `website_display` if a safe display value would alter an exact import value.

Protect common spreadsheet hazards:
- preserve leading zeros in phone numbers, postal codes, IDs, and SKUs by storing them as text
- normalize line endings intentionally
- use real newlines inside quoted CSV fields for multiline cells
- do not put formulas in handoff exports unless explicitly requested
- do not include hidden calculations that nontechnical reviewers cannot inspect

## Readable Multiline Cells

Use multiline cells only when they improve review speed. Prefer short labeled lines:

```text
Reason: Matches sponsor profile
Evidence: Pricing page mentions enterprise events
Gap: No direct decision-maker found
```

Avoid JSON blobs, markdown tables, HTML, dense paragraphs, or more than 4-6 short lines in one cell. If a cell becomes hard to scan, split it into columns such as `reason`, `evidence_excerpt`, `uncertainty_notes`, and `next_action`.

## Column Design

For human review or sales handoff, optimize the left side of the sheet for action:

```text
company
contact_name
role_title
email
linkedin_url
website
fit_status
priority
reason
next_action
owner
```

Then preserve supporting context:

```text
confidence
uncertainty_notes
evidence_path
evidence_url
evidence_excerpt
source_name
source_updated_at
record_id
raw_source_id
```

Use consistent, filterable values. Prefer `confirmed`, `probable`, `needs_review`, `not_found`, `excluded` over freeform status text.

Keep one entity per row unless the requested workflow needs otherwise. Do not merge cells in handoff files. Do not add decorative formatting that makes sorting or importing harder.

For sales handoffs, include a clear `next_action` whenever possible. A row with interesting research but no next action is usually unfinished.

## Evidence And Uncertainty

Preserve evidence paths and source URLs exactly enough that a reviewer can retrace the conclusion. When available, include `evidence_path`, `evidence_url`, `evidence_excerpt`, and `source_updated_at`.

Do not hide uncertainty in prose. Put it in `uncertainty_notes` and pair it with `confidence`.

If evidence is missing, say so plainly with `not_found`, `not_verified`, or `needs_review`. Do not invent confidence.

## Import Checks

Minimum CSV checks:
- parse the file with a CSV reader
- confirm row count and header count
- confirm every row has the same number of columns
- spot-check fields containing commas, quotes, newlines, leading zeros, and formula-like prefixes
- confirm UTF-8 encoding unless another encoding was requested

Minimum XLSX checks:
- reopen the workbook with a spreadsheet library
- confirm sheet names, headers, row count, and key cell values
- check wrapped multiline cells if used
- confirm dangerous values were escaped and are stored as text where needed

If GUI import could not be tested, say that explicitly and report parser-based checks.

## Final Handoff

Return:
- output path
- format
- row count and column count
- import settings the recipient needs
- known caveats or unresolved uncertainty
- whether formula escaping and import validation were performed
