<?php
require_once __DIR__ . '/core/bootstrap.php';

$token = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['token'] ?? '');
$appt  = null;
if ($token) {
    $db   = Database::getInstance();
    $appt = $db->fetchOne(
        "SELECT a.*, d.name AS doctor_name, d.specialization, d.consultation_fee,
                dep.name AS department_name
         FROM appointments a
         JOIN doctors d ON d.id = a.doctor_id
         JOIN departments dep ON dep.id = d.department_id
         WHERE a.token = ?", [$token]
    );
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir      = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/appointment.php'), '/');
$qrUrl    = $appt ? QrCode::url($protocol.'://'.$host.$dir.'/appointment.php?token='.$token, 160) : null;

$statusOrder = ['booked'=>0,'confirmed'=>1,'in_progress'=>2,'completed'=>3,'cancelled'=>3,'no_show'=>3];
$badgeClass  = ['booked'=>'dc-badge-info','confirmed'=>'dc-badge-accent','in_progress'=>'dc-badge-warning','completed'=>'dc-badge-success','cancelled'=>'dc-badge-neutral','no_show'=>'dc-badge-danger'];
$badgeLabel  = ['booked'=>'Booked','confirmed'=>'Confirmed','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled','no_show'=>'No Show'];

if ($appt) {
    $status    = $appt['status'];
    $canCancel = in_array($status, ['booked','confirmed'])
                 && strtotime($appt['appointment_date'].' '.$appt['appointment_time']) > time() + 7200;
    $dateFmt   = date('l, F j, Y', strtotime($appt['appointment_date']));
    $timeFmt   = date('g:i A', strtotime($appt['appointment_time']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Appointment — MediBook</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<link rel="stylesheet" href="../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }

  .timeline { display:flex; align-items:flex-start; gap:0; position:relative; padding:8px 0; }
  .timeline::before { content:none; }
  .tl-step { flex:1; display:flex; flex-direction:column; align-items:center; gap:8px; position:relative; z-index:1; text-align:center; }
  .tl-step:not(:last-child)::after { content:''; position:absolute; top:20px; left:calc(50% + 22px); right:calc(-50% + 22px); height:2px; background:var(--dc-border); z-index:0; }
  .tl-dot {
    width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    border:2px solid var(--dc-border); background:var(--dc-bg-2); transition:all 0.3s;

  }
  .tl-dot .dc-icon { color:var(--dc-text-3); }
  .tl-dot.done    { background:var(--dc-success); border-color:var(--dc-success); }
  .tl-dot.done .dc-icon { color:#111; }
  .tl-dot.current { background:rgba(14,165,233,0.15); border-color:var(--dc-accent); box-shadow:0 0 16px rgba(14,165,233,0.25); }
  .tl-dot.current .dc-icon { color:var(--dc-accent-2); }
  .tl-dot.cancelled-step { background:rgba(255,92,106,0.15); border-color:var(--dc-danger); }
  .tl-dot.cancelled-step .dc-icon { color:var(--dc-danger); }
  .tl-label { font-size:0.8rem; font-weight:600; color:var(--dc-text-3); }
  .tl-label.done    { color:var(--dc-success); }
  .tl-label.current { color:var(--dc-accent-2); }
  .tl-label.cancelled-step { color:var(--dc-danger); }

  .detail-row { display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid var(--dc-border); gap:16px; }
  .detail-row:last-child { border-bottom:none; }
</style>
</head>
<body>

<nav class="dc-nav">
  <div class="dc-nav__brand">
    <i class="dc-icon dc-icon-hospital dc-icon-md"></i>
    Medi<span>Book</span>
  </div>
  <div class="dc-nav__links">
    <a href="index.php" class="dc-nav__link">Home</a>
    <a href="book.php" class="dc-btn dc-btn-primary dc-btn-sm"
       style="background:var(--dc-accent);border-color:var(--dc-accent)">
      <i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Book Appointment
    </a>
  </div>
</nav>

<div style="max-width:700px;margin:0 auto;padding:40px 24px 60px">

  <div style="margin-bottom:28px">
    <h1 class="dc-h2" style="margin-bottom:6px">
      <i class="dc-icon dc-icon-clipboard dc-icon-md" style="vertical-align:middle;color:var(--dc-accent-2);margin-right:8px"></i>
      My Appointment
    </h1>
    <p class="dc-body" style="color:var(--dc-text-2)">Enter your appointment token to view status and details.</p>
  </div>

  <!-- Token lookup -->
  <div class="dc-card-solid dc-mb-lg">
    <div class="dc-form-group dc-mb-sm">
      <label class="dc-label-field">Appointment Token</label>
      <div class="dc-flex dc-gap-sm">
        <input type="text" class="dc-input" id="token-input"
               value="<?= htmlspecialchars($token) ?>"
               placeholder="Enter your 16-character token"
               maxlength="16"
               style="font-family:monospace;letter-spacing:0.08em">
        <button class="dc-btn dc-btn-primary" onclick="lookupToken()"
                style="background:var(--dc-accent);border-color:var(--dc-accent);white-space:nowrap">
          <i class="dc-icon dc-icon-search dc-icon-sm"></i> Find
        </button>
      </div>
      <span class="dc-caption" style="color:var(--dc-text-3)">Your token was shown on the booking confirmation page.</span>
    </div>
  </div>

  <?php if ($appt): ?>

    <!-- Status timeline -->
    <div class="dc-card-solid dc-mb-lg">
      <div class="dc-label dc-mb">Appointment Status</div>
      <div class="timeline">
        <?php
        $isTerminal = in_array($status, ['cancelled','no_show']);
        $steps = $isTerminal
          ? [['booked','calendar','Booked'],['confirmed','check','Confirmed'],[$status,$status==='cancelled'?'x':'alert-triangle',ucfirst($status)]]
          : [['booked','calendar','Booked'],['confirmed','check','Confirmed'],['in_progress','clock','In Progress'],['completed','trophy','Completed']];
        foreach ($steps as [$sKey,$sIcon,$sLabel]):
          $sOrd = $statusOrder[$sKey] ?? 99;
          $cOrd = $statusOrder[$status] ?? 0;
          if ($isTerminal && $sKey === $status) $cls = 'cancelled-step';
          elseif ($sOrd < $cOrd) $cls = 'done';
          elseif ($sOrd === $cOrd) $cls = 'current';
          else $cls = '';
        ?>
        <div class="tl-step">
          <div class="tl-dot <?= $cls ?>">
            <?php if ($cls === 'done'): ?>
              <i class="dc-icon dc-icon-check dc-icon-sm"></i>
            <?php else: ?>
              <i class="dc-icon dc-icon-<?= $sIcon ?> dc-icon-sm"></i>
            <?php endif; ?>
          </div>
          <div class="tl-label <?= $cls ?>"><?= htmlspecialchars($sLabel) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="dc-text-center dc-mt">
        <span class="dc-badge <?= $badgeClass[$status] ?? 'dc-badge-neutral' ?>" style="font-size:0.875rem;padding:6px 14px">
          <?= $badgeLabel[$status] ?? ucfirst($status) ?>
        </span>
      </div>
    </div>

    <!-- Two-column layout -->
    <div class="dc-grid dc-grid-2 dc-gap-lg" style="align-items:start">

      <!-- Details -->
      <div class="dc-card-solid">
        <div class="dc-label dc-mb">Appointment Details</div>
        <div class="detail-row">
          <span class="dc-caption" style="color:var(--dc-text-3)">Patient</span>
          <strong><?= htmlspecialchars($appt['patient_name']) ?></strong>
        </div>
        <div class="detail-row">
          <span class="dc-caption" style="color:var(--dc-text-3)">Doctor</span>
          <span><?= htmlspecialchars($appt['doctor_name']) ?></span>
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
          <span class="dc-caption" style="color:var(--dc-text-3)">Fee</span>
          <span>$<?= number_format($appt['consultation_fee'], 2) ?></span>
        </div>
        <?php if ($appt['notes']): ?>
        <div class="detail-row">
          <span class="dc-caption" style="color:var(--dc-text-3)">Clinic Notes</span>
          <span style="color:var(--dc-warning);max-width:60%;text-align:right"><?= htmlspecialchars($appt['notes']) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- QR + actions -->
      <div>
        <div class="dc-card-solid dc-mb">
          <div class="dc-label dc-mb" style="text-align:center">QR Appointment Card</div>
          <div class="dc-qr-card" style="margin:0 auto;display:inline-flex;width:100%;justify-content:center">
            <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Code" width="160" height="160" style="border-radius:8px">
          </div>
          <p class="dc-caption dc-mt-sm" style="text-align:center;color:var(--dc-text-3)">Show at reception desk</p>
        </div>

        <div class="dc-card-solid">
          <div class="dc-label dc-mb">Actions</div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <a href="confirmation.php?token=<?= htmlspecialchars($token) ?>" class="dc-btn dc-btn-ghost dc-btn-full">
              <i class="dc-icon dc-icon-printer dc-icon-sm"></i> Print Appointment Card
            </a>
            <?php if ($canCancel): ?>
            <button class="dc-btn dc-btn-danger dc-btn-full" onclick="Modal.open('modal-cancel')">
              <i class="dc-icon dc-icon-x dc-icon-sm"></i> Cancel Appointment
            </button>
            <?php elseif (in_array($status, ['booked','confirmed'])): ?>
            <div class="dc-badge dc-badge-warning" style="justify-content:center;padding:10px;border-radius:var(--dc-radius)">
              <i class="dc-icon dc-icon-clock dc-icon-sm"></i> Cannot cancel — less than 2h before
            </div>
            <?php endif; ?>
            <a href="book.php" class="dc-btn dc-btn-ghost dc-btn-full">
              <i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Book Another Appointment
            </a>
          </div>
        </div>
      </div>

    </div>

  <?php elseif ($token): ?>
    <div class="dc-card dc-text-center" style="padding:48px">
      <div class="dc-empty">
        <i class="dc-icon dc-icon-search dc-icon-2xl dc-empty__icon"></i>
        <div class="dc-empty__title">Appointment Not Found</div>
        <p class="dc-empty__text">No appointment found for token <span class="dc-mono"><?= htmlspecialchars($token) ?></span>. Please check the token and try again.</p>
        <a href="book.php" class="dc-btn dc-btn-primary dc-btn-sm dc-mt"
           style="background:var(--dc-accent);border-color:var(--dc-accent)">Book a New Appointment</a>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php if ($appt && $canCancel): ?>
<!-- Cancel Modal -->
<div class="dc-modal-overlay" id="modal-cancel">
  <div class="dc-modal" style="max-width:420px">
    <div class="dc-modal__header">
      <h3 class="dc-h3">Cancel Appointment</h3>
      <button class="dc-modal__close" data-modal-close="modal-cancel">
        <i class="dc-icon dc-icon-x dc-icon-sm"></i>
      </button>
    </div>
    <p class="dc-body dc-mb">Are you sure you want to cancel your appointment with <strong><?= htmlspecialchars($appt['doctor_name']) ?></strong> on <strong><?= htmlspecialchars($dateFmt) ?></strong> at <strong><?= htmlspecialchars($timeFmt) ?></strong>?</p>
    <div class="dc-card" style="background:rgba(245,166,35,0.08);border-color:rgba(245,166,35,0.2);padding:12px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
      <i class="dc-icon dc-icon-alert-triangle dc-icon-sm" style="color:var(--dc-warning);flex-shrink:0"></i>
      <span class="dc-caption" style="color:var(--dc-warning)">This action cannot be undone.</span>
    </div>
    <div class="dc-flex dc-gap-sm">
      <button class="dc-btn dc-btn-danger dc-btn-full" id="btn-confirm-cancel">
        <i class="dc-icon dc-icon-x dc-icon-sm"></i> Yes, Cancel Appointment
      </button>
      <button class="dc-btn dc-btn-ghost" data-modal-close="modal-cancel">Keep It</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="../../core/ui/devcore.js"></script>
<script>
function lookupToken() {
  const t = document.getElementById('token-input').value.trim().toLowerCase();
  if (!t) { Toast.warning('Please enter an appointment token'); return; }
  window.location.href = 'appointment.php?token=' + encodeURIComponent(t);
}
document.getElementById('token-input')?.addEventListener('keydown', e => { if (e.key === 'Enter') lookupToken(); });

<?php if ($appt && $canCancel): ?>
document.getElementById('btn-confirm-cancel')?.addEventListener('click', async () => {
  const btn = document.getElementById('btn-confirm-cancel');
  DCForm.setLoading(btn, true);
  try {
    const apiBase = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/appointment.php'), '/') ?>/api';
    await DC.put(`${apiBase}/appointments.php?token=<?= htmlspecialchars($token) ?>`, { action:'cancel' });
    Modal.close('modal-cancel');
    Toast.success('Appointment cancelled successfully.');
    setTimeout(() => location.reload(), 1500);
  } catch(e) {
    Toast.error(e.message || 'Cancellation failed.');
    DCForm.setLoading(btn, false);
  }
});
<?php endif; ?>
</script>
</body>
</html>