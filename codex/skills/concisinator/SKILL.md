---
name: concisinator
description: "Use when rewriting docs, prompts, notes, specs, or reports to be shorter, clearer, and less rhetorical."
---

# Concisinator

Rewrite text so substance stays and noise dies.

## Trigger

Use when user asks for:

- concise version;
- autism-readable version;
- shorter but same content;
- less aspirational / less rhetorical / less consultant prose;
- clearer hierarchy;
- deslopify / cut the bullshit / remove slop;
- `$concisinator`;
- modes: `light`, `regular`, `high`, `ultra`;
- tag: `deslopifier`.

Do not use for normal summarization where user wants content omitted. This skill
is for compression and clarity while preserving operational substance.

## Core Rule

Preserve content that changes meaning or behavior.

Keep:

- facts;
- decisions;
- requirements;
- constraints;
- definitions;
- policies;
- exceptions;
- caveats;
- examples that clarify a rule;
- failure modes;
- ownership;
- status;
- dates when relevant;
- source-of-truth statements;
- action steps;
- verification checks.

Cut or compress:

- repeated claims;
- rhetorical intros;
- persuasion;
- aspirational language;
- consultant phrasing;
- generic best-practice filler;
- metaphors;
- vibe words;
- hedging;
- recap paragraphs that add nothing;
- examples that repeat an already clear rule.

If compression would remove a unique requirement, keep the requirement and make
it shorter.

## Modes

Default mode: `regular`.

| Mode | Use | Target |
|---|---|---|
| `light` | Preserve voice/structure, remove obvious filler | 20-35% shorter |
| `regular` | Default. Rewrite into clearer headings, bullets, tables | 40-60% shorter |
| `high` | Dense operational doc. Merge sections, table repeated patterns | 60-75% shorter |
| `ultra` | Reference-card style. Max compression without content loss | 75-90% shorter where possible |

Mode is a pressure setting, not permission to drop substance.

## Deslopifier Tag

`deslopifier` means input quality is bad. Goal changes from "shorten good text"
to "extract real substance from slop".

Accept forms:

- `deslopifier`
- `#deslopifier`
- `[deslopifier]`
- `$concisinator deslopifier`
- "deslopify this"

Deslopifier behavior:

1. Identify content-bearing claims, rules, decisions, constraints, and examples.
2. Remove empty confidence, corporate fluff, hype, fake profundity, and repeated
   framing.
3. Convert vague claims into concrete statements where source text supports it.
4. If a claim is unsupported but important, label it as `Claim` or
   `Assumption`, not fact.
5. If two claims conflict, keep the conflict visible in one line.
6. Prefer hard nouns and verbs over abstract nouns.

Deslopifier may be more aggressive than normal Concisinator, because slop is not
content.

## Rewrite Procedure

1. Read the full input before rewriting.
2. Extract the content ledger:
   - decisions;
   - definitions;
   - requirements;
   - constraints;
   - examples;
   - caveats;
   - failure modes;
   - action steps.
3. Choose structure that minimizes cognitive load:
   - headings for major concepts;
   - tables for repeated comparisons;
   - bullets for rules;
   - short paragraphs only for explanation that needs continuity.
4. Rewrite.
5. Check that every ledger item still appears or is intentionally merged.
6. If editing a file, report path and size delta. Do not commit unless asked.

## Style Rules

- Prefer concrete labels over clever phrasing.
- Use direct headings.
- One idea per sentence or bullet.
- Avoid rhetorical questions.
- Avoid "why this matters" prose unless it adds a concrete consequence.
- Replace examples with rule + example only when example carries unique meaning.
- Do not replace precise terms with vague short terms.
- Do not hide uncertainty.
- Do not flatten hierarchy when hierarchy is needed.
- Keep technical names exact.

## File Mode

When user asks to create a concise version of a file:

1. Keep original unchanged unless explicitly told to overwrite.
2. Default new filename: same basename plus ` concise` before extension.
   - `Name.md` -> `Name concise.md`
3. Preserve frontmatter only if it is still accurate. If status/purpose changes,
   update it or omit it.
4. Use `apply_patch` for repo edits.
5. Verify:
   - file exists;
   - word count or byte count changed;
   - git status shows only expected files.

## Output Shape

For direct text rewrite:

- output rewritten text only, unless user asked for notes.

For file rewrite:

```text
Created: PATH
Compression: OLD -> NEW words
Notes: only material caveats
```

## Anti-Patterns

Bad Concisinator:

- makes a summary and drops requirements;
- rewrites into prettier prose;
- keeps motivational language;
- removes caveats because they are long;
- turns concrete examples into vague abstractions;
- hides contradictions;
- changes status from uncertain to certain.

Good Concisinator:

- same operational content;
- fewer words;
- clearer hierarchy;
- less rhetoric;
- obvious decisions and gates;
- visible uncertainty.

