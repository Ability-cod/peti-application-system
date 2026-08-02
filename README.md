# PETI Online Application System

Online application system for **Perfect Education and Training Institute (PETI)**,
Karagwe District, Kagera Region, Tanzania. Registered with NACTEVET.

Built with plain PHP + MySQL (mysqli) + vanilla JS, designed to run on XAMPP.

## Courses
Journalism, Wildlife and National Parks Management, Hotel and Tourism Management,
Pre-Primary Teaching, German Language, Esperanto Language.

## Roles
- **Applicant** — Form Four graduate, logs in with Form Four Index Number
- **Admin** — controls admission windows, courses, payments, and reviews applications
- **Principal (Mkuu wa Chuo)** — read-only view of all applicant details and stats

## Setup (XAMPP)

1. Copy the `peti-system` folder into `htdocs` (e.g. `C:\xampp\htdocs\peti-system`).
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and import
   `database/peti_schema.sql`. This creates the `peti_system` database,
   all tables, the six courses, and one open admission window.
4. In your browser, visit:
   `http://localhost/peti-system/install/seed.php`
   This creates the default Admin and Principal accounts (passwords are
   properly hashed by PHP, which is why this is a separate step from the
   SQL import).
5. **Delete or rename `install/seed.php`** after running it once.
6. Visit `http://localhost/peti-system/` to see the site.

## Default Logins

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `Admin@2026` |
| Principal | `principal` | `Principal@2026` |

**Change these passwords in a real deployment.** (A simple way for now:
generate a new hash with `password_hash('yourpassword', PASSWORD_DEFAULT)`
in a throwaway PHP script and update the `password` column for that user
in the `users` table via phpMyAdmin.)

## Application Flow

1. **Register** (`register.php`) — only possible while an admission window is open.
   Applicant enters Form Four Index Number, year completed Form Four, full
   names, gender, and creates a password. The index number is checked
   against NECTA's public CSEE results before the account is created.
2. Applicant is taken straight to the **full application form**
   (`applicant/application_form.php`): personal details, residence, birth
   details, and both parents'/guardians' details. No payment is required
   for this step.
3. Once details are submitted, the system generates a unique **control
   number** for the Tsh 5,000 application fee.
4. Applicant pays the fee outside the system via CRDB Bank, M-Pesa,
   HaloPesa, Mixx by Yas, or Airtel Money, using the control number.
5. **Admin confirms payment** (`admin/payments.php`) after checking the
   college's bank/mobile money statement for that control number.
6. As soon as payment is confirmed, the applicant can go straight to
   **course selection** (`applicant/course_selection.php`) — no approval
   is required first. This completes their application.
7. **Admin reviews** submitted applications (`admin/applications.php` →
   `application_view.php`) at their own pace and approves or rejects each
   one, independent of the applicant's course selection step.
8. Admin can **send the applicant a message** with the outcome (e.g.
   encouraging them to apply again when the next admission window opens
   if rejected) directly from the application view page.
9. **Principal** has a parallel read-only dashboard and applicant list to
   monitor everything without being able to edit anything.

## Notes on the Payment Flow

A live control-number system tied directly to banks and mobile money
(the kind used across Tanzania for institutional fees) normally requires
a business agreement with a payment aggregator (e.g. Selcom, Azampay, or
the government's GEPG system used by many NACTE-registered colleges).
That is outside the scope of what can be wired up without such an
account. This system generates its own control numbers and lets Admin
manually confirm payment after checking the bank/mobile money statement.
The confirmation logic lives in one place (`admin/payments.php`) so it
can later be replaced with an automatic webhook from a real payment
gateway without changing anything else in the system.

## Project Structure

```
peti-system/
├── database/peti_schema.sql      Database schema + seed courses/window
├── install/seed.php              One-time default account creator (delete after use)
├── config/db.php                 Database connection settings
├── includes/                     auth.php, functions.php, header.php, footer.php
├── assets/css/style.css          Styling
├── assets/js/main.js             Client-side validation
├── index.php                     Public landing page
├── register.php                  Applicant registration (step 1)
├── login.php                     Applicant login
├── staff-login.php               Admin/Principal login
├── logout.php                    Shared logout
├── applicant/                    dashboard, application_form, course_selection, messages
├── admin/                        dashboard, windows, courses, payments, applications, application_view
└── principal/                    dashboard, applications, application_view (read-only)
```

## Security Notes

- Passwords are hashed with `password_hash()` / verified with `password_verify()`.
- All database queries use prepared statements (mysqli).
- All forms include a CSRF token.
- All output is escaped with `htmlspecialchars()` before display.
