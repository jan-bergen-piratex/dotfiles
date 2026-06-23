# GIC Iframe Cutover Review

Date: 2026-06-15
Scope: GIC/GPC iframe cutover, with preference for leaving gamescom.global unchanged.
Status: review note, not yet implemented.

## Verdict

GIC/GPC is not ready for a "Gamescom unchanged" cutover yet.

The main blocker is URL compatibility, not height.

Gamescom currently embeds legacy URLs:

| Page | Current iframe URL | CMS ratio |
|---|---|---|
| Startup | `https://gic.piratex.com/apply?embed=true` | desktop `2:3`, mobile `1:9` |
| Investor | `https://gic.piratex.com/investor/apply?embed=true` | desktop `4:5`, mobile `1:9` |

Current GPC behavior for those exact paths:

| GPC exact legacy URL | Current result |
|---|---|
| `/apply?embed=true` | `404` |
| `/investor/apply?embed=true` | `200`, but `frame-ancestors 'self'` and `X-Frame-Options: DENY` |

If `gic.piratex.com` is switched to GPC while Gamescom stays unchanged, startup breaks and investor is blocked in the iframe.

## Height Evidence

Fresh live measurements:

| Form | Legacy height | New height |
|---|---:|---:|
| Startup desktop | `1827px` | step 1 `1332px`; later steps previously measured up to about `2839px` |
| Startup mobile | `2527px` | step 1 `1893px`; later steps previously measured up to about `3786px` |
| Investor desktop | `1988px` | `2964px` |
| Investor mobile | `2751px` | `4415px` |

Gamescom fixed iframe box budget from current CMS ratios:

| Page | Desktop budget | Mobile budget |
|---|---:|---:|
| Startup | about `1908px` at 1272px iframe width | about `3222px` |
| Investor | about `1590px` at 1272px iframe width | about `3222px` |

Height compression helps UX, but cannot make everything fit cleanly without a larger form redesign.

Legacy investor already exceeded its desktop fixed box, so current production likely already relies on internal iframe scrolling.

## Recommended Fix List

| Fix | Priority | Change | Verification |
|---|---:|---|---|
| F1 | 5 | Add exact legacy route support: `/apply?embed=true` -> startup form, `/investor/apply?embed=true` -> investor form, preserving query params. Also mark `/investor/apply` embeddable in middleware. | `curl -sSI https://gpc.piratex.com/apply?embed=true` and `/investor/apply?embed=true` must end at real form, allow Gamescom in `frame-ancestors`, and not emit `X-Frame-Options`. |
| F2 | 5 | Test unchanged-Gamescom scenario with current fixed iframe ratios. | Use current CMS URLs/ratios and verify both forms load, scroll, and submit. |
| F3 | 4 | Add small embed-only height compression: reduce card padding/min-height, textarea min-height, and vertical gaps. Do not split pages yet. | Re-measure investor and startup max step heights; ensure fields remain usable. |
| F4 | 4 | Cut over `gic.piratex.com` via DNS/Coolify only after F1/F2 pass. | `gic.piratex.com/apply?embed=true` and `/investor/apply?embed=true` serve GPC forms inside iframe. |
| F5 | 3 | Update docs: current "Gamescom must change embed target" is not aligned with the preferred no-Gamescom-change path. | Docs state preferred path: legacy URL compatibility + domain cutover. Helper script remains later/better option. |

## Recommendation

Do F1 first.

It is small, low-risk, and mandatory for "do not talk to Gamescom".

After F1, test exact old Gamescom iframe behavior before deciding whether height compression is necessary for launch. Do not split the form now unless internal scrolling is declared unacceptable.

## Relevant Files

- `/home/jan/pirate/gic-nextjs/src/middleware.ts`
- `/home/jan/pirate/gic-nextjs/next.config.mjs`
- `/home/jan/pirate/gic-nextjs/docs/iframe-embed-integration.md`

