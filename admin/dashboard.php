<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';
Auth::requireRole('admin', 'login.php');
$user    = Auth::user();
$apiBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/dashboard.php')), '/') . '/api';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard — MediBook Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<link rel="stylesheet" href="../../../core/ui/parts/_icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }
  .chart-wrap { height:260px; position:relative; }
  .feed-item { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--dc-border); }
  .feed-item:last-child { border-bottom:none; }
  .feed-avatar { width:34px; height:34px; border-radius:50%; background:var(--dc-accent-glow); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .feed-avatar .dc-icon { color:var(--dc-accent); }
  .live-counter { background:rgba(14,165,233,0.12); border:1px solid rgba(14,165,233,0.2); border-radius:var(--dc-radius-full); padding:4px 14px; font-size:0.8rem; font-weight:600; color:var(--dc-accent-2); display:inline-flex; align-items:center; gap:6px; }
</style>
</head>
<body>

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo">
    <i class="dc-icon dc-icon-hospital dc-icon-md"></i>
    Medi<span>Book</span>
  </div>
  <div class="dc-sidebar__section">Main</div>
  <a href="dashboard.php"    class="dc-sidebar__link active"><i class="dc-icon dc-icon-bar-chart dc-icon-sm"></i> Dashboard</a>
  <a href="appointments.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Appointments</a>
  <a href="doctors.php"      class="dc-sidebar__link"><i class="dc-icon dc-icon-stethoscope dc-icon-sm"></i> Doctors</a>
  <a href="slots.php"        class="dc-sidebar__link"><i class="dc-icon dc-icon-clock dc-icon-sm"></i> Time Slots</a>
  <a href="qr-scanner.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-qr-code dc-icon-sm"></i> QR Scanner</a>
  <div class="dc-sidebar__section" style="margin-top:auto">Account</div>
  <a href="../index.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-globe dc-icon-sm"></i> View Site</a>
  <a href="logout.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<div class="dc-with-sidebar">
  <nav class="dc-nav">
    <div class="dc-nav__brand" style="font-size:1rem;font-weight:600">Dashboard</div>
    <div class="dc-flex dc-items-center" style="gap:16px">
      <div class="live-counter" id="live-today-count">
        <div class="dc-live__dot"></div>
        <span id="live-count-num">—</span> booked today
      </div>
      <div class="dc-live" id="live-indicator">
        <div class="dc-live__dot"></div> Live
      </div>
      <div class="dc-caption dc-text-dim" style="display:flex;align-items:center;gap:6px">
        <i class="dc-icon dc-icon-user dc-icon-sm"></i> <?= htmlspecialchars($user['name']) ?>
      </div>
    </div>
  </nav>

  <div class="dc-container dc-section">

    <div class="dc-flex-between dc-mb-lg">
      <div>
        <h1 class="dc-h2">Analytics Dashboard</h1>
        <p class="dc-body" style="color:var(--dc-text-2)">Real-time clinic operations overview</p>
      </div>
      <div class="dc-caption dc-text-dim" id="last-updated"></div>
    </div>

    <!-- KPI cards -->
    <div class="dc-grid dc-grid-4 dc-mb-lg">
      <div class="dc-stat">
        <div class="dc-stat__icon"><i class="dc-icon dc-icon-calendar dc-icon-md"></i></div>
        <div class="dc-stat__value" id="kpi-today" data-count="0">—</div>
        <div class="dc-stat__label">Appointments Today</div>
      </div>
      <div class="dc-stat">
        <div class="dc-stat__icon" style="background:rgba(34,211,160,0.15)"><i class="dc-icon dc-icon-check dc-icon-md" style="color:var(--dc-success)"></i></div>
        <div class="dc-stat__value" id="kpi-completion" style="color:var(--dc-success)">—</div>
        <div class="dc-stat__label">Completion Rate</div>
      </div>
      <div class="dc-stat">
        <div class="dc-stat__icon" style="background:rgba(255,92,106,0.1)"><i class="dc-icon dc-icon-alert-triangle dc-icon-md" style="color:var(--dc-danger)"></i></div>
        <div class="dc-stat__value" id="kpi-noshow" style="color:var(--dc-danger)">—</div>
        <div class="dc-stat__label">No-Show Rate</div>
      </div>
      <div class="dc-stat">
        <div class="dc-stat__icon" style="background:rgba(245,166,35,0.15)"><i class="dc-icon dc-icon-trophy dc-icon-md" style="color:var(--dc-warning)"></i></div>
        <div class="dc-stat__value" id="kpi-busiest" style="font-size:1rem">—</div>
        <div class="dc-stat__label">Busiest Doctor (Month)</div>
      </div>
    </div>

    <!-- Charts row 1 -->
    <div class="dc-grid dc-grid-2 dc-mb-lg">
      <div class="dc-card">
        <div class="dc-h4 dc-mb" style="display:flex;align-items:center;gap:8px">
          <i class="dc-icon dc-icon-activity dc-icon-sm" style="color:var(--dc-accent-2)"></i>
          Appointments — Last 30 Days
        </div>
        <div class="chart-wrap"><canvas id="lineChart"></canvas></div>
      </div>
      <div class="dc-card">
        <div class="dc-h4 dc-mb" style="display:flex;align-items:center;gap:8px">
          <i class="dc-icon dc-icon-bar-chart dc-icon-sm" style="color:var(--dc-accent-2)"></i>
          Per Doctor (This Month)
        </div>
        <div class="chart-wrap"><canvas id="barChart"></canvas></div>
      </div>
    </div>

    <!-- Charts row 2 + live feed -->
    <div class="dc-grid dc-grid-2 dc-mb-lg">
      <div class="dc-card">
        <div class="dc-h4 dc-mb" style="display:flex;align-items:center;gap:8px">
          <i class="dc-icon dc-icon-hospital dc-icon-sm" style="color:var(--dc-accent-2)"></i>
          By Department (This Month)
        </div>
        <div class="chart-wrap"><canvas id="doughnutChart"></canvas></div>
      </div>
      <div class="dc-card">
        <div class="dc-flex-between dc-mb">
          <div class="dc-h4" style="display:flex;align-items:center;gap:8px">
            <i class="dc-icon dc-icon-clipboard dc-icon-sm" style="color:var(--dc-accent-2)"></i>
            Today's Schedule
          </div>
          <div class="dc-live"><div class="dc-live__dot"></div> Every 10s</div>
        </div>
        <div id="live-feed" style="max-height:240px;overflow-y:auto">
          <div class="dc-empty dc-empty-sm"><i class="dc-icon dc-icon-refresh dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__text" style="margin-top:8px">Loading…</div></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="../../../core/ui/devcore.js"></script>
<script src="../../../core/utils/helpers.js"></script>
<script>
const API = '<?= $apiBase ?>';
let chartsBuilt = false;
let lineChart, barChart, doughnutChart;

function last30Labels() {
  const d = []; for (let i=29;i>=0;i--) { const dt=new Date(); dt.setDate(dt.getDate()-i); d.push(dt.toISOString().slice(0,10)); } return d;
}
function mapByDate(arr, dates) { const m={}; arr.forEach(r=>m[r.label]=+r.count); return dates.map(d=>m[d]||0); }
function esc(s) {
  if (window.DCHelpers && typeof window.DCHelpers.escHtml === 'function') {
    return window.DCHelpers.escHtml(s);
  }
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

const statusBadge = {
  booked:'dc-badge-info',confirmed:'dc-badge-accent',in_progress:'dc-badge-warning',
  completed:'dc-badge-success',cancelled:'dc-badge-neutral',no_show:'dc-badge-danger'
};
const statusLabel = {
  booked:'Booked',confirmed:'Confirmed',in_progress:'In Progress',
  completed:'Completed',cancelled:'Cancelled',no_show:'No Show'
};

async function loadAnalytics() {
  try {
    const res = await DC.get(`${API}/analytics.php`);
    const d   = res.data;

    document.getElementById('kpi-today').textContent      = d.kpi.today_count;
    document.getElementById('kpi-completion').textContent = d.kpi.completion_rate + '%';
    document.getElementById('kpi-noshow').textContent     = d.kpi.no_show_rate + '%';
    document.getElementById('kpi-busiest').textContent    = d.kpi.busiest_doctor;
    document.getElementById('live-count-num').textContent = d.kpi.today_count;
    document.getElementById('last-updated').textContent   = 'Updated ' + new Date().toLocaleTimeString();

    if (!chartsBuilt) {
      chartsBuilt = true;
      const dates = last30Labels();
      lineChart = DCChart.line('lineChart',
        dates.map(d => d.slice(5)),
        [
          { label:'Booked',    data:mapByDate(d.charts.daily_booked, dates),    borderColor:'#38bdf8', backgroundColor:'rgba(56,189,248,0.08)' },
          { label:'Completed', data:mapByDate(d.charts.daily_completed, dates), borderColor:'#22d3a0', backgroundColor:'rgba(34,211,160,0.08)' },
        ]
      );
      barChart = DCChart.bar('barChart',
        d.charts.per_doctor.map(r => r.label.replace('Dr. ','')),
        [{ label:'Appointments', data:d.charts.per_doctor.map(r=>+r.value), backgroundColor:'rgba(56,189,248,0.6)', borderColor:'#38bdf8' }]
      );
      doughnutChart = DCChart.doughnut('doughnutChart',
        d.charts.by_department.map(r=>r.label),
        d.charts.by_department.map(r=>+r.value)
      );
    }

    renderFeed(d.feed);
  } catch(e) { console.error('Analytics error:', e.message); }
}

function renderFeed(feed) {
  if (!feed || !feed.length) {
    document.getElementById('live-feed').innerHTML =
      '<div class="dc-empty dc-empty-sm"><i class="dc-icon dc-icon-calendar dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__text" style="margin-top:8px">No appointments today.</div></div>';
    return;
  }
  let html = '';
  feed.forEach(row => {
    const badge = statusBadge[row.status] || 'dc-badge-neutral';
    const label = statusLabel[row.status] || row.status;
    const noShow = row.possible_no_show
      ? `<span class="dc-badge dc-badge-warning" style="margin-left:6px;font-size:0.68rem"><i class="dc-icon dc-icon-alert-triangle dc-icon-xs"></i> No-Show?</span>` : '';
    html += `<div class="feed-item">
      <div class="feed-avatar"><i class="dc-icon dc-icon-user dc-icon-sm"></i></div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:0.875rem">${esc(row.patient_name)}</div>
        <div class="dc-caption dc-text-dim">${esc(row.doctor_name)}</div>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div class="dc-mono" style="font-size:0.8rem;margin-bottom:3px">${row.appointment_time.slice(0,5)}</div>
        <span class="dc-badge ${badge}">${label}</span>${noShow}
      </div>
    </div>`;
  });
  document.getElementById('live-feed').innerHTML = html;
}

loadAnalytics();

const livePoller = new LivePoller(`${API}/live.php`, (res) => {
  document.getElementById('live-count-num').textContent = res.data.today_count;
}, 10000);
livePoller.start();

setInterval(loadAnalytics, 30000);
</script>
</body>
</html>