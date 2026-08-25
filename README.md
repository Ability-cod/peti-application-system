# PETI Online Application System

Online application system for **Perfect Education and Training Institute (PETI)**,
Karagwe District, Kagera Region, Tanzania. Registered with NACTEVET under
Cap. 9 of 1997, Reg. No. 128835. Established 14/03/2014.

Built with plain PHP + MySQL (mysqli) + vanilla JS, designed to run on XAMPP
and standard cPanel hosting.

## Courses

10 official Advanced Certificate (2-year) programmes, each with a real CNO
(course number) and Tsh 850,000 course fee:

International Hotels Management (309), International Tourism and Airlines
Management (310), Front and Reception Management (311), Food & Beverage
Services Management (312), Tour Guiding and Administration (313), Hotel
Hospitality & Housekeeping Management (317), Hotel & Tourism and Financial
Management (38), Journalism (319), Full Secretarial Course (320), Nursery
School Teaching (321).

Plus 3 additional courses (fees to be announced): Event Decoration,
Tailoring and Fashion Design, Computer Studies.

## Roles

- **Applicant** — Form Four graduate, logs in with Form Four Index Number
- **Admin** — full control of the system
- **Principal (Mkuu wa Chuo)** — has the **same full access as Admin**
  (windows, courses, payments, applications, announcements) — this was a
  deliberate choice, not a bug; Principal is not read-only

## Application Flow

1. **Register** (`register.php`) — only possible while an admission window
   is open. Applicant enters their Form Four Index Number in the format
   `centre/candidate/year` (e.g. `S0001/0001/2020`), their three names,
   gender, and creates a password. The index number is only **format-checked**
   (regex), not live-verified against NECTA — live verification was tried
   and removed because it depended on NECTA's website staying reachable and
   its page structure staying stable, which proved unreliable in testing.
2. Applicant is taken straight to the **full application form**
   (`applicant/application_form.php`): email, phone, nationality, marital
   status, residential address, postal address, date and place of birth,
   guardian/mdhamini details (single guardian, not separate father/mother),
   and an **uploaded Form Four certificate** (JPG/PNG/PDF, max 5MB) — this
   certificate upload is the real identity verification now, checked
   manually by Admin/Principal during review.
3. Once details are submitted, a control number is generated **internally**
   (for database uniqueness only — it is never shown to the applicant,
   since paying to a personal phone number has no "Reference" field to put
   it in anyway).
4. Applicant pays the **Tsh 10,000 application fee** to the phone number
   shown on their dashboard (configured in `admin/payment_settings.php`).
   Tanzania's mobile networks are interoperable, so Tigo Pesa, Airtel
   Money, and HaloPesa users can send directly to a Vodacom number too.
5. Applicant **self-reports payment proof** on their dashboard: the phone
   number they paid from, which network, and the **Transaction ID** from
   their payment confirmation SMS.
6. **Admin/Principal confirms payment** (`admin/payments.php`) by matching
   that Transaction ID against the SMS they receive on the college's phone,
   then marking it paid.
7. Once paid, the applicant can **immediately select a course**
   (`applicant/course_selection.php`) — no approval is required first.
8. **Admin/Principal reviews** submitted applications (including the
   uploaded certificate) at their own pace and approves or rejects each
   one, independent of the applicant's course selection step.
9. On **Approve**, the system automatically sends a congratulations message
   with the **Joining Instructions PDF** attached (if one has been uploaded
   via `admin/joining_instructions.php`). On **Reject**, an automatic
   "not selected this time" message is sent. Admin/Principal can still send
   additional custom messages from the application view page.

## Joining Instructions PDF

A full Swahili replica of PETI's real physical enrolment paperwork,
generated as one PDF and attached automatically to the approval message.
Covers: Utangulizi/Falsafa ya Chuo/Kauli Mbiu, the official course table
with CNO numbers, course fees and payment schedule (TCB Bank account
231227000004), items to bring, school uniform requirements, classroom
supplies, sportswear, a fillable **FOMU YA USAJILI** section (only the
parts not already captured online: TAALUMA checkboxes for Form
Four/Six/other course completed, Kuwa/Bweni choice, and signature lines),
a fillable **MEDICAL EXAMINATION FORM** section (Declaration + the actual
medical exam fields a doctor fills in), and all 27 college rules. Letterhead
appears once, on the first page only.

## Homepage / Public Site

The homepage (`index.php`) is public — no login required — and includes:

- A **photo carousel hero** that auto-discovers images dropped into
  `assets/img/hero/` (any filename, case-insensitive extension: jpg,
  jpeg, png, webp). No code changes needed to add/change photos.
- A **facts strip** (NACTEVET registration, established year, course
  count, location, motto)
- A **"Why Choose PETI"** section
- A **Campus section** featuring a real campus photo
- An **Announcements section** — managed by Admin/Principal via
  `admin/announcements.php`, shown to every visitor
- The **Courses Offered** grid (pulled live from the `courses` table)
- **What You Need to Apply** / **How It Works** explainer sections
- A **footer** with Help Desk (0775 830 058), Admission Office
  (0742 284 479 / 0755 748 329), and two contact emails
  (mbekimbeki08@gmail.com, perfecteducation1276@gmail.com)

Scroll animations (`assets/js/reveal.js`) are **progressive enhancement**:
content is fully visible in plain HTML/CSS with no JavaScript at all: JS
only adds the fade-in effect if it successfully runs, so a script failure
or disabled JS never leaves content permanently invisible.

## Database Schema

Use **`database/schema_final_consolidated.sql`** for any brand-new
database (fresh hosting, a teammate's machine, etc.) — it creates every
table in its current, correct form in one file, including seed data
(13 courses, one open admission window, the payment phone number). Do
not run it against your existing working database; it already has this
schema from the individual pieces applied over time.

Tables: `users` (admin/principal), `courses`, `admission_windows`,
`applicants`, `guardian`, `payments`, `payment_settings`, `messages`,
`announcements`.

## Project Structure

```
peti-system/
├── database/
│   └── schema_final_consolidated.sql   Complete schema + seed data (fresh installs)
├── install/
│   ├── seed.php                        One-time default admin/principal creator
│   └── seed_test_applicant.php         One-time test applicant (already PAID)
├── config/db.php                       DB connection + BASE_URL constant
├── includes/
│   ├── auth.php                        require_applicant/require_admin (admin+principal)/require_principal/require_staff
│   ├── functions.php                   Shared helpers (sanitize, CSRF, hero images, announcements, etc.)
│   ├── header.php / footer.php         Shared layout, fonts, scripts
├── assets/
│   ├── css/style.css                   Full site styling
│   ├── js/main.js, carousel.js, reveal.js
│   └── img/logo.png, img/hero/*.jpg    Logo + homepage carousel photos (any filename)
├── index.php                           Public homepage
├── register.php / login.php / staff-login.php / logout.php
├── serve_certificate.php               Authenticated gatekeeper for viewing uploaded certificates
├── .htaccess                           Security headers, directory listing disabled
├── uploads/certificates/.htaccess      Blocks direct access — certificates only viewable via serve_certificate.php
├── applicant/                          dashboard, application_form, course_selection, messages
├── admin/                              dashboard, windows, courses, payments, payment_settings,
│                                       applications, application_view, announcements, joining_instructions
└── principal/                          dashboard, applications, application_view (same access as admin)
```

## Setup (XAMPP, local development)

1. Copy the `peti-system` folder into `htdocs`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. In phpMyAdmin, import `database/schema_final_consolidated.sql`.
4. In `config/db.php`, confirm `BASE_URL` matches your local path, e.g.
   `define('BASE_URL', 'http://localhost/peti-system/');`
5. Visit `http://localhost/peti-system/install/seed.php` once to create
   the default Admin and Principal accounts, then **delete or rename that
   file**.
6. Visit `http://localhost/peti-system/admin/payment_settings.php` (log in
   as admin first) and confirm the phone number that receives application
   fee payments.
7. Add real campus photos to `assets/img/hero/` for the homepage carousel.
8. Make sure `mod_headers` and `AllowOverride All` are enabled in Apache
   so the `.htaccess` security rules take effect (edit
   `C:\xampp\apache\conf\httpd.conf` if needed, then restart Apache).

## Default Logins

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `Admin@2026` |
| Principal | `principal` | `Principal@2026` |

**Change these before going live.** For testing the applicant flow without
manually paying, `install/seed_test_applicant.php` creates a test
applicant (`TEST0001` / `Test@1234`) whose payment is already marked PAID.

## Security Notes

- Passwords hashed with `password_hash()` / verified with `password_verify()`
- All database queries use prepared statements (mysqli)
- CSRF tokens on every form
- `session_regenerate_id()` on every successful login (session fixation protection)
- Uploaded certificates are **not** directly accessible by URL — they are
  blocked at the Apache level (`uploads/certificates/.htaccess`) and can
  only be viewed through `serve_certificate.php`, which checks that the
  requester is either that applicant or logged-in staff
- Root `.htaccess` sets security headers (X-Frame-Options,
  X-Content-Type-Options, Referrer-Policy) and disables directory listing
- Output escaped with `htmlspecialchars()` throughout

**Still needed before going live on real hosting:**
- HTTPS (SSL) — most hosts (including Tanzanian providers like Truehost
  and DUHosting) offer free Let's Encrypt certificates
- Rate limiting on login/registration (not yet implemented)
- Change the default Admin/Principal passwords

## Hosting (Tanzania)

The system was reviewed with Tanzania-specific hosting in mind:
- `.ac.tz` is the correct domain category for NACTEVET-registered
  colleges (confirmed against real examples: tia.ac.tz, nit.ac.tz,
  cbe.ac.tz) — registration requires PETI's registration certificate and
  a signed approval letter from the Principal, and takes a few days to
  verify (unlike instant `.co.tz`)
- Local providers **Truehost Tanzania** (truehost.co.tz) and **DUHosting**
  (duhosting.tz) offer cPanel hosting with PHP/MySQL/SSL and accept
  Mobile Money payment
- It's possible to start on `.co.tz` (instant) and move to `.ac.tz` later
  without losing any data — only the domain changes, not the hosting,
  database, or files

## Future Work

- Payment gateway integration (e.g. ClickPesa) using the control number,
  so application fee payments can be received directly into a bank
  account and confirmed automatically — replacing the current manual
  Transaction ID matching
- A full post-admission student management system (course fee tracking,
  attendance, results) as a separate future project

## Version Control

This project uses Git. Commit after every working change:
```
git add .
git commit -m "short description of the change"
git push
```
`.gitignore` excludes uploaded certificates (personal student data) and
common OS/temp files — everything else, including the database schema
and this README, is tracked.