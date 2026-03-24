<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';

$db       = Database::getInstance();
$doctorId = isset($_GET['doctor']) ? (int)$_GET['doctor'] : null;
$date     = $_GET['date'] ?? date('Y-m-d');
$today    = date('Y-m-d');

// ── Slot availability for viewed doctor+date ──────────────────
$slotAvailability = [];
if ($doctorId && $date) {
    $dateObj   = new DateTime($date);
    $dayOfWeek = (int)$dateObj->format('w');

    $slots = $db->fetchAll(
        "SELECT id, start_time, max_patients FROM slots
         WHERE doctor_id = ? AND day_of_week = ? AND active = 1",
        [$doctorId, $dayOfWeek]
    );

    foreach ($slots as $slot) {
        $booked = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM appointments
             WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
             AND status NOT IN ('cancelled','no_show')",
            [$doctorId, $date, $slot['start_time']]
        );
        $slotAvailability[] = [
            'doctor_id'   => $doctorId,
            'date'        => $date,
            'time'        => $slot['start_time'],
            'slot_id'     => $slot['id'],
            'booked'      => (int)$booked['cnt'],
            'max'         => (int)$slot['max_patients'],
            'available'   => max(0, (int)$slot['max_patients'] - (int)$booked['cnt']),
        ];
    }
}

// ── Total booked today ────────────────────────────────────────
$todayCount = $db->fetchOne(
    "SELECT COUNT(*) AS cnt FROM appointments
     WHERE appointment_date = ? AND status NOT IN ('cancelled','no_show')",
    [$today]
)['cnt'];

// ── Per-doctor count today (for dashboard header) ─────────────
$perDoctorToday = $db->fetchAll(
    "SELECT doctor_id, COUNT(*) AS count
     FROM appointments
     WHERE appointment_date = ? AND status NOT IN ('cancelled','no_show')
     GROUP BY doctor_id",
    [$today]
);

Api::success([
    'slots_availability' => $slotAvailability,
    'today_count'        => (int)$todayCount,
    'per_doctor_today'   => $perDoctorToday,
]);
