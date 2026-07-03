# Todo List Review - 2026-06-08

Parent topic: personal todo system
Current subtopic: duplicate/deprecation audit
Source file: /home/redbeard/pirate/notes/todos.md

Current active section starts at line 1142.

Findings:
- Historical dated sections repeat many tasks by design. Do not rewrite old snapshots unless Jan asks for archival cleanup.
- Active 2026-06-08 section has semantic duplicates:
  - `look into jack VPS state...` appears as a main block and under `Today`.
  - `verify all connections...` appears under both of those.
  - `Script fuer DB Umzug von GIC` overlaps with `GIC Legacy-Datenmigration ausfuehren`.
  - `GIC nextjs CD bauen mit Backup` overlaps with GIC deploy handoff backup/CD items.
  - `SEO Assistant mit foundry` overlaps with `growth-seo first run` but not fully; keep as parent or merge under assistant first-runs.
  - `Gemma durch opencode austauschen`, `Gemma als Resource`, `Local Gemma Worker Skill`, and lead-enrichment runtime worker blockers overlap.
  - `Roadmap`, `Overview Ist Zustand IT Infrastruktur PIRATE`, `Allgemeine Eingrenzung der Faehigkeiten von Mary`, `Pirate grosses Tech-setup dokumentieren`, and `Server Sicherheit Sanity Check` are likely done/partially done in older notes but still open in 2026-06-08.
  - `Handover skill` is likely superseded by installed `handoff-receiver` and `handoff-creator`; update wording or mark done after Jan confirms.
  - `mails finden... explizit behandeln` appears likely covered by email discovery skill, but still open in latest section.
  - `Hermes log weniger verbose` likely duplicates `Mary Slack log verbosity` already marked done in older section; needs verification.
- Active section has container todos that are not actionable:
  - `Setup`, `Personal Setup`, `Skills`, `Mary skills`, `Research`, `Housekeeping`, `Hermes Mary`, `OMClub Akquise`, `Today`, `Langweilig`, `Brecher`, `Later`, `Lead enrichment`.
- Stale in-progress markers exist only in older 2026-06-05 block:
  - `[a] Trypost installieren`
  - `[a] ondeck weiter`
  - `[a] Nachtscript`
  They should be reset or archived if that older block is still considered active, but latest 2026-06-08 has no `[a]`.
- Missing from `todos-jan` compared with latest todo list:
  - Manuel urgently needs Mary admin access.
  - Concrete Slack channel-management tool: create channels, invite users/agents, post init message.
  - Lead-enrichment Slack pilot hardening/live test.

Suggested cleanup shape:
1. Keep historical dated sections as snapshots.
2. In latest section, consolidate around 6 current blocks:
   - GIC production readiness
   - Mary/Hermes operations
   - Lead enrichment / OMClub acquisition
   - Security/infrastructure truth
   - Skills / agent capabilities
   - Personal setup
3. Move handoff-derived items into the relevant blocks or keep one handoff block only while processing 2026-06-08.
4. Mark or remove completed stale items only with Jan confirmation.
