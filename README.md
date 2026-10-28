# Forge Athletic — Gym Management System

A full-stack gym management system built in Core PHP + MySQL, delivered in
six phases (fixes/foundation → theme → member/trainer/training modules →
store/payments → settings/notifications → final polish & security hardening).
This README reflects the **final, Phase 6** state of the project.

## Stack
- Core PHP 8.x (PDO, prepared statements throughout)
- MySQL / MariaDB (InnoDB, utf8mb4)
- Bootstrap 5 + Font Awesome 6 + Poppins (via CDN)
- jQuery, DataTables, Chart.js, SweetAlert2 (via CDN)
- Composer packages (**optional**): PhpSpreadsheet + Dompdf give richer
  styled Excel/PDF output if installed. Reports work fully without them too
  — see the Excel/PDF Export section below.

## Quick Start (XAMPP)

1. Copy this whole `gym-system/` folder into `C:\xampp\htdocs\` (or your
   `htdocs` equivalent) so it's reachable at `http://localhost/gym-system/`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open phpMyAdmin, create an empty database named **`gym_system`**.
4. Import **`sql/gym_system_final.sql`** — this single file contains the
   entire schema in the correct dependency order (it's a concatenation of
   the five module SQL files, kept in `sql/` for reference/history). No
   other SQL file needs to be imported separately.
5. In `config/db.php`, confirm `DB_USER` / `DB_PASS` match your MySQL setup
   (defaults `root` / empty password are correct for a stock XAMPP install).
6. **(Optional)** For richer, styled Excel/PDF exports, open a terminal in
   the project folder and run:
   ```
   composer install
   ```
   This installs PhpSpreadsheet and Dompdf. **You do not need to do this
   for Reports to work** — every report already exports as a genuine,
   valid `.xlsx` file with zero setup, using a small built-in dependency-free
   writer (`includes/SimpleXlsxWriter.php`). PDF export similarly falls back
   to a print-ready page (click "Print / Save as PDF"). If you do run
   `composer install`, the app automatically detects `vendor/autoload.php`
   and upgrades to the richer PhpSpreadsheet/Dompdf output — no code changes
   needed either way. See "Excel/PDF Export" below for details.
7. Visit `http://localhost/gym-system/` — you'll land on the public site;
   the admin panel redirects to the login page when you open any
   `modules/...` URL while logged out.
8. Log in with the seeded default admin:
   - **Username:** `admin`
   - **Password:** `Admin@123`
   - Change this immediately after first login (via Forgot Password, or by
     updating the `admins` table directly — there's no in-app "change
     password" screen for your own account yet).

## Modules (all phases)

| Module | Where |
|---|---|
| Auth (login/logout/forgot/reset password) | `auth/` |
| Dashboard | `modules/dashboard/` |
| Members (CRUD, medical info, emergency contact, print card, profile) | `modules/members/` |
| Trainers (CRUD, schedule, salary, attendance summary, profile) | `modules/trainers/` |
| Membership plans / renew / freeze | `modules/membership/` |
| Attendance (check-in/out) | `modules/attendance/` |
| Workout plans | `modules/workout/` |
| Diet plans | `modules/diet/` |
| Class schedule | `modules/schedule/` |
| **Product Store** (CRUD, stock, record sale, print invoice) | `modules/store/` |
| **Payments** (record/edit/delete, print receipt) | `modules/payments/` |
| Reports (7 types, date range, Excel/PDF export, print) | `modules/reports/` |
| Settings (gym info/logo, theme palette, SMTP, backup/restore) | `modules/settings/` |
| Notifications (auto-generated + full history page) | `modules/settings/notifications.php` |
| Activity Logs | `modules/settings/activity_logs.php` |
| Public marketing site (home/about/pricing/contact) | project root (`index.php`, `about.php`, `pricing.php`, `contact.php`) |

## What's real vs. what's a placeholder

- Everything listed above is wired to the real `gym_system` database — no
  mock/hardcoded data anywhere.
- **Restore Database** executes an uploaded `.sql` file's statements with a
  simple `;`-at-line-break splitter. Works for straightforward dumps (like
  the ones this app's own Backup feature produces) but won't handle stored
  procedures/triggers with `DELIMITER` blocks. Restricted to the
  `super_admin` role.
- **Email Settings** — SMTP credentials are saved to the database, but no
  mailer library is wired in yet. Forgot Password shows the reset link
  directly on screen instead of emailing it, since XAMPP's default `mail()`
  needs extra local mail-server setup to work. Wire in PHPMailer + these
  saved settings to make it send real email.
- **Theme Settings tab** shows the color palette read-only; the working
  dark/light toggle is the moon/sun icon in the top bar (persists via a
  1-year cookie).

## Excel / PDF Export — how it works

Every report has three export options: **Excel**, **PDF**, and **Print**.
Excel and PDF work in two tiers, chosen automatically — you don't configure
anything:

1. **Composer installed** (`vendor/autoload.php` exists) → uses
   **PhpSpreadsheet** for Excel and **Dompdf** for PDF. Richer styling,
   proper PDF pagination.
2. **Composer not installed** (default, fresh checkout) → Excel uses the
   built-in `includes/SimpleXlsxWriter.php` (zero dependencies, just PHP's
   `ZipArchive` extension, enabled by default in XAMPP) and produces a real,
   valid `.xlsx` file. PDF opens a print-ready page in a new tab and
   triggers the browser's print dialog automatically — choose "Save as PDF"
   as the destination.

Both tiers read from the exact same `get_report_data()` function in
`modules/reports/_queries.php`, so the data is identical either way — only
the output formatting differs. Nothing breaks if you never run
`composer install`.

## Folder Structure

```
gym-system/
├── auth/                    Login, logout, forgot/reset password
├── ajax/
│   ├── attendance/          Check-in / check-out
│   ├── diet/                Diet plan CRUD
│   ├── members/             Members CRUD (incl. medical/emergency upsert)
│   ├── membership/          Renew / freeze / plan actions
│   ├── payments/            Payments CRUD + lookups
│   ├── reports/             data.php — shared JSON endpoint for all 7 reports
│   ├── schedule/            Class schedule CRUD
│   ├── settings/            Save settings, logo upload, backup, restore, notifications
│   ├── store/                Product CRUD, record/undo sale, sales history, lookups
│   ├── trainers/            Trainers CRUD
│   └── workout/             Workout plan CRUD
├── config/
│   ├── db.php                PDO connection + BASE_URL / UPLOAD_* constants
│   └── session.php           Session bootstrap, login guard, CSRF, activity log,
│                              settings cache, notification auto-sync
├── includes/
│   ├── header.php            <head>, sidebar include, topbar (theme/notif/user menu)
│   ├── sidebar.php            Nav links for every module
│   └── footer.php             Shared script includes + global CSRF auto-attach
├── modules/
│   ├── attendance/ diet/ members/ membership/ payments/ reports/
│   │   schedule/ settings/ store/ trainers/ workout/ dashboard/
├── assets/
│   ├── css/style.css          Full theme: palette, dark mode, glassmorphism,
│   │                           responsive breakpoints, print rules, animations
│   ├── img/                   default-avatar.png, default-product.png
│   └── js/                    theme.js, notifications.js, members.js, trainers.js
├── sql/
│   ├── gym_system_final.sql   ← Import THIS ONE file (full schema, correct order)
│   └── member_management.sql, trainer_management.sql, core_and_reports.sql,
│       public_site.sql, phase3_modules.sql   (kept for reference/history)
├── uploads/                   profile_pictures/, trainer_pictures/, product_images/,
│                                logo/, backups/  (all .htaccess-locked against
│                                script execution)
├── vendor/                    (created by `composer install`)
├── composer.json
└── index.php                  Public homepage
```

## Phase 6 — Final Polish: bugs found & fixed

- **Member Save / Trainer Save were silently failing.** Root cause: both
  forms (`modules/members/index.php`, `modules/trainers/index.php`) were
  missing the CSRF hidden field, so `verify_csrf()` in `save.php` rejected
  every submission with HTTP 419 (shown to the user as "Server error while
  saving"). Fixed by adding the field to both forms, and normalized both
  files to use `config/session.php` + `require_login()` consistently with
  every other module (they were only including `config/db.php`).
- The same missing-token pattern also affected Delete/Toggle-Status calls
  for Members and Trainers, and Delete calls in the new Store/Payments
  modules — fixed at each call site. A global `$(document).ajaxSend(...)`
  hook was also added in `includes/footer.php` that auto-attaches the CSRF
  token to any AJAX POST that's missing one, as a safety net for future code.
- **Security:** `modules/members/profile.php` and `modules/trainers/profile.php`
  ran database queries before checking login (only `config/db.php` was
  included). No data actually reached the browser, but the queries executed
  for logged-out requests — fixed to check login first, before any query.
- **Security:** `auth/login.php`'s post-login `?redirect=` parameter was
  used unchecked, allowing an open-redirect (`login.php?redirect=https://evil.com`).
  Added a same-site-only guard so redirects can only target a relative,
  in-app path.
- **SQL / docs:** the old README only documented importing 3 of the 5 SQL
  files, so a fresh install would be missing the `workout_plans`,
  `diet_plans`, `class_schedule`, and `contact_messages` tables. Fixed by
  adding `sql/gym_system_final.sql`, a single correctly-ordered import.
- **Reports/Excel export was completely broken on a fresh checkout.**
  Root cause: `modules/reports/export_excel.php` and `export_pdf.php` both
  hard-required `vendor/autoload.php` and `die()`d with an install-composer
  error if it was missing — but no `vendor/` folder ships with the project
  (Composer needs internet + Packagist access, which many local XAMPP setups
  don't have configured). Fixed by adding a genuine, dependency-free
  `.xlsx` writer (`includes/SimpleXlsxWriter.php`, built on PHP's built-in
  `ZipArchive` — no library needed) that all 7 reports now use automatically
  when `vendor/` isn't present; `export_pdf.php` similarly falls back to a
  print-ready HTML page ("Print / Save as PDF"). If Composer dependencies
  *are* installed, the app auto-detects `vendor/autoload.php` and upgrades
  to the richer PhpSpreadsheet/Dompdf output with no config changes needed.
  Verified end-to-end: all 7 report types now download a valid, openable
  `.xlsx` (confirmed with an independent parser, not just "no PHP error").
- **UI consistency:** trainer salary figures (profile page, list table, and
  the Trainers module's payroll stat card) were showing a hardcoded `$`
  instead of the `Rs` prefix used everywhere else in the app — fixed in all
  3 places.
- Verified (no changes needed): every AJAX endpoint has a login check;
  every state-changing endpoint has CSRF verification; login/logout/password
  reset (hashed passwords, session regeneration, account-enumeration
  protection) is solid; dark/light theme covers tables, forms, modals, and
  DataTables via CSS variables; all internal module/AJAX links and image
  references resolve to real files (no broken links or broken images);
  responsive breakpoints and reduced-motion support are in place.

## Testing Checklist

1. **Login** — wrong password shows an error and logs `login_failed` in
   Activity Logs; correct login lands on the Dashboard and logs `login`.
2. **Logout** — confirm it clears the session and that visiting any
   `modules/...` URL afterward redirects to login instead of showing content.
3. **Dashboard** — stat cards and charts reflect real row counts.
4. **Members** — Add a member (all 3 tabs: Basic/Medical/Emergency), Edit,
   Delete, toggle Active/Inactive, Print Card, open Profile. Save should now
   succeed (this was the reported bug — confirm the success toast appears
   and the row shows up in the table without a page reload).
5. **Trainers** — same as above (Add/Edit/Delete/Status/Profile). Confirm
   Save now succeeds.
6. **Membership / Attendance / Workout / Diet / Schedule** — confirm each
   list loads, Add/Edit/Delete work, and any date/status logic behaves.
7. **Product Store** — add a product with an image, click **Record Sale**,
   confirm stock decreases and the sale appears with a working Print Invoice;
   delete a sale and confirm stock is restored.
8. **Payments** — add a payment (amount auto-fills from the selected plan),
   edit, delete, print receipt.
9. **Reports** — visit each of the 7 report pages, change the date range,
   confirm the table/chart/summary update, then test Excel export, PDF
   export, and Print on at least one report.
10. **Settings → Gym Info** — change the gym name / upload a logo, save,
    confirm the sidebar brand + browser tab title update.
11. **Settings → Backup & Restore** — download a backup, then (on a
    disposable test database) try restoring it.
12. **Notifications** — drop a product's stock below 10 or below 0 and
    reload any page; a low/out-of-stock alert should appear in the bell
    dropdown and on the full Notifications page. Mark-read/delete/mark-all
    should all work.
13. **Dark/Light mode** — toggle via the moon/sun icon; reload and confirm
    it stays in the chosen mode; check a form, a table, and a modal all look
    correct in both modes.
14. **Responsiveness** — resize the browser below ~991px and confirm the
    sidebar collapses behind the hamburger button.
15. **CSRF** — open dev tools, edit a form's hidden `csrf_token` value before
    submitting; confirm the server rejects it (419) instead of saving.
16. **Open redirect** — visit `login.php?redirect=https://example.com` while
    logged out, then log in; confirm you land back inside the app, not on
    the external site.
