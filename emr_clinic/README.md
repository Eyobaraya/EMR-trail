# 🏥 EMR Clinic System

A complete Electronic Medical Records (EMR) system designed for a local clinic with multiple rooms. Built with PHP, MySQL, and Bootstrap, it replaces paper-based workflows and connects all clinic PCs via LAN.

---

## 🚀 Features

### ✅ Completed Features

* **User Authentication & Role Management**

  * Secure login system with session handling
  * Role-based access: Admin, Doctor, Lab Tech, Ultrasound Tech, Emergency Nurse, Receptionist

* **Patient Management**

  * Unique patient card creation (name, date, sex, card number, phone)
  * Status (active/inactive) for tracking returning patients
  * Excel import of old patient data

* **Reception Queueing System**

  * Receptionist activates patient records
  * Patients are automatically added to a doctor's queue — no manual search by doctor

* **Dashboard System**

  * Role-specific dashboards
  * Recent activity, patient queue, and statistics

---

### 🔄 In Progress

* Doctor queue system (automatically receives patients in order)
* Visit management (symptoms, diagnosis, prescription, timestamp)
* Lab test request & result submission
* Ultrasound report entry and image uploads
* Emergency room visit form (vitals, symptoms, quick notes)
* PDF/printable summaries and visit exports

---

## 🛠️ Technology Stack

* **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
* **Backend**: PHP 7.4+
* **Database**: MySQL 5.7+
* **Server**: Apache (XAMPP/WAMP)
* **Other Tools**: PhpSpreadsheet (Excel import), TCPDF (PDF), jQuery

---

## 📋 Requirements

* PHP 7.4 or higher
* MySQL 5.7 or higher
* Apache web server
* XAMPP, WAMP, or any local server environment

---

## 🚀 Installation & Setup

### 1. Download & Extract

* Clone the repository or extract `emr_clinic.zip` into your `htdocs` folder.

### 2. Database Setup

1. Open phpMyAdmin → Create database `emr_clinic`
2. Import `database/schema.sql`

### 3. Configure Database Connection

Update `config/db.php` with:

```php
$host = 'localhost';
$db = 'emr_clinic';
$user = 'root';
$pass = '';
```

### 4. Add Test Users

Manually add users to the `users` table in phpMyAdmin (or use `setup.php` script).

### 5. Access the System

Go to:

```
http://localhost/emr_clinic/
```

Use login credentials created earlier.

---

## 👥 User Roles & Permissions

### 🔧 Admin

* Full access to all modules and user management

### 👨‍⚕️ Doctor

* Automatically receives patients from queue
* Adds diagnoses, prescriptions, lab/ultrasound requests
* Views past visits and results

### 🔬 Lab Technician

* Sees lab requests
* Enters results, uploads scans

### 🎩 Ultrasound Technician

* Views ultrasound requests
* Uploads reports and images

### 🚨 Emergency Nurse

* Registers emergency patients
* Inputs vitals/symptoms
* Forwards to doctor if needed

### 📋 Receptionist

* Registers new patients
* Activates returning patients
* Sends patients to doctor queue

---

## 🔒 Security Features

* Hashed passwords using `password_hash()`
* Session-based authentication
* Role-level page protection
* SQL injection prevention (prepared statements)
* XSS protection with `htmlspecialchars()`
* Planned CSRF protection

---

## 📊 Database Schema

Includes:

* `users` — for all clinic staff
* `patients` — all patient cards
* `visits` — doctor/emergency room visits
* `lab_requests` — test orders and results
* `ultrasound_reports` — ultrasound documentation
* `emergency_visits` — emergency assessments

---

## 🚧 Roadmap

### ✅ Phase 1: Core Setup

* [x] Folder structure & configuration
* [x] Database schema & connection
* [x] Authentication system
* [x] Patient registration

### ✅ Phase 2: Patient Flow

* [x] Reception-based patient activation
* [x] Patient queue system for doctors

### 🔄 Phase 3: Visit Management

* [ ] New visit form (symptoms, diagnosis)
* [ ] Visit history view per patient

### 🔄 Phase 4: Lab Module

* [ ] Test request form (from doctor)
* [ ] Lab result submission + scan upload

### 🔄 Phase 5: Ultrasound Module

* [ ] Report entry & image upload

### 🔄 Phase 6: Emergency Room

* [ ] Emergency intake form (vitals + notes)
* [ ] Connection to doctor review

### 🔄 Phase 7: Reporting & Printouts

* [ ] PDF generation (visit summary, test result)
* [ ] Date filters, exports, and summaries

---

## 😖 Support

* Ask here in this repo
* Contact the dev team

## 📝 License

MIT License

---

**Note:** This system is intended for use on a private clinic network (LAN) and does not require internet access. Ensure proper database backups and secure local access.
