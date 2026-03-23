# 🏥 MediBook — Medical Clinic Appointment Booking System

> A production-ready medical clinic booking system with real-time slot locking, QR appointment cards, and clinic analytics.

Part of the **DevCore Portfolio Suite** — 4 industry-specific projects, 1 shared core library.

---

## ✨ Features

| Feature | Description |
|---|---|
| ⚡ **Real-Time Slot Locking** | Slots update live across all browser sessions via `LivePoller`. No double bookings ever. |
| 📱 **QR Appointment Cards** | Auto-generated QR code links to public appointment page. Printable medical card format. |
| 🧙 **3-Step Booking Wizard** | Single-page wizard: Choose Doctor → Pick Slot → Enter Details. No page reloads. |
| 📊 **Analytics Dashboard** | KPIs, line chart, bar chart, doughnut chart, live today feed — all powered by `Analytics` class. |
| ⚠️ **No-Show Detection** | Appointments past 30+ minutes with status `confirmed` are flagged as "Possible No-Show". |
| 👨‍⚕️ **Doctor Management** | Add, edit, deactivate doctors with photo upload via pluggable storage driver. |
| 🗂️ **Weekly Slot Grid** | Visual Mon–Fri grid to toggle time slots on/off per doctor. |
| 🔍 **QR Scanner (Reception)** | Receptionist pastes token or token is scanned → full appointment pulled up instantly. |
| 🔒 **Secure Token System** | Each appointment gets a unique 16-char hex token via `bin2hex(random_bytes(8))`. |
| 🗃️ **Status Lifecycle** | `booked → confirmed → in_progress → completed / cancelled / no_show` |

---

## 🛠 Tech Stack

- **Backend:** PHP 8.1+ · PDO MySQL
- **Frontend:** Vanilla JS · Devcore UI Design System
- **Charts:** Chart.js 4 via `DCChart` wrapper
- **QR Codes:** goqr.me API via `QrCode::url()`
- **Storage:** Pluggable — Local filesystem, AWS S3, or Cloudflare R2
- **Auth:** Session-based via `Auth` class
- **Shared Library:** DevCore (`Database`, `Api`, `Analytics`, `Auth`, `Validator`, `Storage`, `QrCode`)

---

## 🚀 Setup Instructions

### 1. Folder Structure

Place the project inside your DevCore portfolio root:

```
your-portfolio/
├── config.php                    ← Main config (copy from config.example.php)
├── core/                         ← DevCore shared library
│   ├── bootstrap.php
│   ├── backend/
│   └── ui/
└── medibook/       ← This project
    ├── index.php
    ├── book.php
    ├── ...
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
http://localhost/medibook/index.php         → Public homepage
http://localhost/medibook/book.php → Booking wizard
http://localhost/medibook/admin/dashboard.php →  → Admin panel
```

**Admin Login:**
- Email: `admin@clinic.com`
- Password: `admin123`

---

## ⚡ How Real-Time Slot Locking Works

**The problem:** If two patients are on `book.php` at the same time, viewing the same doctor and date, and the last available slot is taken by Patient A — Patient B should see it grey out immediately, not discover the conflict only after submitting.

**The solution — `LivePoller` + `/api/live.php`:**

1. When a patient reaches Step 2 (Date & Slot), a `LivePoller` starts polling `/api/live.php?doctor=X&date=Y` every **5 seconds**.
2. The API returns the current `booked` count and `max` capacity for each slot.
3. The JS `renderSlots()` function compares live data against the displayed slot buttons and:
   - Updates "3 left" / "Last slot!" / "Full" labels dynamically
   - Disables fully booked slots without page reload
   - If the currently-selected slot becomes full, it clears the selection and shows a warning Toast
4. When a booking is submitted, the server performs a **final atomic check** (`SELECT COUNT(*) ... FOR UPDATE` semantics via PDO) before inserting — preventing race conditions even if two submissions arrive simultaneously.

**Why it matters:** Without live locking, a clinic can overbook slots. A patient shows up and finds no appointment on record — damaging trust. This system makes overbooking practically impossible.

---

## 📱 How the QR Appointment Card Works

**Patient flow:**

```
1. Patient visits book.php and completes the 3-step wizard
   ↓
2. POST /api/appointments.php — server generates token = bin2hex(random_bytes(8))
   ↓
3. Patient is redirected to confirmation.php?token=TOKEN
   ↓
4. confirmation.php renders a printable QR card using QrCode::url()
   The QR encodes: https://yourclinic.com/appointment.php?token=TOKEN
   ↓
5. Patient screenshots, prints, or shows QR on phone at reception
   ↓
6. Receptionist opens admin/qr-scanner.php and pastes the token
   (or scans QR with any QR reader → opens appointment.php → shows token)
   ↓
7. Full appointment details appear with status update buttons
   Receptionist clicks "In Progress" → "Completed" as visit progresses
```

**QR Card contents:** Clinic name · Doctor name · Department · Date · Time · Patient name · Token

**Print support:** The "Print Appointment Card" button opens a clean print-optimized popup containing only the card — no navigation, no dark background.

---

## ⚠️ How No-Show Detection Works

The system doesn't send SMS/email reminders (that would require a mail provider), but it flags potential no-shows in the live dashboard:

1. `admin/dashboard.php` polls `/api/analytics.php` every 30 seconds
2. The analytics endpoint queries today's appointments and checks:
   ```sql
   WHERE status = 'confirmed'
   AND CONCAT(appointment_date, ' ', appointment_time) < NOW() - INTERVAL 30 MINUTE
   ```
3. Any such appointment has `possible_no_show: true` in the response
4. The live feed renders these rows with a `⚠️ Possible No-Show` warning badge
5. The receptionist can then manually update the status to `no_show`

**No-Show Rate KPI** = `no_show_count / total_appointments * 100` — shown on the dashboard stat card.

---

## 🗃️ Doctor Photo Storage

Doctor photos are stored via the **pluggable DevCore Storage driver**. To change provider, edit a single line in `config.php`:

```php
'storage' => [
    'driver' => 'local',   // ← change to 's3' or 'r2'
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

The returned URL is stored in `doctors.photo_url` and served directly in all views. Switching providers requires only changing the config — no code changes.

---

## 📁 Project Structure

```
medibook/
├── index.php               Public homepage — departments, CTAs
├── book.php                3-step booking wizard
├── confirmation.php        Booking confirmed + QR card
├── appointment.php         Public appointment lookup + status timeline
├── cancel.php              Cancel via token
├── config.example.php      Config template
├── database.sql            Full schema + 60 sample appointments
├── api/
│   ├── doctors.php         CRUD for doctors
│   ├── slots.php           Slot availability + CRUD
│   ├── appointments.php    Book, view, update status
│   ├── analytics.php       Dashboard KPIs + chart data
│   └── live.php            Real-time slot availability (polled every 5s)
└── admin/
    ├── login.php           Admin authentication
    ├── dashboard.php       Analytics dashboard
    ├── appointments.php    Appointment management table
    ├── doctors.php         Doctor CRUD with photo upload
    ├── slots.php           Weekly schedule grid editor
    ├── qr-scanner.php      Reception QR token lookup
    └── logout.php          Session logout
```

---

## 🔗 DevCore Shared Library

This project depends on the **DevCore shared library** (`../../core/`).

> [DevCore on GitHub →](https://github.com/your-org/devcore) *(update with actual URL)*

**Classes used:**
- `Database` — Singleton PDO wrapper
- `Api` — Standardized JSON responses
- `Auth` — Session-based authentication
- `Analytics` — Reusable clinic analytics queries
- `QrCode` — QR code generation via goqr.me
- `Validator` — Input validation
- `Storage` — Pluggable file storage (Local / S3 / R2)

**UI System:**
- `devcore.css` — Dark design system (dc-card, dc-btn, dc-badge, dc-stat, dc-table, dc-sidebar…)
- `devcore.js` — `DC.get/post`, `Toast`, `Modal`, `LivePoller`, `DCChart`, `DCForm`

---

## 🧪 Sample Data

The `database.sql` includes:
- **6 departments** with icons
- **12 doctors** (2 per dept) with realistic bios, 5–22 years experience, $50–$250 fees
- **180 weekly slots** (Mon–Fri, 3 times/day per doctor, max 3 patients)
- **60+ appointments** over 30 past + 7 future days, mixed statuses
- **10+ appointments today** for live dashboard demo
- **1 admin account** — `admin@clinic.com` / `admin123`

---

## 🏗️ Part of the DevCore Portfolio Suite

> **DevCore Portfolio Suite** — 4 industry-specific projects, 1 shared core library.

| Project | Description |
|---|---|
| 🏥 **MediBook** (this) | Medical clinic appointment booking |
| 🍽️ **RestroDesk** | Restaurant table booking & menu management |
| 🏠 **Estatecore** | Real estate property listings & inquiries |
| 📦 **Livestore** | A full-featured e-commerce store with live inventory, real-time stock counters, and QR order receipts |

All projects share the same `core/` library — one codebase, four industries.

---

## 📄 License

MIT License — free for personal and commercial use.
