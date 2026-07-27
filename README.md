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
- **Compose** — write your subject/body using `{{column_name}}` merge tags pulled from your CSV. A required unsubscribe/footer line is included by default — edit but don't remove it.
- **Review & Send** — always run a **Dry Run** first to check personalization and merge tags render correctly, then switch to Live Send.

## Security notes specific to this PHP version

- **Use HTTPS.** Hostinger includes free SSL — turn it on before anyone logs in or sends real SMTP passwords through this. Login/session cookies and SMTP credentials both travel over the wire on each request.
- SMTP credentials are stored **only** in a temporary file per send job (`data/jobs/<id>.json`), deleted from active use once the job finishes — but the file itself isn't auto-deleted. If you want jobs cleaned up automatically, you can add a daily cron job on Hostinger to delete files in `data/jobs/` older than a day.
- The `data/` folder must not be publicly browsable. Most hosts block direct access to files without an `index.php`, but double-check `data/users.json` isn't reachable directly by visiting its URL — if it loads in a browser, add a blank `data/.htaccess` with `Deny from all`, or ask hosting support to lock that folder down.

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
- No CRM/dedup — make sure you're not re-uploading a list you already emailed.
- Job files in `data/jobs/` aren't automatically cleaned up; consider a simple cron job if you send often.
