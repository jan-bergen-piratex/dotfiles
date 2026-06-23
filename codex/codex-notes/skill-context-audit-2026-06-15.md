# Skill Context Audit

Date: 2026-06-15

## Result

Reduced active Codex skills from 73 to 42. Archived skills were moved, not deleted. Active skill descriptions were shortened to trigger-only text.

Validation result: all active skills passed
`/home/jan/.codex/skills/.system/skill-creator/scripts/quick_validate.py`
when run through `python3`.

Archive location:

```text
/home/jan/.codex/skills.disabled/2026-06-15
```

## Archived Skills

- ponytail-help
- resonance-design-designer
- resonance-design-studio
- resonance-engineering-automation
- resonance-engineering-backend
- resonance-engineering-frontend
- resonance-engineering-game-dev
- resonance-engineering-mobile
- resonance-engineering-performance
- resonance-marketing-conversion
- resonance-marketing-copywriter
- resonance-ops-audit
- resonance-ops-core
- resonance-ops-librarian
- resonance-ops-product
- resonance-ops-productivity
- resonance-ops-refactor
- resonance-ops-retro
- resonance-ops-reviewer
- resonance-ops-ship
- resonance-ops-system-health
- resonance-ops-update-resonance
- resonance-ops-update-roadmap
- resonance-ops-voice
- resonance-sales-call-intelligence
- resonance-sales-cold-call
- resonance-sales-pipeline
- resonance-skill-author
- resonance-strategy-architect
- resonance-strategy-plan
- resonance-strategy-venture

## Shortened Active Skills

| Skill | Description chars |
|---|---:|
| autonomous-todo-runner | 103 |
| cavecrew | 114 |
| caveman | 102 |
| codex-cli-temp-script-workflow | 113 |
| concisinator | 103 |
| data-contract-migration | 106 |
| generated-pipeline-scaffold | 114 |
| gic-coolify-ops | 109 |
| handoff-creator | 117 |
| handoff-receiver | 100 |
| llm-batch-pipeline | 105 |
| mary-add-website-scope | 107 |
| mary-brain-git-sync | 108 |
| piratex-brain-librarian | 109 |
| ponytail | 108 |
| ponytail-review | 114 |
| project-reviewer | 116 |
| prompt-qa | 111 |
| reasoning-scope-router | 112 |
| resonance-engineering-database | 92 |
| resonance-engineering-debugger | 94 |
| resonance-engineering-devops | 98 |
| resonance-marketing-seo | 97 |
| resonance-ops-qa | 99 |
| resonance-ops-security | 112 |
| resonance-research-market-research | 111 |
| resonance-strategy-growth | 104 |
| resonance-strategy-gtm-thinker | 104 |
| resonance-strategy-researcher | 105 |
| review-feedback-integration | 114 |
| sendy-allinkl-export | 102 |
| skill-author | 105 |
| spreadsheet-handoff | 113 |
| subagent-spawner | 87 |
| todo-curator | 96 |
| workflow-deployment | 114 |
| xcc-clipboard | 98 |

## Restore

To restore one archived skill, move its directory back into `/home/jan/.codex/skills`. Example:

```bash
mv /home/jan/.codex/skills.disabled/2026-06-15/resonance-engineering-frontend /home/jan/.codex/skills/
```

## Notes

- System skills under `.system` were left untouched.
- Skill bodies were preserved. Only active frontmatter descriptions were rewritten.
- Archived Resonance skills remain available for manual restore.
- Kept active Resonance skills had their non-Codex `archetype` frontmatter key
  removed so Codex validation passes.
