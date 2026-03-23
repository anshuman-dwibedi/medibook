<?php
require_once '../../core/bootstrap.php';

$db          = Database::getInstance();
$departments = $db->fetchAll(
    "SELECT dep.*, COUNT(d.id) AS doctor_count
     FROM departments dep
     LEFT JOIN doctors d ON d.department_id = dep.id AND d.active = 1
     GROUP BY dep.id ORDER BY dep.name"
);
$totalDoctors = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM doctors WHERE active = 1")['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>MediBook — Book Your Appointment Online</title>
<meta name="description" content="Book appointments with specialist doctors across 6 departments. Real-time slot availability and instant QR confirmation.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<link rel="stylesheet" href="../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }

  .hero { padding:80px 0 60px; text-align:center; }
  .hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:var(--dc-accent-glow); border:1px solid rgba(14,165,233,0.3);
    border-radius:var(--dc-radius-full); padding:6px 16px;
    font-size:0.8rem; font-weight:600; color:var(--dc-accent-2); margin-bottom:20px;
  }
  .hero-stats { display:flex; gap:40px; justify-content:center; margin-top:56px; padding-top:40px; border-top:1px solid var(--dc-border); flex-wrap:wrap; }
  .hero-stat { text-align:center; }
  .hero-stat-num { font-family:var(--dc-font-display); font-size:2rem; font-weight:800; color:var(--dc-accent-2); }
  .hero-stat-lbl { font-size:0.8rem; color:var(--dc-text-3); margin-top:4px; }

  .dept-card {
    background:var(--dc-bg-glass); border:1px solid var(--dc-border);
    border-radius:var(--dc-radius-lg); padding:24px; display:flex;
    flex-direction:column; text-decoration:none;
    transition:border-color var(--dc-t-med), box-shadow var(--dc-t-med), transform var(--dc-t-med);
  }
  .dept-card:hover { border-color:var(--dc-accent); transform:translateY(-3px); box-shadow:0 8px 32px rgba(14,165,233,0.12); }
  .dept-icon-wrap {
    width:48px; height:48px; border-radius:var(--dc-radius);
    background:var(--dc-accent-glow); border:1px solid rgba(14,165,233,0.2);
    display:flex; align-items:center; justify-content:center; margin-bottom:14px; flex-shrink:0;
  }
  .dept-icon-wrap .dc-icon { color:var(--dc-accent-2); }

  .steps { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:24px; }
  .step { text-align:center; padding:28px 20px; }
  .step-num {
    width:44px; height:44px; border-radius:50%;
    background:rgba(14,165,233,0.1); border:2px solid rgba(14,165,233,0.25);
    display:flex; align-items:center; justify-content:center;
    font-family:var(--dc-font-display); font-weight:800; color:var(--dc-accent-2);
    margin:0 auto 14px;
  }
  .step-icon { display:flex; justify-content:center; margin-bottom:12px; }
  .step-icon .dc-icon { color:var(--dc-accent-2); }

  .feature-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px; }
  .feature-item { padding:22px; border-radius:var(--dc-radius-lg); border:1px solid var(--dc-border); background:var(--dc-bg-glass); }
  .feature-icon {
    width:40px; height:40px; border-radius:var(--dc-radius);
    background:var(--dc-accent-glow); display:flex; align-items:center;
    justify-content:center; margin-bottom:12px;
  }
  .feature-icon .dc-icon { color:var(--dc-accent-2); }

  .section { padding:72px 0; }
  .section-alt { background:rgba(255,255,255,0.02); border-top:1px solid var(--dc-border); border-bottom:1px solid var(--dc-border); }
</style>
</head>
<body>

<nav class="dc-nav">
  <div class="dc-nav__brand">
    <i class="dc-icon dc-icon-hospital dc-icon-md"></i>
    Medi<span>Book</span>
  </div>
  <div class="dc-nav__links">
    <a href="#departments" class="dc-nav__link dc-hide-mobile">Departments</a>
    <a href="appointment.php" class="dc-nav__link">
      <i class="dc-icon dc-icon-clipboard dc-icon-sm"></i> My Appointment
    </a>
    <a href="book.php" class="dc-btn dc-btn-primary dc-btn-sm"
       style="background:var(--dc-accent);border-color:var(--dc-accent)">
      <i class="dc-icon dc-icon-calendar dc-icon-sm"></i> Book Now
    </a>
  </div>
</nav>

<section class="hero">
  <div class="dc-container dc-animate-fade-up">
    <div class="hero-badge">
      <div class="dc-live__dot" style="width:8px;height:8px;background:var(--dc-accent);border-radius:50%;animation:dc-pulse 1.5s ease infinite;flex-shrink:0"></div>
      Accepting Appointments Online
    </div>
    <h1 class="dc-h1" style="margin-bottom:16px">
      Your Health,<br>Our <span style="color:var(--dc-accent)">Priority</span>
    </h1>
    <p class="dc-body" style="max-width:520px;margin:0 auto 36px;color:var(--dc-text-2)">
      Book appointments with top specialists across 6 departments. Real-time slot availability, instant confirmation and a QR appointment card.
    </p>
    <div class="dc-flex" style="gap:14px;justify-content:center;flex-wrap:wrap">
      <a href="book.php" class="dc-btn dc-btn-primary dc-btn-lg"
         style="background:var(--dc-accent);border-color:var(--dc-accent)">
        <i class="dc-icon dc-icon-calendar dc-icon-md"></i> Book an Appointment
      </a>
      <a href="appointment.php" class="dc-btn dc-btn-ghost dc-btn-lg">
        <i class="dc-icon dc-icon-search dc-icon-md"></i> Track My Appointment
      </a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><div class="hero-stat-num"><?= $totalDoctors ?>+</div><div class="hero-stat-lbl">Specialist Doctors</div></div>
      <div class="hero-stat"><div class="hero-stat-num">6</div><div class="hero-stat-lbl">Medical Departments</div></div>
      <div class="hero-stat"><div class="hero-stat-num">98%</div><div class="hero-stat-lbl">Patient Satisfaction</div></div>
      <div class="hero-stat"><div class="hero-stat-num">24/7</div><div class="hero-stat-lbl">Online Booking</div></div>
    </div>
  </div>
</section>

<section class="section" id="departments">
  <div class="dc-container">
    <div class="dc-text-center dc-mb-lg">
      <div class="dc-page-header__eyebrow">Our Departments</div>
      <h2 class="dc-h2">Specialist Care Across Every Discipline</h2>
      <p class="dc-body dc-mt-sm" style="color:var(--dc-text-2)">Choose your department to browse doctors and book your appointment.</p>
    </div>
    <div class="dc-grid dc-grid-3 dc-stagger">
      <?php
      $deptIconMap = ['General Medicine'=>'stethoscope','Cardiology'=>'activity','Dermatology'=>'user','Orthopedics'=>'note','Pediatrics'=>'user','Dental'=>'note'];
      foreach ($departments as $dept):
        $icon = $deptIconMap[$dept['name']] ?? 'hospital';
      ?>
      <a href="book.php?dept=<?= $dept['id'] ?>" class="dept-card">
        <div class="dept-icon-wrap">
          <i class="dc-icon dc-icon-<?= $icon ?> dc-icon-md"></i>
        </div>
        <div class="dc-h4" style="margin-bottom:6px"><?= htmlspecialchars($dept['name']) ?></div>
        <div class="dc-caption" style="color:var(--dc-text-3);flex:1;margin-bottom:18px">
          <?= $dept['doctor_count'] ?> specialist<?= $dept['doctor_count'] != 1 ? 's' : '' ?> available
        </div>
        <div class="dc-btn dc-btn-ghost dc-btn-sm" style="width:100%;justify-content:center">
          Book Now <i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="dc-container">
    <div class="dc-text-center dc-mb-lg">
      <div class="dc-page-header__eyebrow">How It Works</div>
      <h2 class="dc-h2">Book in 3 Simple Steps</h2>
    </div>
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-icon"><i class="dc-icon dc-icon-user dc-icon-lg"></i></div>
        <h3 class="dc-h4" style="margin-bottom:8px">Choose Your Doctor</h3>
        <p class="dc-body" style="color:var(--dc-text-2)">Browse specialists by department. View profiles, experience and fees.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-icon"><i class="dc-icon dc-icon-calendar dc-icon-lg"></i></div>
        <h3 class="dc-h4" style="margin-bottom:8px">Pick a Time Slot</h3>
        <p class="dc-body" style="color:var(--dc-text-2)">See real-time availability. Slots update live — no double bookings, ever.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-icon"><i class="dc-icon dc-icon-qr-code dc-icon-lg"></i></div>
        <h3 class="dc-h4" style="margin-bottom:8px">Get Your QR Card</h3>
        <p class="dc-body" style="color:var(--dc-text-2)">Receive your confirmation with a QR code. Show at reception for instant check-in.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="dc-container">
    <div class="dc-text-center dc-mb-lg">
      <div class="dc-page-header__eyebrow">Why MediBook</div>
      <h2 class="dc-h2">Smart Features for Modern Healthcare</h2>
    </div>
    <div class="feature-grid">
      <div class="feature-item">
        <div class="feature-icon"><i class="dc-icon dc-icon-activity dc-icon-md"></i></div>
        <div class="dc-h4" style="margin-bottom:6px">Real-Time Availability</div>
        <p class="dc-body" style="color:var(--dc-text-2)">Slots update live across all browser sessions. Never book a slot already taken.</p>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="dc-icon dc-icon-qr-code dc-icon-md"></i></div>
        <div class="dc-h4" style="margin-bottom:6px">QR Appointment Card</div>
        <p class="dc-body" style="color:var(--dc-text-2)">Receive a scannable QR card. Show at reception for instant check-in, no paperwork.</p>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="dc-icon dc-icon-lock dc-icon-md"></i></div>
        <div class="dc-h4" style="margin-bottom:6px">Secure Token System</div>
        <p class="dc-body" style="color:var(--dc-text-2)">Each appointment has a unique 16-character token. Only you can access your details.</p>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="dc-icon dc-icon-clipboard dc-icon-md"></i></div>
        <div class="dc-h4" style="margin-bottom:6px">Live Status Updates</div>
        <p class="dc-body" style="color:var(--dc-text-2)">Track your appointment from Booked through Confirmed, In Progress to Completed.</p>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="dc-icon dc-icon-x dc-icon-md"></i></div>
        <div class="dc-h4" style="margin-bottom:6px">Easy Cancellation</div>
        <p class="dc-body" style="color:var(--dc-text-2)">Cancel up to 2 hours before your appointment with a single click.</p>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="dc-icon dc-icon-stethoscope dc-icon-md"></i></div>
        <div class="dc-h4" style="margin-bottom:6px"><?= $totalDoctors ?> Specialist Doctors</div>
        <p class="dc-body" style="color:var(--dc-text-2)">Choose from experienced specialists across all 6 medical departments.</p>
      </div>
    </div>
  </div>
</section>

<section style="padding:60px 0;background:linear-gradient(135deg,rgba(14,165,233,0.08),rgba(14,165,233,0.02));border-top:1px solid rgba(14,165,233,0.15)">
  <div class="dc-container dc-text-center">
    <h2 class="dc-h2" style="margin-bottom:10px">Ready to Book Your Appointment?</h2>
    <p class="dc-body dc-mb-lg" style="color:var(--dc-text-2)">Mon–Fri · 9:00 AM – 3:00 PM · Real-time slot availability</p>
    <a href="book.php" class="dc-btn dc-btn-primary dc-btn-lg"
       style="background:var(--dc-accent);border-color:var(--dc-accent)">
      <i class="dc-icon dc-icon-calendar dc-icon-md"></i> Book Appointment Now
    </a>
  </div>
</section>

<footer style="padding:28px 0;border-top:1px solid var(--dc-border);text-align:center">
  <div class="dc-container">
    <div class="dc-caption" style="color:var(--dc-text-3)">
      MediBook Clinic &middot; Part of the <strong>DevCore Portfolio Suite</strong>
      &middot; <a href="admin/login.php" style="color:var(--dc-text-3)">Admin</a>
    </div>
  </div>
</footer>

<script src="../../core/ui/devcore.js"></script>
</body>
</html>