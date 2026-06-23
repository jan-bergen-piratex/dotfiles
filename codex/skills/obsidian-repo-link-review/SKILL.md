---
name: obsidian-repo-link-review
description: "Use when Jan provides an obsidian://open link, especially links Manuel sent for review, and Codex must resolve the referenced file inside a git-backed vault/repo such as tech-brain or piratex-brain, pull/update the corresponding repo using the relevant sync skill when applicable, read the file, and give Jan a quick deslopified overview or opinion."
---

# Obsidian Repo Link Review

Resolve Obsidian links as repo-backed file references, not as evidence that Jan's local Obsidian vault is authoritative.

## Core Assumption

When Jan sends an `obsidian://open?...` link, assume Manuel is using Obsidian to view git repos such as `tech-brain`, while Jan is asking Codex to inspect the underlying repo file.

Do not stop after checking Jan's active Obsidian vault. Decode the link and look for the file in known repo checkouts.

## Workflow

1. Decode the Obsidian URL.
   - Parse query keys `vault` and `file`.
   - URL-decode `%20`, `%2F`, umlauts, and other escaped characters.
   - Treat `file` as a repo-relative path without adding `.md` only after checking both forms.

2. Map the vault to candidate repos.
   - `Tech Brain`, `tech-brain`, or technical/Mary/ops docs: `/home/jan/pirate/tech-brain`.
   - `piratex-brain`, `PIRATEx Brain`, company knowledge, people, projects, or business context: `/home/jan/pirate/piratex-brain`.
   - If the vault name is ambiguous, search likely repo roots under `/home/jan/pirate` before asking Jan.

3. Pull or update the corresponding repo before reading when freshness matters.
   - For `tech-brain` or `piratex-brain`, use the `mary-brain-git-sync` skill's local repo verification/pull discipline.
   - Prefer non-destructive commands: `git -C <repo> status --short --branch`, `git -C <repo> fetch origin`, then inspect whether local can fast-forward.
   - Do not reset, clean, overwrite, or directly edit live Mary brain checkouts.
   - If local dirty state blocks a pull, report it and read the current local file only if Jan asked for a quick read.

4. Resolve the file path.
   - Check exact `<repo>/<decoded file>`.
   - Check `<repo>/<decoded file>.md`.
   - If missing, search with `rg --files <repo>` for:
     - the basename;
     - date prefix such as `260619`;
     - normalized words from the slug, including both umlaut and ASCII variants.
   - If several files match, choose the closest path and state the choice.

5. Read the file and answer Jan.
   - Give a quick overview first: what the file is about, why it exists, and the decision it is trying to support.
   - If Jan asks for opinion/review, start from a neutral prior and separate agreement, disagreement, risks, and unknowns.
   - Use `concisinator` in `deslopifier` mode when summarizing or rewriting the file's argument: extract substance, remove fluff, preserve caveats.
   - Keep output short unless Jan asks for a deep review.

## Output Shape

Default response:

```text
I found it at: <repo-relative path>

Short version: <2-4 sentences>

My take:
- <agreement or strongest useful point>
- <main risk / weak assumption>
- <recommended next step>
```

If the file cannot be found:

```text
I decoded the link as:
- vault: <vault>
- file: <decoded path>

I checked:
- <repo/path>
- <search patterns>

I could not find the file. Most likely: <sync issue / wrong repo / unsaved note>.
```

## Boundaries

- Do not rely on `/home/jan/Dokumente/Obsidian Vault` unless the linked file is actually found there.
- Do not modify repo files unless Jan explicitly asks.
- Do not sync Mary live brains just to read a document. Only use live sync when the task is to make Mary use changed docs.
- Do not guess file contents from the filename.
