# Receipt Generator

A simple PHP + MySQL + Bootstrap 5 web app for builders/developers to issue
payment receipts for multiple projects. Enter customer name, address, unit
no., amount, date etc. and the app automatically:

- Generates a unique, sequential receipt number per project (e.g. `GB/RCT/26-27/046`)
- Renders the receipt using that project's own letterhead/branding (logo, colors, seal, signature)
- Exports the receipt to **PDF** and **JPG** automatically
- Saves both files on the server so they can be re-downloaded anytime

Works for any number of projects/companies — each project has its own logo,
colors, address and receipt numbering.

## Requirements

- PHP 8.0+ with the `pdo_mysql` and `gd` extensions (both are enabled by default on almost all hosting)
- MySQL / MariaDB
- A web server (Apache/Nginx) or just `php -S` for local testing

## Setup

1. **Database** — create a database and import the schema:
   ```
   mysql -u youruser -p your_database < database/schema.sql
   ```
   This creates the `users`, `projects` and `receipts` tables and a default
   login:
   - Email: `admin@example.com`
   - Password: `admin123`

   **Change this password immediately after first login** (update it directly
   in the `users` table with a new `password_hash`, generated e.g. via
   `php -r "echo password_hash('yournewpassword', PASSWORD_BCRYPT);"`).

2. **Config** — copy the sample config and fill in your DB credentials:
   ```
   cp config/config.sample.php config/config.php
   ```
   Edit `config/config.php`:
   ```php
   'db' => [
       'host' => '127.0.0.1',
       'name' => 'your_database',
       'user' => 'youruser',
       'pass' => 'yourpassword',
   ],
   ```

3. **Upload** the whole folder to your hosting (e.g. `public_html/`), or
   point your web server's document root at this folder.

4. **Permissions** — make sure the web server can write to:
   - `storage/pdf/`, `storage/jpg/` (generated receipts)
   - `uploads/logos/`, `uploads/seals/`, `uploads/signatures/` (branding images)

5. Visit the site, log in, and:
   - Go to **Projects → New Project** to set up your first builder/project
     (name, logo, colors, receipt numbering).
   - Go to **New Receipt** to issue a receipt — PDF and JPG are generated and
     saved automatically, and can be downloaded from the receipt list at any time.

## Local development

```
php -S localhost:8000
```

Then open http://localhost:8000/login.php

## Project structure

```
config/          DB connection + app config
database/        MySQL schema
includes/        Shared PHP (auth, helpers, PDF/JPG generators)
templates/       HTML receipt template (used for on-screen preview & PDF)
projects/        Manage projects/letterheads
receipts/        Create/list/view/download receipts
storage/         Generated PDF & JPG files (per receipt)
uploads/          Uploaded logos/seals/signatures
vendor/          Dompdf (PDF library) via Composer
```

## Notes

- PDF export uses [Dompdf](https://github.com/dompdf/dompdf) (pure PHP, no
  external binaries needed).
- JPG export is rendered with PHP's built-in GD library, so it works on
  virtually any shared hosting without extra dependencies (no Imagick/Ghostscript required).
- If you ever need to reinstall dependencies: `composer install`.
