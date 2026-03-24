<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';
Auth::requireRole('admin', 'login.php');
$db          = Database::getInstance();
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");
$apiBase     = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/doctors.php')), '/') . '/api';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Doctors — MediBook Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<link rel="stylesheet" href="../../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }
  .doctor-avatar { width:44px; height:44px; border-radius:50%; object-fit:cover; border:2px solid var(--dc-border); flex-shrink:0; }
  .avatar-placeholder { width:44px; height:44px; border-radius:50%; background:var(--dc-bg-3); border:2px solid var(--dc-border); flex-shrink:0; display:flex; align-items:center; justify-content:center; }
  .avatar-placeholder .dc-icon { color:var(--dc-text-3); }
  .photo-preview-wrap { text-align:center; margin-bottom:16px; }
  .photo-preview { width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid var(--dc-border-2); display:block; margin:0 auto 8px; }
  .upload-zone {
    border:2px dashed var(--dc-border-2); border-radius:var(--dc-radius-lg);
    padding:20px; text-align:center; cursor:pointer; position:relative;
    transition:border-color var(--dc-t-fast), background var(--dc-t-fast);
  }
  .upload-zone:hover { border-color:var(--dc-accent); background:var(--dc-accent-glow); }
  .upload-zone input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
  .upload-zone .dc-icon { color:var(--dc-text-3); display:block; margin:0 auto 6px; }
</style>
</head>
<body>

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo"><i class="dc-icon dc-icon-hospital dc-icon-md"></i> Medi<span>Book</span></div>
  <div class="dc-sidebar__section">Main</div>
  <a href="dashboard.php"    class="dc-sidebar__link"><i class="dc-icon dc-icon-bar-chart dc-icon-sm"></i> Dashboard</a>
  <a href="appointments.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Appointments</a>
  <a href="doctors.php"      class="dc-sidebar__link active"><i class="dc-icon dc-icon-stethoscope dc-icon-sm"></i> Doctors</a>
  <a href="slots.php"        class="dc-sidebar__link"><i class="dc-icon dc-icon-clock dc-icon-sm"></i> Time Slots</a>
  <a href="qr-scanner.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-qr-code dc-icon-sm"></i> QR Scanner</a>
  <div class="dc-sidebar__section" style="margin-top:auto">Account</div>
  <a href="../index.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-globe dc-icon-sm"></i> View Site</a>
  <a href="logout.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<div class="dc-with-sidebar">
  <nav class="dc-nav">
    <div class="dc-nav__brand" style="font-size:1rem;font-weight:600">Doctors</div>
    <button class="dc-btn dc-btn-primary dc-btn-sm" onclick="openAddModal()"
            style="background:var(--dc-accent);border-color:var(--dc-accent)">
      <i class="dc-icon dc-icon-plus dc-icon-sm"></i> Add Doctor
    </button>
  </nav>

  <div class="dc-container dc-section">
    <div class="dc-flex-between dc-mb-lg">
      <div>
        <h1 class="dc-h2">Doctors</h1>
        <p class="dc-body" style="color:var(--dc-text-2)">Manage clinic doctors, specializations and consultation fees.</p>
      </div>
    </div>

    <div class="dc-card-solid" id="doctors-table">
      <div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-refresh dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__text" style="margin-top:8px">Loading…</div></div>
    </div>
  </div>
</div>

<!-- Add / Edit Modal -->
<div class="dc-modal-overlay" id="modal-doctor">
  <div class="dc-modal" style="max-width:580px">
    <div class="dc-modal__header">
      <h3 class="dc-h3" id="modal-title">Add Doctor</h3>
      <button class="dc-modal__close" data-modal-close="modal-doctor"><i class="dc-icon dc-icon-x dc-icon-sm"></i></button>
    </div>
    <form id="doctor-form" enctype="multipart/form-data">
      <input type="hidden" id="edit-id" value="">

      <div class="photo-preview-wrap">
        <img id="photo-preview" class="photo-preview" src="https://ui-avatars.com/api/?name=Doctor&size=80&background=18181f&color=9898b0" alt="">
        <div class="upload-zone">
          <i class="dc-icon dc-icon-upload dc-icon-lg"></i>
          <div style="font-size:0.875rem;color:var(--dc-text-2)">Click to upload photo</div>
          <div class="dc-caption dc-text-dim" style="margin-top:2px">JPG, PNG or WebP · max 5MB</div>
          <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/png,image/webp">
        </div>
      </div>

      <div class="dc-grid dc-grid-2" style="gap:14px;margin-bottom:14px">
        <div class="dc-form-group">
          <label class="dc-label-field">Full Name *</label>
          <input type="text" class="dc-input" id="f-name" name="name" placeholder="Dr. Jane Smith" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Department *</label>
          <select class="dc-select" id="f-dept" name="department_id" required>
            <option value="">Select department</option>
            <?php foreach ($departments as $dep): ?>
            <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="dc-form-group" style="margin-bottom:14px">
        <label class="dc-label-field">Specialization *</label>
        <input type="text" class="dc-input" id="f-spec" name="specialization" placeholder="e.g. Interventional Cardiology" required>
      </div>
      <div class="dc-form-group" style="margin-bottom:14px">
        <label class="dc-label-field">Bio</label>
        <textarea class="dc-textarea" id="f-bio" name="bio" rows="3" placeholder="Doctor's background and expertise…"></textarea>
      </div>
      <div class="dc-grid dc-grid-2" style="gap:14px;margin-bottom:20px">
        <div class="dc-form-group">
          <label class="dc-label-field">Experience (years) *</label>
          <input type="number" class="dc-input" id="f-exp" name="experience_years" min="1" max="50" placeholder="e.g. 12" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Consultation Fee ($) *</label>
          <input type="number" class="dc-input" id="f-fee" name="consultation_fee" min="1" step="0.01" placeholder="e.g. 150.00" required>
        </div>
      </div>
      <div class="dc-flex dc-gap-sm">
        <button type="submit" class="dc-btn dc-btn-primary dc-btn-full" id="btn-save-doctor"
                style="background:var(--dc-accent);border-color:var(--dc-accent)">
          <i class="dc-icon dc-icon-check dc-icon-sm"></i> Save Doctor
        </button>
        <button type="button" class="dc-btn dc-btn-ghost" data-modal-close="modal-doctor">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div class="dc-modal-overlay" id="modal-delete">
  <div class="dc-modal" style="max-width:400px">
    <div class="dc-modal__header">
      <h3 class="dc-h3">Remove Doctor</h3>
      <button class="dc-modal__close" data-modal-close="modal-delete"><i class="dc-icon dc-icon-x dc-icon-sm"></i></button>
    </div>
    <p class="dc-body dc-mb">Are you sure you want to remove <strong id="delete-name"></strong>? Their appointment history will remain intact.</p>
    <div class="dc-flex dc-gap-sm">
      <button class="dc-btn dc-btn-danger dc-btn-full" id="btn-confirm-delete">
        <i class="dc-icon dc-icon-trash dc-icon-sm"></i> Remove Doctor
      </button>
      <button class="dc-btn dc-btn-ghost" data-modal-close="modal-delete">Cancel</button>
    </div>
  </div>
</div>

<script src="../../../core/ui/devcore.js"></script>
<script src="../../../core/utils/helpers.js"></script>
<script>
const API = '<?= $apiBase ?>';
let deletingId = null;

function esc(s) {
  if (window.DCHelpers && typeof window.DCHelpers.escHtml === 'function') {
    return window.DCHelpers.escHtml(s);
  }
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function loadDoctors() {
  try {
    const res = await DC.get(`${API}/doctors.php`);
    const docs = res.data || [];
    if (!docs.length) {
      document.getElementById('doctors-table').innerHTML =
        '<div class="dc-empty dc-empty-md"><i class="dc-icon dc-icon-stethoscope dc-icon-2xl dc-empty__icon"></i><div class="dc-empty__title" style="margin-top:10px">No Doctors Yet</div><p class="dc-empty__text">Add your first doctor to get started.</p></div>';
      return;
    }
    let html = `<div class="dc-table-wrap"><table class="dc-table"><thead><tr>
      <th>Doctor</th><th>Department</th><th>Specialization</th><th>Experience</th><th>Fee</th><th>Actions</th>
    </tr></thead><tbody>`;
    docs.forEach(d => {
      const avatarHtml = d.photo_url
        ? `<img class="doctor-avatar" src="${esc(d.photo_url)}" alt="">`
        : `<div class="avatar-placeholder"><i class="dc-icon dc-icon-user dc-icon-sm"></i></div>`;
      html += `<tr>
        <td><div class="dc-flex dc-gap-sm" style="align-items:center">${avatarHtml}<div><strong>${esc(d.name)}</strong></div></div></td>
        <td>${esc(d.department_name)}</td>
        <td>${esc(d.specialization)}</td>
        <td><span style="display:flex;align-items:center;gap:4px"><i class="dc-icon dc-icon-trophy dc-icon-xs" style="color:var(--dc-warning)"></i>${esc(d.experience_years)} yrs</span></td>
        <td><strong style="color:var(--dc-accent-2)">$${parseFloat(d.consultation_fee).toFixed(2)}</strong></td>
        <td><div class="dc-flex dc-gap-sm">
          <button class="dc-btn dc-btn-ghost dc-btn-sm" onclick='openEditModal(${JSON.stringify(d).replace(/'/g,"&#39;")})'>
            <i class="dc-icon dc-icon-edit dc-icon-sm"></i> Edit
          </button>
          <button class="dc-btn dc-btn-danger dc-btn-sm" onclick="confirmDelete(${d.id},'${esc(d.name)}')">
            <i class="dc-icon dc-icon-trash dc-icon-sm"></i>
          </button>
        </div></td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('doctors-table').innerHTML = html;
  } catch(e) { Toast.error('Failed to load doctors: ' + e.message); }
}

function openAddModal() {
  document.getElementById('modal-title').textContent = 'Add Doctor';
  document.getElementById('edit-id').value = '';
  document.getElementById('doctor-form').reset();
  document.getElementById('photo-preview').src = 'https://ui-avatars.com/api/?name=Doctor&size=80&background=18181f&color=9898b0';
  Modal.open('modal-doctor');
}

function openEditModal(d) {
  document.getElementById('modal-title').textContent = 'Edit Doctor';
  document.getElementById('edit-id').value    = d.id;
  document.getElementById('f-name').value     = d.name;
  document.getElementById('f-dept').value     = d.department_id;
  document.getElementById('f-spec').value     = d.specialization;
  document.getElementById('f-bio').value      = d.bio || '';
  document.getElementById('f-exp').value      = d.experience_years;
  document.getElementById('f-fee').value      = d.consultation_fee;
  document.getElementById('photo-preview').src = d.photo_url
    || `https://ui-avatars.com/api/?name=${encodeURIComponent(d.name)}&size=80&background=18181f&color=9898b0`;
  Modal.open('modal-doctor');
}

function confirmDelete(id, name) {
  deletingId = id;
  document.getElementById('delete-name').textContent = name;
  Modal.open('modal-delete');
}

document.getElementById('photo-input').addEventListener('change', function() {
  const f = this.files[0]; if (!f) return;
  const r = new FileReader(); r.onload = e => { document.getElementById('photo-preview').src = e.target.result; }; r.readAsDataURL(f);
});

document.getElementById('doctor-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-save-doctor');
  const id  = document.getElementById('edit-id').value;
  DCForm.setLoading(btn, true);
  try {
    const formData = new FormData(this);
    const url    = id ? `${API}/doctors.php?id=${id}` : `${API}/doctors.php`;
    const method = id ? 'PUT' : 'POST';
    const res = await fetch(url, { method, body: formData });
    const data = await res.json();
    if (data.status !== 'success') throw new Error(data.message);
    Modal.close('modal-doctor');
    Toast.success(id ? 'Doctor updated' : 'Doctor added');
    loadDoctors();
  } catch(err) { Toast.error(err.message || 'Save failed'); } finally { DCForm.setLoading(btn, false); }
});

document.getElementById('btn-confirm-delete').addEventListener('click', async () => {
  if (!deletingId) return;
  const btn = document.getElementById('btn-confirm-delete');
  DCForm.setLoading(btn, true);
  try {
    await DC.delete(`${API}/doctors.php?id=${deletingId}`);
    Modal.close('modal-delete'); Toast.success('Doctor removed'); loadDoctors();
  } catch(e) { Toast.error(e.message); } finally { DCForm.setLoading(btn, false); deletingId = null; }
});

loadDoctors();
</script>
</body>
</html>