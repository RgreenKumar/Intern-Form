# TalentTrack — Dynamic Candidate and Form Management System

A PHP + MySQL web application for publishing internship/workshop forms,
collecting applications, tracking candidate tasks and generating offer
letters / certificates. Built as part of the RGreen Technologies internship.

## Features
- Role-based login for Admin and Candidate (single unified login page)
- OTP email verification for candidate registration
- Dynamic form builder (create, edit, publish, archive forms with custom fields)
- Public pages: Home, Internships, Workshops, How it Works
- Optional external form link (redirect Apply Now to e.g. a Google Form)
- Task assignment and status tracking
- Offer letter / certificate PDF generation
- Self-service "Forgot Password" (OTP based)

## Requirements
- XAMPP (Apache + MySQL + PHP 8+) — https://www.apachefriends.org
- A Gmail account with an App Password (for sending OTP emails)

## Setup Instructions

1. **Clone this repo** into your XAMPP `htdocs` folder:
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/RgreenKumar/Intern-Form.git
   git checkout RAMCO_RGREEN_CSE_Web1_2024-27
   ```

2. **Start XAMPP** — turn on Apache and MySQL from the XAMPP Control Panel.

3. **Create the database:**
   - Open `http://localhost/phpmyadmin`
   - Create a new database (e.g. `talenttrack`)
   - Go to the **Import** tab, choose `candidate-form-system/database/schema.sql`, click **Go**

4. **Configure the app:**
   - Open `candidate-form-system/config/config.php`
   - Set your own `ADMIN_REGISTRATION_KEY` (this is the secret key needed to create an admin account)
   - Set your own `SMTP_USERNAME` and `SMTP_PASSWORD` (a Gmail **App Password**, not your normal password — generate one at https://myaccount.google.com/apppasswords)
   - Check `config/db.php` matches your database name/credentials

5. **Open the app:**
   ```
   http://localhost/candidate-form-system/public/index.php
   ```

6. **Create an admin account:**
   - Go to `public/admin-register.php`
   - Enter the `ADMIN_REGISTRATION_KEY` you set in step 4

## Project Structure
```
candidate-form-system/
├── admin/          # Admin dashboard, forms, tasks, documents
├── candidate/       # Candidate dashboard, profile, applications
├── public/          # Public site + login/register (entry point)
├── includes/        # Shared PHP (auth, functions, PHPMailer, FPDF)
├── database/         # schema.sql
└── config/           # config.php, db.php
```

## Notes
- `config/config.php` is excluded from version control for security — each
  developer must create their own with their own SMTP/admin credentials.
- Uploaded files (`uploads/`) are not tracked in git.
