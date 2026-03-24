<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';
Auth::requireRole('admin', 'login.php');
$db      = Database::getInstance();
$doctors = $db->fetchAll("SELECT id, name FROM doctors WHERE active = 1 ORDER BY name");
$apiBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/appointments.php')), '/') . '/api';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Appointments — MediBook Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<link rel="stylesheet" href="../../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }
  .filter-bar { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
  .live-pill { background:rgba(14,165,233,0.12); border:1px solid rgba(14,165,233,0.2); border-radius:var(--dc-radius-full); padding:4px 14px; font-size:0.8rem; font-weight:600; color:var(--dc-accent-2); display:inline-flex; align-items:center; gap:6px; }
</style>
</head>
<body>

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo"><i class="dc-icon dc-icon-hospital dc-icon-md"></i> Medi<span>Book</span></div>
  <div class="dc-sidebar__section">Main</div>
  <a href="dashboard.php"    class="dc-sidebar__link"><i class="dc-icon dc-icon-bar-chart dc-icon-sm"></i> Dashboard</a>
  <a href="appointments.php" class="dc-sidebar__link active"><i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Appointments</a>
  <a href="doctors.php"      class="dc-sidebar__link"><i class="dc-icon dc-icon-stethoscope dc-icon-sm"></i> Doctors</a>
  <a href="slots.php"        class="dc-sidebar__link"><i class="dc-icon dc-icon-clock dc-icon-sm"></i> Time Slots</a>
  <a href="qr-scanner.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-qr-code dc-icon-sm"></i> QR Scanner</a>
  <div class="dc-sidebar__section" style="margin-top:auto">Account</div>
  <a href="../index.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-globe dc-icon-sm"></i> View Site</a>
  <a href="logout.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<div class="dc-with-sidebar">
  <nav class="dc-nav">
    <div class="dc-nav__brand" style="font-size:1rem;font-weight:600">Appointments</div>
    <div class="dc-flex dc-items-center" style="gap:12px">
      <div class="live-pill">
        <div class="dc-live__dot"></div>
        <span id="live-today-num">—</span> booked today
      </div>
      <div class="dc-live"><div class="dc-live__dot"></div> Live</div>
    </div>
  </nav>

  <div class="dc-container dc-section">

    <div class="dc-flex-between dc-mb-lg">
      <div>
        <h1 class="dc-h2">Appointments</h1>
        <p class="dc-body" style="color:var(--dc-text-2)">Filter, search and manage all clinic appointments.</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="dc-card-solid dc-mb-lg">
      <div class="filter-bar">
        <div class="dc-form-group" style="min-width:180px">
          <label class="dc-label-field">Doctor</label>
          <select class="dc-select" id="f-doctor">
            <option value="">All Doctors</option>
            <?php foreach ($doctors as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Date</label>
          <input type="date" class="dc-input" id="f-date">
        </div>
        <div class="dc-form-group" style="min-width:155px">
          <label class="dc-label-field">Status</label>
          <select class="dc-select" id="f-status">
            <option value="">All Statuses</option>
            <option value="booked">Booked</option>
            <option value="confirmed">Confirmed</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="no_show">No Show</option>
          </select>
        </div>
        <div class="dc-form-group" style="flex:1;min-width:200px">
          <label class="dc-label-field">Search</label>
          <input type="text" class="dc-input" id="f-search" placeholder="Name, email or token…">
        </div>
        <div class="dc-form-group" style="justify-content:flex-end">
          <button class="dc-btn dc-btn-primary dc-btn-sm" id="btn-search"
                  style="background:var(--dc-accent);border-color:var(--dc-accent)">
            <i class="dc-icon dc-icon-search dc-icon-sm"></i> Search
          </button>
        </div>
        <div class="dc-form-group">
          <button class="dc-btn dc-btn-ghost dc-btn-sm" id="btn-reset">
            <i class="dc-icon dc-icon-x dc-icon-sm"></i> Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="dc-card-solid">
      <div id="appointments-table">
        <div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-refresh dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__text" style="margin-top:8px">Loading appointments…</div></div>
      </div>
      <div id="pagination" class="dc-flex-center dc-gap-sm dc-border-top" style="margin-top:20px;padding-top:16px"></div>
    </div>

  </div>
</div>

<!-- Status Modal -->
<div class="dc-modal-overlay" id="modal-status">
  <div class="dc-modal">
    <div class="dc-modal__header">
      <h3 class="dc-h3">Update Appointment</h3>
      <button class="dc-modal__close" data-modal-close="modal-status"><i class="dc-icon dc-icon-x dc-icon-sm"></i></button>
    </div>
    <p class="dc-body dc-mb">Token: <span id="modal-appt-token" class="dc-mono dc-caption"></span></p>
    <div class="dc-form-group dc-mb">
      <label class="dc-label-field">New Status</label>
      <select class="dc-select" id="modal-new-status">
        <option value="booked">Booked</option>
        <option value="confirmed">Confirmed</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
        <option value="no_show">No Show</option>
      </select>
    </div>
    <div class="dc-form-group dc-mb-lg">
      <label class="dc-label-field">Admin Notes (optional)</label>
      <textarea class="dc-textarea" id="modal-notes" rows="3" placeholder="Internal notes…"></textarea>
    </div>
    <div class="dc-flex dc-gap-sm">
      <button class="dc-btn dc-btn-primary dc-btn-full" id="btn-save-status"
              style="background:var(--dc-accent);border-color:var(--dc-accent)">
        <i class="dc-icon dc-icon-check dc-icon-sm"></i> Save Changes
      </button>
      <button class="dc-btn dc-btn-ghost" data-modal-close="modal-status">Cancel</button>
    </div>
  </div>
</div>

<script src="../../../core/ui/devcore.js"></script>
<script src="../../../core/utils/helpers.js"></script>
<script>
const API = '<?= $apiBase ?>';
let currentPage = 1;
let editingId   = null;

const statusBadge = { booked:'dc-badge-info',confirmed:'dc-badge-accent',in_progress:'dc-badge-warning',completed:'dc-badge-success',cancelled:'dc-badge-neutral',no_show:'dc-badge-danger' };
const statusLabel = { booked:'Booked',confirmed:'Confirmed',in_progress:'In Progress',completed:'Completed',cancelled:'Cancelled',no_show:'No Show' };

function esc(s) {
  if (window.DCHelpers && typeof window.DCHelpers.escHtml === 'function') {
    return window.DCHelpers.escHtml(s);
  }
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function buildQuery(page) {
  const p = new URLSearchParams({page});
  const v = id => document.getElementById(id).value;
  if (v('f-doctor')) p.set('doctor', v('f-doctor'));
  if (v('f-date'))   p.set('date',   v('f-date'));
  if (v('f-status')) p.set('status', v('f-status'));
  if (v('f-search').trim()) p.set('search', v('f-search').trim());
  return p.toString();
}

async function loadAppointments(page = 1) {
  currentPage = page;
  document.getElementById('appointments-table').innerHTML =
    '<div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-refresh dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__text" style="margin-top:8px">Loading…</div></div>';
  try {
    const res  = await DC.get(`${API}/appointments.php?${buildQuery(page)}`);
    const data = res.data || [];
    const meta = res.meta || {};
    if (!data.length) {
      document.getElementById('appointments-table').innerHTML =
        '<div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-search dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__title" style="margin-top:10px">No Appointments Found</div><p class="dc-empty__text">Try adjusting your filters.</p></div>';
      document.getElementById('pagination').innerHTML = '';
      return;
    }

    let html = `<div class="dc-table-wrap"><table class="dc-table"><thead><tr>
      <th>Token</th><th>Patient</th><th>Doctor</th><th>Date &amp; Time</th><th>Status</th><th>Actions</th>
    </tr></thead><tbody>`;
    data.forEach(a => {
      const badge = statusBadge[a.status] || 'dc-badge-neutral';
      const lbl   = statusLabel[a.status] || a.status;
      html += `<tr>
        <td><span class="dc-mono" style="font-size:0.75rem;letter-spacing:0.04em">${esc(a.token)}</span></td>
        <td><strong>${esc(a.patient_name)}</strong><br><span class="dc-caption dc-text-dim">${esc(a.patient_email)}</span></td>
        <td>${esc(a.doctor_name)}<br><span class="dc-caption dc-text-dim">${esc(a.department_name)}</span></td>
        <td><strong>${esc(a.appointment_date)}</strong><br><span class="dc-caption">${a.appointment_time ? a.appointment_time.slice(0,5) : ''}</span></td>
        <td><span class="dc-badge ${badge}">${lbl}</span></td>
        <td><div class="dc-flex dc-gap-sm">
          <button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="openModal(${a.id},'${esc(a.token)}','${a.status}','${esc(a.notes||'')}')">
            <i class="dc-icon dc-icon-edit dc-icon-sm"></i> Update
          </button>
          <a href="../appointment.php?token=${esc(a.token)}" target="_blank" class="dc-btn dc-btn-ghost dc-btn-sm">
            <i class="dc-icon dc-icon-eye dc-icon-sm"></i>
          </a>
        </div></td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('appointments-table').innerHTML = html;

    let pag = '';
    if (meta.total_pages > 1) {
      if (page > 1) pag += `<button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="loadAppointments(${page-1})"><i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Prev</button>`;
      pag += `<span class="dc-caption">Page ${page} of ${meta.total_pages} &middot; ${meta.total} total</span>`;
      if (page < meta.total_pages) pag += `<button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="loadAppointments(${page+1})">Next <i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i></button>`;
    }
    document.getElementById('pagination').innerHTML = pag;
  } catch(e) {
    document.getElementById('appointments-table').innerHTML =
      `<div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-alert-triangle dc-icon-2xl dc-empty__icon" style="color:var(--dc-danger)"></i><div class="dc-empty__text" style="margin-top:8px">${esc(e.message)}</div></div>`;
  }
}

function openModal(id, token, status, notes) {
  editingId = id;
  document.getElementById('modal-appt-token').textContent = token;
  document.getElementById('modal-new-status').value       = status;
  document.getElementById('modal-notes').value            = notes;
  Modal.open('modal-status');
}

document.getElementById('btn-save-status').addEventListener('click', async () => {
  if (!editingId) return;
  const btn = document.getElementById('btn-save-status');
  DCForm.setLoading(btn, true);
  try {
    await DC.put(`${API}/appointments.php?id=${editingId}`, {
      status: document.getElementById('modal-new-status').value,
      notes:  document.getElementById('modal-notes').value,
    });
    Modal.close('modal-status');
    Toast.success('Appointment updated');
    loadAppointments(currentPage);
  } catch(e) { Toast.error(e.message); } finally { DCForm.setLoading(btn, false); }
});

document.getElementById('btn-search').addEventListener('click', () => loadAppointments(1));
document.getElementById('f-search').addEventListener('keydown', e => { if (e.key==='Enter') loadAppointments(1); });
document.getElementById('btn-reset').addEventListener('click', () => {
  ['f-doctor','f-date','f-status','f-search'].forEach(id => document.getElementById(id).value = '');
  loadAppointments(1);
});

const livePoller = new LivePoller(`${API}/live.php`, res => {
  document.getElementById('live-today-num').textContent = res.data.today_count;
}, 10000);
livePoller.start();

loadAppointments(1);
</script>
</body>
</html>