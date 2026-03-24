<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$token  = $_GET['token'] ?? null;
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ── GET ──────────────────────────────────────────────────
    case 'GET':
        // Single by token (public)
        if ($token) {
            $appt = $db->fetchOne(
                "SELECT a.*, d.name AS doctor_name, d.specialization, d.consultation_fee,
                        dep.name AS department_name, dep.icon AS department_icon
                 FROM appointments a
                 JOIN doctors d ON d.id = a.doctor_id
                 JOIN departments dep ON dep.id = d.department_id
                 WHERE a.token = ?",
                [$token]
            );
            if (!$appt) Api::error('Appointment not found', 404);
            Api::success($appt);
        }

        // Admin list with filters
        Auth::requireRole('admin');
        $params = [];
        $where  = ['1=1'];

        if (!empty($_GET['doctor']))     { $where[] = 'a.doctor_id = ?';       $params[] = (int)$_GET['doctor']; }
        if (!empty($_GET['date']))       { $where[] = 'a.appointment_date = ?'; $params[] = $_GET['date']; }
        if (!empty($_GET['status']))     { $where[] = 'a.status = ?';           $params[] = $_GET['status']; }
        if (!empty($_GET['department'])) {
            $where[] = 'd.department_id = ?';
            $params[] = (int)$_GET['department'];
        }

        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $whereStr = implode(' AND ', $where);
        $total = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM appointments a JOIN doctors d ON d.id=a.doctor_id WHERE $whereStr",
            $params
        )['cnt'];

        $rows = $db->fetchAll(
            "SELECT a.*, d.name AS doctor_name, d.specialization,
                    dep.name AS department_name
             FROM appointments a
             JOIN doctors d ON d.id = a.doctor_id
             JOIN departments dep ON dep.id = d.department_id
             WHERE $whereStr
             ORDER BY a.appointment_date DESC, a.appointment_time DESC
             LIMIT $limit OFFSET $offset",
            $params
        );

        Api::paginated($rows, $total, $page, $limit);
        break;

    // ── POST ─ book new appointment ───────────────────────────
    case 'POST':
        $body = Api::body();
        $v = Validator::make($body, [
            'doctor_id'       => 'required|integer',
            'appointment_date'=> 'required',
            'appointment_time'=> 'required',
            'patient_name'    => 'required|min:2|max:120',
            'patient_email'   => 'required|email',
            'patient_phone'   => 'required|min:7|max:30',
            'patient_dob'     => 'required',
        ]);
        if ($v->fails()) Api::error('Validation failed', 422, $v->errors());

        $doctorId  = (int)$body['doctor_id'];
        $apptDate  = $body['appointment_date'];
        $apptTime  = $body['appointment_time'];
        $email     = strtolower(trim($body['patient_email']));

        // Validate date format and booking window
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $apptDate)) {
            Api::error('Invalid appointment date format', 400);
        }

        $dateObj = DateTime::createFromFormat('Y-m-d', $apptDate);
        $dateErrors = DateTime::getLastErrors();
        if (
            !$dateObj
            || ($dateErrors && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0))
            || $dateObj->format('Y-m-d') !== $apptDate
        ) {
            Api::error('Invalid appointment date', 400);
        }

        $today = new DateTimeImmutable('today');
        $maxDate = $today->modify('+180 days');
        $requestedDate = DateTimeImmutable::createFromFormat('Y-m-d', $apptDate);
        if (!$requestedDate || $requestedDate < $today || $requestedDate > $maxDate) {
            Api::error('Appointment date must be within the next 180 days', 400);
        }

        // Get slot
        $dateObj   = new DateTime($apptDate);
        $dayOfWeek = (int)$dateObj->format('w');
        $slot = $db->fetchOne(
            "SELECT * FROM slots WHERE doctor_id = ? AND day_of_week = ? AND start_time = ? AND active = 1",
            [$doctorId, $dayOfWeek, $apptTime]
        );
        if (!$slot) Api::error('No slot available for this doctor/date/time', 400);

        // Check capacity
        $booked = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM appointments
             WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
             AND status NOT IN ('cancelled','no_show')",
            [$doctorId, $apptDate, $apptTime]
        );
        if ((int)$booked['cnt'] >= (int)$slot['max_patients']) {
            Api::error('This slot is fully booked', 409);
        }

        // Check duplicate for same email
        $dup = $db->fetchOne(
            "SELECT id FROM appointments
             WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND patient_email = ?
             AND status NOT IN ('cancelled','no_show')",
            [$doctorId, $apptDate, $apptTime, $email]
        );
        if ($dup) Api::error('You already have a booking for this slot', 409);

        $token = bin2hex(random_bytes(8)); // 16-char hex

        $newId = $db->insert('appointments', [
            'token'           => $token,
            'doctor_id'       => $doctorId,
            'slot_id'         => $slot['id'],
            'patient_name'    => trim($body['patient_name']),
            'patient_email'   => $email,
            'patient_phone'   => trim($body['patient_phone']),
            'patient_dob'     => $body['patient_dob'],
            'reason'          => trim($body['reason'] ?? ''),
            'appointment_date'=> $apptDate,
            'appointment_time'=> $apptTime,
            'status'          => 'booked',
        ]);

        Api::success(['id' => $newId, 'token' => $token], 201);
        break;

    // ── PUT ─ update status ───────────────────────────────────
    case 'PUT':
        Auth::requireRole('admin');
        if (!$token && !$id) Api::error('Token or ID required', 400);
        $body = Api::body();

        $allowed = ['booked','confirmed','in_progress','completed','cancelled','no_show'];
        if (!in_array($body['status'] ?? '', $allowed)) Api::error('Invalid status', 400);

        $data  = ['status' => $body['status']];
        if (!empty($body['notes'])) $data['notes'] = $body['notes'];

        $where = $token ? "token = '$token'" : "id = $id";
        $db->update('appointments', $data, $where);
        Api::success(['updated' => true]);
        break;

    default:
        Api::error('Method not allowed', 405);
}