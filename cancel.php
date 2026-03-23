<?php
require_once '../../core/bootstrap.php';

$token   = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['token'] ?? '');
$success = false;
$error   = '';

if (!$token) { header('Location: appointment.php'); exit; }

$db   = Database::getInstance();
$appt = $db->fetchOne("SELECT a.*, d.name AS doctor_name FROM appointments a JOIN doctors d ON d.id = a.doctor_id WHERE a.token = ?", [$token]);
if (!$appt) { header('Location: appointment.php?token=' . $token); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    if (!in_array($appt['status'], ['booked','confirmed'])) {
        $error = 'This appointment cannot be cancelled (current status: ' . $appt['status'] . ').';
    } elseif (strtotime($appt['appointment_date'].' '.$appt['appointment_time']) < time() + 7200) {
        $error = 'Appointments can only be cancelled at least 2 hours before the scheduled time.';
    } else {
        $db->update('appointments', ['status' => 'cancelled'], 'token = ?', [$token]);
        $success = true;
    }
}

$dateFmt = date('l, F j, Y', strtotime($appt['appointment_date']));
$timeFmt = date('g:i A', strtotime($appt['appointment_time']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Cancel Appointment — MediBook</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<link rel="stylesheet" href="../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }
  .cancel-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 24px; }
</style>
</head>
<body>
<nav class="dc-nav">
  <div class="dc-nav__brand"><i class="dc-icon dc-icon-hospital dc-icon-md"></i> Medi<span>Book</span></div>
  <div class="dc-nav__links">
    <a href="appointment.php?token=<?= htmlspecialchars($token) ?>" class="dc-nav__link">
      <i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Back to Appointment
    </a>
  </div>
</nav>

<div class="cancel-wrap">
  <div style="width:100%;max-width:460px">

    <?php if ($success): ?>
      <div class="dc-card dc-text-center dc-animate-fade-up" style="padding:40px">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(34,211,160,0.12);border:2px solid var(--dc-success);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
          <i class="dc-icon dc-icon-check dc-icon-xl" style="color:var(--dc-success)"></i>
        </div>
        <h2 class="dc-h2 dc-mb-sm">Appointment Cancelled</h2>
        <p class="dc-body dc-mb-lg" style="color:var(--dc-text-2)">Your appointment has been successfully cancelled. We hope to see you again soon.</p>
        <a href="book.php" class="dc-btn dc-btn-primary dc-btn-full dc-mb-sm"
           style="background:var(--dc-accent);border-color:var(--dc-accent)">
          <i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Book a New Appointment
        </a>
        <a href="index.php" class="dc-btn dc-btn-ghost dc-btn-full">
          <i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Back to Home
        </a>
      </div>

    <?php else: ?>
      <div class="dc-card dc-animate-fade-up" style="padding:32px">
        <div style="text-align:center;margin-bottom:24px">
          <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,92,106,0.1);border:2px solid rgba(255,92,106,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <i class="dc-icon dc-icon-x dc-icon-lg" style="color:var(--dc-danger)"></i>
          </div>
          <h2 class="dc-h3">Cancel Appointment</h2>
        </div>

        <?php if ($error): ?>
        <div class="dc-card" style="background:rgba(255,92,106,0.08);border-color:rgba(255,92,106,0.3);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
          <i class="dc-icon dc-icon-alert-triangle dc-icon-sm" style="color:var(--dc-danger);flex-shrink:0"></i>
          <span style="color:var(--dc-danger);font-size:0.875rem"><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <div style="background:var(--dc-bg-3);border-radius:var(--dc-radius);padding:16px;margin-bottom:20px">
          <div style="font-weight:600;margin-bottom:6px;display:flex;align-items:center;gap:8px">
            <i class="dc-icon dc-icon-user dc-icon-sm" style="color:var(--dc-accent-2)"></i>
            <?= htmlspecialchars($appt['doctor_name']) ?>
          </div>
          <div class="dc-caption" style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
            <i class="dc-icon dc-icon-calendar dc-icon-xs" style="color:var(--dc-text-3)"></i>
            <?= htmlspecialchars($dateFmt) ?>
          </div>
          <div class="dc-caption" style="display:flex;align-items:center;gap:6px">
            <i class="dc-icon dc-icon-clock dc-icon-xs" style="color:var(--dc-text-3)"></i>
            <?= htmlspecialchars($timeFmt) ?>
          </div>
        </div>

        <?php if (!$error || in_array($appt['status'], ['booked','confirmed'])): ?>
        <div class="dc-card" style="background:rgba(245,166,35,0.08);border-color:rgba(245,166,35,0.2);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
          <i class="dc-icon dc-icon-alert-triangle dc-icon-sm" style="color:var(--dc-warning);flex-shrink:0"></i>
          <span class="dc-caption" style="color:var(--dc-warning)">This action cannot be undone.</span>
        </div>
        <form method="POST">
          <input type="hidden" name="confirm" value="1">
          <button type="submit" class="dc-btn dc-btn-danger dc-btn-full dc-mb-sm">
            <i class="dc-icon dc-icon-x dc-icon-sm"></i> Yes, Cancel My Appointment
          </button>
        </form>
        <?php endif; ?>

        <a href="appointment.php?token=<?= htmlspecialchars($token) ?>" class="dc-btn dc-btn-ghost dc-btn-full">
          <i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Keep My Appointment
        </a>
      </div>
    <?php endif; ?>

  </div>
</div>
<script src="../../core/ui/devcore.js"></script>
</body>
</html>