<?php
require_once dirname(__DIR__, 3) . '/core/bootstrap.php';
Auth::requireRole('admin');

$db    = Database::getInstance();
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

// ── KPI: Appointments Today ──────────────────────────────────
$todayCount = $db->fetchOne(
    "SELECT COUNT(*) AS cnt FROM appointments WHERE appointment_date = ? AND status NOT IN ('cancelled','no_show')",
    [$today]
)['cnt'];

// ── KPI: Completion Rate ─────────────────────────────────────
$statuses = $db->fetchAll(
    "SELECT status, COUNT(*) AS cnt FROM appointments
     WHERE appointment_date <= ?
     GROUP BY status",
    [$today]
);
$statusMap = array_column($statuses, 'cnt', 'status');
$completed  = (int)($statusMap['completed']  ?? 0);
$cancelled  = (int)($statusMap['cancelled']  ?? 0);
$noShow     = (int)($statusMap['no_show']    ?? 0);
$denom      = $completed + $cancelled + $noShow;
$completionRate = $denom > 0 ? round($completed / $denom * 100, 1) : 0;
$noShowRate     = $denom > 0 ? round($noShow    / $denom * 100, 1) : 0;

// ── KPI: Busiest Doctor this month ───────────────────────────
$busiest = $db->fetchOne(
    "SELECT d.name, COUNT(*) AS cnt
     FROM appointments a JOIN doctors d ON d.id = a.doctor_id
     WHERE a.appointment_date >= ? AND a.status NOT IN ('cancelled','no_show')
     GROUP BY a.doctor_id ORDER BY cnt DESC LIMIT 1",
    [$monthStart]
);

// ── Chart: Appointments per day (last 30 days) ───────────────
$days30Start = date('Y-m-d', strtotime('-29 days'));
$dailyBooked = $db->fetchAll(
    "SELECT appointment_date AS label, COUNT(*) AS value
     FROM appointments
     WHERE appointment_date >= ? AND appointment_date <= ?
     GROUP BY appointment_date ORDER BY appointment_date",
    [$days30Start, $today]
);
$dailyCompleted = $db->fetchAll(
    "SELECT appointment_date AS label, COUNT(*) AS value
     FROM appointments
     WHERE appointment_date >= ? AND appointment_date <= ? AND status = 'completed'
     GROUP BY appointment_date ORDER BY appointment_date",
    [$days30Start, $today]
);

// ── Chart: Per doctor this month ─────────────────────────────
$perDoctor = $db->fetchAll(
    "SELECT d.name AS label, COUNT(*) AS value
     FROM appointments a JOIN doctors d ON d.id = a.doctor_id
     WHERE a.appointment_date >= ? AND a.status NOT IN ('cancelled','no_show')
     GROUP BY a.doctor_id ORDER BY value DESC",
    [$monthStart]
);

// ── Chart: By department (doughnut) ──────────────────────────
$byDept = $db->fetchAll(
    "SELECT dep.name AS label, COUNT(*) AS value
     FROM appointments a
     JOIN doctors d ON d.id = a.doctor_id
     JOIN departments dep ON dep.id = d.department_id
     WHERE a.status NOT IN ('cancelled','no_show')
     GROUP BY dep.id ORDER BY value DESC",
    []
);

// ── Live feed: today's upcoming appointments ──────────────────
$feed = $db->fetchAll(
    "SELECT a.patient_name, a.appointment_time, a.status, a.token,
            d.name AS doctor_name,
            TIMESTAMPDIFF(MINUTE, CONCAT(a.appointment_date,' ',a.appointment_time), NOW()) AS mins_ago
     FROM appointments a JOIN doctors d ON d.id = a.doctor_id
     WHERE a.appointment_date = ?
     ORDER BY a.appointment_time ASC",
    [$today]
);

// Flag possible no-shows: status still confirmed/booked and 30+ min past appointment time
foreach ($feed as &$row) {
    $row['possible_no_show'] = (
        in_array($row['status'], ['confirmed','booked']) &&
        (int)$row['mins_ago'] >= 30
    ) ? 1 : 0;
}
unset($row);

Api::success([
    'kpi' => [
        'today_count'     => (int)$todayCount,
        'completion_rate' => $completionRate,
        'no_show_rate'    => $noShowRate,
        'busiest_doctor'  => $busiest['name'] ?? 'N/A',
        'busiest_count'   => (int)($busiest['cnt'] ?? 0),
    ],
    'charts' => [
        'daily_booked'    => $dailyBooked,
        'daily_completed' => $dailyCompleted,
        'per_doctor'      => $perDoctor,
        'by_department'   => $byDept,
    ],
    'feed' => $feed,
]);