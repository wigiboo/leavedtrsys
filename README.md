# UPI Elementary School Leave Monitoring

A PHP and MySQL school leave monitoring app for XAMPP. It includes school registration and platform approval, role based school dashboards, leave submissions and approvals, feedback, notifications, monthly daily time records (DTR), staff/student account management, and profile picture upload. App dates use Philippine Standard Time (Asia/Manila).

Open `index.html` directly for the interactive browser preview. Preview entries are kept only in local browser storage; use `public/index.php` through XAMPP for the database-backed application.

## Run with XAMPP

1. Use PHP 8.1+ and MySQL 8. Enable `pdo_mysql`, `fileinfo`, and `mbstring` in PHP.
2. Place the project under `C:\xampp\htdocs\upi-elementary` and start Apache and MySQL.
3. Import `database/schema.sql` in phpMyAdmin. If this is an existing database from the earlier version, run `database/add_attendance.sql` instead to add the DTR table.
4. Copy `.env.example` to `.env` and set your database credentials.
5. Generate a platform admin password hash: `php -r "echo password_hash('Use-A-Long-Password', PASSWORD_DEFAULT), PHP_EOL;"`. Insert it with:

   `INSERT INTO users(name,email,password_hash,role) VALUES('Michael Ron Dimaano','admin@example.com','PASTE_HASH_HERE','platform_admin');`

6. Browse to `http://localhost/upi-elementary/public/`. The school registration form starts with UPI Elementary School and Michael Ron Dimaano and can be edited. Schools await approval; the platform administrator activates them from Schools.
7. Allow Apache to write to `storage/`. Uploaded profile photos are stored outside `public/` under `storage/avatars`.

## Notes

The app uses PDO prepared statements, hashed passwords, CSRF form tokens, output escaping, school scoped database reads/updates, role checks, basic login throttling, secure session cookie flags, and MIME checked profile photo uploads. DTR entries are private to the signed in account, stored by date, and viewed by month with time in/out and daily totals. The provided scope implements CSV exports, but PDF reports, a calendar view, CSV roster import, and private supporting document upload remain future work. Set `public/` as the web document root in deployment and serve over HTTPS.


