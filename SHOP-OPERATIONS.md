# URTH shop operations

## Approval before launch

`shop-info.php` contains the ordering, payment/delivery, returns/refunds, digital licence, privacy, FAQ and contact sections. The policy banner is intentional: the text is not a substitute for URTH-approved terms. Replace the pending copy only after URTH supplies and approves the final policies, prices, payment/bank details and product content.

## Private files

Set `URTH_PRIVATE_STORAGE` to an absolute writable directory outside the web document root. If unset, the application uses a `urth-private` directory outside the XAMPP `htdocs` directory. Do not point it at a public uploads folder.

Receipts and product/order digital files are restricted to the private storage root and downloaded by authenticated staff. Upload extensions and size are checked server-side (10 MB for receipts; 100 MB for digital files). Customer-uploaded documents should be added through a similarly authenticated/token-authorized flow before that feature is enabled; the current public checkout does not accept file attachments.
On Windows, grant the storage directory and backup files only to the PHP/Apache service account and authorized administrators; PHP's Unix-style permission bits do not replace Windows ACLs.

Before rollout, take a database/files backup, then run this once from a command prompt:

```powershell
php C:\xampp\htdocs\urth\migrate_private_uploads.php
```

This moves existing receipt/deliverable files referenced by the shop database. `uploads/.htaccess` denies direct Apache access to the legacy directory. Do not remove the deny rule.

## Staff access

Configure `URTH_ADMIN_USERNAME` and a unique `URTH_ADMIN_PASSWORD` of at least 16 characters in the server environment before opening the admin panel. The previous documented placeholder admin account is removed automatically; no default password is provisioned. Login attempts are limited per username and source IP.

Checkout submissions use a honeypot, a per-session limit of 5 attempts per 10 minutes, and a per-network limit of 10 attempts per 10 minutes. The database stores only a keyed hash of the source address for rate limiting.

## Backups

`backup_shop.php` creates a private SQL backup and copies private uploads, shop product-image uploads and the admin SQLite database. Run it from the command line:

```powershell
php C:\xampp\htdocs\urth\backup_shop.php
```

Schedule that command with Windows Task Scheduler or the production host's scheduler at an approved frequency. Backups include personal/order data and must remain access-restricted; configure off-site encrypted backup retention and periodically test restoration.

## Email retries

The admin order-update form records email attempts in a staff-only outbox. Set `URTH_MAIL_FROM` and configure the PHP mail transport on the host before sending customer emails. Failed attempts appear under **Failed Emails** in the admin panel and can be retried there. A transport acceptance is not proof of final mailbox delivery.

## PHP upload limits

The PHP server limits must allow files up to the application limits. For example, configure `upload_max_filesize = 100M` and `post_max_size = 110M` (or larger) in the active PHP configuration, then restart Apache/PHP. The application still enforces per-file limits independently.
