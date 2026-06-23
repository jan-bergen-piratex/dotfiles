---
name: generated-pipeline-scaffold
description: "Use when scaffolding or repairing generated data/LLM pipelines with phase dirs, schemas, prompts, and smoke tests."
---

# Generated Pipeline Scaffold

Use this skill for phase-based generated-data pipelines where deterministic code, prompts, intermediate data, final outputs, and review artifacts must stay separated and reproducible.

Do not use it for ordinary app scaffolds, one-off scripts with no recoverable state, or heavyweight orchestration unless the user explicitly asks for that.

## Core Stance

Build a small blueprint that can survive interrupted runs, partial failures, and later review.

Rules:
- Keep deterministic code separate from prompt text.
- Keep raw inputs, intermediate generated data, final outputs, and review artifacts in distinct paths.
- Prefer phase directories and explicit contracts over hidden framework state.
- Use stable record IDs, manifests, smoke fixtures, and exact README commands.
- Avoid premature schema rigidity: make identity, status, provenance, and failure shape strict; keep phase payloads flexible until review stabilizes them.
- Do not add Airflow, LangChain, dashboards, vector stores, databases, queues, or broad dependency stacks unless the project already uses them or the user asks.

## First Pass

Before editing, inspect the target project:

```bash
pwd
find . -maxdepth 2 -type f | sort | sed -n '1,120p'
find . -maxdepth 2 -type d | sort | sed -n '1,120p'
find . -maxdepth 2 \( -name Cargo.toml -o -name pyproject.toml -o -name package.json -o -name Makefile -o -name justfile \) -print
```

Use the existing stack when present. If there is no stack, keep the scaffold language-light. Use Rust/cargo for durable CLIs when the repo is already Rust, performance matters, or Jan asks for production-grade tooling.

Ask only if the destination directory or target stack is genuinely ambiguous.

## Default Layout

Adapt names to the actual domain, but keep these ownership boundaries:

```text
PROJECT/
  README.md
  .env.example
  prompts/
  schemas/
    envelope.schema.json
    contracts/
  scripts/
  src/
  tests/
  data/
    raw/
    fixtures/smoke/
    phase1_extracted/
    phase2_enriched/
    phase3_reviewed/
  outputs/
    handoff/
    manifests/
  review/
    samples/
    decisions.md
```

Use `apply_patch` for file contents. Use `chmod +x scripts/smoke.sh` after creating executable scripts.

## Phase Design

Name phases by their contract, not only by implementation:

```text
phase1_messages
phase2_contacts
phase3_enrichment
phase4_reviewed_handoff
```

Each phase must define:
- input path pattern
- output path pattern
- fixture command
- real-data command placeholder
- resume behavior
- record identity rule
- failure representation
- acceptance checks

For mbox and lead-enrichment projects, a good default is:

```text
data/raw/
data/fixtures/smoke/
data/phase1_extracted/*.jsonl
data/phase2_enriched/*.jsonl
data/phase3_reviewed/*.jsonl
outputs/handoff/*.csv
outputs/manifests/*.json
review/samples/*.csv
```

## Record Contract

Every generated JSONL record should have a stable envelope:

```json
{
  "record_id": "deterministic-id",
  "source_id": "source-message-or-row-id",
  "phase": "phase2_enriched",
  "status": "ok",
  "payload": {},
  "provenance": {
    "source_path": "data/raw/input.mbox",
    "prompt_id": "phase2_enrich",
    "prompt_version": "2026-06-01",
    "model": null,
    "run_id": "20260601T120000Z"
  },
  "error": null
}
```

Required envelope fields: `record_id`, `source_id`, `phase`, `status`, `payload`, `provenance`, `error`.

Allowed statuses: `pending`, `ok`, `skipped`, `needs_review`, `failed`.

Make `record_id` deterministic from stable source facts, such as message ID, normalized email, source path plus byte offset, or content hash. Never use array indexes as durable IDs.

## Schema Strategy

Create `schemas/envelope.schema.json` early, but keep phase payloads flexible. Only lock down payload fields once real review has shown which fields are stable.

For each phase, create `schemas/contracts/phaseN_output.md` with:
- producer command
- input files
- output files
- record identity
- required envelope fields
- payload fields
- allowed statuses
- resume behavior
- failure behavior
- review handoff
- acceptance checks

## Prompt Files

LLM prompts live in `prompts/`, never buried inline in code except for loading by path.

Each prompt file should include:
- `prompt_id`
- version
- purpose
- input contract
- output contract
- rules
- one small positive example
- one weak-evidence example

Prompts should be versioned by file content and referenced in manifests.

## README Commands

The generated project README must contain copy-pasteable commands, not vague prose.

Minimum command set:
- run smoke test on tiny fixture
- run each phase on fixture data
- run generated or LLM phases in dry-run/mock mode when possible
- validate generated artifacts
- produce final handoff from reviewed fixture data

If the actual implementation uses `cargo run`, `python -m`, `uv run`, `make`, or `just`, use that project's real commands instead.

## Smoke Tests

`scripts/smoke.sh` must be fast, deterministic, and safe to run repeatedly.

It should verify:
- required directories exist
- smoke fixture exists
- phase commands run on fixture data
- every JSONL line parses
- required envelope fields exist
- `record_id` values are non-empty and unique per output file
- output manifest exists
- final handoff file has expected headers
- no real network or paid LLM call runs unless explicitly requested

Use a tiny fixture with 2-5 records, including one edge case that should become `needs_review`, `skipped`, or `failed`.

## Manifest

Every non-trivial run should write a manifest under `outputs/manifests/` with command, phase, input paths, output paths, prompt versions, schema versions, counts, git state if available, and timestamp. If git is unavailable or dirty, record that fact instead of blocking the run.

## Gitignore Defaults

Protect private and generated data while keeping reproducible fixtures and contracts:

```gitignore
.env
data/raw/*
data/phase1_extracted/*
data/phase2_enriched/*
data/phase3_reviewed/*
outputs/handoff/*
outputs/manifests/*
review/samples/*

!data/raw/.gitkeep
!outputs/handoff/.gitkeep
!outputs/manifests/.gitkeep
!review/samples/.gitkeep
!data/fixtures/**
```

Adjust if the user explicitly wants generated artifacts committed.

## Done Criteria

A scaffold is complete when phase directories exist, README has exact fixture and real-data command shapes, prompts exist for each generated phase, schemas and output contracts exist, smoke fixture exists, smoke test runs or the final report states why it could not run, generated state is recoverable via stable IDs and manifests, and no secrets or unrelated project files were touched.
