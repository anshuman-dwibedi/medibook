<?php
require_once '../../core/bootstrap.php';

$token = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['token'] ?? '');
if (!$token) { header('Location: index.php'); exit; }

$db   = Database::getInstance();
$appt = $db->fetchOne(
    "SELECT a.*, d.name AS doctor_name, d.specialization, d.consultation_fee,
            dep.name AS department_name
     FROM appointments a
     JOIN doctors d ON d.id = a.doctor_id
     JOIN departments dep ON dep.id = d.department_id
     WHERE a.token = ?", [$token]
);
if (!$appt) { header('Location: index.php'); exit; }

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir      = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/confirmation.php'), '/');
$qrData   = $protocol . '://' . $host . $dir . '/appointment.php?token=' . $token;
$qrUrl    = QrCode::url($qrData, 200);

$dateFmt = date('l, F j, Y', strtotime($appt['appointment_date']));
$timeFmt = date('g:i A', strtotime($appt['appointment_time']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Appointment Confirmed — MediBook</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<link rel="stylesheet" href="../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }

  .check-circle {
    width:80px; height:80px; border-radius:50%;
    background:rgba(34,211,160,0.12); border:3px solid var(--dc-success);
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 24px;
    animation:checkPop 0.6s var(--dc-ease) both;
  }
  .check-circle .dc-icon { color:var(--dc-success); }
  @keyframes checkPop {
    from { transform:scale(0) rotate(-45deg); opacity:0; }
    80%  { transform:scale(1.15); }
    to   { transform:scale(1) rotate(0); opacity:1; }
  }

  .appt-card { background:var(--dc-bg-2); border:1px solid var(--dc-border-2); border-radius:var(--dc-radius-xl); overflow:hidden; max-width:620px; margin:0 auto; }
  .appt-header { background:linear-gradient(135deg,rgba(14,165,233,0.12),rgba(14,165,233,0.02)); padding:22px 28px; border-bottom:1px solid var(--dc-border); }
  .appt-body   { padding:22px 28px; }
  .detail-row  { display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid var(--dc-border); gap:16px; }
  .detail-row:last-child { border-bottom:none; }

  .qr-medical-card {
    background:#fff; color:#111; border-radius:var(--dc-radius-xl);
    padding:24px; max-width:360px; margin:0 auto;
    border:1px solid rgba(0,0,0,0.08);
    box-shadow:0 8px 32px rgba(0,0,0,0.15);
  }
  .qr-card-header { display:flex; align-items:center; gap:10px; margin-bottom:18px; padding-bottom:14px; border-bottom:2px solid rgba(14,165,233,0.2); }
  .qr-logo-icon { width:36px; height:36px; background:#eff6ff; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .qr-logo-icon .dc-icon { color:#0ea5e9; }
  .qr-clinic-name { font-family:'Syne',sans-serif; font-size:1rem; font-weight:800; color:#0ea5e9; }
  .qr-card-body { display:flex; gap:18px; align-items:flex-start; }
  .qr-info { flex:1; }
  .qr-field { margin-bottom:9px; }
  .qr-field-label { font-size:0.62rem; text-transform:uppercase; letter-spacing:0.08em; color:#666; font-weight:700; }
  .qr-field-val { font-size:0.875rem; font-weight:600; color:#111; margin-top:2px; }
  .qr-token { font-family:monospace; font-size:0.72rem; background:#f0f9ff; padding:6px 10px; border-radius:6px; color:#0369a1; margin-top:12px; text-align:center; border:1px solid #bae6fd; letter-spacing:0.04em; }
  .qr-footer { margin-top:14px; padding-top:10px; border-top:1px solid #f0f0f0; font-size:0.68rem; color:#888; text-align:center; }

  .next-step-row { display:flex; align-items:flex-start; gap:14px; padding:12px 0; border-bottom:1px solid var(--dc-border); }
  .next-step-row:last-child { border-bottom:none; }
  .next-icon { width:34px; height:34px; border-radius:50%; background:var(--dc-accent-glow); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .next-icon .dc-icon { color:var(--dc-accent-2); }

  @media print {
    .no-print { display:none !important; }
    nav, footer { display:none; }
    body { background:white; }
    .qr-medical-card { box-shadow:none; border:2px solid #ddd; }
  }
</style>
</head>
<body>

<nav class="dc-nav no-print">
  <div class="dc-nav__brand">
    <i class="dc-icon dc-icon-hospital dc-icon-md"></i>
    Medi<span>Book</span>
  </div>
  <div class="dc-nav__links">
    <a href="appointment.php?token=<?= htmlspecialchars($token) ?>" class="dc-nav__link">
      <i class="dc-icon dc-icon-clipboard dc-icon-sm"></i> View Appointment
    </a>
    <a href="book.php" class="dc-btn dc-btn-ghost dc-btn-sm">Book Another</a>
  </div>
</nav>

<div style="max-width:680px;margin:0 auto;padding:48px 24px 60px">

  <!-- Success header -->
  <div class="dc-text-center dc-mb-lg dc-animate-fade-up">
    <div class="check-circle">
      <i class="dc-icon dc-icon-check dc-icon-xl"></i>
    </div>
    <h1 class="dc-h2" style="margin-bottom:8px">Appointment Confirmed!</h1>
    <p class="dc-body" style="color:var(--dc-success)">Your appointment has been successfully booked. You are all set!</p>
  </div>

  <!-- Appointment summary card -->
  <div class="appt-card dc-mb-lg dc-animate-fade-up" style="animation-delay:0.08s">
    <div class="appt-header">
      <div class="dc-flex-between">
        <div>
          <div class="dc-label dc-mb-sm">Appointment Summary</div>
          <div class="dc-h3"><?= htmlspecialchars($appt['patient_name']) ?></div>
        </div>
        <span class="dc-badge dc-badge-info">Booked</span>
      </div>
    </div>
    <div class="appt-body">
      <div class="detail-row">
        <span class="dc-caption" style="color:var(--dc-text-3)">Doctor</span>
        <strong><?= htmlspecialchars($appt['doctor_name']) ?></strong>
      </div>
      <div class="detail-row">
        <span class="dc-caption" style="color:var(--dc-text-3)">Specialization</span>
        <span><?= htmlspecialchars($appt['specialization']) ?></span>
      </div>
      <div class="detail-row">
        <span class="dc-caption" style="color:var(--dc-text-3)">Department</span>
        <span><?= htmlspecialchars($appt['department_name']) ?></span>
      </div>
      <div class="detail-row">
        <span class="dc-caption" style="color:var(--dc-text-3)">Date</span>
        <strong><?= htmlspecialchars($dateFmt) ?></strong>
      </div>
      <div class="detail-row">
        <span class="dc-caption" style="color:var(--dc-text-3)">Time</span>
        <strong style="color:var(--dc-accent-2)"><?= htmlspecialchars($timeFmt) ?></strong>
      </div>
      <div class="detail-row">
        <span class="dc-caption" style="color:var(--dc-text-3)">Consultation Fee</span>
        <span>$<?= number_format($appt['consultation_fee'], 2) ?></span>
      </div>
      <div class="detail-row">
        <span class="dc-caption" style="color:var(--dc-text-3)">Appointment Token</span>
        <span class="dc-mono" style="font-size:0.8rem;letter-spacing:0.05em"><?= htmlspecialchars($token) ?></span>
      </div>

      <!-- Reminder notice -->
      <div style="background:rgba(245,166,35,0.08);border:1px solid rgba(245,166,35,0.2);border-radius:var(--dc-radius);padding:12px 16px;margin-top:8px;display:flex;align-items:flex-start;gap:10px">
        <i class="dc-icon dc-icon-clock dc-icon-sm" style="color:var(--dc-warning);flex-shrink:0;margin-top:2px"></i>
        <div>
          <div style="font-weight:600;color:var(--dc-warning);font-size:0.875rem;margin-bottom:3px">Please arrive 10 minutes early</div>
          <p class="dc-caption" style="color:var(--dc-text-2)">Bring your QR card or appointment token for check-in at the reception desk.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- QR Card section -->
  <div class="dc-text-center dc-mb-lg dc-animate-fade-up" style="animation-delay:0.16s">
    <div class="dc-h3 dc-mb-sm">
      <i class="dc-icon dc-icon-qr-code dc-icon-md" style="vertical-align:middle;color:var(--dc-accent-2);margin-right:6px"></i>
      Your QR Appointment Card
    </div>
    <p class="dc-body dc-mb-lg" style="color:var(--dc-text-2)">Screenshot, print or show this card at the reception desk for instant check-in.</p>

    <div class="qr-medical-card" id="printable-card">
      <div class="qr-card-header">
        <div class="qr-logo-icon">
          <i class="dc-icon dc-icon-hospital dc-icon-md"></i>
        </div>
        <div>
          <div class="qr-clinic-name">MediBook Clinic</div>
          <div style="font-size:0.68rem;color:#666">Medical Appointment Card</div>
        </div>
      </div>
      <div class="qr-card-body">
        <div class="qr-info">
          <div class="qr-field">
            <div class="qr-field-label">Patient</div>
            <div class="qr-field-val"><?= htmlspecialchars($appt['patient_name']) ?></div>
          </div>
          <div class="qr-field">
            <div class="qr-field-label">Doctor</div>
            <div class="qr-field-val"><?= htmlspecialchars($appt['doctor_name']) ?></div>
          </div>
          <div class="qr-field">
            <div class="qr-field-label">Department</div>
            <div class="qr-field-val"><?= htmlspecialchars($appt['department_name']) ?></div>
          </div>
          <div class="qr-field">
            <div class="qr-field-label">Date</div>
            <div class="qr-field-val"><?= date('M j, Y', strtotime($appt['appointment_date'])) ?></div>
          </div>
          <div class="qr-field">
            <div class="qr-field-label">Time</div>
            <div class="qr-field-val" style="color:#0ea5e9"><?= htmlspecialchars($timeFmt) ?></div>
          </div>
        </div>
        <div class="dc-qr-card" style="padding:6px;background:transparent">
          <img src="<?= htmlspecialchars($qrUrl) ?>" alt="Appointment QR" width="120" height="120" style="border-radius:8px;display:block">
        </div>
      </div>
      <div class="qr-token"><?= htmlspecialchars($token) ?></div>
      <div class="qr-footer">Scan QR or enter token at reception &middot; MediBook Clinic</div>
    </div>

    <div class="dc-flex dc-gap-sm dc-mt-lg no-print" style="justify-content:center;flex-wrap:wrap">
      <button class="dc-btn dc-btn-primary" onclick="printCard()"
              style="background:var(--dc-accent);border-color:var(--dc-accent)">
        <i class="dc-icon dc-icon-printer dc-icon-sm"></i> Print Appointment Card
      </button>
      <a href="appointment.php?token=<?= htmlspecialchars($token) ?>" class="dc-btn dc-btn-ghost">
        <i class="dc-icon dc-icon-eye dc-icon-sm"></i> View Full Details
      </a>
      <a href="book.php" class="dc-btn dc-btn-ghost">
        <i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Book Another
      </a>
    </div>
  </div>

  <!-- What's next -->
  <div class="dc-card dc-animate-fade-up" style="animation-delay:0.24s">
    <div class="dc-h4 dc-mb">What Happens Next</div>
    <div class="next-step-row">
      <div class="next-icon"><i class="dc-icon dc-icon-note dc-icon-sm"></i></div>
      <div>
        <div style="font-weight:600;font-size:0.875rem;margin-bottom:3px">Keep your token safe</div>
        <p class="dc-caption" style="color:var(--dc-text-3)">Use token <span class="dc-mono" style="font-size:0.78rem"><?= htmlspecialchars($token) ?></span> to view or cancel your appointment at any time.</p>
      </div>
    </div>
    <div class="next-step-row">
      <div class="next-icon"><i class="dc-icon dc-icon-hospital dc-icon-sm"></i></div>
      <div>
        <div style="font-weight:600;font-size:0.875rem;margin-bottom:3px">Arrive 10 minutes early</div>
        <p class="dc-caption" style="color:var(--dc-text-3)">Check in at reception before your scheduled time.</p>
      </div>
    </div>
    <div class="next-step-row">
      <div class="next-icon"><i class="dc-icon dc-icon-qr-code dc-icon-sm"></i></div>
      <div>
        <div style="font-weight:600;font-size:0.875rem;margin-bottom:3px">Show your QR code</div>
        <p class="dc-caption" style="color:var(--dc-text-3)">Present the QR card above at the reception desk for instant check-in.</p>
      </div>
    </div>
    <div class="next-step-row">
      <div class="next-icon"><i class="dc-icon dc-icon-x dc-icon-sm"></i></div>
      <div>
        <div style="font-weight:600;font-size:0.875rem;margin-bottom:3px">Need to cancel?</div>
        <p class="dc-caption" style="color:var(--dc-text-3)">You can cancel up to 2 hours before via <a href="appointment.php?token=<?= htmlspecialchars($token) ?>" style="color:var(--dc-accent-2)">your appointment page</a>.</p>
      </div>
    </div>
  </div>

</div>

<footer class="no-print" style="padding:24px 0;border-top:1px solid var(--dc-border);text-align:center">
  <div class="dc-caption" style="color:var(--dc-text-3)">MediBook Clinic &middot; DevCore Portfolio Suite</div>
</footer>

<script src="../../core/ui/devcore.js"></script>
<script>
function printCard() {
  const card = document.getElementById('printable-card').outerHTML;
  const w = window.open('','_blank','width=480,height=680');
  w.document.write(`<!DOCTYPE html><html><head>
    <title>MediBook Appointment Card</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
      *{box-sizing:border-box;margin:0;padding:0}
      @page{size:auto;margin:0}
      html,body{font-family:'DM Sans',sans-serif;background:#fff;padding:0;margin:0;width:100%;height:auto}
      .qr-medical-card{background:#fff;color:#111;padding:24px;width:100%;border:none;box-shadow:none}
      .qr-card-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid rgba(14,165,233,0.2)}
      .qr-logo-icon{width:36px;height:36px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center}
      .qr-clinic-name{font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;color:#0ea5e9}
      .qr-card-body{display:flex;gap:18px;align-items:flex-start}
      .qr-info{flex:1}
      .qr-field{margin-bottom:9px}
      .qr-field-label{font-size:0.62rem;text-transform:uppercase;letter-spacing:0.08em;color:#666;font-weight:700}
      .qr-field-val{font-size:0.875rem;font-weight:600;color:#111;margin-top:2px}
      .dc-qr-card{padding:6px;background:transparent;display:inline-flex;flex-direction:column}
      .dc-qr-card img{border-radius:8px;display:block}
      .qr-token{font-family:monospace;font-size:0.72rem;background:#f0f9ff;padding:6px 10px;border-radius:6px;color:#0369a1;margin-top:12px;text-align:center;border:1px solid #bae6fd;letter-spacing:0.04em}
      .qr-footer{margin-top:14px;padding-top:10px;border-top:1px solid #f0f0f0;font-size:0.68rem;color:#888;text-align:center}
      .dc-icon{display:inline-block;width:18px;height:18px;background:#0ea5e9;-webkit-mask-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Crect x='3' y='3' width='7' height='7'/%3E%3Crect x='14' y='3' width='7' height='7'/%3E%3Crect x='14' y='14' width='7' height='7'/%3E%3Crect x='3' y='14' width='7' height='7'/%3E%3C/svg%3E");mask-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Crect x='3' y='3' width='7' height='7'/%3E%3Crect x='14' y='3' width='7' height='7'/%3E%3Crect x='14' y='14' width='7' height='7'/%3E%3Crect x='3' y='14' width='7' height='7'/%3E%3C/svg%3E");-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-size:contain;mask-size:contain}
    </style>
  </head><body>${card}</body></html>`);
  w.document.close();
  setTimeout(() => w.print(), 500);
}
</script>
</body>
</html>