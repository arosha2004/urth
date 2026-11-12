# URTH shop operations

## Approval before launch

`shop-info.php` contains the ordering, payment/delivery, returns/refunds, digital licence, privacy, FAQ and contact sections. The policy banner is intentional: the text is not a substitute for URTH-approved terms. Replace the pending copy only after URTH supplies and approves the final policies, prices, payment/bank details and product content.

## Private files

Set `URTH_PRIVATE_STORAGE` to an absolute writable directory outside the web document root. If unset, the application uses a `urth-private` directory outside the XAMPP `htdocs` directory. Do not point it at a public uploads folder.

Payment receipts, customer reference files, and product/order digital files are stored under the private storage root. Staff can access receipts and customer references only through the authenticated admin panel. Customer reference uploads accept JPG, PNG, WebP or PDF files, up to 5 files of 10 MB each, for furniture and design-session requests. Receipt uploads are limited to 10 MB; staff digital deliverables are limited to 100 MB.

Customer status links and digital download links use random, hashed tokens and expire after 30 days. Digital downloads require a fully-paid order and a private deliverable file; replacing a ready deliverable revokes the previous link. Do not email private files as attachments.

Before deploying checkout and quote-request changes, back up the database and run the shop migration once from a command prompt:

```powershell
php C:\xampp\htdocs\urth\admin_shop_migration.php
```

The migration expects the base `orders`, `order_items` and `products` tables to exist. For a database without those tables, run the applicable setup scripts first. Review the migration output and resolve any reported errors before enabling the shop.

On Windows, grant the storage directory and backup files only to the PHP/Apache service account and authorized administrators; PHP's Unix-style permission bits do not replace Windows ACLs.

Before rollout, take a database/files backup, then run this once from a command prompt:

```powershell
php C:\xampp\htdocs\urth\migrate_private_uploads.php
```

This moves existing receipt/deliverable files referenced by the shop database. `uploads/.htaccess` denies direct Apache access to the legacy directory. Do not remove the deny rule.

## Staff access

Configure `URTH_ADMIN_USERNAME` and a unique `URTH_ADMIN_PASSWORD` of at least 16 characters in the server environment before opening the admin panel. The previous documented placeholder admin account is removed automatically; no default password is provisioned. Login attempts are limited per username and source IP.

Checkout and quote submissions use a honeypot, a per-session limit of 5 attempts per 10 minutes, and a per-network limit of 10 attempts per 10 minutes. The database stores only a keyed hash of the source address for rate limiting. Stock and configured booking capacity are checked when checkout is submitted; orders remain requests pending URTH review, so confirm current stock and available slots again before sending payment instructions.

## Checkout, quote requests and payment configuration

Set `URTH_PUBLIC_BASE_URL` to the canonical public site URL and `URTH_ORDER_EMAIL` to the mailbox that should receive order, quote and payment-evidence alerts. New checkout orders go to `URTH_SHOP_ORDER_EMAIL` (default `sales@shop.urthspaces.com`), with Reply-To set to the customer; the customer's confirmation email has Reply-To set to the same orders mailbox. Configure `URTH_MAIL_FROM` and the host's PHP mail transport for outbound email. Check the email outbox after launch; transport acceptance is not proof of mailbox delivery.

Bank instructions are shown to staff only when all of these server environment values are configured: `URTH_BANK_NAME`, `URTH_BANK_ACCOUNT_NAME`, `URTH_BANK_SORT_CODE`, `URTH_BANK_ACCOUNT_NUMBER`, and `URTH_BANK_IBAN`. Enter verified URTH banking details only. Do not add credentials or payment instructions to source code. Card and PayPal payment are not enabled by these changes.

Fixed-price checkout and quote requests do not collect payment. URTH must review delivery, availability and the final total, then send approved payment instructions. Customers can submit payment evidence through their private order-status link after payment instructions have been confirmed. Quote requests become payable only after URTH prepares and sends a quote.

Delivery charges, delivery areas/timing, return and cancellation terms, digital licences and payment options remain subject to URTH approval. Do not replace the pending policy language with assumed values.

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
