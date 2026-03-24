<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';
Auth::requireRole('admin', 'login.php');
$apiBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/qr-scanner.php')), '/') . '/api';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>QR Scanner — MediBook Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<link rel="stylesheet" href="../../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }
  .scanner-box {
    background:var(--dc-bg-glass); border:2px dashed var(--dc-border-2);
    border-radius:var(--dc-radius-xl); padding:48px 32px; text-align:center;
  }
  .scanner-icon {
    width:72px; height:72px; border-radius:20px; background:var(--dc-accent-glow);
    border:1px solid rgba(14,165,233,0.25); display:flex; align-items:center;
    justify-content:center; margin:0 auto 20px;
  }
  .scanner-icon .dc-icon { color:var(--dc-accent-2); }
  .detail-row { display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid var(--dc-border); gap:16px; }
  .detail-row:last-child { border-bottom:none; }
  .tl-step { flex:1; display:flex; flex-direction:column; align-items:center; gap:8px; text-align:center; }
  .tl-dot { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid var(--dc-border); background:var(--dc-bg-2); }
  .tl-dot .dc-icon { color:var(--dc-text-3); }
  .tl-dot.done    { background:var(--dc-success); border-color:var(--dc-success); }
  .tl-dot.done .dc-icon { color:#111; }
  .tl-dot.current { background:rgba(14,165,233,0.15); border-color:var(--dc-accent); box-shadow:0 0 14px rgba(14,165,233,0.2); }
  .tl-dot.current .dc-icon { color:var(--dc-accent-2); }
  .tl-dot.cancelled-step { background:rgba(255,92,106,0.15); border-color:var(--dc-danger); }
  .tl-dot.cancelled-step .dc-icon { color:var(--dc-danger); }
  .tl-label { font-size:0.78rem; font-weight:600; color:var(--dc-text-3); }
  .tl-label.done    { color:var(--dc-success); }
  .tl-label.current { color:var(--dc-accent-2); }
  .tl-label.cancelled-step { color:var(--dc-danger); }
  .timeline { display:flex; align-items:flex-start; position:relative; padding:8px 0; }
  .timeline::before { content:''; position:absolute; top:19px; left:20px; right:20px; height:2px; background:var(--dc-border); z-index:0; }
  .tl-step { position:relative; z-index:1; }
</style>
</head>
<body>

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo"><i class="dc-icon dc-icon-hospital dc-icon-md"></i> Medi<span>Book</span></div>
  <div class="dc-sidebar__section">Main</div>
  <a href="dashboard.php"    class="dc-sidebar__link"><i class="dc-icon dc-icon-bar-chart dc-icon-sm"></i> Dashboard</a>
  <a href="appointments.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Appointments</a>
  <a href="doctors.php"      class="dc-sidebar__link"><i class="dc-icon dc-icon-stethoscope dc-icon-sm"></i> Doctors</a>
  <a href="slots.php"        class="dc-sidebar__link"><i class="dc-icon dc-icon-clock dc-icon-sm"></i> Time Slots</a>
  <a href="qr-scanner.php"   class="dc-sidebar__link active"><i class="dc-icon dc-icon-qr-code dc-icon-sm"></i> QR Scanner</a>
  <div class="dc-sidebar__section" style="margin-top:auto">Account</div>
  <a href="../index.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-globe dc-icon-sm"></i> View Site</a>
  <a href="logout.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<div class="dc-with-sidebar">
  <nav class="dc-nav">
    <div class="dc-nav__brand" style="font-size:1rem;font-weight:600">QR Scanner</div>
  </nav>

  <div class="dc-container dc-section" style="max-width:820px">
    <div class="dc-flex-between dc-mb-lg">
      <div>
        <h1 class="dc-h2">QR Appointment Scanner</h1>
        <p class="dc-body dc-text-muted">Scan or paste a token to pull up the full appointment instantly.</p>
      </div>
    </div>

    <!-- Scanner input -->
    <div class="scanner-box dc-mb-lg">
      <div class="scanner-icon"><i class="dc-icon dc-icon-qr-code dc-icon-xl"></i></div>
      <h3 class="dc-h3 dc-mb-sm">Enter Appointment Token</h3>
      <p class="dc-body dc-text-muted dc-mb-lg" style="max-width:420px;margin-left:auto;margin-right:auto">
        Paste the 16-character token from the patient's QR appointment card, or scan the QR code with any reader.
      </p>
      <div class="dc-flex dc-gap-sm" style="max-width:480px;margin:0 auto">
        <input type="text" class="dc-input" id="token-input"
               placeholder="e.g. f3a8b2c1d4e56701"
               maxlength="16"
               style="font-family:monospace;letter-spacing:0.08em;flex:1;text-align:center">
        <button class="dc-btn dc-btn-primary" id="btn-lookup"
                style="background:var(--dc-accent);border-color:var(--dc-accent);white-space:nowrap">
          <i class="dc-icon dc-icon-search dc-icon-sm"></i> Look Up
        </button>
      </div>
      <p class="dc-caption dc-text-dim dc-mt">16 characters · shown on the patient's confirmation page</p>
    </div>

    <!-- Result -->
    <div id="result-area"></div>
  </div>
</div>

<script src="../../../core/ui/devcore.js"></script>
<script src="../../../core/utils/helpers.js"></script>
<script>
const API = '<?= $apiBase ?>';
const statusBadge = { booked:'dc-badge-info',confirmed:'dc-badge-accent',in_progress:'dc-badge-warning',completed:'dc-badge-success',cancelled:'dc-badge-neutral',no_show:'dc-badge-danger' };
const statusLabel = { booked:'Booked',confirmed:'Confirmed',in_progress:'In Progress',completed:'Completed',cancelled:'Cancelled',no_show:'No Show' };
const statusOrder = { booked:0,confirmed:1,in_progress:2,completed:3,cancelled:3,no_show:3 };
const stepIcons   = { booked:'calendar',confirmed:'check',in_progress:'clock',completed:'trophy',cancelled:'x',no_show:'alert-triangle' };

function esc(s) {
  if (window.DCHelpers && typeof window.DCHelpers.escHtml === 'function') {
    return window.DCHelpers.escHtml(s);
  }
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function formatDate(d) {
  return d ? new Date(d + 'T12:00').toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) : '—';
}

async function lookupToken() {
  const token = document.getElementById('token-input').value.trim().toLowerCase();
  if (!token)       { Toast.warning('Please enter a token'); return; }
  if (token.length !== 16) { Toast.warning('Token must be exactly 16 characters'); return; }

  const btn = document.getElementById('btn-lookup');
  DCForm.setLoading(btn, true);
  document.getElementById('result-area').innerHTML =
    '<div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-refresh dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__text" style="margin-top:8px">Looking up appointment…</div></div>';

  try {
    const res = await DC.get(`${API}/appointments.php?token=${token}`);
    renderResult(res.data);
  } catch(e) {
    document.getElementById('result-area').innerHTML = `
      <div class="dc-card dc-text-center" style="padding:40px;border-color:rgba(255,92,106,0.3)">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,92,106,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
          <i class="dc-icon dc-icon-x dc-icon-lg" style="color:var(--dc-danger)"></i>
        </div>
        <div class="dc-h3 dc-mb-sm">Not Found</div>
        <p class="dc-body dc-text-muted">${esc(e.message)}</p>
        <p class="dc-caption dc-text-dim dc-mt">Double-check the token and try again.</p>
      </div>`;
  } finally { DCForm.setLoading(btn, false); }
}

function renderResult(a) {
  const badge = statusBadge[a.status] || 'dc-badge-neutral';
  const lbl   = statusLabel[a.status] || a.status;
  const isTerminal = ['cancelled','no_show'].includes(a.status);
  const steps = isTerminal
    ? ['booked','confirmed',a.status]
    : ['booked','confirmed','in_progress','completed'];

  const currOrd = statusOrder[a.status] ?? 0;
  let tlHtml = '';
  steps.forEach(sKey => {
    const sOrd = statusOrder[sKey] ?? 99;
    let cls = '';
    if (isTerminal && sKey === a.status) cls = 'cancelled-step';
    else if (sOrd < currOrd) cls = 'done';
    else if (sOrd === currOrd) cls = 'current';
    const icon = cls === 'done' ? 'check' : (stepIcons[sKey] || 'clock');
    const lbl2 = statusLabel[sKey] || sKey;
    tlHtml += `<div class="tl-step"><div class="tl-dot ${cls}"><i class="dc-icon dc-icon-${icon} dc-icon-sm"></i></div><div class="tl-label ${cls}">${esc(lbl2)}</div></div>`;
  });

  const quickActions = { booked:['confirmed','no_show','cancelled'],confirmed:['in_progress','no_show','cancelled'],in_progress:['completed'],completed:[],cancelled:[],no_show:[] };
  const actionBtnClass = { confirmed:'dc-btn-primary',in_progress:'dc-btn-primary',completed:'dc-btn-success',no_show:'dc-btn-danger',cancelled:'dc-btn-danger' };
  const actionBtns = (quickActions[a.status] || []).map(ns =>
    `<button class="dc-btn ${actionBtnClass[ns]||'dc-btn-ghost'} dc-btn-sm" onclick="updateStatus(${a.id},'${ns}')">
      <i class="dc-icon dc-icon-${stepIcons[ns]||'check'} dc-icon-sm"></i> ${statusLabel[ns]}
    </button>`
  ).join('');

  document.getElementById('result-area').innerHTML = `
    <div class="dc-animate-fade-up">
      <div class="dc-flex-between dc-mb">
        <div class="dc-h3">Appointment Found</div>
        <span class="dc-badge ${badge}" style="font-size:0.875rem">${lbl}</span>
      </div>

      <div class="dc-card-solid dc-mb-lg" style="border-color:rgba(14,165,233,0.2)">
        <div style="background:linear-gradient(135deg,rgba(14,165,233,0.1),rgba(14,165,233,0.02));padding:18px 22px;border-radius:var(--dc-radius-lg);margin:-24px -24px 20px">
          <div class="dc-flex-between" style="flex-wrap:wrap;gap:12px">
            <div>
              <div class="dc-h3" style="margin-bottom:4px">${esc(a.patient_name)}</div>
              <div class="dc-caption dc-text-dim" style="display:flex;align-items:center;gap:8px">
                <i class="dc-icon dc-icon-mail dc-icon-xs"></i>${esc(a.patient_email)}
                &nbsp;&middot;&nbsp;
                <i class="dc-icon dc-icon-phone dc-icon-xs"></i>${esc(a.patient_phone)}
              </div>
            </div>
            <div class="dc-text-right">
              <div class="dc-mono dc-text-dim" style="font-size:0.75rem">${esc(a.token)}</div>
              <div class="dc-caption dc-text-dim dc-mt-sm">Booked ${new Date(a.created_at).toLocaleDateString()}</div>
            </div>
          </div>
        </div>
        <div class="detail-row"><span class="dc-caption dc-text-dim">Doctor</span><strong>${esc(a.doctor_name)}</strong></div>
        <div class="detail-row"><span class="dc-caption dc-text-dim">Department</span><span>${esc(a.department_name)}</span></div>
        <div class="detail-row"><span class="dc-caption dc-text-dim">Date</span><strong>${formatDate(a.appointment_date)}</strong></div>
        <div class="detail-row"><span class="dc-caption dc-text-dim">Time</span><strong style="color:var(--dc-accent-2)">${a.appointment_time ? a.appointment_time.slice(0,5) : '—'}</strong></div>
        <div class="detail-row"><span class="dc-caption dc-text-dim">Reason</span><span style="max-width:55%;text-align:right">${esc(a.reason)}</span></div>
        ${a.notes ? `<div class="detail-row"><span class="dc-caption dc-text-dim">Notes</span><span style="color:var(--dc-warning);max-width:55%;text-align:right">${esc(a.notes)}</span></div>` : ''}
      </div>

      <div class="dc-grid dc-grid-2 dc-gap-lg">
        <div class="dc-card">
          <div class="dc-h4 dc-mb">Status Timeline</div>
          <div class="timeline">${tlHtml}</div>
          <div class="dc-text-center dc-mt"><span class="dc-badge ${badge}">${lbl}</span></div>
        </div>
        <div class="dc-card">
          <div class="dc-h4 dc-mb">Quick Actions</div>
          ${actionBtns ? `<div class="dc-flex dc-gap-sm" style="flex-wrap:wrap">${actionBtns}</div>` : '<p class="dc-body dc-text-muted">No actions available for this status.</p>'}
          <hr class="dc-divider">
          <a href="../appointment.php?token=${esc(a.token)}" target="_blank" class="dc-btn dc-btn-ghost dc-btn-sm">
            <i class="dc-icon dc-icon-eye dc-icon-sm"></i> View Public Page
          </a>
        </div>
      </div>
    </div>`;
}

async function updateStatus(id, newStatus) {
  try {
    await DC.put(`${API}/appointments.php?id=${id}`, { status: newStatus });
    Toast.success('Status updated to: ' + (statusLabel[newStatus] || newStatus));
    lookupToken();
  } catch(e) { Toast.error(e.message); }
}

document.getElementById('btn-lookup').addEventListener('click', lookupToken);
document.getElementById('token-input').addEventListener('keydown', e => { if (e.key === 'Enter') lookupToken(); });
document.getElementById('token-input').focus();

const urlToken = new URLSearchParams(location.search).get('token');
if (urlToken) { document.getElementById('token-input').value = urlToken; lookupToken(); }
</script>
</body>
</html>