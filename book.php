<?php
require_once __DIR__ . '/core/bootstrap.php';

$db      = Database::getInstance();
$deptId  = isset($_GET['dept']) ? (int)$_GET['dept'] : null;
$depts   = $db->fetchAll("SELECT id, name FROM departments ORDER BY name");
// Compute API base path so JS fetch calls work regardless of server subdirectory
$apiBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/book.php'), '/') . '/api';
$minBookDate = date('Y-m-d');
$maxBookDate = date('Y-m-d', strtotime('+180 days'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Book an Appointment — MediBook Clinic</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<link rel="stylesheet" href="../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }

  /* Progress bar */
  .progress-bar { display:flex; align-items:center; gap:0; margin-bottom:40px; }
  .progress-step {
    display:flex; align-items:center; gap:8px;
    padding:8px 18px; border-radius:var(--dc-radius-full);
    font-size:0.875rem; font-weight:600; color:var(--dc-text-3);
    transition:all var(--dc-t-fast);
  }
  .progress-step.active { background:rgba(14,165,233,0.12); color:var(--dc-accent-2); }
  .progress-step.done   { color:var(--dc-success); }
  .progress-dot {
    width:24px; height:24px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.75rem; font-weight:700;
    background:var(--dc-bg-3); color:var(--dc-text-3);
    border:2px solid var(--dc-border);
  }
  .progress-step.active .progress-dot { background:var(--dc-accent); border-color:var(--dc-accent); color:#fff; }
  .progress-step.done   .progress-dot { background:var(--dc-success); border-color:var(--dc-success); color:#111; }
  .progress-sep { flex:1; height:1px; background:var(--dc-border); min-width:16px; }

  /* Wizard panels */
  .wizard-step { display:none; animation:dc-fade-up 0.3s var(--dc-ease) both; }
  .wizard-step.active { display:block; }

  /* Doctor cards */
  .doctor-card {
    background:var(--dc-bg-glass); border:2px solid var(--dc-border);
    border-radius:var(--dc-radius-lg); padding:18px; cursor:pointer;
    transition:all var(--dc-t-fast);
  }
  .doctor-card:hover { border-color:var(--dc-accent-2); }
  .doctor-card.selected { border-color:var(--dc-accent); background:rgba(14,165,233,0.06); }
  .doctor-avatar {
    width:56px; height:56px; border-radius:50%; object-fit:cover;
    border:2px solid var(--dc-border); flex-shrink:0;
  }
  .doctor-avatar-placeholder {
    width:56px; height:56px; border-radius:50%; flex-shrink:0;
    background:var(--dc-bg-3); border:2px solid var(--dc-border);
    display:flex; align-items:center; justify-content:center;
  }
  .doctor-avatar-placeholder .dc-icon { color:var(--dc-text-3); }

  /* Slot buttons */
  .slots-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:10px; }
  .slot-btn {
    padding:12px 10px; border-radius:var(--dc-radius); font-size:0.875rem;
    font-weight:600; text-align:center; cursor:pointer;
    border:2px solid var(--dc-border); background:var(--dc-bg-3); color:var(--dc-text-2);
    transition:all var(--dc-t-fast);
  }
  .slot-btn:hover:not([disabled]) { border-color:var(--dc-accent-2); color:var(--dc-accent-2); background:rgba(14,165,233,0.06); }
  .slot-btn.selected { background:rgba(0,255,200,0.08); border-color:#00ffc8; color:#00ffc8; box-shadow:0 0 12px rgba(0,255,200,0.45), 0 0 28px rgba(0,255,200,0.2), inset 0 0 12px rgba(0,255,200,0.06); text-shadow:0 0 8px rgba(0,255,200,0.8); }
  .slot-btn[disabled] { opacity:0.4; cursor:not-allowed; }
  .slot-avail { display:block; font-size:0.7rem; font-weight:400; margin-top:3px; }

  /* Dept filter pills */
  .dept-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
  .dept-pill {
    padding:5px 12px; border-radius:var(--dc-radius-full); font-size:0.8rem; font-weight:500;
    border:1px solid var(--dc-border); background:var(--dc-bg-glass); color:var(--dc-text-2);
    cursor:pointer; transition:all var(--dc-t-fast);
  }
  .dept-pill:hover, .dept-pill.active { border-color:var(--dc-accent); color:var(--dc-accent-2); background:rgba(14,165,233,0.08); }

  /* Summary bar */
  .summary-bar {
    background:var(--dc-bg-glass); border:1px solid var(--dc-border);
    border-radius:var(--dc-radius-lg); padding:14px 20px;
    display:flex; gap:28px; flex-wrap:wrap; align-items:center;
    margin-bottom:20px;
  }
  .summary-bar .sum-item { font-size:0.875rem; }
  .summary-bar .sum-label { color:var(--dc-text-3); font-size:0.75rem; display:block; margin-bottom:2px; }
  .summary-bar .sum-val { font-weight:600; }
</style>
</head>
<body>

<nav class="dc-nav">
  <div class="dc-nav__brand">
    <i class="dc-icon dc-icon-hospital dc-icon-md"></i>
    Medi<span>Book</span>
  </div>
  <div class="dc-nav__links">
    <a href="index.php" class="dc-nav__link">
      <i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Home
    </a>
    <a href="appointment.php" class="dc-nav__link">My Appointment</a>
  </div>
</nav>

<div style="max-width:860px;margin:0 auto;padding:40px 24px 60px">

  <div style="margin-bottom:32px">
    <h1 class="dc-h2" style="margin-bottom:6px">Book an Appointment</h1>
    <p class="dc-body" style="color:var(--dc-text-2)">Complete all 3 steps to confirm your booking.</p>
  </div>

  <!-- Progress Bar -->
  <div class="progress-bar" id="progress-bar">
    <div class="progress-step active" id="ps-1">
      <div class="progress-dot">1</div> Choose Doctor
    </div>
    <div class="progress-sep"></div>
    <div class="progress-step" id="ps-2">
      <div class="progress-dot">2</div> Date &amp; Slot
    </div>
    <div class="progress-sep"></div>
    <div class="progress-step" id="ps-3">
      <div class="progress-dot">3</div> Your Details
    </div>
  </div>

  <!-- ── STEP 1: Doctor ────────────────────────────────────── -->
  <div class="wizard-step active" id="step-1">

    <div class="dc-card-solid dc-mb">
      <div class="dc-flex-between" style="flex-wrap:wrap;gap:12px">
        <div>
          <div class="dc-label-field" style="margin-bottom:6px">Filter by Department</div>
          <div class="dept-pills" id="dept-pills">
            <div class="dept-pill <?= !$deptId ? 'active' : '' ?>" data-dept="">All Departments</div>
            <?php foreach ($depts as $dep): ?>
            <div class="dept-pill <?= $deptId == $dep['id'] ? 'active' : '' ?>"
                 data-dept="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['name']) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="dc-live" id="live-badge">
          <div class="dc-live__dot"></div> Live availability
        </div>
      </div>
    </div>

    <div class="dc-grid dc-grid-2" id="doctors-grid">
      <div class="dc-card" style="grid-column:1/-1;text-align:center;padding:40px">
        <div class="dc-skeleton" style="height:16px;width:180px;margin:0 auto;border-radius:4px"></div>
      </div>
    </div>

    <div style="margin-top:20px;display:flex;justify-content:flex-end">
      <button class="dc-btn dc-btn-primary" id="btn-next-1" disabled
              onclick="goStep(2)"
              style="background:var(--dc-accent);border-color:var(--dc-accent)">
        Next: Date &amp; Slot <i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i>
      </button>
    </div>
  </div>

  <!-- ── STEP 2: Date & Slot ───────────────────────────────── -->
  <div class="wizard-step" id="step-2">

    <!-- Selected doctor mini-card -->
    <div class="dc-card-solid dc-mb" id="sel-doc-card" style="display:none">
      <div class="dc-flex" style="align-items:center;gap:14px">
        <img id="sel-doc-photo" class="doctor-avatar" src="" alt="" style="display:none">
        <div class="doctor-avatar-placeholder" id="sel-doc-avatar-ph">
          <i class="dc-icon dc-icon-user dc-icon-md"></i>
        </div>
        <div style="flex:1">
          <div class="dc-h4" id="sel-doc-name"></div>
          <div class="dc-caption" id="sel-doc-spec" style="color:var(--dc-text-3)"></div>
        </div>
        <button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="goStep(1)">Change</button>
      </div>
    </div>

    <div class="dc-card-solid dc-mb">
      <div class="dc-flex-between dc-mb" style="flex-wrap:wrap;gap:12px">
        <div>
          <div class="dc-label-field" style="margin-bottom:6px">Appointment Date</div>
          <input type="date" class="dc-input" id="appt-date"
                 min="<?= date('Y-m-d') ?>"
               max="<?= htmlspecialchars($maxBookDate, ENT_QUOTES, 'UTF-8') ?>"
                 style="max-width:220px">
        </div>
        <div class="dc-live" id="slot-live" style="display:none">
          <div class="dc-live__dot"></div> Live
        </div>
      </div>

      <div class="dc-label-field dc-mb-sm">Available Time Slots</div>
      <div id="slots-container">
        <p class="dc-body" style="color:var(--dc-text-3)">Select a date above to see available slots.</p>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px">
      <button class="dc-btn dc-btn-ghost" onclick="goStep(1)">
        <i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Back
      </button>
      <button class="dc-btn dc-btn-primary" id="btn-next-2" disabled onclick="goStep(3)"
              style="background:var(--dc-accent);border-color:var(--dc-accent)">
        Next: Your Details <i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i>
      </button>
    </div>
  </div>

  <!-- ── STEP 3: Patient Details ───────────────────────────── -->
  <div class="wizard-step" id="step-3">

    <!-- Booking summary -->
    <div class="summary-bar dc-mb">
      <div class="sum-item"><span class="sum-label">Doctor</span><span class="sum-val" id="sum-doctor">—</span></div>
      <div class="sum-item"><span class="sum-label">Date</span><span class="sum-val" id="sum-date">—</span></div>
      <div class="sum-item"><span class="sum-label">Time</span><span class="sum-val" id="sum-time">—</span></div>
      <div class="sum-item"><span class="sum-label">Fee</span><span class="sum-val" id="sum-fee" style="color:var(--dc-accent-2)">—</span></div>
    </div>

    <div class="dc-card-solid dc-mb">
      <div class="dc-h4" style="margin-bottom:20px">
        <i class="dc-icon dc-icon-user dc-icon-md" style="color:var(--dc-accent-2);vertical-align:middle;margin-right:6px"></i>
        Your Information
      </div>
      <div class="dc-grid dc-grid-2" style="gap:16px">
        <div class="dc-form-group">
          <label class="dc-label-field">Full Name *</label>
          <input type="text" class="dc-input" id="p-name" placeholder="Jane Smith" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Date of Birth *</label>
          <input type="date" class="dc-input" id="p-dob" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Email Address *</label>
          <input type="email" class="dc-input" id="p-email" placeholder="jane@example.com" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Phone Number *</label>
          <input type="tel" class="dc-input" id="p-phone" placeholder="+1 555 000 0000" required>
        </div>
        <div class="dc-form-group" style="grid-column:1/-1">
          <label class="dc-label-field">Reason for Visit *</label>
          <textarea class="dc-textarea" id="p-reason" rows="3"
                    placeholder="Briefly describe your symptoms or reason for the appointment…" required></textarea>
        </div>
      </div>

      <!-- Error alert -->
      <div id="booking-error" style="display:none;margin-top:16px">
        <div class="dc-card" style="background:rgba(255,92,106,0.08);border-color:rgba(255,92,106,0.3);padding:12px 16px;display:flex;align-items:center;gap:8px">
          <i class="dc-icon dc-icon-alert-triangle dc-icon-sm" style="color:var(--dc-danger);flex-shrink:0"></i>
          <span id="booking-error-msg" style="color:var(--dc-danger);font-size:0.875rem"></span>
        </div>
      </div>

      <p class="dc-caption dc-mt" style="color:var(--dc-text-3)">
        <i class="dc-icon dc-icon-lock dc-icon-xs" style="vertical-align:middle;margin-right:4px"></i>
        Your information is kept strictly confidential.
      </p>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px">
      <button class="dc-btn dc-btn-ghost" onclick="goStep(2)">
        <i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Back
      </button>
      <button class="dc-btn dc-btn-primary dc-btn-lg" id="btn-confirm" onclick="confirmBooking()"
              style="background:var(--dc-accent);border-color:var(--dc-accent)">
        <i class="dc-icon dc-icon-check dc-icon-md"></i> Confirm Booking
      </button>
    </div>
  </div>

</div>

<script src="../../core/ui/devcore.js"></script>
<script src="../../core/utils/helpers.js"></script>
<script>
const API = '<?= $apiBase ?>';
const BOOK_MIN_DATE = '<?= $minBookDate ?>';
const BOOK_MAX_DATE = '<?= $maxBookDate ?>';

const booking = { doctorId:null, doctorName:null, doctorFee:null, doctorPhoto:null, doctorSpec:null, date:null, time:null };
let allDoctors   = [];
let currentSlots = [];
let slotPoller   = null;

function parseDateOnly(value) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return null;
  const d = new Date(value + 'T00:00:00Z');
  if (Number.isNaN(d.getTime())) return null;
  return d;
}

function isWithinBookingWindow(value) {
  const selected = parseDateOnly(value);
  const min = parseDateOnly(BOOK_MIN_DATE);
  const max = parseDateOnly(BOOK_MAX_DATE);
  if (!selected || !min || !max) return false;
  return selected >= min && selected <= max;
}

// ── Step Navigation ──────────────────────────────────────────
function goStep(n) {
  document.querySelectorAll('.wizard-step').forEach((s,i) => s.classList.toggle('active', i+1===n));
  [1,2,3].forEach(i => {
    const ps  = document.getElementById('ps-' + i);
    const dot = ps.querySelector('.progress-dot');
    ps.classList.remove('active','done');
    if (i === n) ps.classList.add('active');
    if (i < n)  { ps.classList.add('done'); dot.innerHTML = '<i class="dc-icon dc-icon-check dc-icon-xs" style="width:12px;height:12px"></i>'; }
    else         dot.textContent = i;
  });
  window.scrollTo({top:0,behavior:'smooth'});

  if (n === 2) {
    updateSelDocCard();
    if (booking.date) loadSlots();
    startSlotPoller();
  } else {
    stopSlotPoller();
  }
  if (n === 3) updateSummary();
}

// ── Dept filter ──────────────────────────────────────────────
document.querySelectorAll('.dept-pill').forEach(pill => {
  pill.addEventListener('click', function() {
    document.querySelectorAll('.dept-pill').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    loadDoctors(this.dataset.dept || '');
  });
});

// ── Step 1: Doctors ──────────────────────────────────────────
async function loadDoctors(deptId = '') {
  document.getElementById('doctors-grid').innerHTML =
    '<div class="dc-card" style="grid-column:1/-1;text-align:center;padding:40px">' +
    '<div class="dc-skeleton" style="height:14px;width:160px;margin:0 auto;border-radius:4px"></div></div>';
  try {
    const url = deptId ? `${API}/doctors.php?department=${deptId}` : `${API}/doctors.php`;
    const res = await DC.get(url);
    allDoctors = res.data || [];
    renderDoctors(allDoctors);
  } catch(e) {
    Toast.error('Failed to load doctors: ' + e.message);
  }
}

function renderDoctors(doctors) {
  const grid = document.getElementById('doctors-grid');
  if (!doctors.length) {
    grid.innerHTML = '<div class="dc-card" style="grid-column:1/-1;text-align:center;padding:48px"><div class="dc-empty"><i class="dc-icon dc-icon-user dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__title">No doctors found</div><p class="dc-empty__text">Try selecting a different department.</p></div></div>';
    return;
  }
  grid.innerHTML = doctors.map(d => {
    const isSelected = booking.doctorId === d.id;
    const avatarHtml = d.photo_url
      ? `<img class="doctor-avatar" src="${esc(d.photo_url)}" alt="${esc(d.name)}">`
      : `<div class="doctor-avatar-placeholder"><i class="dc-icon dc-icon-user dc-icon-md"></i></div>`;
    return `
    <div class="doctor-card ${isSelected?'selected':''}" onclick="selectDoctor(${d.id})" id="doc-card-${d.id}">
      <div class="dc-flex dc-gap" style="align-items:flex-start;margin-bottom:14px">
        ${avatarHtml}
        <div style="flex:1;min-width:0">
          <div class="dc-h4" style="margin-bottom:3px">${esc(d.name)}</div>
          <div class="dc-caption" style="color:var(--dc-text-3);margin-bottom:6px">${esc(d.specialization)}</div>
          <span class="dc-badge dc-badge-info">${esc(d.department_name||'')}</span>
        </div>
      </div>
      <div class="dc-flex dc-gap dc-mb">
        <div style="flex:1">
          <div class="dc-label" style="margin-bottom:2px">Experience</div>
          <div style="font-weight:600;font-size:0.875rem;display:flex;align-items:center;gap:4px">
            <i class="dc-icon dc-icon-trophy dc-icon-xs" style="color:var(--dc-accent-2)"></i>
            ${d.experience_years} yrs
          </div>
        </div>
        <div style="flex:1">
          <div class="dc-label" style="margin-bottom:2px">Consultation</div>
          <div style="font-weight:700;font-size:0.9rem;color:var(--dc-accent-2);display:flex;align-items:center;gap:4px">
            <i class="dc-icon dc-icon-dollar dc-icon-xs" style="color:var(--dc-accent-2)"></i>
            $${parseFloat(d.consultation_fee).toFixed(2)}
          </div>
        </div>
      </div>
      <button class="dc-btn dc-btn-sm dc-btn-full ${isSelected?'dc-btn-primary':'dc-btn-ghost'}"
              style="${isSelected?'background:var(--dc-accent);border-color:var(--dc-accent)':''}"
              onclick="selectDoctor(${d.id});event.stopPropagation()">
        ${isSelected
          ? '<i class="dc-icon dc-icon-check dc-icon-sm"></i> Selected'
          : 'Select Doctor'}
      </button>
    </div>`;
  }).join('');
}

function selectDoctor(id) {
  const doc = allDoctors.find(d => d.id === id);
  if (!doc) return;
  booking.doctorId    = id;
  booking.doctorName  = doc.name;
  booking.doctorFee   = doc.consultation_fee;
  booking.doctorPhoto = doc.photo_url || '';
  booking.doctorSpec  = doc.specialization;
  document.getElementById('btn-next-1').disabled = false;
  renderDoctors(allDoctors);
  setTimeout(() => goStep(2), 280);
}

// ── Step 2: Slots ────────────────────────────────────────────
function updateSelDocCard() {
  const card = document.getElementById('sel-doc-card');
  document.getElementById('sel-doc-name').textContent = booking.doctorName || '';
  document.getElementById('sel-doc-spec').textContent = booking.doctorSpec || '';
  const photo = document.getElementById('sel-doc-photo');
  const ph    = document.getElementById('sel-doc-avatar-ph');
  if (booking.doctorPhoto) {
    photo.src = booking.doctorPhoto; photo.style.display = ''; ph.style.display = 'none';
  } else {
    photo.style.display = 'none'; ph.style.display = '';
  }
  card.style.display = '';
}

// -- Date Input Fix -----------------------------------------------------------
// WHY: <input type="date"> has a known browser quirk where typing
// a month like "10", "11", or "12" causes the 'change' event to
// fire after just the first digit ("1"), jumping focus to the day
// field before the user can finish. This means the full month
// value is never captured by the 'change' event alone.
//
// FIX: We listen to both 'change' AND 'blur'. The 'blur' event
// fires only when the user leaves the field entirely - by then
// the full date (including double-digit months) is committed.
// We also keep the year sanity check (year < 2020) to silently
// ignore transient "0000" states while the year is still being
// typed, preventing false "out of range" warnings mid-entry.
function handleDateInput() {
  const selectedDate = this.value;

  // Not fully entered yet — do nothing, wait
  if (!selectedDate || selectedDate.length < 10 || !/^\d{4}-\d{2}-\d{2}$/.test(selectedDate)) {
    return;
  }

  // Year sanity check — ignore obviously incomplete years
  const year = parseInt(selectedDate.slice(0, 4));
  if (year < 2020 || year > 2100) {
    return;
  }

  const nativeDate = new Date(selectedDate + 'T00:00:00');
  if (isNaN(nativeDate.getTime())) {
    return;
  }

  if (!isWithinBookingWindow(selectedDate)) {
    booking.date = null;
    booking.time = null;
    document.getElementById('btn-next-2').disabled = true;
    document.getElementById('slots-container').innerHTML =
      '<p class="dc-body" style="color:var(--dc-text-3)">Please choose a date between today and the next 180 days.</p>';
    return;
  }

  booking.date = selectedDate;
  booking.time = null;
  document.getElementById('btn-next-2').disabled = true;
  loadSlots();
  restartSlotPoller();
}

document.getElementById('appt-date').addEventListener('change', handleDateInput);
document.getElementById('appt-date').addEventListener('blur', handleDateInput);

async function loadSlots() {
  if (!booking.doctorId || !booking.date) return;
  if (!isWithinBookingWindow(booking.date)) {
    Toast.warning('Selected date is outside booking range.');
    return;
  }
  document.getElementById('slot-live').style.display = '';
  document.getElementById('slots-container').innerHTML =
    '<p class="dc-body" style="color:var(--dc-text-3)"><i class="dc-icon dc-icon-refresh dc-icon-sm" style="vertical-align:middle;margin-right:6px;color:var(--dc-accent-2)"></i>Loading slots…</p>';
  try {
    const res   = await DC.get(`${API}/slots.php?doctor=${booking.doctorId}&date=${booking.date}`);
    currentSlots = Array.isArray(res.data) ? res.data : [];
    renderSlots(currentSlots);
  } catch(e) {
    document.getElementById('slots-container').innerHTML =
      `<div class="dc-card" style="background:rgba(255,92,106,0.08);border-color:rgba(255,92,106,0.3);padding:14px;display:flex;align-items:center;gap:8px">
        <i class="dc-icon dc-icon-alert-triangle dc-icon-sm" style="color:var(--dc-danger)"></i>
        <span style="color:var(--dc-danger);font-size:0.875rem">Failed to load slots — ${esc(e.message)}</span>
      </div>`;
  }
}

function renderSlots(slots) {
  if (!slots || !slots.length) {
    document.getElementById('slots-container').innerHTML =
      '<div class="dc-empty" style="padding:32px 0"><i class="dc-icon dc-icon-calendar dc-icon-2xl dc-empty__icon"></i>' +
      '<div class="dc-empty__title">No slots available</div>' +
      '<p class="dc-empty__text">This doctor has no slots on this day. Try another date.</p></div>';
    return;
  }
  let html = '<div class="slots-grid">';
  slots.forEach(s => {
    const time     = s.start_time.slice(0,5);
    const avail    = parseInt(s.available);
    const isFull   = s.is_full || avail <= 0;
    const isSel    = booking.time === time;
    const isLast   = avail === 1 && !isFull;

    let availLabel = '';
    if (isFull)       availLabel = `<span class="slot-avail" style="color:var(--dc-danger)">Full</span>`;
    else if (isLast)  availLabel = `<span class="slot-avail dc-text-warning">Last slot!</span>`;
    else              availLabel = `<span class="slot-avail" style="color:var(--dc-success)">${avail} left</span>`;

    html += `<button class="slot-btn ${isSel?'selected':''}" ${isFull?'disabled':''} data-time="${time}" onclick="selectSlot('${time}')">
      <i class="dc-icon dc-icon-clock dc-icon-xs" style="vertical-align:middle;margin-right:3px;${isSel?'color:#fff':'color:var(--dc-accent-2)'}"></i>
      ${time}${availLabel}
    </button>`;
  });
  html += '</div>';
  document.getElementById('slots-container').innerHTML = html;
}

function selectSlot(time) {
  booking.time = time;
  document.getElementById('btn-next-2').disabled = false;
  renderSlots(currentSlots);
}

function startSlotPoller() {
  stopSlotPoller();
  if (!booking.doctorId || !booking.date) return;
  slotPoller = new LivePoller(`${API}/live.php?doctor=${booking.doctorId}&date=${booking.date}`, res => {
    const avail = res.data.slots_availability || [];
    avail.forEach(live => {
      const slot = currentSlots.find(s => s.start_time && s.start_time.slice(0,5) === live.time);
      if (slot) { slot.available = live.available; slot.is_full = live.available <= 0; }
    });
    renderSlots(currentSlots);
    if (booking.time) {
      const sel = currentSlots.find(s => s.start_time && s.start_time.slice(0,5) === booking.time);
      if (sel && parseInt(sel.available) <= 0) {
        booking.time = null;
        document.getElementById('btn-next-2').disabled = true;
        Toast.warning('Your selected slot just filled up — please choose another.');
      }
    }
  }, 5000);
  slotPoller.start();
}

function stopSlotPoller()    { if (slotPoller) { slotPoller.stop(); slotPoller = null; } }
function restartSlotPoller() { stopSlotPoller(); setTimeout(startSlotPoller, 400); }

// ── Step 3 ───────────────────────────────────────────────────
function updateSummary() {
  document.getElementById('sum-doctor').textContent = booking.doctorName || '—';
  document.getElementById('sum-date').textContent   = booking.date
    ? new Date(booking.date + 'T12:00').toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'}) : '—';
  document.getElementById('sum-time').textContent   = booking.time || '—';
  document.getElementById('sum-fee').textContent    = booking.doctorFee ? '$' + parseFloat(booking.doctorFee).toFixed(2) : '—';
}

async function confirmBooking() {
  const name   = document.getElementById('p-name').value.trim();
  const email  = document.getElementById('p-email').value.trim();
  const phone  = document.getElementById('p-phone').value.trim();
  const dob    = document.getElementById('p-dob').value;
  const reason = document.getElementById('p-reason').value.trim();

  if (!name || !email || !phone || !dob || !reason) {
    showError('Please fill in all required fields.');
    return;
  }
  if (!booking.doctorId || !booking.date || !booking.time) {
    showError('Booking details missing — please go back and complete all steps.');
    return;
  }
  if (!isWithinBookingWindow(booking.date)) {
    showError('Please select a valid appointment date.');
    return;
  }

  const btn = document.getElementById('btn-confirm');
  DCForm.setLoading(btn, true);
  document.getElementById('booking-error').style.display = 'none';

  try {
    const res = await DC.post(`${API}/appointments.php`, {
      doctor_id:        booking.doctorId,
      appointment_date: booking.date,
      appointment_time: booking.time + ':00',
      patient_name:     name,
      patient_email:    email,
      patient_phone:    phone,
      patient_dob:      dob,
      reason,
    });
    stopSlotPoller();
    window.location.href = 'confirmation.php?token=' + res.data.token;
  } catch(e) {
    showError(e.message || 'Booking failed — please try again.');
    DCForm.setLoading(btn, false);
  }
}

function showError(msg) {
  document.getElementById('booking-error-msg').textContent = msg;
  document.getElementById('booking-error').style.display = 'block';
  document.getElementById('booking-error').scrollIntoView({behavior:'smooth'});
}

function esc(s) {
  if (window.DCHelpers && typeof window.DCHelpers.escHtml === 'function') {
    return window.DCHelpers.escHtml(s);
  }
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init
loadDoctors('<?= $deptId ?: '' ?>');
<?php if ($deptId): ?>
document.querySelector('.dept-pill[data-dept="<?= $deptId ?>"]')?.classList.add('active');
document.querySelector('.dept-pill[data-dept=""]')?.classList.remove('active');
<?php endif; ?>
</script>
</body>
</html>