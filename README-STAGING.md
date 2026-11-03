# Ahmad website — milestone 1 staging candidate

This is a separate working copy, not a production replacement. The original archive and database export are unchanged. No private SQL export or production credentials are included.

## Completed
- Retained legitimate application directories and excluded identified backdoors, phishing redirect directory, empty suspicious root files, logs and installer.
- Replaced embedded database credentials with private configuration/environment variables and utf8mb4 PDO connection, native prepares, and generic connection errors.
- Shared secure-cookie/session bootstrap, session ID regeneration after login, 30-minute admin inactivity timeout, CSRF validation with server-inserted form tokens.
- Legacy delete/toggle/logout GET links now show a POST confirmation; confirmation submissions do not trigger ordinary edit handlers.
- Upload MIME/extension/size checks, image decoding checks, randomized filenames, Apache denial of executable uploads.
- Denied direct public access to resumes. An authenticated resume download workflow still needs implementation.
- Fixed About page PDO/MySQLi mismatch, pagination lower bound, stylesheet typo, and nested-route relative URL base.
- Buffered legacy pages so existing late redirects can send headers. Moving request handlers before rendering remains a future refactor.

## Database review
The supplied export defines 13 tables including users, permissions, activity logs, news, services, careers, settings, statistics, team members and testimonials. This milestone does not alter schema or data. Quotation tables are not present. Tables use mixed legacy encodings; do not blindly convert production data.

## Stage safely
1. Ask hosting support to inspect the entire account, sibling sites, scheduled tasks, PHP auto-prepend configuration, access logs and FTP/SSH accounts. Removing files here cannot establish that the hosting account is clean.
2. Create a staging directory or subdomain with PHP 8.2+, PDO MySQL, fileinfo and Apache 2.4/mod_rewrite. This candidate assumes the application is at its host root; use a staging subdomain rather than a nested URL path.
3. Create a NEW database and restricted database user. Import the supplied SQL privately using phpMyAdmin. Do not place SQL under the web root.
4. Create `ahmad-private/config.php` next to the staging document root, outside it; follow PRIVATE_CONFIG_EXAMPLE.txt. Alternatively set the four AHMAD_DB_* environment variables.
5. Use HTTPS. Ensure Apache honors the included .htaccess files; verify a direct resume or PHP file inside uploads returns 403. Set a writable private PHP session directory and private server error log.
6. Rotate hosting, database, admin, FTP/SSH and email credentials as applicable; inspect database accounts/content for unauthorized changes. Use a private staging account and do not send real client quotations yet.

## Required staging checks
- Login/logout, wrong password, session expiration and permission restrictions.
- Submit each admin edit form; verify delete/toggle GET requests do not mutate data and confirmations do.
- Missing/wrong CSRF token must return 403; reopening the form should restore operation.
- Upload valid images; reject disguised executable files, wrong MIME and oversized files.
- Contact and career form success/failure paths, email delivery, private resume storage/download policy.
- About, nested news/career URLs, invalid IDs and pagination.

## Still pending — not a full security sign-off
Login rate limiting, comprehensive output escaping/rich-text sanitization, SMTP integration, durable private resume downloads, CSP policy, security audit of every authorization/data path, database migration checks, and full browser/runtime QA.

## Next milestones
2. Quotation tables and exact-decimal calculations, editor, immutable issued revisions, server-generated branded PDF, private download endpoint, high-entropy share tokens stored as hashes, password hashing, persistent attempt limits, expiry and revocation. Protect PDF access exactly like the HTML view. Default to password-protected sharing; noindex is not access control.
3. Blog drafts/publishing, categories, slugs with legacy URL redirects, sanitized content editing, SEO fields and accessible mobile presentation.

Do not describe this candidate as malware-free or production-ready until the account and end-to-end tests have been checked.
