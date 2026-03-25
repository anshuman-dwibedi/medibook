# MediBook - Clinic Appointment Booking System

A complete clinic appointment management system with doctor profiles, real-time slot availability, QR check-in, appointment scheduling, and analytics. Perfect for multi-doctor clinics and healthcare centers.

Built on the DevCore Shared Library with secure patient data handling and staff workflows.

## Live Deployment

- Production Website: https://medibook.42web.io

**Part of the DevCore Suite** — a collection of business-ready web applications sharing a common core library.

---

## Features

| Feature | Description |
|---------|-------------|
| Doctor Profiles | Directory of doctors with specialties, departments, profiles, availability |
| Appointment Booking | Patients book appointments for available doctors and time slots |
| Real-Time Slot Availability | Slot availability updates every 4 seconds showing booked vs available times |
| Slot Locking | Prevent double-booking with transaction-based slot locking on confirm |
| Appointment Confirmation | Email/SMS confirmation with unique appointment code and details |
| Appointment Cancellation | Patients can cancel appointments, returning slots to available pool |
| QR Check-In System | Staff scan patient QR codes at reception for automated check-in |
| No-Show Detection | System tracks no-shows for analytics and patient follow-up |
| Analytics Dashboard | Appointment metrics, doctor performance, completion rates, daily trends |
| Admin Management Panel | Doctors, appointment slots, patient records behind secure auth |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.1+ with DevCore framework |
| Database | MySQL 8 / MariaDB 10.6+ |
| Frontend | Vanilla JavaScript ES2022 + DevCore UI library |
| Charts | Chart.js via DevCore wrapper |
| QR Codes | qrserver.com API for check-in cards |
| Sessions | PHP native sessions for user auth |
| Shared Core | DevCore Shared Library (git submodule at ./core/) |

---

## Project Structure

```
medibook/
├── index.php                   Public appointment booking page
├── appointment.php             View appointment details
├── book.php                    Appointment booking form
├── cancel.php                  Appointment cancellation
├── confirmation.php            Booking confirmation + QR card
├── config.example.php          Configuration template
├── database.sql                Schema + sample doctors and slots
├── .env.example                Environment variables
│
├── api/
│   ├── doctors.php             GET list/single, POST create, PUT update, DELETE (admin)
│   ├── slots.php               GET available, POST create, PUT update, DELETE (admin)
│   ├── appointments.php        POST book, GET list/view, PUT update, DELETE cancel
│   ├── live.php                GET real-time slot availability (public polling)
│   └── analytics.php           GET dashboard stats (admin only)
│
├── admin/
│   ├── login.php               Staff authentication
│   ├── dashboard.php           Analytics + appointment feed
│   ├── doctors.php             Doctor management (add/edit/delete)
│   ├── slots.php               Schedule management for doctors
│   ├── appointments.php        View/manage all appointments
│   ├── qr-scanner.php          Check-in via QR code scanner
│   └── logout.php              Session logout
│
└── core/                       DevCore shared library (git submodule)
    ├── bootstrap.php           Autoloader + config loader
    ├── backend/                PHP classes (Database, Api, Auth, etc.)
    └── ui/                     CSS framework + JavaScript utilities
```

---

## Setup Instructions

### 1. Clone DevCore Shared Library

```bash
git clone https://github.com/anshuman-dwibedi/devcore-shared.git core
```

Or using submodule:
```bash
git clone --recursive https://github.com/anshuman-dwibedi/medibook.git
```

### 2. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE medibook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p medibook < database.sql
```

Database includes sample doctors and appointment slots.

### 3. Configure Application

```bash
cp config.example.php config.php
```

Edit `config.php`:

```php
return [
    'db_host'    => 'localhost',
    'db_name'    => 'medibook',
    'db_user'    => 'root',
    'db_pass'    => 'your_password',
    'app_name'   => 'MediBook Clinic',
    'app_url'    => 'http://localhost/medibook',
    'debug'      => true,  // set false in production
    'api_secret' => 'your-secure-random-string',
];
```

### 4. Start Web Server

Using PHP built-in server:
```bash
php -S localhost:8000
```

Or configure Apache/Nginx to point to project root.

### 5. Access Application

- **Patient Booking:** http://localhost:8000/medibook/index.php
- **Admin Panel:** http://localhost:8000/medibook/admin/login.php

**Default Admin Credentials:**
```
Email: admin@clinic.com
Password: admin123
```

> Change immediately in production.

---

## Configuration

### config.example.php

Database credentials, app URL, and other settings. Copy to `config.php` and customize.

Sample doctors in database:
- Dr. Sharma (Cardiology, 3 slots available)
- Dr. Patel (General Medicine, 5 slots available)
- Dr. Verma (Orthopedics, 4 slots available)

---

## How It Works

### Appointment Booking Flow

1. Patient visits homepage → sees list of doctors
2. Selects doctor and available date → sees time slots via `/api/slots.php`
3. Selects available slot and applies discount code if available
4. Fills in patient details (name, email, phone, symptoms)
5. `POST /api/appointments.php` creates booking with slot locked
6. Confirmation page displays appointment code and printable QR card
7. Staff scans QR at reception for check-in on appointment day

**Slot Locking Mechanism:**
```php
// Prevent double-booking via transaction
BEGIN;
SELECT slot FROM doctor_slots WHERE id=X FOR UPDATE;  // Lock row
IF (slot > 0) {
    UPDATE doctor_slots SET slot = slot - 1 WHERE id=X;
    INSERT INTO appointments (...);
}
COMMIT;
```

### Real-Time Availability Polling

Every **4 seconds**, `/api/live.php` returns available slots per doctor:

```javascript
const poller = new LivePoller('api/live.php', (res) => {
  res.doctors.forEach(doc => {
    // Update slot availability UI in real-time
  });
}, 4000);
```

### QR Check-In System

1. Patient arrives for appointment
2. Staff opens Qr-Scanner (`/admin/qr-scanner.php`) on tablet
3. Staff scans patient's appointment QR code
4. System updates appointment status to "checked-in"
5. Doctor is notified patient is ready
6. Appointment timer starts

### No-Show Tracking

System automatically markers appointment as "no-show" if:
- Patient doesn't check in within 15-minute grace period after appointment time
- Staff manually marks as no-show via dashboard

No-show data feeds into analytics for patient follow-up workflows.

### Analytics Dashboard

Dashboard metrics:
- Appointments today, this month, total
- Doctor performance (appointments, completion rate, average feedback)
- Sector performance comparison
- Daily appointment trend chart
- Live appointment feed
- Patient feedback summary

---

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/doctors.php | No | List all doctors with specialties |
| GET | /api/doctors.php?id=X | No | Get doctor details and schedule |
| POST | /api/doctors.php | Admin | Create new doctor |
| PUT | /api/doctors.php?id=X | Admin | Update doctor profile |
| DELETE | /api/doctors.php?id=X | Admin | Delete doctor (soft delete) |
| GET | /api/slots.php?doctor=X&date=Y | No | Get available slots for doctor on date |
| POST | /api/slots.php | Admin | Create appointment slot for doctor |
| PUT | /api/slots.php?id=X | Admin | Update slot details |
| DELETE | /api/slots.php?id=X | Admin | Delete slot |
| POST | /api/appointments.php | No | Book new appointment |
| GET | /api/appointments.php | No/Admin | List appointments (patient lookup or admin view) |
| GET | /api/appointments.php?id=X | No | Get appointment details |
| PUT | /api/appointments.php?id=X | Admin | Update appointment status (checked-in, completed, no-show, etc.) |
| DELETE | /api/appointments.php?id=X | Admin | Cancel appointment (refund, reschedule slots) |
| GET | /api/live.php | No | Real-time slot availability per doctor (polling) |
| GET | /api/analytics.php | Admin | Dashboard statistics, charts, performance metrics |

---

## Troubleshooting

**Database not found**
- Create: `mysql -u root -p -e "CREATE DATABASE medibook;"`
- Import: `mysql -u root -p medibook < database.sql`
- Verify database name in config.php

**"Cannot include core/bootstrap.php"**
- Clone: `git clone https://github.com/anshuman-dwibedi/devcore-shared.git core`
- Or: `git submodule update --init`

**Slots not showing in real-time**
- Check browser console for JS errors
- Verify `/api/live.php?doctor=X&date=Y` returns JSON
- Ensure polling interval not too aggressive

**Double-booking occurring**
- Verify database transactions enabled: `SHOW VARIABLES LIKE 'innodb_support_xa';`
- Check slot locking in `/api/appointments.php` line ~80
- Ensure appointment creation wrapped in transaction

**QR codes not generating**
- QR uses qrserver.com API (requires internet access)
- Verify `QrCode::url()` called correctly in confirmation.php
- Test: Visit http://api.qrserver.com/v1/create-qr-code/?size=200x200&data=test

**Admin login not working**
- Verify users table populated: `SELECT COUNT(*) FROM users;`
- Reset password: `UPDATE users SET password = '$2y$10$...' WHERE email = 'admin@clinic.com';`
- Check PHP sessions enabled in php.ini

**No-show dates incorrect**
- Verify server time matches actual time: `date`
- Check database timezone: `SELECT @@global.time_zone, @@session.time_zone;`
- Grace period is configurable in api/appointments.php (default: 15 minutes)

---

## Environment Variables

Create `.env` or configure in config.php:

| Variable | Purpose |
|----------|---------|
| DB_HOST | MySQL hostname |
| DB_NAME | Database name |
| DB_USER | Database username |
| DB_PASS | Database password |
| APP_NAME | Clinic name in UI |
| APP_URL | Public base URL |
| DEBUG | Debug mode (true/false) |
| API_SECRET | API bearer token secret |
| APPOINTMENT_GRACE_PERIOD | No-show grace period in minutes (default: 15) |
| SMS_ENABLED | Enable SMS confirmations (true/false) |
| SMS_GATEWAY | SMS provider (Twilio, etc.) |
| EMAIL_FROM | Sender email for confirmations |

---

## License

MIT License — see LICENSE file.

---

**Questions?** Visit [DevCore Shared Library](https://github.com/anshuman-dwibedi/devcore-shared) repository.
