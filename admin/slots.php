<?php
require_once dirname(__DIR__, 3) . '/core/bootstrap.php';
Auth::requireRole('admin', 'login.php');
$db      = Database::getInstance();
$doctors = $db->fetchAll("SELECT id, name FROM doctors WHERE active = 1 ORDER BY name");
$apiBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/slots.php')), '/') . '/api';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Time Slots — MediBook Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<link rel="stylesheet" href="../../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }
  .slot-grid { display:grid; grid-template-columns:100px repeat(5,1fr); gap:8px; }
  .slot-header { text-align:center; padding:10px 6px; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--dc-text-3); background:var(--dc-bg-3); border-radius:var(--dc-radius); }
  .slot-time-label { display:flex; align-items:center; justify-content:flex-end; padding-right:10px; font-size:0.8rem; font-weight:600; color:var(--dc-text-2); }
  .slot-cell {
    border-radius:var(--dc-radius); padding:10px 8px; text-align:center; cursor:pointer;
    border:2px solid transparent; transition:all var(--dc-t-fast); min-height:64px;
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px;
  }
  .slot-cell.active   { background:rgba(34,211,160,0.1); border-color:rgba(34,211,160,0.3); color:var(--dc-success); }
  .slot-cell.inactive { background:var(--dc-bg-3); border-color:var(--dc-border); color:var(--dc-text-3); }
  .slot-cell.active:hover   { background:rgba(34,211,160,0.18); border-color:var(--dc-success); }
  .slot-cell.inactive:hover { background:var(--dc-bg-glass); border-color:var(--dc-border-2); color:var(--dc-text-2); }
  .slot-cell .slot-time { font-size:0.8rem; font-weight:700; }
  .slot-cell .slot-cap  { font-size:0.68rem; opacity:0.7; }
  .slot-cell .dc-icon   { width:14px; height:14px; }
  .slot-add { background:var(--dc-bg-glass); border:2px dashed var(--dc-border); color:var(--dc-text-3); }
  .slot-add:hover { border-color:var(--dc-accent); color:var(--dc-accent-2); background:rgba(14,165,233,0.05); }
</style>
</head>
<body>

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo"><i class="dc-icon dc-icon-hospital dc-icon-md"></i> Medi<span>Book</span></div>
  <div class="dc-sidebar__section">Main</div>
  <a href="dashboard.php"    class="dc-sidebar__link"><i class="dc-icon dc-icon-bar-chart dc-icon-sm"></i> Dashboard</a>
  <a href="appointments.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Appointments</a>
  <a href="doctors.php"      class="dc-sidebar__link"><i class="dc-icon dc-icon-stethoscope dc-icon-sm"></i> Doctors</a>
  <a href="slots.php"        class="dc-sidebar__link active"><i class="dc-icon dc-icon-clock dc-icon-sm"></i> Time Slots</a>
  <a href="qr-scanner.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-qr-code dc-icon-sm"></i> QR Scanner</a>
  <div class="dc-sidebar__section" style="margin-top:auto">Account</div>
  <a href="../index.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-globe dc-icon-sm"></i> View Site</a>
  <a href="logout.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<div class="dc-with-sidebar">
  <nav class="dc-nav">
    <div class="dc-nav__brand" style="font-size:1rem;font-weight:600">Time Slots</div>
    <button class="dc-btn dc-btn-primary dc-btn-sm" data-modal-open="modal-add-slot"
            style="background:var(--dc-accent);border-color:var(--dc-accent)">
      <i class="dc-icon dc-icon-plus dc-icon-sm"></i> Add Slot
    </button>
  </nav>

  <div class="dc-container dc-section">
    <div class="dc-flex-between dc-mb-lg">
      <div>
        <h1 class="dc-h2">Weekly Time Slots</h1>
        <p class="dc-body dc-text-muted">Click a cell to toggle active / inactive. Green = accepting bookings.</p>
      </div>
    </div>

    <div class="dc-card-solid dc-mb-lg">
      <div class="dc-form-group" style="max-width:300px">
        <label class="dc-label-field">Select Doctor</label>
        <select class="dc-select" id="sel-doctor">
          <option value="">— Choose a doctor —</option>
          <?php foreach ($doctors as $d): ?>
          <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="dc-card-solid" id="grid-wrap">
      <div class="dc-empty dc-empty-md">
        <i class="dc-icon dc-icon-clock dc-icon-2xl dc-empty__icon"></i>
        <div class="dc-empty__title" style="margin-top:10px">Select a Doctor</div>
        <p class="dc-empty__text">Choose a doctor above to view and edit their weekly schedule.</p>
      </div>
    </div>

    <!-- Legend -->
    <div class="dc-flex dc-gap-lg dc-mt dc-items-center">
      <div class="dc-flex dc-gap-sm dc-items-center">
        <div style="width:14px;height:14px;border-radius:3px;background:rgba(34,211,160,0.18);border:2px solid rgba(34,211,160,0.4)"></div>
        <span class="dc-caption">Active — accepting bookings</span>
      </div>
      <div class="dc-flex dc-gap-sm dc-items-center">
        <div style="width:14px;height:14px;border-radius:3px;background:var(--dc-bg-3);border:2px solid var(--dc-border)"></div>
        <span class="dc-caption">Inactive — not bookable</span>
      </div>
      <div class="dc-flex dc-gap-sm dc-items-center">
        <div style="width:14px;height:14px;border-radius:3px;background:var(--dc-bg-glass);border:2px dashed var(--dc-border)"></div>
        <span class="dc-caption">Click + to add slot</span>
      </div>
    </div>
  </div>
</div>

<!-- Add Slot Modal -->
<div class="dc-modal-overlay" id="modal-add-slot">
  <div class="dc-modal" style="max-width:400px">
    <div class="dc-modal__header">
      <h3 class="dc-h3">Add Time Slot</h3>
      <button class="dc-modal__close" data-modal-close="modal-add-slot"><i class="dc-icon dc-icon-x dc-icon-sm"></i></button>
    </div>
    <div class="dc-form-group dc-mb">
      <label class="dc-label-field">Doctor</label>
      <select class="dc-select" id="add-slot-doctor">
        <option value="">Select doctor</option>
        <?php foreach ($doctors as $d): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="dc-form-group dc-mb">
      <label class="dc-label-field">Day of Week</label>
      <select class="dc-select" id="add-slot-day">
        <option value="1">Monday</option>
        <option value="2">Tuesday</option>
        <option value="3">Wednesday</option>
        <option value="4">Thursday</option>
        <option value="5">Friday</option>
        <option value="6">Saturday</option>
        <option value="0">Sunday</option>
      </select>
    </div>
    <div class="dc-grid dc-grid-2" style="gap:12px;margin-bottom:12px">
      <div class="dc-form-group">
        <label class="dc-label-field">Start Time</label>
        <input type="time" class="dc-input" id="add-slot-start" value="09:00">
      </div>
      <div class="dc-form-group">
        <label class="dc-label-field">End Time</label>
        <input type="time" class="dc-input" id="add-slot-end" value="10:00">
      </div>
    </div>
    <div class="dc-form-group dc-mb-lg">
      <label class="dc-label-field">Max Patients per Slot</label>
      <input type="number" class="dc-input" id="add-slot-max" value="3" min="1" max="20">
    </div>
    <div class="dc-flex dc-gap-sm">
      <button class="dc-btn dc-btn-primary dc-btn-full" id="btn-add-slot"
              style="background:var(--dc-accent);border-color:var(--dc-accent)">
        <i class="dc-icon dc-icon-plus dc-icon-sm"></i> Add Slot
      </button>
      <button class="dc-btn dc-btn-ghost" data-modal-close="modal-add-slot">Cancel</button>
    </div>
  </div>
</div>

<script src="../../../core/ui/devcore.js"></script>
<script>
const API = '<?= $apiBase ?>';
const DAY_FULL = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
let currentDoctorId = null;
let allSlots = [];

document.getElementById('sel-doctor').addEventListener('change', function() {
  currentDoctorId = this.value || null;
  if (currentDoctorId) {
    loadSlots(currentDoctorId);
    document.getElementById('add-slot-doctor').value = currentDoctorId;
  } else {
    document.getElementById('grid-wrap').innerHTML =
      '<div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-clock dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__title" style="margin-top:10px">Select a Doctor</div><p class="dc-empty__text">Choose a doctor above to manage their schedule.</p></div>';
  }
});

async function loadSlots(doctorId) {
  document.getElementById('grid-wrap').innerHTML =
    '<div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-refresh dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__text" style="margin-top:8px">Loading schedule…</div></div>';
  try {
    const res = await DC.get(`${API}/slots.php?doctor=${doctorId}&admin=1`);
    allSlots = res.data || [];
    renderGrid(allSlots);
  } catch(e) { Toast.error('Failed to load slots: ' + e.message); }
}

function renderGrid(slots) {
  const times = [...new Set(slots.map(s => s.start_time.slice(0,5)))].sort();
  const days  = [1,2,3,4,5];

  if (!times.length) {
    document.getElementById('grid-wrap').innerHTML =
      '<div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-calendar dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__title" style="margin-top:10px">No Slots Configured</div><p class="dc-empty__text">Click "Add Slot" to create the first slot for this doctor.</p></div>';
    return;
  }

  let html = '<div class="slot-grid">';
  html += '<div class="slot-header">Time</div>';
  days.forEach(d => { html += `<div class="slot-header">${DAY_FULL[d]}</div>`; });

  times.forEach(time => {
    html += `<div class="slot-time-label"><i class="dc-icon dc-icon-clock dc-icon-xs" style="margin-right:5px;color:var(--dc-text-3)"></i>${time}</div>`;
    days.forEach(day => {
      const slot = slots.find(s => s.start_time.slice(0,5) === time && parseInt(s.day_of_week) === day);
      if (slot) {
        const isActive = slot.active == 1;
        const cls  = isActive ? 'active' : 'inactive';
        const icon = isActive ? 'check' : 'x';
        html += `<div class="slot-cell ${cls}" onclick="toggleSlot(${slot.id}, ${isActive?0:1})" title="Click to ${isActive?'deactivate':'activate'}">
          <i class="dc-icon dc-icon-${icon}"></i>
          <span class="slot-time">${time}</span>
          <span class="slot-cap">max ${slot.max_patients}</span>
        </div>`;
      } else {
        html += `<div class="slot-cell slot-add" onclick="prefill(${day},'${time}')" title="Add slot">
          <i class="dc-icon dc-icon-plus dc-icon-sm"></i>
        </div>`;
      }
    });
  });

  html += '<div class="slot-time-label" style="font-size:0.7rem;opacity:0.5">New</div>';
  days.forEach(day => {
    html += `<div class="slot-cell slot-add" onclick="prefill(${day},'16:00')"><i class="dc-icon dc-icon-plus dc-icon-sm"></i></div>`;
  });
  html += '</div>';
  document.getElementById('grid-wrap').innerHTML = html;
}

async function toggleSlot(id, newActive) {
  try {
    await DC.put(`${API}/slots.php?id=${id}`, { active: newActive });
    Toast.success(newActive ? 'Slot activated' : 'Slot deactivated');
    loadSlots(currentDoctorId);
  } catch(e) { Toast.error(e.message); }
}

function prefill(day, time) {
  document.getElementById('add-slot-day').value   = day;
  document.getElementById('add-slot-start').value = time;
  const [h, m] = time.split(':').map(Number);
  document.getElementById('add-slot-end').value   = String(h+1).padStart(2,'0') + ':' + String(m).padStart(2,'0');
  if (currentDoctorId) document.getElementById('add-slot-doctor').value = currentDoctorId;
  Modal.open('modal-add-slot');
}

document.getElementById('btn-add-slot').addEventListener('click', async () => {
  const btn      = document.getElementById('btn-add-slot');
  const doctorId = document.getElementById('add-slot-doctor').value;
  const day      = document.getElementById('add-slot-day').value;
  const start    = document.getElementById('add-slot-start').value;
  const end      = document.getElementById('add-slot-end').value;
  const maxPat   = document.getElementById('add-slot-max').value;
  if (!doctorId || !start || !end) { Toast.warning('Please fill in all fields'); return; }
  DCForm.setLoading(btn, true);
  try {
    await DC.post(`${API}/slots.php`, { doctor_id:parseInt(doctorId), day_of_week:parseInt(day), start_time:start+':00', end_time:end+':00', max_patients:parseInt(maxPat)||3 });
    Modal.close('modal-add-slot');
    Toast.success('Slot added');
    if (currentDoctorId) loadSlots(currentDoctorId);
  } catch(e) { Toast.error(e.message); } finally { DCForm.setLoading(btn, false); }
});
</script>
</body>
</html>