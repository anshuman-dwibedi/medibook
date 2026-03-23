# ðŸ¥ MediBook â€” Medical Clinic Appointment Booking System

> A production-ready medical clinic booking system with real-time slot locking, QR appointment cards, and clinic analytics.

Part of the **DevCore Portfolio Suite** â€” 4 industry-specific projects, 1 shared core library.

---

## âœ¨ Features

| Feature | Description |
|---|---|
| âš¡ **Real-Time Slot Locking** | Slots update live across all browser sessions via `LivePoller`. No double bookings ever. |
| ðŸ“± **QR Appointment Cards** | Auto-generated QR code links to public appointment page. Printable medical card format. |
| ðŸ§™ **3-Step Booking Wizard** | Single-page wizard: Choose Doctor â†’ Pick Slot â†’ Enter Details. No page reloads. |
| ðŸ“Š **Analytics Dashboard** | KPIs, line chart, bar chart, doughnut chart, live today feed â€” all powered by `Analytics` class. |
| âš ï¸ **No-Show Detection** | Appointments past 30+ minutes with status `confirmed` are flagged as "Possible No-Show". |
| ðŸ‘¨â€âš•ï¸ **Doctor Management** | Add, edit, deactivate doctors with photo upload via pluggable storage driver. |
| ðŸ—‚ï¸ **Weekly Slot Grid** | Visual Monâ€“Fri grid to toggle time slots on/off per doctor. |
| ðŸ” **QR Scanner (Reception)** | Receptionist pastes token or token is scanned â†’ full appointment pulled up instantly. |
| ðŸ”’ **Secure Token System** | Each appointment gets a unique 16-char hex token via `bin2hex(random_bytes(8))`. |
| ðŸ—ƒï¸ **Status Lifecycle** | `booked â†’ confirmed â†’ in_progress â†’ completed / cancelled / no_show` |

---

## ðŸ›  Tech Stack

- **Backend:** PHP 8.1+ Â· PDO MySQL
- **Frontend:** Vanilla JS Â· Devcore UI Design System
- **Charts:** Chart.js 4 via `DCChart` wrapper
- **QR Codes:** goqr.me API via `QrCode::url()`
- **Storage:** Pluggable â€” Local filesystem, AWS S3, or Cloudflare R2
- **Auth:** Session-based via `Auth` class
- **Shared Library:** DevCore (`Database`, `Api`, `Analytics`, `Auth`, `Validator`, `Storage`, `QrCode`)

---

## ðŸš€ Setup Instructions

### 1. Folder Structure

Place the project inside your DevCore portfolio root:

```
your-portfolio/
â”œâ”€â”€ config.php                    â† Main config (copy from config.example.php)
â”œâ”€â”€ core/                         â† DevCore shared library
â”‚   â”œâ”€â”€ bootstrap.php
â”‚   â”œâ”€â”€ backend/
â”‚   â””â”€â”€ ui/
â””â”€â”€ medibook/       â† This project
    â”œâ”€â”€ index.php
    â”œâ”€â”€ book.php
    â”œâ”€â”€ ...
```

### 2. Configure

```bash
cp medibook/config.example.php config.php
```

Edit `config.php`:
```php
'db_host' => 'localhost',
'db_name' => 'medibook',
'db_user' => 'root',
'db_pass' => 'your_password',
'app_url' => 'http://localhost/medibook',
```

### 3. Create Database & Import Schema

```bash
mysql -u root -p -e "CREATE DATABASE medibook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p medibook < medibook/database.sql
```

### 4. Create Upload Directory (for Local Storage)

```bash
mkdir -p medibook/uploads/doctors
chmod 755 medibook/uploads
```

### 5. Open in Browser

```
http://localhost/medibook/index.php         â†’ Public homepage
http://localhost/medibook/book.php â†’ Booking wizard
http://localhost/medibook/admin/dashboard.php â†’  â†’ Admin panel
```

**Admin Login:**
- Email: `admin@clinic.com`
- Password: `admin123`

---

## âš¡ How Real-Time Slot Locking Works

**The problem:** If two patients are on `book.php` at the same time, viewing the same doctor and date, and the last available slot is taken by Patient A â€” Patient B should see it grey out immediately, not discover the conflict only after submitting.

**The solution â€” `LivePoller` + `/api/live.php`:**

1. When a patient reaches Step 2 (Date & Slot), a `LivePoller` starts polling `/api/live.php?doctor=X&date=Y` every **5 seconds**.
2. The API returns the current `booked` count and `max` capacity for each slot.
3. The JS `renderSlots()` function compares live data against the displayed slot buttons and:
   - Updates "3 left" / "Last slot!" / "Full" labels dynamically
   - Disables fully booked slots without page reload
   - If the currently-selected slot becomes full, it clears the selection and shows a warning Toast
4. When a booking is submitted, the server performs a **final atomic check** (`SELECT COUNT(*) ... FOR UPDATE` semantics via PDO) before inserting â€” preventing race conditions even if two submissions arrive simultaneously.

**Why it matters:** Without live locking, a clinic can overbook slots. A patient shows up and finds no appointment on record â€” damaging trust. This system makes overbooking practically impossible.

---

## ðŸ“± How the QR Appointment Card Works

**Patient flow:**

```
1. Patient visits book.php and completes the 3-step wizard
   â†“
2. POST /api/appointments.php â€” server generates token = bin2hex(random_bytes(8))
   â†“
3. Patient is redirected to confirmation.php?token=TOKEN
   â†“
4. confirmation.php renders a printable QR card using QrCode::url()
   The QR encodes: https://yourclinic.com/appointment.php?token=TOKEN
   â†“
5. Patient screenshots, prints, or shows QR on phone at reception
   â†“
6. Receptionist opens admin/qr-scanner.php and pastes the token
   (or scans QR with any QR reader â†’ opens appointment.php â†’ shows token)
   â†“
7. Full appointment details appear with status update buttons
   Receptionist clicks "In Progress" â†’ "Completed" as visit progresses
```

**QR Card contents:** Clinic name Â· Doctor name Â· Department Â· Date Â· Time Â· Patient name Â· Token

**Print support:** The "Print Appointment Card" button opens a clean print-optimized popup containing only the card â€” no navigation, no dark background.

---

## âš ï¸ How No-Show Detection Works

The system doesn't send SMS/email reminders (that would require a mail provider), but it flags potential no-shows in the live dashboard:

1. `admin/dashboard.php` polls `/api/analytics.php` every 30 seconds
2. The analytics endpoint queries today's appointments and checks:
   ```sql
   WHERE status = 'confirmed'
   AND CONCAT(appointment_date, ' ', appointment_time) < NOW() - INTERVAL 30 MINUTE
   ```
3. Any such appointment has `possible_no_show: true` in the response
4. The live feed renders these rows with a `âš ï¸ Possible No-Show` warning badge
5. The receptionist can then manually update the status to `no_show`

**No-Show Rate KPI** = `no_show_count / total_appointments * 100` â€” shown on the dashboard stat card.

---

## ðŸ—ƒï¸ Doctor Photo Storage

Doctor photos are stored via the **pluggable DevCore Storage driver**. To change provider, edit a single line in `config.php`:

```php
'storage' => [
    'driver' => 'local',   // â† change to 's3' or 'r2'
    ...
]
```

| Driver | Description |
|---|---|
| `local` | Files saved to `/uploads/doctors/` in the project root. Zero config. |
| `s3` | AWS S3. Add `key`, `secret`, `bucket`, `region` to config. |
| `r2` | Cloudflare R2. Add `account_id`, `key`, `secret`, `bucket`, `base_url`. |

**Usage in code:**
```php
$photoUrl = Storage::uploadFile($_FILES['photo'], 'doctors');
```

The returned URL is stored in `doctors.photo_url` and served directly in all views. Switching providers requires only changing the config â€” no code changes.

---

## ðŸ“ Project Structure

```
medibook/
â”œâ”€â”€ index.php               Public homepage â€” departments, CTAs
â”œâ”€â”€ book.php                3-step booking wizard
â”œâ”€â”€ confirmation.php        Booking confirmed + QR card
â”œâ”€â”€ appointment.php         Public appointment lookup + status timeline
â”œâ”€â”€ cancel.php              Cancel via token
â”œâ”€â”€ config.example.php      Config template
â”œâ”€â”€ database.sql            Full schema + 60 sample appointments
â”œâ”€â”€ api/
â”‚   â”œâ”€â”€ doctors.php         CRUD for doctors
â”‚   â”œâ”€â”€ slots.php           Slot availability + CRUD
â”‚   â”œâ”€â”€ appointments.php    Book, view, update status
â”‚   â”œâ”€â”€ analytics.php       Dashboard KPIs + chart data
â”‚   â””â”€â”€ live.php            Real-time slot availability (polled every 5s)
â””â”€â”€ admin/
    â”œâ”€â”€ login.php           Admin authentication
    â”œâ”€â”€ dashboard.php       Analytics dashboard
    â”œâ”€â”€ appointments.php    Appointment management table
    â”œâ”€â”€ doctors.php         Doctor CRUD with photo upload
    â”œâ”€â”€ slots.php           Weekly schedule grid editor
    â”œâ”€â”€ qr-scanner.php      Reception QR token lookup
    â””â”€â”€ logout.php          Session logout
```

---

## ðŸ”— DevCore Shared Library

This project depends on the **DevCore shared library** (`./core/`).

> [DevCore on GitHub](https://github.com/anshuman-dwibedi/devcore-shared)

**Classes used:**
- `Database` â€” Singleton PDO wrapper
- `Api` â€” Standardized JSON responses
- `Auth` â€” Session-based authentication
- `Analytics` â€” Reusable clinic analytics queries
- `QrCode` â€” QR code generation via goqr.me
- `Validator` â€” Input validation
- `Storage` â€” Pluggable file storage (Local / S3 / R2)

**UI System:**
- `devcore.css` â€” Dark design system (dc-card, dc-btn, dc-badge, dc-stat, dc-table, dc-sidebarâ€¦)
- `devcore.js` â€” `DC.get/post`, `Toast`, `Modal`, `LivePoller`, `DCChart`, `DCForm`

---

## ðŸ§ª Sample Data

The `database.sql` includes:
- **6 departments** with icons
- **12 doctors** (2 per dept) with realistic bios, 5â€“22 years experience, $50â€“$250 fees
- **180 weekly slots** (Monâ€“Fri, 3 times/day per doctor, max 3 patients)
- **60+ appointments** over 30 past + 7 future days, mixed statuses
- **10+ appointments today** for live dashboard demo
- **1 admin account** â€” `admin@clinic.com` / `admin123`

---

## ðŸ—ï¸ Part of the DevCore Portfolio Suite

> **DevCore Portfolio Suite** â€” 4 industry-specific projects, 1 shared core library.

| Project | Description |
|---|---|
| ðŸ¥ **MediBook** (this) | Medical clinic appointment booking |
| ðŸ½ï¸ **RestroDesk** | Restaurant table booking & menu management |
| ðŸ  **Estatecore** | Real estate property listings & inquiries |
| ðŸ“¦ **Livestore** | A full-featured e-commerce store with live inventory, real-time stock counters, and QR order receipts |

All projects share the same `core/` library â€” one codebase, four industries.

---

## ðŸ“„ License

MIT License â€” free for personal and commercial use.

