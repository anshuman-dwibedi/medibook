-- ============================================================
-- MediBook — Medical Clinic Appointment Booking System
-- Complete SQL: Create DB → Tables → Indexes → Sample Data
-- Run this file once to get a fully working demo database
-- ============================================================

-- ── 1. CREATE & SELECT DATABASE ──────────────────────────────
CREATE DATABASE IF NOT EXISTS medibook
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE medibook;

-- ── 2. SAFETY: disable FK checks during setup ────────────────
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================================
-- TABLE: departments
-- ============================================================
DROP TABLE IF EXISTS departments;
CREATE TABLE departments (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)    NOT NULL,
    icon       VARCHAR(10)     NOT NULL DEFAULT '🏥',
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_dept_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Medical departments / specialties';

-- ============================================================
-- TABLE: doctors
-- ============================================================
DROP TABLE IF EXISTS doctors;
CREATE TABLE doctors (
    id                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    department_id     INT UNSIGNED    NOT NULL,
    name              VARCHAR(120)    NOT NULL,
    specialization    VARCHAR(120)    NOT NULL,
    bio               TEXT,
    photo_url         VARCHAR(512)    DEFAULT NULL,
    experience_years  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    consultation_fee  DECIMAL(8,2)    NOT NULL DEFAULT 50.00,
    active            TINYINT(1)      NOT NULL DEFAULT 1,
    created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_department (department_id),
    KEY idx_active     (active),
    KEY idx_dept_active (department_id, active)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Clinic doctors and their profiles';

-- ============================================================
-- TABLE: slots
-- Represents recurring weekly availability per doctor
-- day_of_week: 0=Sunday 1=Monday 2=Tuesday 3=Wednesday
--              4=Thursday 5=Friday 6=Saturday
-- ============================================================
DROP TABLE IF EXISTS slots;
CREATE TABLE slots (
    id           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    doctor_id    INT UNSIGNED     NOT NULL,
    day_of_week  TINYINT UNSIGNED NOT NULL COMMENT '0=Sun … 6=Sat',
    start_time   TIME             NOT NULL,
    end_time     TIME             NOT NULL,
    max_patients TINYINT UNSIGNED NOT NULL DEFAULT 3,
    active       TINYINT(1)       NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_doctor_day_time (doctor_id, day_of_week, start_time),
    KEY idx_doctor_day  (doctor_id, day_of_week),
    KEY idx_active      (active)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Weekly recurring time slots per doctor';

-- ============================================================
-- TABLE: appointments
-- ============================================================
DROP TABLE IF EXISTS appointments;
CREATE TABLE appointments (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    token            CHAR(16)      NOT NULL  COMMENT '16-char hex, bin2hex(random_bytes(8))',
    doctor_id        INT UNSIGNED  NOT NULL,
    slot_id          INT UNSIGNED  NULL      COMMENT 'Reference to slots table (nullable for legacy)',
    patient_name     VARCHAR(120)  NOT NULL,
    patient_email    VARCHAR(200)  NOT NULL,
    patient_phone    VARCHAR(30)   NOT NULL,
    patient_dob      DATE          NOT NULL,
    reason           TEXT          NOT NULL,
    appointment_date DATE          NOT NULL,
    appointment_time TIME          NOT NULL,
    status           ENUM(
                       'booked',
                       'confirmed',
                       'in_progress',
                       'completed',
                       'cancelled',
                       'no_show'
                     ) NOT NULL DEFAULT 'booked',
    notes            TEXT          NULL      COMMENT 'Admin/clinic internal notes',
    created_at       TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token              (token),
    UNIQUE KEY uq_patient_slot       (patient_email, doctor_id, appointment_date, appointment_time),
    KEY idx_doctor_date              (doctor_id, appointment_date),
    KEY idx_appointment_date         (appointment_date),
    KEY idx_status                   (status),
    KEY idx_token                    (token),
    KEY idx_created_at               (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Patient appointment bookings';

-- ============================================================
-- TABLE: admins
-- ============================================================
DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(200) NOT NULL,
    password   VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    role       VARCHAR(30)  NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Admin/staff accounts for clinic portal';

-- ============================================================
-- RE-ENABLE FK CHECKS
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA — DEPARTMENTS (6)
-- ============================================================
INSERT INTO departments (name, icon) VALUES
('General Medicine', '⚕️'),
('Cardiology',       '❤️'),
('Dermatology',      '🌡️'),
('Orthopedics',      '🦴'),
('Pediatrics',       '👶'),
('Dental',           '🦷');

-- ============================================================
-- SEED DATA — DOCTORS (12, 2 per department)
-- ============================================================
INSERT INTO doctors
  (department_id, name, specialization, bio, photo_url, experience_years, consultation_fee)
VALUES
-- ── General Medicine (dept 1) ──────────────────────────────
(1,
 'Dr. James Wilson',
 'General Practitioner',
 'Dr. Wilson has 15 years of experience in comprehensive primary care. He specializes in preventive medicine and chronic disease management, helping patients of all ages maintain optimal health through evidence-based approaches and compassionate care.',
 'https://ui-avatars.com/api/?name=James+Wilson&size=200&background=6c63ff&color=fff&bold=true',
 15, 75.00),

(1,
 'Dr. Sarah Chen',
 'Family Medicine',
 'Dr. Chen is a compassionate family physician dedicated to building long-term relationships with her patients. She covers the full spectrum of family health from newborns to seniors, with a special interest in women''s health and geriatric care.',
 'https://ui-avatars.com/api/?name=Sarah+Chen&size=200&background=22d3a0&color=fff&bold=true',
 10, 60.00),

-- ── Cardiology (dept 2) ────────────────────────────────────
(2,
 'Dr. Marcus Thompson',
 'Cardiologist',
 'Dr. Thompson is a board-certified cardiologist with 22 years of clinical excellence. He specializes in heart failure, arrhythmia management, and complex cardiac cases, and has performed over 3,000 cardiac procedures throughout his career.',
 'https://ui-avatars.com/api/?name=Marcus+Thompson&size=200&background=ff5c6a&color=fff&bold=true',
 22, 200.00),

(2,
 'Dr. Elena Rodriguez',
 'Interventional Cardiology',
 'Dr. Rodriguez is a leading interventional cardiologist with expertise in coronary artery disease, percutaneous coronary interventions, and structural heart disease. She trained at Johns Hopkins and has published research in the New England Journal of Medicine.',
 'https://ui-avatars.com/api/?name=Elena+Rodriguez&size=200&background=f5a623&color=fff&bold=true',
 18, 250.00),

-- ── Dermatology (dept 3) ──────────────────────────────────
(3,
 'Dr. Aisha Patel',
 'Dermatologist',
 'Dr. Patel is a highly skilled dermatologist specializing in medical and surgical dermatology. She treats a wide range of skin conditions including eczema, psoriasis, acne, and skin cancer, with evidence-based approaches tailored to each patient.',
 'https://ui-avatars.com/api/?name=Aisha+Patel&size=200&background=38bdf8&color=fff&bold=true',
 12, 120.00),

(3,
 'Dr. Ryan Kim',
 'Cosmetic Dermatology',
 'Dr. Kim blends medical expertise with aesthetic sensibility. He offers advanced treatments including laser therapy, chemical peels, injectables, and medical-grade skincare protocols. Known for his natural-looking results and meticulous technique.',
 'https://ui-avatars.com/api/?name=Ryan+Kim&size=200&background=a78bfa&color=fff&bold=true',
 8, 150.00),

-- ── Orthopedics (dept 4) ──────────────────────────────────
(4,
 'Dr. David Turner',
 'Orthopedic Surgeon',
 'Dr. Turner is an accomplished orthopedic surgeon with 20 years of experience in joint replacement, spine surgery, and complex trauma reconstruction. He has performed over 2,500 surgeries and is known for his precision and excellent patient outcomes.',
 'https://ui-avatars.com/api/?name=David+Turner&size=200&background=6c63ff&color=fff&bold=true',
 20, 180.00),

(4,
 'Dr. Lisa Chang',
 'Sports Medicine',
 'Dr. Chang is a sports medicine specialist passionate about helping athletes recover faster and perform better. She works with professional sports teams and weekend warriors alike, offering cutting-edge regenerative therapies and rehabilitation protocols.',
 'https://ui-avatars.com/api/?name=Lisa+Chang&size=200&background=22d3a0&color=fff&bold=true',
 14, 160.00),

-- ── Pediatrics (dept 5) ───────────────────────────────────
(5,
 'Dr. Emily Foster',
 'Pediatrician',
 'Dr. Foster has dedicated 16 years to the health and wellbeing of children from birth through adolescence. She is known for her warm manner and thorough approach, creating a safe environment where children feel comfortable and parents feel heard.',
 'https://ui-avatars.com/api/?name=Emily+Foster&size=200&background=f5a623&color=fff&bold=true',
 16, 90.00),

(5,
 'Dr. Michael Osei',
 'Child Specialist',
 'Dr. Osei brings 9 years of expertise in childhood development, immunizations, ADHD management, and pediatric chronic illness. He is passionate about empowering families with knowledge and building healthy habits from the earliest years of life.',
 'https://ui-avatars.com/api/?name=Michael+Osei&size=200&background=ff5c6a&color=fff&bold=true',
 9, 80.00),

-- ── Dental (dept 6) ───────────────────────────────────────
(6,
 'Dr. Amanda White',
 'General Dentist',
 'Dr. White provides comprehensive dental care with 11 years of experience. Her gentle approach and state-of-the-art techniques — including digital X-rays and same-day crowns — ensure comfortable, anxiety-free visits for the whole family.',
 'https://ui-avatars.com/api/?name=Amanda+White&size=200&background=38bdf8&color=fff&bold=true',
 11, 100.00),

(6,
 'Dr. Carlos Mendez',
 'Orthodontist',
 'Dr. Mendez is a certified orthodontist with 17 years of experience creating beautiful, functional smiles. He specializes in Invisalign clear aligners, traditional metal braces, ceramic braces, and complex adult orthodontic cases.',
 'https://ui-avatars.com/api/?name=Carlos+Mendez&size=200&background=a78bfa&color=fff&bold=true',
 17, 175.00);

-- ============================================================
-- SEED DATA — WEEKLY SLOTS
-- Each doctor gets Mon–Fri (day_of_week 1–5)
-- 3 slots per day: 09:00, 11:00, 14:00
-- max_patients = 3 per slot
-- Total: 12 doctors × 5 days × 3 slots = 180 slot rows
-- ============================================================
INSERT INTO slots (doctor_id, day_of_week, start_time, end_time, max_patients) VALUES
-- Doctor 1 — Mon–Fri
(1,1,'09:00:00','10:00:00',3),(1,1,'11:00:00','12:00:00',3),(1,1,'14:00:00','15:00:00',3),
(1,2,'09:00:00','10:00:00',3),(1,2,'11:00:00','12:00:00',3),(1,2,'14:00:00','15:00:00',3),
(1,3,'09:00:00','10:00:00',3),(1,3,'11:00:00','12:00:00',3),(1,3,'14:00:00','15:00:00',3),
(1,4,'09:00:00','10:00:00',3),(1,4,'11:00:00','12:00:00',3),(1,4,'14:00:00','15:00:00',3),
(1,5,'09:00:00','10:00:00',3),(1,5,'11:00:00','12:00:00',3),(1,5,'14:00:00','15:00:00',3),
-- Doctor 2
(2,1,'09:00:00','10:00:00',3),(2,1,'11:00:00','12:00:00',3),(2,1,'14:00:00','15:00:00',3),
(2,2,'09:00:00','10:00:00',3),(2,2,'11:00:00','12:00:00',3),(2,2,'14:00:00','15:00:00',3),
(2,3,'09:00:00','10:00:00',3),(2,3,'11:00:00','12:00:00',3),(2,3,'14:00:00','15:00:00',3),
(2,4,'09:00:00','10:00:00',3),(2,4,'11:00:00','12:00:00',3),(2,4,'14:00:00','15:00:00',3),
(2,5,'09:00:00','10:00:00',3),(2,5,'11:00:00','12:00:00',3),(2,5,'14:00:00','15:00:00',3),
-- Doctor 3
(3,1,'09:00:00','10:00:00',3),(3,1,'11:00:00','12:00:00',3),(3,1,'14:00:00','15:00:00',3),
(3,2,'09:00:00','10:00:00',3),(3,2,'11:00:00','12:00:00',3),(3,2,'14:00:00','15:00:00',3),
(3,3,'09:00:00','10:00:00',3),(3,3,'11:00:00','12:00:00',3),(3,3,'14:00:00','15:00:00',3),
(3,4,'09:00:00','10:00:00',3),(3,4,'11:00:00','12:00:00',3),(3,4,'14:00:00','15:00:00',3),
(3,5,'09:00:00','10:00:00',3),(3,5,'11:00:00','12:00:00',3),(3,5,'14:00:00','15:00:00',3),
-- Doctor 4
(4,1,'09:00:00','10:00:00',3),(4,1,'11:00:00','12:00:00',3),(4,1,'14:00:00','15:00:00',3),
(4,2,'09:00:00','10:00:00',3),(4,2,'11:00:00','12:00:00',3),(4,2,'14:00:00','15:00:00',3),
(4,3,'09:00:00','10:00:00',3),(4,3,'11:00:00','12:00:00',3),(4,3,'14:00:00','15:00:00',3),
(4,4,'09:00:00','10:00:00',3),(4,4,'11:00:00','12:00:00',3),(4,4,'14:00:00','15:00:00',3),
(4,5,'09:00:00','10:00:00',3),(4,5,'11:00:00','12:00:00',3),(4,5,'14:00:00','15:00:00',3),
-- Doctor 5
(5,1,'09:00:00','10:00:00',3),(5,1,'11:00:00','12:00:00',3),(5,1,'14:00:00','15:00:00',3),
(5,2,'09:00:00','10:00:00',3),(5,2,'11:00:00','12:00:00',3),(5,2,'14:00:00','15:00:00',3),
(5,3,'09:00:00','10:00:00',3),(5,3,'11:00:00','12:00:00',3),(5,3,'14:00:00','15:00:00',3),
(5,4,'09:00:00','10:00:00',3),(5,4,'11:00:00','12:00:00',3),(5,4,'14:00:00','15:00:00',3),
(5,5,'09:00:00','10:00:00',3),(5,5,'11:00:00','12:00:00',3),(5,5,'14:00:00','15:00:00',3),
-- Doctor 6
(6,1,'09:00:00','10:00:00',3),(6,1,'11:00:00','12:00:00',3),(6,1,'14:00:00','15:00:00',3),
(6,2,'09:00:00','10:00:00',3),(6,2,'11:00:00','12:00:00',3),(6,2,'14:00:00','15:00:00',3),
(6,3,'09:00:00','10:00:00',3),(6,3,'11:00:00','12:00:00',3),(6,3,'14:00:00','15:00:00',3),
(6,4,'09:00:00','10:00:00',3),(6,4,'11:00:00','12:00:00',3),(6,4,'14:00:00','15:00:00',3),
(6,5,'09:00:00','10:00:00',3),(6,5,'11:00:00','12:00:00',3),(6,5,'14:00:00','15:00:00',3),
-- Doctor 7
(7,1,'09:00:00','10:00:00',3),(7,1,'11:00:00','12:00:00',3),(7,1,'14:00:00','15:00:00',3),
(7,2,'09:00:00','10:00:00',3),(7,2,'11:00:00','12:00:00',3),(7,2,'14:00:00','15:00:00',3),
(7,3,'09:00:00','10:00:00',3),(7,3,'11:00:00','12:00:00',3),(7,3,'14:00:00','15:00:00',3),
(7,4,'09:00:00','10:00:00',3),(7,4,'11:00:00','12:00:00',3),(7,4,'14:00:00','15:00:00',3),
(7,5,'09:00:00','10:00:00',3),(7,5,'11:00:00','12:00:00',3),(7,5,'14:00:00','15:00:00',3),
-- Doctor 8
(8,1,'09:00:00','10:00:00',3),(8,1,'11:00:00','12:00:00',3),(8,1,'14:00:00','15:00:00',3),
(8,2,'09:00:00','10:00:00',3),(8,2,'11:00:00','12:00:00',3),(8,2,'14:00:00','15:00:00',3),
(8,3,'09:00:00','10:00:00',3),(8,3,'11:00:00','12:00:00',3),(8,3,'14:00:00','15:00:00',3),
(8,4,'09:00:00','10:00:00',3),(8,4,'11:00:00','12:00:00',3),(8,4,'14:00:00','15:00:00',3),
(8,5,'09:00:00','10:00:00',3),(8,5,'11:00:00','12:00:00',3),(8,5,'14:00:00','15:00:00',3),
-- Doctor 9
(9,1,'09:00:00','10:00:00',3),(9,1,'11:00:00','12:00:00',3),(9,1,'14:00:00','15:00:00',3),
(9,2,'09:00:00','10:00:00',3),(9,2,'11:00:00','12:00:00',3),(9,2,'14:00:00','15:00:00',3),
(9,3,'09:00:00','10:00:00',3),(9,3,'11:00:00','12:00:00',3),(9,3,'14:00:00','15:00:00',3),
(9,4,'09:00:00','10:00:00',3),(9,4,'11:00:00','12:00:00',3),(9,4,'14:00:00','15:00:00',3),
(9,5,'09:00:00','10:00:00',3),(9,5,'11:00:00','12:00:00',3),(9,5,'14:00:00','15:00:00',3),
-- Doctor 10
(10,1,'09:00:00','10:00:00',3),(10,1,'11:00:00','12:00:00',3),(10,1,'14:00:00','15:00:00',3),
(10,2,'09:00:00','10:00:00',3),(10,2,'11:00:00','12:00:00',3),(10,2,'14:00:00','15:00:00',3),
(10,3,'09:00:00','10:00:00',3),(10,3,'11:00:00','12:00:00',3),(10,3,'14:00:00','15:00:00',3),
(10,4,'09:00:00','10:00:00',3),(10,4,'11:00:00','12:00:00',3),(10,4,'14:00:00','15:00:00',3),
(10,5,'09:00:00','10:00:00',3),(10,5,'11:00:00','12:00:00',3),(10,5,'14:00:00','15:00:00',3),
-- Doctor 11
(11,1,'09:00:00','10:00:00',3),(11,1,'11:00:00','12:00:00',3),(11,1,'14:00:00','15:00:00',3),
(11,2,'09:00:00','10:00:00',3),(11,2,'11:00:00','12:00:00',3),(11,2,'14:00:00','15:00:00',3),
(11,3,'09:00:00','10:00:00',3),(11,3,'11:00:00','12:00:00',3),(11,3,'14:00:00','15:00:00',3),
(11,4,'09:00:00','10:00:00',3),(11,4,'11:00:00','12:00:00',3),(11,4,'14:00:00','15:00:00',3),
(11,5,'09:00:00','10:00:00',3),(11,5,'11:00:00','12:00:00',3),(11,5,'14:00:00','15:00:00',3),
-- Doctor 12
(12,1,'09:00:00','10:00:00',3),(12,1,'11:00:00','12:00:00',3),(12,1,'14:00:00','15:00:00',3),
(12,2,'09:00:00','10:00:00',3),(12,2,'11:00:00','12:00:00',3),(12,2,'14:00:00','15:00:00',3),
(12,3,'09:00:00','10:00:00',3),(12,3,'11:00:00','12:00:00',3),(12,3,'14:00:00','15:00:00',3),
(12,4,'09:00:00','10:00:00',3),(12,4,'11:00:00','12:00:00',3),(12,4,'14:00:00','15:00:00',3),
(12,5,'09:00:00','10:00:00',3),(12,5,'11:00:00','12:00:00',3),(12,5,'14:00:00','15:00:00',3);

-- ============================================================
-- SEED DATA — APPOINTMENTS
-- 12 for TODAY (live dashboard demo)
-- ~42 spread over past 30 days (mixed statuses)
-- 10 for future 7 days
-- ============================================================

-- ── TODAY'S APPOINTMENTS (8 confirmed/booked + 4 completed/in-progress) ──
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('f3a8b2c1d4e56701', 1,  NULL, 'Alice Johnson',
 'alice.j@email.com',   '+1-555-0101', '1988-04-12',
 'Annual physical checkup and blood pressure review',
 CURDATE(), '09:00:00', 'completed'),

('a9e7f6b3c2d15802', 3,  NULL, 'Robert Martinez',
 'rob.m@email.com',     '+1-555-0102', '1965-09-23',
 'Chest pain and shortness of breath evaluation',
 CURDATE(), '09:00:00', 'completed'),

('c5d3e1f2a8b07603', 5,  NULL, 'Patricia Garcia',
 'pat.g@email.com',     '+1-555-0103', '1980-02-14',
 'Recurring skin rash and eczema flare-up treatment',
 CURDATE(), '09:00:00', 'in_progress'),

('b7f9c4d2e6a31804', 7,  NULL, 'Thomas Anderson',
 'tom.a@email.com',     '+1-555-0104', '1975-11-30',
 'Knee pain and swelling after sports injury last week',
 CURDATE(), '09:00:00', 'in_progress'),

('e2a6c8d1f4b59305', 9,  NULL, 'Linda Wilson',
 'linda.w@email.com',   '+1-555-0105', '1995-07-08',
 'Child wellness check and vaccinations for 3-year-old',
 CURDATE(), '11:00:00', 'confirmed'),

('d4b8f7c5e3a12406', 11, NULL, 'Kevin Thompson',
 'kevin.t@email.com',   '+1-555-0106', '1990-01-25',
 'Tooth sensitivity and routine dental cleaning',
 CURDATE(), '11:00:00', 'confirmed'),

('a1c7e5f3b9d28507', 2,  NULL, 'Sandra Davis',
 'sandra.d@email.com',  '+1-555-0107', '1972-06-17',
 'Follow-up on blood work and diabetes management plan',
 CURDATE(), '11:00:00', 'confirmed'),

('g3h5k7m9n1p3q5r7', 4,  NULL, 'Charles Brown',
 'charles.b@email.com', '+1-555-0108', '1968-03-05',
 'High cholesterol and hypertension consultation',
 CURDATE(), '11:00:00', 'booked'),

('s2t4u6v8w1x3y5z9', 6,  NULL, 'Nancy White',
 'nancy.w@email.com',   '+1-555-0109', '1985-12-20',
 'Acne treatment plan and skincare consultation',
 CURDATE(), '14:00:00', 'booked'),

('q8r6p4o2n1m9l7k5', 8,  NULL, 'Daniel Taylor',
 'daniel.t@email.com',  '+1-555-0110', '1978-08-14',
 'Lower back pain and postural assessment',
 CURDATE(), '14:00:00', 'booked'),

('j4i2h1g9f7e5d3c1', 10, NULL, 'Maria Harris',
 'maria.h@email.com',   '+1-555-0111', '1982-05-30',
 'Sports injury recovery plan and physical therapy',
 CURDATE(), '14:00:00', 'booked'),

('b9c7d5e3f1a8b6c4', 12, NULL, 'James Rodriguez',
 'james.r@email.com',   '+1-555-0112', '1973-10-11',
 'Invisalign consultation and teeth alignment assessment',
 CURDATE(), '14:00:00', 'booked');

-- ── PAST: 1 day ago ──────────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('p1a2b3c4d5e6f7g8', 1,  NULL, 'Michael Scott',
 'mscott@email.com',    '+1-555-0201', '1969-03-15',
 'General checkup and cholesterol panel review',
 DATE_SUB(CURDATE(),INTERVAL 1 DAY), '09:00:00', 'completed'),

('h9i8j7k6l5m4n3o2', 3,  NULL, 'Dwight Schrute',
 'dschrute@email.com',  '+1-555-0202', '1970-01-20',
 'Cardiac stress test evaluation and follow-up',
 DATE_SUB(CURDATE(),INTERVAL 1 DAY), '11:00:00', 'completed'),

('r1s2t3u4v5w6x7y8', 5,  NULL, 'Pam Beesly',
 'pbeesly@email.com',   '+1-555-0203', '1979-03-25',
 'Mild psoriasis treatment plan and prescription',
 DATE_SUB(CURDATE(),INTERVAL 1 DAY), '14:00:00', 'completed');

-- ── PAST: 2 days ago ─────────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('z9a8b7c6d5e4f3g2', 7,  NULL, 'Jim Halpert',
 'jhalpert@email.com',  '+1-555-0204', '1978-10-01',
 'Shoulder tendinitis after basketball game',
 DATE_SUB(CURDATE(),INTERVAL 2 DAY), '09:00:00', 'completed'),

('h1i2j3k4l5m6n7o8', 9,  NULL, 'Angela Martin',
 'amartin@email.com',   '+1-555-0205', '1986-06-25',
 'Newborn wellness visit and nutrition guidance',
 DATE_SUB(CURDATE(),INTERVAL 2 DAY), '11:00:00', 'completed'),

('p9q8r7s6t5u4v3w2', 11, NULL, 'Ryan Howard',
 'rhoward@email.com',   '+1-555-0206', '1983-05-17',
 'Wisdom tooth extraction consultation',
 DATE_SUB(CURDATE(),INTERVAL 2 DAY), '14:00:00', 'completed');

-- ── PAST: 3 days ago ─────────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('x1y2z3a4b5c6d7e8', 2,  NULL, 'Kelly Kapoor',
 'kkapoor@email.com',   '+1-555-0207', '1980-02-05',
 'Persistent cough and seasonal allergy management',
 DATE_SUB(CURDATE(),INTERVAL 3 DAY), '09:00:00', 'completed'),

('f9g8h7i6j5k4l3m2', 4,  NULL, 'Oscar Martinez',
 'omartinez@email.com', '+1-555-0208', '1975-08-16',
 'Heart palpitations and fatigue assessment',
 DATE_SUB(CURDATE(),INTERVAL 3 DAY), '11:00:00', 'no_show'),

('n1o2p3q4r5s6t7u8', 6,  NULL, 'Stanley Hudson',
 'shudson@email.com',   '+1-555-0209', '1956-02-19',
 'Rosacea and sensitive skin treatment consultation',
 DATE_SUB(CURDATE(),INTERVAL 3 DAY), '14:00:00', 'completed');

-- ── PAST: 4 days ago ─────────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('v9w8x7y6z5a4b3c2', 8,  NULL, 'Phyllis Vance',
 'pvance@email.com',    '+1-555-0210', '1958-07-10',
 'Hip replacement recovery follow-up appointment',
 DATE_SUB(CURDATE(),INTERVAL 4 DAY), '09:00:00', 'completed'),

('d1e2f3g4h5i6j7k8', 10, NULL, 'Meredith Palmer',
 'mpalmer@email.com',   '+1-555-0211', '1965-12-08',
 'Achilles tendon pain from daily running routine',
 DATE_SUB(CURDATE(),INTERVAL 4 DAY), '11:00:00', 'completed'),

('l9m8n7o6p5q4r3s2', 12, NULL, 'Toby Flenderson',
 'tflenderson@email.com','+1-555-0212','1963-04-01',
 'Dental bridge fitting and adjustment follow-up',
 DATE_SUB(CURDATE(),INTERVAL 4 DAY), '14:00:00', 'cancelled');

-- ── PAST: 5 days ago ─────────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('t1u2v3w4x5y6z7a8', 1,  NULL, 'Creed Bratton',
 'cbratton@email.com',  '+1-555-0213', '1943-02-08',
 'Blood work review and medication adjustment',
 DATE_SUB(CURDATE(),INTERVAL 5 DAY), '09:00:00', 'no_show'),

('b9c8d7e6f5g4h3i2', 3,  NULL, 'Kevin Malone',
 'kmalone@email.com',   '+1-555-0214', '1978-05-01',
 'Coronary calcium scoring and heart health consultation',
 DATE_SUB(CURDATE(),INTERVAL 5 DAY), '11:00:00', 'completed');

-- ── PAST: 6 days ago ─────────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('j1k2l3m4n5o6p7q8', 5,  NULL, 'Erin Hannon',
 'ehannon@email.com',   '+1-555-0215', '1988-05-10',
 'Contact dermatitis and allergic skin reaction',
 DATE_SUB(CURDATE(),INTERVAL 6 DAY), '09:00:00', 'completed'),

('r9s8t7u6v5w4x3y2', 7,  NULL, 'Darryl Philbin',
 'dphilbin@email.com',  '+1-555-0216', '1971-09-29',
 'Rotator cuff injury evaluation and treatment plan',
 DATE_SUB(CURDATE(),INTERVAL 6 DAY), '14:00:00', 'cancelled');

-- ── PAST: 7 days ago ─────────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('z1a2b3c4d5e6f7g8', 9,  NULL, 'Jan Levinson',
 'jlevinson@email.com', '+1-555-0217', '1968-04-15',
 'Teenage annual health assessment and immunizations',
 DATE_SUB(CURDATE(),INTERVAL 7 DAY), '09:00:00', 'completed'),

('h9i8j7k6l5m4n3p2', 11, NULL, 'Roy Anderson',
 'randerson@email.com', '+1-555-0218', '1978-11-12',
 'Teeth whitening and dental veneer consultation',
 DATE_SUB(CURDATE(),INTERVAL 7 DAY), '11:00:00', 'completed');

-- ── PAST: 8–10 days ago ──────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('q1r2s3t4u5v6w7x8', 2,  NULL, 'David Wallace',
 'dwallace@email.com',  '+1-555-0219', '1967-02-22',
 'Flu symptoms, fatigue and dehydration evaluation',
 DATE_SUB(CURDATE(),INTERVAL 8 DAY), '09:00:00', 'completed'),

('y9z8a7b6c5d4e3f2', 4,  NULL, 'Todd Packer',
 'tpacker@email.com',   '+1-555-0220', '1971-07-07',
 'Arrhythmia monitoring and medication review',
 DATE_SUB(CURDATE(),INTERVAL 8 DAY), '14:00:00', 'no_show'),

('g1h2i3j4k5l6m7n8', 6,  NULL, 'Gabe Lewis',
 'glewis@email.com',    '+1-555-0221', '1981-03-30',
 'Sunburn treatment and suspicious mole check',
 DATE_SUB(CURDATE(),INTERVAL 9 DAY), '11:00:00', 'completed'),

('o9p8q7r6s5t4u3v2', 8,  NULL, 'Andrew Bernard',
 'abernard@email.com',  '+1-555-0222', '1978-03-05',
 'Knee arthroscopy 4-week post-operative check',
 DATE_SUB(CURDATE(),INTERVAL 10 DAY),'09:00:00', 'completed');

-- ── PAST: 11–15 days ago ─────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('w1x2y3z4a5b6c7d8', 10, NULL, 'Robert California',
 'rcali@email.com',     '+1-555-0223', '1958-10-10',
 'Plantar fasciitis management and orthotics fitting',
 DATE_SUB(CURDATE(),INTERVAL 11 DAY),'14:00:00', 'completed'),

('e9f8g7h6i5j4k3l2', 12, NULL, 'Will Ferrell',
 'wferrell@email.com',  '+1-555-0224', '1967-07-16',
 'Braces tightening adjustment and cleaning',
 DATE_SUB(CURDATE(),INTERVAL 12 DAY),'09:00:00', 'completed'),

('m1n2o3p4q5r6s7t8', 1,  NULL, 'Bruno Mars',
 'bmars@email.com',     '+1-555-0225', '1985-10-08',
 'Sinus infection evaluation and antibiotics review',
 DATE_SUB(CURDATE(),INTERVAL 13 DAY),'11:00:00', 'completed'),

('u9v8w7x6y5z4a3b2', 3,  NULL, 'Adele Atkins',
 'aatkins@email.com',   '+1-555-0226', '1988-05-05',
 'Echocardiogram results discussion and next steps',
 DATE_SUB(CURDATE(),INTERVAL 14 DAY),'09:00:00', 'cancelled'),

('c1d2e3f4g5h6i7j8', 5,  NULL, 'Ed Sheeran',
 'esheeran@email.com',  '+1-555-0227', '1991-02-17',
 'Vitiligo treatment options and UV phototherapy plan',
 DATE_SUB(CURDATE(),INTERVAL 15 DAY),'14:00:00', 'completed');

-- ── PAST: 16–20 days ago ─────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('k9l8m7n6o5p4q3r2', 7,  NULL, 'Taylor Swift',
 'tswift@email.com',    '+1-555-0228', '1989-12-13',
 'ACL reconstruction 6-month recovery progress evaluation',
 DATE_SUB(CURDATE(),INTERVAL 16 DAY),'11:00:00', 'completed'),

('s1t2u3v4w5x6y7z8', 9,  NULL, 'Harry Styles',
 'hstyles@email.com',   '+1-555-0229', '1994-02-01',
 'Infant feeding difficulty and reflux consultation',
 DATE_SUB(CURDATE(),INTERVAL 17 DAY),'09:00:00', 'no_show'),

('a9b8c7d6e5f4g3h2', 11, NULL, 'Olivia Rodrigo',
 'orodri@email.com',    '+1-555-0230', '2003-02-20',
 'Night teeth grinding guard fitting and jaw evaluation',
 DATE_SUB(CURDATE(),INTERVAL 18 DAY),'11:00:00', 'completed'),

('i1j2k3l4m5n6o7p8', 2,  NULL, 'Billie Eilish',
 'beilish@email.com',   '+1-555-0231', '2001-12-18',
 'Chronic headache and migraine management consultation',
 DATE_SUB(CURDATE(),INTERVAL 19 DAY),'14:00:00', 'completed'),

('q9r8s7t6u5v4w3x2', 4,  NULL, 'Dua Lipa',
 'dlipa@email.com',     '+1-555-0232', '1995-08-22',
 '24-hour Holter monitor results discussion',
 DATE_SUB(CURDATE(),INTERVAL 20 DAY),'09:00:00', 'completed');

-- ── PAST: 21–25 days ago ─────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('y1z2a3b4c5d6e7f8', 6,  NULL, 'The Weeknd',
 'weeknd@email.com',    '+1-555-0233', '1990-02-16',
 'Hyperpigmentation treatment plan and chemical peel',
 DATE_SUB(CURDATE(),INTERVAL 21 DAY),'11:00:00', 'cancelled'),

('g9h8i7j6k5l4m3n2', 8,  NULL, 'Ariana Grande',
 'agrande@email.com',   '+1-555-0234', '1993-06-26',
 'Wrist stress fracture rehabilitation progress check',
 DATE_SUB(CURDATE(),INTERVAL 22 DAY),'14:00:00', 'completed'),

('o1p2q3r4s5t6u7v8', 10, NULL, 'Post Malone',
 'pmalone@email.com',   '+1-555-0235', '1995-07-04',
 'Running biomechanics analysis and gait correction',
 DATE_SUB(CURDATE(),INTERVAL 23 DAY),'09:00:00', 'completed'),

('w9x8y7z6a5b4c3d2', 12, NULL, 'Lana Del Rey',
 'ldelrey@email.com',   '+1-555-0236', '1985-06-21',
 'Root canal treatment and crown placement consultation',
 DATE_SUB(CURDATE(),INTERVAL 24 DAY),'11:00:00', 'completed'),

('e1f2g3h4i5j6k7l8', 1,  NULL, 'Drake Graham',
 'dgraham@email.com',   '+1-555-0237', '1986-10-24',
 'Annual blood pressure monitoring and lifestyle review',
 DATE_SUB(CURDATE(),INTERVAL 25 DAY),'14:00:00', 'completed');

-- ── PAST: 26–30 days ago ─────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('m9n8o7p6q5r4s3t2', 3,  NULL, 'Rihanna Fenty',
 'rfenty@email.com',    '+1-555-0238', '1988-02-20',
 'Cardiac stress test and coronary artery evaluation',
 DATE_SUB(CURDATE(),INTERVAL 26 DAY),'09:00:00', 'no_show'),

('u1v2w3x4y5z6a7b8', 5,  NULL, 'Kendall Jenner',
 'kjenner@email.com',   '+1-555-0239', '1995-11-03',
 'Skin allergy patch testing and sensitivity panel',
 DATE_SUB(CURDATE(),INTERVAL 27 DAY),'11:00:00', 'completed'),

('c9d8e7f6g5h4i3j2', 7,  NULL, 'Justin Bieber',
 'jbieber@email.com',   '+1-555-0240', '1994-03-01',
 'Hip labral tear 3-month follow-up evaluation',
 DATE_SUB(CURDATE(),INTERVAL 28 DAY),'14:00:00', 'completed'),

('k1l2m3n4o5p6q7r8', 9,  NULL, 'Zendaya Coleman',
 'zcoleman@email.com',  '+1-555-0241', '1996-09-01',
 'Childhood asthma management and inhaler review',
 DATE_SUB(CURDATE(),INTERVAL 29 DAY),'09:00:00', 'completed'),

('s9t8u7v6w5x4y3z2', 11, NULL, 'Timothée Chalamet',
 'tchal@email.com',     '+1-555-0242', '1995-12-27',
 'Jaw pain and TMJ disorder assessment',
 DATE_SUB(CURDATE(),INTERVAL 30 DAY),'11:00:00', 'completed');

-- ── FUTURE: next 7 days ───────────────────────────────────────
INSERT INTO appointments
  (token, doctor_id, slot_id, patient_name, patient_email, patient_phone,
   patient_dob, reason, appointment_date, appointment_time, status)
VALUES
('n1a2b3c4d5e6f7a1', 1,  NULL, 'Emma Stone',
 'estone@email.com',    '+1-555-0301', '1988-11-06',
 'Post-surgery follow-up and wound healing check',
 DATE_ADD(CURDATE(),INTERVAL 1 DAY), '09:00:00', 'confirmed'),

('o2b3c4d5e6f7g8b2', 4,  NULL, 'Ryan Gosling',
 'rgosling@email.com',  '+1-555-0302', '1980-11-12',
 'Hypertension medication dosage review',
 DATE_ADD(CURDATE(),INTERVAL 1 DAY), '11:00:00', 'booked'),

('p3c4d5e6f7g8h9c3', 6,  NULL, 'Margot Robbie',
 'mrobbie@email.com',   '+1-555-0303', '1990-07-02',
 'Laser hair removal and skin consultation',
 DATE_ADD(CURDATE(),INTERVAL 2 DAY), '09:00:00', 'booked'),

('q4d5e6f7g8h9i0d4', 8,  NULL, 'Tom Hanks',
 'thanks@email.com',    '+1-555-0304', '1956-07-09',
 'Lumbar spinal stenosis evaluation and treatment plan',
 DATE_ADD(CURDATE(),INTERVAL 2 DAY), '14:00:00', 'confirmed'),

('r5e6f7g8h9i0j1e5', 10, NULL, 'Meryl Streep',
 'mstreep@email.com',   '+1-555-0305', '1949-06-22',
 'Marathon training related injury assessment',
 DATE_ADD(CURDATE(),INTERVAL 3 DAY), '11:00:00', 'booked'),

('s6f7g8h9i0j1k2f6', 12, NULL, 'Brad Pitt',
 'bpitt@email.com',     '+1-555-0306', '1963-12-18',
 'Smile makeover and cosmetic dental consultation',
 DATE_ADD(CURDATE(),INTERVAL 3 DAY), '09:00:00', 'booked'),

('t7g8h9i0j1k2l3g7', 2,  NULL, 'Angelina Jolie',
 'ajolie@email.com',    '+1-555-0307', '1975-06-04',
 'Shortness of breath and heart palpitations',
 DATE_ADD(CURDATE(),INTERVAL 4 DAY), '09:00:00', 'confirmed'),

('u8h9i0j1k2l3m4h8', 3,  NULL, 'Leonardo DiCaprio',
 'ldicaprio@email.com', '+1-555-0308', '1974-11-11',
 'High cholesterol management and statin review',
 DATE_ADD(CURDATE(),INTERVAL 5 DAY), '11:00:00', 'booked'),

('v9i0j1k2l3m4n5i9', 5,  NULL, 'Scarlett Johansson',
 'sjohan@email.com',    '+1-555-0309', '1984-11-22',
 'Eczema flare-up treatment and steroid cream review',
 DATE_ADD(CURDATE(),INTERVAL 6 DAY), '14:00:00', 'booked'),

('w0j1k2l3m4n5o6j0', 7,  NULL, 'Chris Evans',
 'cevans@email.com',    '+1-555-0310', '1981-06-13',
 'Torn meniscus consultation and MRI review',
 DATE_ADD(CURDATE(),INTERVAL 7 DAY), '09:00:00', 'booked');

-- ============================================================
-- SEED DATA — ADMIN ACCOUNT
-- Email:    admin@clinic.com
-- Password: admin123   (bcrypt cost=12)
-- ============================================================
INSERT INTO admins (name, email, password, role) VALUES
('Clinic Administrator',
 'admin@clinic.com',
 '$2a$12$ZNzRQvODic4uFOoRa2EA.eTsw9FhqMtVukxqSOEablxjkrMP4QSLW',
 'admin');

-- ============================================================
-- VERIFICATION QUERIES — run these to confirm everything loaded
-- ============================================================
/*
SELECT 'departments' AS tbl, COUNT(*) AS rows FROM departments
UNION ALL
SELECT 'doctors',      COUNT(*) FROM doctors
UNION ALL
SELECT 'slots',        COUNT(*) FROM slots
UNION ALL
SELECT 'appointments', COUNT(*) FROM appointments
UNION ALL
SELECT 'admins',       COUNT(*) FROM admins;

-- Expected output:
-- departments | 6
-- doctors     | 12
-- slots       | 180
-- appointments| 74
-- admins      | 1

-- Today's appointments:
SELECT patient_name, appointment_time, status
FROM appointments
WHERE appointment_date = CURDATE()
ORDER BY appointment_time;

-- Status distribution:
SELECT status, COUNT(*) AS total
FROM appointments
GROUP BY status
ORDER BY total DESC;
*/

-- ============================================================
-- DONE
-- All tables created, all data inserted.
-- Open http://localhost/medical-booking-system/ to start.
-- Admin: admin@clinic.com / admin123
-- ============================================================