# TODO: Configure Gmail SMTP for Password Recovery

## Tasks
- [x] Edit `senhas/recup.php`:
  - [x] Remove `putenv` calls for SMTP_USER and SMTP_PASS.
  - [x] Change password reset link to use dynamic host from `$_SERVER['HTTP_HOST']`.
  - [x] Remove `SMTPOptions` that disable SSL verification.
  - [x] Set `SMTPDebug` to 0.
- [x] Edit `API/api_recover.php`:
  - [x] Change password reset link to use dynamic host from `$_SERVER['HTTP_HOST']`.
- [x] Advise user to generate Gmail App Password for `marcos2008campinas@gmail.com` and set environment variables `SMTP_USER` and `SMTP_PASS` on the server.
- [x] Verify PHPMailer installation on the server (already confirmed via composer.json).
- [x] Test email sending after changes.

# TODO: Fix Admin Page Succo Saving Issue

## Tasks
- [x] Modified `js/admin.js` to load sucos from `produtos.json` if localStorage is empty, ensuring persistence across sessions.
