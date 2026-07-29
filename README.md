# Dispatch (PHP) — Bulk Email Sender

Same tool as the Python version, rebuilt in PHP so it runs on standard shared hosting (Hostinger, etc.) without needing a VPS or a persistent Python process.

## How sending works here (important difference from the Python version)

Shared PHP hosting doesn't let a script run forever in the background. So instead of one long-running send job, the browser calls the server **once per email**, waits your chosen delay, then calls again — repeating until the list is done. This has a nice side effect: it naturally throttles sending without needing any special hosting features, and it works fine on ordinary shared hosting.

## 1. Requirements

- PHP 8.0+ with the `openssl` and `mbstring` extensions (both standard on virtually all hosting, including Hostinger)
- No Composer/database needed — PHPMailer is already vendored in `vendor/phpmailer/`

## 2. Upload

Upload the whole `coldmail-php` folder to your hosting account (e.g. via Hostinger's File Manager or FTP), typically into `public_html/dispatch/` or similar. Whatever folder you choose, that becomes your URL, e.g. `https://yourdomain.com/dispatch/`.

**Make sure the `data/` folder is writable** by PHP (0755 or 0700 permissions) — it's where user accounts and in-progress jobs are stored.

For production, set the `DISPATCH_BASE_URL` environment variable to the public application URL, without a trailing slash, so unsubscribe links always point to the correct host and folder:

```text
DISPATCH_BASE_URL=https://yourdomain.com/dispatch
```

If it is not set, Dispatch derives the URL from the incoming web request.

## 3. Add team logins

You need shell/SSH access for this one-time step (Hostinger's hPanel has a "SSH Access" section, or use their browser-based terminal if offered):

```bash
cd coldmail-php
php manage_users.php add yourname
```

It'll prompt for a password (min 6 characters). Repeat per teammate. Other commands:

```bash
php manage_users.php list
php manage_users.php remove name
```

**No SSH access at all?** You can also run `manage_users.php` from your own computer instead (it just writes `data/users.json`), then upload that one file to the server afterward. Just make sure your local PHP version matches roughly (8.x) since password hashes are portable across PHP 8.x installs.

## 4. Visit it

Go to `https://yourdomain.com/dispatch/login.php` (or wherever you uploaded it) and sign in.

## 5. Get an app password (Gmail / Outlook)

Same as before — if your email account has 2FA on, you need an app password, not your normal login:
- **Gmail**: Google Account → Security → 2-Step Verification → App Passwords
- **Outlook/Office365**: account.microsoft.com/security → App passwords

## 6. Using it

First time only: click the **⚙ Settings** link in the header and save your SMTP host, email, app password, and From Name. This is saved per team member — everyone configures their own sending account once and it's remembered from then on, no re-entering it every campaign.

After that, the main flow is 3 steps: **Recipients → Compose → Send.**

- **Recipients** — paste CSV (header row required, must include an `email` column), or load a previously saved list from the dropdown. You can also save the current list under a name to reuse later — saved lists are private per team member.
- **Compose** — write your subject/body using `{{column_name}}` merge tags pulled from your CSV. A required unsubscribe/footer line is included by default — edit but don't remove it. You can also save/reuse whole message templates the same way as recipient lists.
- **Review & Send** — always run a **Dry Run** first to check personalization and merge tags render correctly, then switch to Live Send.
- **Recipient audit** — validation removes duplicates and invalid addresses, always excludes suppressed recipients, and reports addresses contacted by an earlier campaign. Exclusions can be downloaded as CSV.
- **Testing** — Settings can test SMTP authentication, while Compose can send a personalized test message to an address you choose.
- **Unsubscribe protection** — every live message includes a signed unsubscribe link. Confirmed requests are added to the sender's suppression list and checked again immediately before each send.
- **Draft recovery** — campaign fields are autosaved in the current browser for up to 30 days and cleared when a campaign starts.
- **Interrupted campaigns** — if the browser is refreshed or closed mid-campaign, reopen Dispatch and use the **Resume campaign** notice. The saved delay and progress are restored.
- **Live-send confirmation** — live campaigns show a final sender, recipient-count, subject, and delay confirmation before the first email is sent.

## Campaign history

Click **History** in the header to see every campaign you've sent (or dry-run), with subject, date, recipient count, sent/failed counts, and a link to download that campaign's results log. This is private per team member and is built directly from the job files in `data/jobs/` — see the note in "Limits" below about what that means for cleanup.

History includes subject search, status filters, pagination, campaign detail pages, CSV/JSON reports, retry-failures actions, and a Resume link for unfinished campaigns.

## Security notes specific to this PHP version

- **Use HTTPS.** Hostinger includes free SSL — turn it on before anyone logs in or saves an SMTP password. Session cookies and settings submissions must be protected in transit.
- SMTP credentials are stored once in the signed-in user's protected settings file. Campaign job/history files never contain the SMTP password.
- The included `data/.htaccess` blocks direct access on Apache/LiteSpeed. Double-check that `data/users.json` is not reachable from a browser; on a non-Apache server, add an equivalent server rule or move `DATA_DIR` outside the public web root.

## Compliance — please read before sending

Same rules apply as any bulk email tool:
- **CAN-SPAM (US)**: no misleading subject/from info, working opt-out (the footer line handles this), honor opt-outs within 10 business days, include your physical mailing address.
- **CASL (Canada)**: generally needs consent (implied consent from an existing relationship counts) plus ID + unsubscribe.
- **GDPR/PECR (EU/UK)**: cold-emailing individuals generally needs a lawful basis (usually consent); B2B outreach is on firmer ground but still needs an easy opt-out, honored promptly.

This tool doesn't verify your list is compliant — that's on you as the sender.

## Branding / contact footer

Placeholder contact email (`alfadevs.team@gmail.com`) and site link live in `index.php` — search for those strings and swap in your real values once you have them.

## Limits

- Regular email provider sending limits still apply (Gmail ~500/day on personal accounts).
- A campaign accepts at most 5,000 valid recipients and a recipient upload is limited to 5 MB.
- No CRM/dedup — make sure you're not re-uploading a list you already emailed.
- Job files in `data/jobs/` aren't automatically cleaned up — and now that **History** reads directly from them, deleting old job files means losing that campaign from your history too. If you send a lot and want to trim old ones, keep a reasonable retention window (e.g. a cron job that only deletes files older than 90 days) rather than clearing them frequently.

## Development checks

Run the built-in regression and JavaScript syntax checks before deploying changes:

```bash
php tests/run.php
node tests/check-js.js
```
