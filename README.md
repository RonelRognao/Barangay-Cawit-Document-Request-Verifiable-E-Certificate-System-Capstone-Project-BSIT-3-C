# Barangay Cawit Document Request & Verifiable E-Certificate System (Laravel)

A web-based Barangay Document Request and Verifiable E-Certificate System with QR Code Authentication and Admin Theme Switcher (Christmas / Modern) for Barangay Cawit, Manito, Albay.

## Requirements
- PHP 8.2+ (tested on PHP 8.4)
- Composer
- SQLite (default, no XAMPP required) or MySQL

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
3. Run database migrations and seed starter accounts & document types:
   ```bash
   php artisan migrate --seed
   ```
   *(If prompted to create `database/database.sqlite`, type `yes`.)*
4. Start the development server:
   ```bash
   php artisan serve
   ```
5. Open `http://127.0.0.1:8000` in your browser.

*(Optional: To use MySQL/XAMPP instead of SQLite, update `DB_CONNECTION=mysql` and database credentials in `.env`, create an empty database named `barangay_document_system`, then run `php artisan migrate --seed`.)*

## Default Test Accounts
- **Admin:** `admin@barangaycawit.gov.ph` / `Admin@12345`
- **Resident:** `resident@barangaycawit.gov.ph` / `Resident@12345`

## BSIT 3-C Capstone Project:
- Barangay Cawit Document Request & Verifiable E-Certificate System

## Key Features
- **Role-Based Access Control:** Separate, middleware-protected portals for **Residents** and **Administrators**.
- **Online Document Requests:** Residents can request barangay documents online with auto-generated reference numbers (`BRG-...`) and real-time status tracking (*Pending*, *Under Review*, *Approved*, *Rejected*, *Completed*).
- **Admin Request Processing:** Admins can review resident details, update application statuses, and send official remarks.
- **E-Certificate Issuance & Revocation:** Admins can issue official electronic certificates (`BRG-CERT-...`) for approved requests and revoke invalid certificates when needed.
- **QR Code Verification Portal:** Public verification page (`/verify`) to authenticate certificates and check revocation status via scannable QR codes.
- **Printable PDF E-Certificates:** Residents can view and print official A4 landscape copies of their issued certificates.
- **Notifications & Audit Logs:** Automatic resident notifications on request updates and complete admin activity logs for system accountability.
- **Dynamic Theme Switcher:** Admin-controlled 1-click toggle between **Christmas Wonderland** and **Modern Official** themes across the entire portal.

## Group Members:
- Ronel Rognao
- Mike Aycardo
- Jian Agdoro