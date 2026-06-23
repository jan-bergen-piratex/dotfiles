# Skill Author Research Baseline

Read this only when designing a substantial skill, resolving a disagreement about skill style, or auditing an existing skill.

## External Standards

OpenAI:
- Skills are reusable workflows that can include instructions, examples, and code.
- OpenAI skills follow the open Agent Skills standard and are supported across ChatGPT, Codex, and the API, but skills do not automatically sync between products.
- Skill-creator exists as a default helper for creating or modifying skills.
- Review uploaded or third-party skills before trusting them, because skills may include code and instructions.

Anthropic / Claude:
- A skill is a folder with a required `SKILL.md`; optional files include scripts, templates, references, and examples.
- The description is critical because it tells the agent when to load the skill.
- Personal, project, and plugin skills have different scope. Choose scope deliberately.
- Keep the main file focused; move large support material into referenced files.
- Test realistic prompts after creating a skill and iterate on the description if the skill does not trigger correctly.

Agent Skills standard:
- `name` and `description` are required.
- Names should be lowercase, hyphenated, 1-64 characters, and match the directory.
- Descriptions should explain both what the skill does and when to use it.
- Progressive disclosure has three levels: metadata, `SKILL.md`, and optional resources loaded as needed.
- Keep `SKILL.md` under 500 lines and avoid deep reference chains.

## Creation Principles

Start from real expertise:
- Mine successful project chats, patches, review comments, failure logs, and user corrections.
- Generic best-practice pages are only scaffolding. Project-specific evidence is the value.

Spend context carefully:
- Add what the agent would not otherwise know.
- Remove explanations the base model already knows.
- Prefer moderate detail over exhaustive detail.

Calibrate control:
- Be prescriptive for fragile sequences, destructive actions, migrations, credentials, and data loss risks.
- Give freedom when multiple approaches are valid.
- Provide defaults, not menus.

Use practical patterns:
- gotchas sections for non-obvious mistakes
- output templates where format matters
- checklists for dependent workflows
- validation loops before finalizing
- plan-validate-execute for batch or destructive work
- scripts when agents keep reimplementing the same deterministic logic

## Jan / Resonance Adaptation

Durable memory:
- If it is not in a file, it does not exist.
- Chat history is useful as evidence, not as storage.

Compounding:
- Never solve the same problem twice.
- When a user correction reveals a stable preference or failure mode, update the relevant skill, doc, or knowledge file.

Quality:
- Expert test: would a top operator in this domain use this method?
- Preserve useful existing heuristics. Improve, do not blindly replace.
- Quality over quantity. No junk skills for one-off trivia.

Execution:
- Minimum scope that solves the problem.
- Verify before marking done.
- Push fragile logic into deterministic scripts where that materially reduces repeated mistakes.
