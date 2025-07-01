# 🏥 EMR Clinic System

A comprehensive Electronic Medical Records (EMR) system designed for local clinics, built with PHP, MySQL, and Bootstrap.

## 🚀 Features

### ✅ Completed Features
- **User Authentication & Role Management**
  - Secure login system with role-based access
  - Support for multiple user roles: Admin, Doctor, Lab Tech, Ultrasound Tech, Emergency Nurse, Receptionist
  - Session management and security

- **Patient Management**
  - Patient registration with unique card numbers
  - Patient search and filtering
  - Patient status management (active/inactive)
  - Patient history tracking

- **Dashboard System**
  - Role-specific dashboards
  - Statistics and overview cards
  - Recent activity tracking
  - Quick action buttons

### 🔄 In Progress
- Visit management and medical history
- Lab module (requests and results)
- Ultrasound module
- Emergency room module
- Reporting and PDF generation

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache (XAMPP/WAMP)
- **Additional**: Font Awesome icons, jQuery

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server
- XAMPP, WAMP, or similar local development environment

## 🚀 Installation & Setup

### 1. Clone/Download the Project
```bash
# If using git
git clone <repository-url>
cd emr_clinic

# Or download and extract to your web server directory
```

### 2. Database Setup
1. Start your MySQL server (XAMPP/WAMP)
2. Create a new database named `emr_clinic`
3. Import the database schema:
   ```sql
   -- Run the contents of database/schema.sql in your MySQL client
   ```

### 3. Configuration
1. Edit `config/db.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'emr_clinic');
   define('DB_USER', 'root');        // Your MySQL username
   define('DB_PASS', '');            // Your MySQL password
   ```

### 4. Initialize Sample Data
1. Open your browser and navigate to:
   ```
   http://localhost/emr_clinic/scripts/setup.php
   ```
2. This will create sample users and patients for testing

### 5. Access the System
1. Navigate to: `http://localhost/emr_clinic/`
2. Login with default credentials:
   - **Admin**: username: `admin`, password: `admin123`
   - **Doctor**: username: `doctor`, password: `password123`
   - **Lab Tech**: username: `lab`, password: `password123`
   - **Ultrasound Tech**: username: `ultrasound`, password: `password123`
   - **Emergency Nurse**: username: `emergency`, password: `password123`
   - **Receptionist**: username: `receptionist`, password: `password123`

## 📁 Project Structure

```
emr_clinic/
├── auth/                   # Authentication pages
│   ├── login.php          # Login form
│   └── logout.php         # Logout handler
├── config/                # Configuration files
│   └── db.php            # Database connection
├── database/              # Database files
│   └── schema.sql        # Database schema
├── includes/              # PHP includes
│   └── auth.php          # Authentication functions
├── models/                # Database models (future)
├── public/                # Public assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── uploads/          # File uploads
├── scripts/               # Utility scripts
│   └── setup.php         # Database setup script
├── templates/             # HTML templates
│   ├── header.php        # Page header
│   └── sidebar.php       # Navigation sidebar
├── views/                 # Page views
│   ├── dashboard_admin.php # Admin dashboard
│   ├── patients.php      # Patient list
│   ├── add_patient.php   # Add patient form
│   └── ...               # Other view files
├── .htaccess             # Apache configuration
├── index.php             # Main entry point
└── README.md             # This file
```

## 👥 User Roles & Permissions

### 🔧 Admin
- Full system access
- User management
- System configuration
- Reports and analytics

### 👨‍⚕️ Doctor
- Patient management
- Visit creation and management
- Lab test requests
- Ultrasound requests
- View lab results

### 🔬 Lab Technician
- View pending lab requests
- Enter lab results
- Upload scanned results
- Mark tests as completed

### 🩻 Ultrasound Technician
- View ultrasound requests
- Enter ultrasound results
- Upload ultrasound images
- Generate ultrasound reports

### 🚨 Emergency Nurse
- Emergency patient registration
- Emergency visit management
- Quick patient assessment
- Forward to doctor when needed

### 📋 Receptionist
- Patient registration
- Appointment scheduling
- Patient search and lookup
- Basic patient information management

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- Role-based access control
- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`
- CSRF protection (planned)

## 🎨 UI/UX Features

- Responsive design with Bootstrap 5
- Modern, clean interface
- Role-specific navigation
- Interactive dashboards
- Search and filtering capabilities
- Mobile-friendly layout

## 📊 Database Schema

The system uses the following main tables:
- `users` - User accounts and roles
- `patients` - Patient information
- `visits` - Medical visit records
- `lab_requests` - Laboratory test requests
- `ultrasound_reports` - Ultrasound reports
- `emergency_visits` - Emergency room records

## 🚧 Development Roadmap

### Phase 1: Core System ✅
- [x] Project setup and structure
- [x] Database schema
- [x] User authentication
- [x] Basic patient management

### Phase 2: Patient Management ✅
- [x] Patient registration
- [x] Patient search and filtering
- [x] Patient status management

### Phase 3: Visit Management (In Progress)
- [ ] Visit form creation
- [ ] Visit history tracking
- [ ] Medical history management

### Phase 4: Lab Module (Planned)
- [ ] Lab test requests
- [ ] Lab results entry
- [ ] Result notifications

### Phase 5: Ultrasound Module (Planned)
- [ ] Ultrasound requests
- [ ] Ultrasound reports
- [ ] Image upload functionality

### Phase 6: Emergency Module (Planned)
- [ ] Emergency patient registration
- [ ] Emergency visit management
- [ ] Quick assessment forms

### Phase 7: Reporting (Planned)
- [ ] PDF report generation
- [ ] Statistical reports
- [ ] Data export functionality

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## 🔄 Updates

### Version 1.0.0 (Current)
- Initial release with core functionality
- User authentication and role management
- Patient management system
- Basic dashboard functionality

---

**Note**: This is a local clinic EMR system designed for internal use. Ensure proper security measures are in place before deploying to production environments. 