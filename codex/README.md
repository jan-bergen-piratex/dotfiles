# Codex Portable Setup

This directory contains the non-secret Codex setup needed to continue Jan's
Codex workflow on another device.

Included:

- `AGENTS.md`
- `SOUL.md`
- `config.toml`
- `skills/` without Codex system skills
- `rules/`
- `memories/`
- `codex-notes/`

Excluded on purpose:

- `auth.json`
- sessions, history, logs, caches, sqlite state
- shell snapshots and temporary plugin clones
- Codex system skills, which the CLI provides itself

## Install

From this repo:

```fish
fish codex/install.fish
```

The installer backs up existing target files under
`~/.codex-portable-backups/<timestamp>/` before copying.

After installing on a new device, authenticate Codex normally on that device.
Do not copy `auth.json` between machines.
