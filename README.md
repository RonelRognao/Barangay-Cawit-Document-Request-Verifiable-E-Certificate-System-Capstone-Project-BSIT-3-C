# Barangay Cawit Document Request & Verifiable E-Certificate System (Laravel)

A web-based Barangay Document Request, RBI/KYC Registry, COA Fee & O.R. Tracking, and Verifiable E-Certificate System with QR Code Authentication and Theme Switcher (**Christmas Wonderland** / **Modern Official**) for Barangay Cawit, Manito, Albay.

## Requirements
- PHP 8.2+ (tested on PHP 8.4)
- Composer
- SQLite (default, no XAMPP required) or MySQL / MariaDB

> **Note on File Uploads (`php.ini`):** If testing Valid ID or attachment uploads larger than 2 MB, ensure `upload_max_filesize = 10M` and `post_max_size = 12M` in your `php.ini`.

## Quick Setup (Without XAMPP / Using SQLite)
1. Clone the repository, open the project folder, and install dependencies:
   ```bash
   composer install
   ```
2. Copy the environment file and generate the app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *(On Windows Command Prompt, use `copy .env.example .env`)*
3. Run database migrations and seed starter accounts, Puroks, officials, and document types:
   ```bash
   php artisan migrate --seed
   ```
   *(If prompted to create `database/database.sqlite`, type `yes`.)*
4. Clear caches (optional) and start the development server:
   ```bash
   php artisan optimize:clear
   php artisan serve
   ```
5. Open `http://127.0.0.1:8000` in your browser.

*(Optional: To use MySQL/XAMPP instead of SQLite, update `DB_CONNECTION=mysql` and database credentials in `.env`, create an empty database named `barangay_document_system`, then run `php artisan migrate --seed`.)*

## Default Test Accounts
### Barangay Hall Staff Accounts (Admin Portal — RBAC)
| Role | Email | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin@barangaycawit.gov.ph` | `Admin@12345` | Full system access |
| **Barangay Secretary** | `secretary@barangaycawit.gov.ph` | `Secretary@12345` | Requests, KYC, Blotter, Issue/Revoke Certs, Officials, Staff, Reports |
| **Barangay Treasurer** | `treasurer@barangaycawit.gov.ph` | `Treasurer@12345` | O.R. / Payment verification, Read-only Requests, Logs & COA Reports |
| **Barangay Clerk** | `clerk@barangaycawit.gov.ph` | `Clerk@12345` | Walk-in intake, Request screening (`Pending` / `Under Review`), Blotter entry |

### Resident Portal Account (KYC Verified)
- **Resident (Juan Dela Cruz):** `resident@barangaycawit.gov.ph` / `Resident@12345`

*(New accounts created via `/register` start as `Pending` KYC verification until approved by Admin/Secretary under **Residents (RBI)**.)*

## BSIT 3-C Capstone Project
- **System:** Barangay Cawit Document Request & Verifiable E-Certificate System

## Key Features
- **Granular Role-Based Access Control (RBAC):** Separate permissions and portals for **System Admin**, **Punong Barangay**, **Barangay Secretary**, **Barangay Treasurer**, **Barangay Clerk**, and **Residents**.
- **Resident RBI & KYC Verification (RA 7160 Sec. 394):** Purok/Sitio directory (`Purok 1`–`7`), residency duration tracking, private Valid ID & Proof of Residency uploads (`storage/app/private`), and Admin KYC review before document requests are unlocked.
- **Blotter / Lupong Tagapamayapa & Derogatory Check:** Built-in blotter case log that automatically alerts staff on `/admin/manage-requests` and requires documented override remarks before approving applicants with active cases.
- **Document Types, Business Fields & RA 11261 Compliance:** Configurable fees, validity periods, standardized purposes, dynamic Business Clearance fields/attachments, and RA 11261 First-Time Jobseeker checks (6+ months residency, one-time fee waiver, Oath of Undertaking).
- **COA Fee & Official Receipt (O.R.) Tracking:** Treasurer/Secretary payment & exemption tagging (`Indigency`, `RA 11261`, `Senior Citizen`, `PWD`, `O.R. No.`) with an issuance guardrail blocking paid certificates until O.R. is recorded.
- **Walk-In Assisted Intake:** Front-desk form for encoding requests on behalf of walk-in or non-smartphone residents.
- **Tamper-Evident E-Certificates & Dynamic Signatories:** Manage active Punong Barangay / OIC / Secretary signatories and private e-signatures, auto-calculate `Valid Until` expiration dates, issue/revoke certificates (`BRG-CERT-...`), and verify publicly at `/verify` with **RA 10173 PII masking** and HMAC-SHA256 integrity seals.
- **Audit Logs & DILG/COA Reports:** IP-attributed, before/after diff audit logs (with noise filtering & CSV export) plus printable & CSV-exportable monthly DILG/COA collection and waiver reports.
- **Dynamic Theme Switcher:** Admin-controlled 1-click toggle between **Christmas Wonderland** and **Modern Official** themes.

## Group Members
- Ronel Rognao
- Mike Aycardo
- Jian Agdoro