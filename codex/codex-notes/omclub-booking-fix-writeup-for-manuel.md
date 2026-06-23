# OMClub Booking Fix Writeup for Manuel

Date: 2026-06-09 16:38 CEST
Repo: `PIRATEglobal/omclub.de`

## Executive Summary

I reviewed PR #1 (`feat/sqlite-journal`) in isolation, found one implementation bug in its checkout JavaScript, patched it directly on the PR branch, merged PR #1, then re-diagnosed the merged state.

After the merge, the remaining booking blocker was the earlier diagnosed Astro/browser boundary bug: `publicAppsScriptMasterUrl` was defined in Astro frontmatter but read inside browser JavaScript, where it does not exist.

I fixed that in a small follow-up branch and opened PR #2:

https://github.com/PIRATEglobal/omclub.de/pull/2

## Initial Context

The live booking issue looked like failed final submissions on `/booking`. Earlier diagnosis pointed at the final checkout component:

`src/components/checkout/Step4Summary.astro`

The important architectural detail is that this Astro component has two execution contexts:

- Astro frontmatter runs at build/render time.
- The plain `<script>` block runs in the browser.

Values defined in frontmatter are not automatically available inside the browser script.

## Review of PR #1

PR #1:

https://github.com/PIRATEglobal/omclub.de/pull/1

Intent of PR #1:

- Add a SQLite write-ahead journal.
- Persist customer submissions server-side before external effects.
- Add `/api/journal_ack.php`.
- Let the checkout backup endpoint return an `idempotency_key`.
- Let browser-side checkout code ack Apps Script success/failure back into the journal.

My conceptual verdict:

I did not object to the SQLite journal concept. The design is reasonable: durable local submission record first, external systems second, and fail-open if SQLite is unavailable.

My implementation concern:

PR #1 also changed the checkout submit path and introduced a JavaScript scope bug:

```ts
try {
  const backupPromise = fetch(...)
  const ackAppsScript = async (...) => { ... }

  await fetch(webhookUrl)
  await ackAppsScript("ok")
} catch (err) {
  ackAppsScript("failed", ...)
  const backup = await backupPromise
}
```

`const` is block-scoped, so `backupPromise` and `ackAppsScript` do not exist inside `catch`. If Apps Script failed, the fallback path could throw `ReferenceError` instead of showing the user the manual recovery message.

## Patch Applied to PR #1

Branch patched:

`feat/sqlite-journal`

Commit:

`baab658 Fix checkout journal ack error path`

Change:

- Moved `backupPromise` declaration outside the `try`.
- Moved `ackAppsScript` declaration outside the `try`.
- Made the `catch` path await the failed ack.
- Added a fallback object if the backup request was never started.

PR #1 was then merged into `main`.

Merged main commit:

`a4fa80f Merge pull request #1 from PIRATEglobal/feat/sqlite-journal`

## Re-Diagnosis After PR #1 Merge

After merging PR #1, I compared the merged state to the earlier diagnosis.

Fixed by the PR #1 patch:

- `backupPromise` is no longer out of scope in `catch`.
- `ackAppsScript` is no longer out of scope in `catch`.
- The PR #1 journal ack path is internally coherent now.

Still broken after PR #1:

```ts
const publicAppsScriptMasterUrl =
  import.meta.env.PUBLIC_APPSCRIPT_MASTER_URL ||
  import.meta.env.PUBLIC_APPSCRIPT_BOOKING_SHEET;
```

This value was still defined in Astro frontmatter, while the browser submit handler did:

```ts
const webhookUrl = publicAppsScriptMasterUrl;
```

That cannot work reliably because the browser script does not have access to that frontmatter variable. `npx astro check` also reported:

```text
src/components/checkout/Step4Summary.astro:650:30 - error ts(2304):
Cannot find name 'publicAppsScriptMasterUrl'.
```

This matched the production symptom: the final booking request path could fail before reaching Apps Script.

## PR #2 Fix

PR #2:

https://github.com/PIRATEglobal/omclub.de/pull/2

Branch:

`fix/checkout-appscript-url-bridge`

Commit:

`94837ef Fix checkout Apps Script URL bridge`

Change:

The Apps Script URL is now rendered into the custom element as a data attribute:

```astro
<step-four-summary
  ...
  data-appscript-url={publicAppsScriptMasterUrl}
>
```

The browser submit handler reads it from the element:

```ts
const webhookUrl = this.dataset.appscriptUrl || "";
```

Reasoning:

- This matches the component's existing pattern for `data-lang`, `data-msg-submitting`, and `data-msg-submit`.
- It avoids relying on a frontmatter variable inside browser JS.
- It keeps the script as a processed Astro module script, avoiding a broader rewrite to inline `define:vars`.

## Verification

Commands run:

```bash
PUBLIC_APPSCRIPT_MASTER_URL=https://script.google.com/macros/s/dummy/exec npm run build
```

Result:

- Passed.
- Build used the existing local `src/data/catalog.json` fallback after the dummy Apps Script URL failed.
- Astro compiled the booking page and generated the browser bundle.

```bash
PUBLIC_APPSCRIPT_MASTER_URL=https://script.google.com/macros/s/dummy/exec npm run test -- tests/build-preflight.test.js
```

Result:

- Passed: 3 tests.

```bash
npx astro check
```

Result:

- Still fails because the repo has existing unrelated diagnostics.
- Important delta: errors went from 23 to 22 after PR #2.
- The removed error was the relevant one:
  `Cannot find name 'publicAppsScriptMasterUrl'` in `Step4Summary.astro`.

Generated artifact check:

- `dist/booking/index.html` contains `data-appscript-url="https://script.google.com/macros/s/dummy/exec"` when built with the dummy env.
- The generated browser bundle reads `this.dataset.appscriptUrl`.
- The generated browser bundle no longer references `publicAppsScriptMasterUrl`.

## Known Remaining Caveats

PR #2 has not been merged yet.

`npx astro check` still reports unrelated existing repo errors, for example:

- `Footer.astro` implicit `this` typing.
- missing/invalid i18n keys in `Header.astro` and `ContactModal.astro`.
- strict indexing issues in `tests/i18n-sync.test.ts`.
- strict indexing issues in `Stimmen.astro`.

These are not caused by PR #2 and did not block `npm run build`.

The local build used a dummy Apps Script URL because I do not have the production secret locally. The GitHub deploy workflow passes `PUBLIC_APPSCRIPT_BOOKING_SHEET`; the component supports both:

```ts
import.meta.env.PUBLIC_APPSCRIPT_MASTER_URL ||
import.meta.env.PUBLIC_APPSCRIPT_BOOKING_SHEET
```

## Review Request

Please review PR #2 mainly for:

- whether `data-appscript-url` is the preferred Astro-to-browser bridge here;
- whether the merged SQLite journal flow should keep the current client-side Apps Script ack design;
- whether any additional post-deploy checks should be added for `_private/db/omclub.sqlite`, `pdo_sqlite`, and `_private` HTTP protection.

My merge recommendation:

Merge PR #2 if the small data-attribute bridge is acceptable. It fixes the concrete booking blocker without changing the journal architecture or unrelated checkout behavior.
