<?php
require_once dirname(__DIR__, 3) . '/core/bootstrap.php';

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET ─ available slots for a doctor+date ───────────────
    case 'GET':
        $doctorId  = isset($_GET['doctor']) ? (int)$_GET['doctor'] : null;
        $date      = $_GET['date'] ?? null;
        $adminMode = !empty($_GET['admin']);

        if (!$doctorId) Api::error('doctor required', 400);

        // Admin mode: return all slots for this doctor (all days, active + inactive)
        if ($adminMode) {
            Auth::requireRole('admin');
            $slots = $db->fetchAll(
                "SELECT s.id, s.doctor_id, s.day_of_week, s.start_time, s.end_time,
                        s.max_patients, s.active
                 FROM slots s
                 WHERE s.doctor_id = ?
                 ORDER BY s.day_of_week, s.start_time",
                [$doctorId]
            );
            Api::success($slots);
            break;
        }

        // Public mode: return available slots for a specific date
        if (!$date) Api::error('date required', 400);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) Api::error('Invalid date format', 400);

        $dateObj    = new DateTime($date);
        $dayOfWeek  = (int)$dateObj->format('w'); // 0=Sun … 6=Sat

        // All active slots for this doctor on this weekday
        $slots = $db->fetchAll(
            "SELECT s.id, s.start_time, s.end_time, s.max_patients, s.day_of_week
             FROM slots s
             WHERE s.doctor_id = ? AND s.day_of_week = ? AND s.active = 1
             ORDER BY s.start_time",
            [$doctorId, $dayOfWeek]
        );

        // Count existing (non-cancelled/no_show) bookings for each slot on this date
        foreach ($slots as &$slot) {
            $booked = $db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM appointments
                 WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
                 AND status NOT IN ('cancelled','no_show')",
                [$doctorId, $date, $slot['start_time']]
            );
            $slot['booked']    = (int)($booked['cnt'] ?? 0);
            $slot['available'] = max(0, $slot['max_patients'] - $slot['booked']);
            $slot['date']      = $date;
        }
        unset($slot);

        Api::success($slots);
        break;

    // ── POST ─ admin: create a slot ───────────────────────────
    case 'POST':
        Auth::requireRole('admin');
        $body = Api::body();
        $v = Validator::make($body, [
            'doctor_id'   => 'required|integer',
            'day_of_week' => 'required|integer',
            'start_time'  => 'required',
            'end_time'    => 'required',
            'max_patients'=> 'required|integer',
        ]);
        if ($v->fails()) Api::error('Validation failed', 422, $v->errors());

        $newId = $db->insert('slots', [
            'doctor_id'   => (int)$body['doctor_id'],
            'day_of_week' => (int)$body['day_of_week'],
            'start_time'  => $body['start_time'],
            'end_time'    => $body['end_time'],
            'max_patients'=> (int)$body['max_patients'],
            'active'      => 1,
        ]);
        Api::success(['id' => $newId], 201);
        break;

    // ── PUT ─ toggle slot active/inactive ─────────────────────
    case 'PUT':
        Auth::requireRole('admin');
        $id   = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $body = Api::body();
        if (!$id) Api::error('Slot ID required', 400);
        $db->update('slots', ['active' => (int)(bool)$body['active']], "id = $id");
        Api::success(['updated' => true]);
        break;

    // ── DELETE ───────────────────────────────────────────────
    case 'DELETE':
        Auth::requireRole('admin');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id) Api::error('Slot ID required', 400);
        $db->delete('slots', "id = $id");
        Api::success(['deleted' => true]);
        break;

    default:
        Api::error('Method not allowed', 405);
}