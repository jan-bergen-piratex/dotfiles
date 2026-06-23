# OMClub Website Investigation Notes

Date: 2026-06-09

Scope: `https://omclub.de/booking/` booking failures. Investigation only. No production edits.

## Access

- Live host: All-Inkl SSH.
- SSH command pattern:
  `ssh -F /dev/null -i /home/jan/.ssh/id_ed25519_gic_allinkl -o IdentitiesOnly=yes -o BatchMode=yes -o ConnectTimeout=10 -p 22 ssh-w010c8ea@w010c8ea.kasserver.com`
- Live web root: `/www/htdocs/w010c8ea/web/omclub.de`
- Logs: `/www/htdocs/w010c8ea/logs/access_log_omclub_de_YYYY-MM-DD.gz`

## Runtime Shape

- The live OMClub website is a static Astro-style build served from All-Inkl.
- The booking page is static HTML plus bundled JS in `/_astro`.
- There is no classic app backend for bookings and no database found in the deployed code.
- Booking data flow:
  1. Browser loads `/booking/index.html`.
  2. Checkout state lives in `sessionStorage` under `om-checkout-data`.
  3. Product catalog and current price phase are embedded in `/_astro/CheckoutStore.c0Qin-b6.js`.
  4. Final submit tries to POST to Google Apps Script.
  5. Final submit also starts `/api/checkout_backup.php`, which sends a backup email to `sponsoring@omclub.de`.
  6. Google Apps Script can call `/api/send-email.php` to send emails through the All-Inkl SMTP bridge.

## Relevant Files

- `/booking/index.html`: German booking page.
- `/en/booking/index.html`: English booking page.
- `/_astro/CheckoutStore.c0Qin-b6.js`: catalog, cart state, price calculations.
- `/_astro/Step4Summary.astro_astro_type_script_index_0_lang.CG4tEVmS.js`: final submit logic.
- `/api/checkout_backup.php`: backup mail endpoint for checkout payload.
- `/api/send-email.php`: SMTP bridge used by Google Apps Script.
- `/api/boot.php`: loads `api/secrets.php` and `.env`.
- `/api/mailer_helper.php`: PHPMailer setup.
- `/api/shield_verify.php`: anti-spam verification.
- `/.htaccess`: HTTPS, caching, headers, and file protection.

## Confirmed Evidence

- `/booking/` currently serves a file last modified `2026-06-08 06:06 UTC`.
- Live file mtimes show these changed on `2026-06-08 08:06 Europe/Berlin`:
  - `booking/index.html`
  - `en/booking/index.html`
  - `api/checkout_backup.php`
  - `/_astro/Step4Summary.astro_astro_type_script_index_0_lang.CG4tEVmS.js`
- The last clearly successful browser booking in logs was on `2026-06-01 09:12:33 +0200`:
  - `POST /api/checkout_backup.php` returned `200`
  - Google Apps Script called `POST /api/send-email.php` with `200`
  - user reached `/booking/success/` with `200`
- From `2026-06-02` through `2026-06-08`, the checked logs did not show another `POST /api/checkout_backup.php`.
- `/booking/` page views continued on `2026-06-08`.
- Exact log counts for `access_log_omclub_de_2026-06-01.gz` through `2026-06-08.gz`:

| Date | Booking Views | Backup POSTs | Success Pages | Apps Script Send Email |
|---|---:|---:|---:|---:|
| 2026-06-01 | 33 | 1 | 1 | 5 |
| 2026-06-02 | 17 | 0 | 0 | 0 |
| 2026-06-03 | 13 | 0 | 0 | 0 |
| 2026-06-04 | 17 | 0 | 0 | 0 |
| 2026-06-05 | 20 | 0 | 0 | 1 |
| 2026-06-06 | 16 | 0 | 0 | 0 |
| 2026-06-07 | 13 | 0 | 0 | 0 |
| 2026-06-08 | 16 | 0 | 0 | 0 |

- `.env` is protected over HTTP and returns `403`.
- `OPTIONS /api/checkout_backup.php` returns `405`. Same-origin checkout uses `text/plain`, so this is not the main same-origin booking failure.

## Source Ownership Status

- Source repo was later identified and cloned to `/home/jan/pirate/omclub.de`.
- Remote: `git@github.com:PIRATEglobal/omclub.de.git`.
- Branch: `main`, clean at `c792883fcf5cfb446909448823805211fc9b0936`.
- This is the OMClub booking source repo. It contains Astro source, tests, GitHub Actions deploy, All-Inkl PHP endpoints, Apps Script docs, and checkout components.
- Accessible local repos are `gic`, `gic-nextjs`, `piratex.com`, `tech-brain`, `piratex-brain`, `ondeck`, and `resonance`.
- `/home/jan/pirate/piratex.com` is not the OMClub booking app source. It only has OMClub references in PIRATEx content and case studies.
- GitHub CLI visibility from this session does not include PIRATEx private repos beyond the local clones. It can see Jan's personal `obsidian-test` and public Manusco repos, but not an OMClub source repo.
- `tech-brain/ops/company-architecture-security-inventory.md` already lists `omclub.de` as a domain whose live DNS, hosting, deployment path, and ownership were not verified in that earlier pass. This investigation verifies live hosting/path, but not source repo ownership.
- Live `.ftp-deploy-sync-state.json` and source `.github/workflows/deploy.yml` match: GitHub Actions builds `dist/` and deploys to All-Inkl `server-dir: /omclub.de/`.

## Primary Failure Candidate

Live `Step4Summary...CG4tEVmS.js` and source `src/components/checkout/Step4Summary.astro` contain deterministic JavaScript defects in the final submit path.

Source frontmatter defines:

```astro
const publicAppsScriptMasterUrl =
  import.meta.env.PUBLIC_APPSCRIPT_MASTER_URL ||
  import.meta.env.PUBLIC_APPSCRIPT_BOOKING_SHEET;
```

But the browser code is inside a plain `<script>`, not a `define:vars` script. Production therefore contains the raw browser reference:

```js
const o = publicAppsScriptMasterUrl;
```

No definition of `publicAppsScriptMasterUrl` was found in `/booking/index.html` or the loaded JS bundles. Other pages use `PUBLIC_APPSCRIPT_MASTER_URL`, not `publicAppsScriptMasterUrl`.

If that variable is missing, final submit throws a `ReferenceError` before the backup request is created.

The source then declares `backupPromise` inside the `try` block and uses it in `catch`:

```js
try {
  const backupPromise = fetch(...);
  ...
} catch (err) {
  const backup = await backupPromise;
}
```

That is invalid scope for `const`. The catch block cannot see a const declared inside the try block. The live minified bundle exposes the same bug as `await backupPromise`.

So this is not just a minifier accident. It is source-level code that can build to broken browser JavaScript.

## Deploy and Build Findings

- GitHub Actions run `27119134841` for commit `c792883` ran on `2026-06-08 06:05 UTC` and concluded `success`.
- The run deployed the same commit that introduced the `backupPromise` source change.
- `.github/workflows/deploy.yml` only passes `PUBLIC_APPSCRIPT_BOOKING_SHEET` to `npm run build`.
- Local `npm run build` in the fresh clone fails before Astro because `node_modules` is not installed.
- Running with a dummy `PUBLIC_APPSCRIPT_MASTER_URL` shows `scripts/fetch-catalog.js` can fall back to checked-in `src/data/catalog.json`, but Astro cannot run without dependencies.
- `scripts/fetch-catalog.js` computes `API_URL` before loading `.env`, so local `.env` fallback does not update `API_URL` after `process.loadEnvFile`. This is a separate build-script bug for local builds.
- Tests currently do not assert that `Step4Summary.astro` injects the Apps Script URL into browser JS, nor that `backupPromise` is visible to `catch`.

Isolated reproduction:

```bash
node -e 'async function submit(){try{const o=publicAppsScriptMasterUrl; const s=Promise.resolve({ok:true,status:200}); await fetch(o||"");}catch(o){console.log("first caught:", o.name + ": " + o.message); const s=await backupPromise; console.log(s)}} submit().catch(e=>console.log("final thrown:", e.name + ": " + e.message))'
```

Observed output:

```text
first caught: ReferenceError: publicAppsScriptMasterUrl is not defined
final thrown: ReferenceError: backupPromise is not defined
```

Likely user-visible behavior: final submit disables the button / shows processing, then fails without reaching success and without the intended manual fallback alert.

## Secondary Risks

- `api/checkout_backup.php` sends real email. Do not POST test bookings unless operational noise is acceptable.
- `api/checkout_backup.php` protects the email fallback, but it stores nothing in a DB or file. If both Google Apps Script and backup email fail, the server has no durable booking record.
- The checkout anti-spam shield is currently non-strict for checkout, and the checkout payload nests form fields under `customer`, while the shield checks top-level keys. It is not likely to block legitimate checkout submissions in the current code.
- `VAT ID` is marked required in the final billing form. Some legitimate buyers may not know or have a VAT ID and may perceive this as failure before submit.
- Historic emails still contain old English booking URLs like `/booking-en/`; current logs show repeated `404` requests for `/booking-en/`. Add redirects if old outbound emails still circulate.
- Secrets exist in both `.env` and `api/secrets.php` under the web root. HTTP access is blocked by `.htaccess`, but the layout is still fragile and duplicated.

## Proposed Fixes

1. Fix the final submit JS source:
   - Use the same configured constant name consistently.
   - Ensure the Apps Script URL is injected into `/booking/index.html` and `/en/booking/index.html`.
   - Define the backup promise before any code that can throw.
   - In the catch block, await the actual backup promise variable.
2. Add a browser-level smoke test for `/booking/`:
   - Fill minimal booking data.
   - Intercept network requests instead of sending real emails or real Google Apps Script calls.
   - Assert submit attempts both primary and backup paths.
   - Assert backend failure shows fallback message and re-enables button.
3. Add deployment gate:
   - Fail build if booking bundle contains `publicAppsScriptMasterUrl` without a matching definition.
   - Fail build if final submit references `backupPromise` without declaration.
4. Add old URL redirects:
   - `/booking-en/` to `/en/booking/`.
   - Known old deep links such as `/booking-en/*` to `/en/booking/`.
5. Decide whether `VAT ID` should be required. If not always legally required, make it optional or explain it.
6. Improve durability:
   - Store checkout submissions server-side before sending emails.
   - Keep Google Apps Script as downstream processing, not the only durable primary record.
