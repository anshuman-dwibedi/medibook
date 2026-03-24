<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';


$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ── GET ──────────────────────────────────────────────────
    case 'GET':
        if ($id) {
            $doctor = $db->fetchOne(
                "SELECT d.*, dep.name AS department_name, dep.icon AS department_icon
                 FROM doctors d
                 JOIN departments dep ON dep.id = d.department_id
                 WHERE d.id = ? AND d.active = 1",
                [$id]
            );
            if (!$doctor) Api::error('Doctor not found', 404);
            // Fetch weekly slots
            $doctor['slots'] = $db->fetchAll(
                "SELECT * FROM slots WHERE doctor_id = ? AND active = 1 ORDER BY day_of_week, start_time",
                [$id]
            );
            Api::success($doctor);
        }

        $deptFilter = isset($_GET['department']) ? (int)$_GET['department'] : null;
        $sql  = "SELECT d.*, dep.name AS department_name, dep.icon AS department_icon
                 FROM doctors d
                 JOIN departments dep ON dep.id = d.department_id
                 WHERE d.active = 1";
        $params = [];
        if ($deptFilter) { $sql .= " AND d.department_id = ?"; $params[] = $deptFilter; }
        $sql .= " ORDER BY dep.id, d.name";
        Api::success($db->fetchAll($sql, $params));
        break;

    // ── POST ─────────────────────────────────────────────────
    case 'POST':
        Auth::requireRole('admin');
        $body = Api::body();
        $v = Validator::make($body, [
            'department_id'   => 'required|integer',
            'name'            => 'required|min:2|max:120',
            'specialization'  => 'required|min:2|max:120',
            'experience_years'=> 'required|integer',
            'consultation_fee'=> 'required|numeric',
        ]);
        if ($v->fails()) Api::error('Validation failed', 422, $v->errors());

        $photoUrl = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photoUrl = Storage::uploadFile($_FILES['photo'], 'doctors');
        }

        $newId = $db->insert('doctors', [
            'department_id'   => (int)$body['department_id'],
            'name'            => trim($body['name']),
            'specialization'  => trim($body['specialization']),
            'bio'             => $body['bio'] ?? '',
            'photo_url'       => $photoUrl,
            'experience_years'=> (int)$body['experience_years'],
            'consultation_fee'=> (float)$body['consultation_fee'],
            'active'          => 1,
        ]);
        Api::success(['id' => $newId], 201);
        break;

    // ── PUT ──────────────────────────────────────────────────
    case 'PUT':
        Auth::requireRole('admin');
        if (!$id) Api::error('Doctor ID required', 400);
        $body = Api::body();

        $photoUrl = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photoUrl = Storage::uploadFile($_FILES['photo'], 'doctors');
        }

        $data = array_filter([
            'department_id'   => isset($body['department_id'])   ? (int)$body['department_id']   : null,
            'name'            => isset($body['name'])            ? trim($body['name'])            : null,
            'specialization'  => isset($body['specialization'])  ? trim($body['specialization'])  : null,
            'bio'             => $body['bio']             ?? null,
            'experience_years'=> isset($body['experience_years'])? (int)$body['experience_years'] : null,
            'consultation_fee'=> isset($body['consultation_fee'])? (float)$body['consultation_fee']: null,
            'active'          => isset($body['active'])          ? (int)$body['active']           : null,
            'photo_url'       => $photoUrl,
        ], fn($v) => $v !== null);

        if (empty($data)) Api::error('No fields to update', 400);
        $db->update('doctors', $data, "id = $id");
        Api::success(['updated' => true]);
        break;

    // ── DELETE ───────────────────────────────────────────────
    case 'DELETE':
        Auth::requireRole('admin');
        if (!$id) Api::error('Doctor ID required', 400);
        $db->update('doctors', ['active' => 0], "id = $id");
        Api::success(['deleted' => true]);
        break;

    default:
        Api::error('Method not allowed', 405);
}