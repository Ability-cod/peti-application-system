# PETI Application System

An online college application and admissions system for Perfect Education and Training Institute (PETI), a NACTEVET-registered college located in Karagwe District, Kagera Region, Tanzania.

---

## Overview

PETI previously relied on manual, paper-based admissions, which made it difficult to verify applicant credentials and manage the flow from application to course placement. This system digitizes the entire process — from registration through certificate verification, payment, and course selection — while giving administrators and the Principal clear visibility into every applicant.

## Courses Offered

- Journalism
- Wildlife and National Parks Management
- Hotel and Tourism Management
- Pre-Primary (Early Childhood) Teaching
- German Language
- Esperanto Language

## User Roles

| Role | Access |
|---|---|
| Applicant | Registers with Form Four index number, applies, pays, selects course |
| Admin | Manages application windows, courses, payments; reviews applications |
| Principal (Mkuu wa Chuo) | Views all applicant details (read-only) |

Admin and Principal accounts are set up by default at deployment.

## Application Flow

1. **Register** (`register.php`) — applicant enters their Form Four index number (format: `KITUO/MTAHINIWA/MWAKA`, e.g. `S0001/0001/2020`), full name (first/middle/last), gender, and password. Index number is validated by format only.
2. **Fill application form and upload certificate** (`applicant/application_form.php`) — personal details, residence, birth details, both parents'/guardians' information, and a scanned copy of the Form Four certificate (JPG, PNG, or PDF, max 5MB). The uploaded certificate serves as the actual identity verification step.
3. **Control number** is generated automatically once the application form is complete.
4. **Payment** — applicant pays Tsh 5,000 via the college's mobile money Lipa Namba (shown on the applicant dashboard, managed by admin through `admin/payment_settings.php`), using the control number as payment reference.
5. **Payment confirmation** — admin manually confirms payment (`admin/payments.php`) after checking the SMS notification and matching the control number.
6. **Course selection** (`applicant/course_selection.php`) — once payment is confirmed, the applicant proceeds directly to choose a course. There is no admin approval gate before this step.
7. **Application review** — admin can review applications at any time (`admin/applications.php` → `application_view.php`), view the uploaded certificate, approve or reject, and message the applicant with the outcome (e.g. inviting them to try the next application window if they missed a slot).

## Tech Stack

- **Backend:** PHP, MySQL
- **Frontend:** JavaScript / React
- **Interface language:** English

## Database Structure

- `applicants` — first_name, middle_name, last_name, gender, certificate_path, plus contact, residence, and birth details
- `parents_guardians` — parent/guardian details linked to each applicant
- `payment_settings` — single record holding the college's active mobile money network and Lipa Namba
- `payments`, `messages`, `courses`, `admission_windows`, `users` (admin/principal accounts)

## Setup

1. Clone the repository
   ```
   git clone https://github.com/Ability-cod/peti-application-system.git
   ```
2. Import the database schema into MySQL
3. Configure database credentials in the backend config file
4. Place the project inside your XAMPP `htdocs` folder
5. Start Apache and MySQL from XAMPP Control Panel
6. Open the project in your browser via `http://localhost/peti-application-system`

### Test accounts (seed scripts)

- Run `install/seed.php` to create the default admin and principal accounts
- Run `install/seed_test_applicant.php` to create a test applicant with payment already marked as paid

Change all default passwords before any real deployment.

## Notes

- Live NECTA verification was attempted (single-year, multi-year sequential, and parallel checks) but removed due to network/DNS issues in the development environment. Certificate upload now serves as the verification step instead.
- Automatic payment gateway integration (e.g. Selcom, AzamPay) is not yet implemented — payments are currently confirmed manually by admin.
- The Tsh 5,000 application fee is active in the current database.

## Security Notes

- Passwords are hashed before storage
- All queries use prepared statements to prevent SQL injection
- Uploaded certificates are limited to JPG/PNG/PDF, max 5MB

---

Developer: Ability M. Johnbosco
Programme: BSc. Information Technology and Systems - Mzumbe University, 2026