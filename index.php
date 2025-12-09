<?php
include 'connection.php';
// Fetch settings from the database
$sql = "SELECT * FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result ? $result->fetch_assoc() : [];
$system_name = $settings['system_name'] ?? 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
$logo = !empty($settings['logo']) ? 'webfonts/' . $settings['logo'] : 'REPORT.png';
$contact_email = $settings['contact_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="<?php echo htmlspecialchars($system_name); ?>. Search, create and manage archive reports."
    />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($logo); ?>" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="css/all.min.css" />
    <link rel="stylesheet" href="css/fontawesome.min.css" />
    <title><?php echo htmlspecialchars($system_name); ?> — Home</title>
    <style>
      .image-container {
        min-height: 420px;
        background: linear-gradient(90deg, #ffa700 0%, #bd000a 100%);
        background-image: url(film\ archive.jpg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        position: relative;
        width: 100%;
      }
      .image-container::after {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.35);
        z-index: 0;
      }
      .hero {
        position: relative;
        z-index: 1;
        text-align: left;
        max-width: 700px;
        padding: 60px 40px;
        margin-left: 40px;
      }
      .hero-title {
        font-size: 2.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #fff;
        margin-bottom: 18px;
        line-height: 1.1;
        text-shadow: 0 2px 8px rgba(0,0,0,0.18);
      }
      .hero-desc {
        font-size: 1.25rem;
        color: #f0f0f0;
        margin-bottom: 32px;
        text-shadow: 0 1px 4px rgba(0,0,0,0.12);
      }
      .hero-ctas {
        margin-top: 10px;
      }
      .btn.cta {
        background: #ffa700;
        color: #6a1b9a;
        font-weight: 700;
        border-radius: 8px;
        padding: 14px 32px;
        font-size: 1.1rem;
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: background 0.2s, color 0.2s;
        cursor: pointer;
      }
      .btn.cta:hover {
        background: #ffd54f;
        color: #d32f2f;
      }
      .features {
        padding: 48px 20px 36px 20px;
        text-align: center;
        background: #fff;
      }
      .features-title {
        font-size: 2rem;
        margin-bottom: 24px;
        color: #d32f2f;
        font-weight: 700;
      }
      .features .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 24px;
        max-width: 1000px;
        margin: 18px auto;
      }
      .card {
        padding: 28px 18px 22px 18px;
        border-radius: 12px;
        background: #f7f7f7;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        transition: box-shadow 0.2s, transform 0.2s;
        position: relative;
        text-align: left;
      }
      .card:hover {
        box-shadow: 0 6px 24px rgba(211,47,47,0.12);
        transform: translateY(-4px) scale(1.03);
      }
      .card h3 {
        font-size: 1.3rem;
        margin-bottom: 8px;
        color: #d32f2f;
        font-weight: 700;
      }
      .card p {
        font-size: 1rem;
        color: #444;
      }
      .card hr {
        border: none;
        border-top: 2px solid #ffa700;
        margin: 10px 0 16px 0;
      }
      footer {
        position: static;
        z-index: 1;
        clear: both;
      }
      /* Glassmorphism Sliding Login Panel Styles */
      .login-panel {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(30, 30, 30, 0.25);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.4s;
      }
      .login-panel.active {
        opacity: 1;
        pointer-events: auto;
      }
      .login-panel-content {
        background: white;
        border-radius: 24px;
        box-shadow:
          0 4px 32px 0 rgba(60,60,60,0.18),
          0 1.5px 8px 0 rgba(211,47,47,0.10);
        padding: 36px 32px 28px 32px;
        min-width: 320px;
        max-width: 370px;
        width: 92vw;
        position: relative;
        animation: slideIn 0.5s cubic-bezier(.77,0,.18,1) forwards;
        display: flex;
        flex-direction: column;
        align-items: center;
        border: 1.5px solid rgba(255,255,255,0.35);
        backdrop-filter: blur(16px);
      }
      @keyframes slideIn {
        from { transform: translateY(-60px) scale(0.95); opacity: 0; }
        to { transform: translateY(0) scale(1); opacity: 1; }
      }
      .close-btn {
        position: absolute;
        top: 16px;
        right: 18px;
        background: none;
        border: none;
        font-size: 2rem;
        color: #444444;
        cursor: pointer;
        z-index: 2;
        transition: color 0.2s;
        border-radius: 50%;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .close-btn:hover {
        color: #444;
        background: #44444449;
      }
      .login-panel-content h2 {
        margin-bottom: 22px;
        color: #d32f2f;
        font-size: 1.8rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-shadow: 0 2px 8px rgba(211,47,47,0.08);
      }
      .login-panel-content .btn.cta {
        min-width: 150px;
        margin: 0 10px;
        font-size: 1.12rem;
        padding: 20px;
        border-radius: 32px;
        box-shadow: 0 2px 12px rgba(211,47,47,0.10), 0 1px 4px rgba(255,167,0,0.10);
        border: none;
        font-weight: 700;
        letter-spacing: 1px;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s, filter 0.2s;
        outline: none;
      }
      .login-panel-content .btn.cta:first-child {
        background: linear-gradient(90deg, #d32f2f 60%, #ffa700 100%);
        color: #fff;
      }
      .login-panel-content .btn.cta:last-child {
        background: linear-gradient(90deg, #ffa700 60%, #d32f2f 100%);
        color: #fff;
      }
      .login-panel-content .btn.cta:hover {
        filter: brightness(1.08) saturate(1.2);
        box-shadow: 0 6px 24px rgba(211,47,47,0.16);
        background: linear-gradient(90deg, #d32f2f 40%, #ffa700 100%);
        color: #fff;
      }
      .login-panel-content > div {
        width: 100%;
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-top: 10px;
        flex-wrap: wrap;
      }
      .login-panel-content.split-panel {
        display: flex;
        flex-direction: row;
        min-width: 420px;
        max-width: 600px;
        padding: 0;
        overflow: hidden;
        border-radius: 24px;
      }
      .login-panel-left {
        flex: 1 1 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.18);
        border-top-left-radius: 24px;
        border-bottom-left-radius: 24px;
        padding: 32px 18px;
        min-width: 180px;
      }
      .login-panel-img {
        max-width: 150px;
        max-height: 150px;
        border-radius: 100px;
        box-shadow: 0 2px 15px rgba(60,60,60,0.10);
        background: #fff;
        border: 2px #44444443;
        object-fit: contain;
      }
      .login-panel-right {
        flex: 2 1 260px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 38px 32px 28px 32px;
        background: rgba(255,255,255,0.22);
        border-top-right-radius: 24px;
        border-bottom-right-radius: 24px;
      }
      .login-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.5rem;
        font-weight: 700;
        color: #d32f2f;
        margin-bottom: 22px;
        letter-spacing: 1px;
        text-shadow: 0 2px 8px rgba(211,47,47,0.08);
      }
      .lock-icon {
        color: #ffa700;
        font-size: 1.3em;
        margin-right: 4px;
      }
      .role-btns {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
      }
      .role-btns .btn.cta {
        min-width: 0;
        width: 100%;
        margin: 0;
        font-size: 1.08rem;
        padding: 16px 0;
        border-radius: 8px;
        box-shadow: 0 2px 12px rgba(211,47,47,0.10), 0 1px 4px rgba(255,167,0,0.10);
        border: none;
        font-weight: 700;
        letter-spacing: 1px;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s, filter 0.2s;
        outline: none;
        text-align: center;
        text-transform: uppercase;
      }
      .role-btns .btn.cta:first-child {
        background:#003D73;
        color: #fff;
      }
      .role-btns .btn.cta:last-child {
        background: #003D73;
        color: #fff;
      }
      .role-btns .btn.cta:hover {
        filter: brightness(1.08) saturate(1.2);
        box-shadow: 0 6px 24px hsla(236, 100%, 61%, 0.16);
        background: #ffff;
        color: #003D73;
      }
      @media (max-width: 700px) {
        .login-panel-content.split-panel {
          flex-direction: column;
          min-width: 0;
          max-width: 98vw;
        }
        .login-panel-left, .login-panel-right {
          border-radius: 0;
          padding: 24px 8px;
        }
        .login-panel-left {
          border-top-left-radius: 24px;
          border-top-right-radius: 24px;
          border-bottom-left-radius: 0;
          border-bottom-right-radius: 0;
        }
        .login-panel-right {
          border-bottom-left-radius: 24px;
          border-bottom-right-radius: 24px;
          border-top-left-radius: 0;
          border-top-right-radius: 0;
        }
      }
    </style>
  </head>
  <body>
    <a
      class="skip-link"
      href="#main"
      style="
        position: absolute;
        left: -999px;
        top: auto;
        width: 1px;
        height: 1px;
        overflow: hidden;
      "
      >Skip to content</a
    >

    <header role="banner">
      <nav class="navbar" role="navigation" aria-label="Primary">
    <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px; color:#f7f7f7; text-decoration: none; font-weight: bold; font-size: 20px;">
        <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" style="height: 40px; width: auto; border: 1px solid #fff; border-radius:30px; background-color: #f7f7f7;">
        <?php echo htmlspecialchars($system_name); ?>
      </a>
    <ul class="nav-links">
      <li><a href="About.php">ABOUT US</a></li>
      <li><a href="user/signup.php">SIGN UP</a></li>
      <li><a href="#" id="login-trigger">LOGIN</a></li>
    </ul>
  </nav>

    </header>

    <!-- Sliding Login Panel -->
<div id="login-panel" class="login-panel">
  <div class="login-panel-content split-panel">
    <button class="close-btn" id="close-login-panel" title="Close">&times;</button>
    <div class="login-panel-left">
      <img src="<?php echo htmlspecialchars($logo); ?>" alt="BFP Archives" class="login-panel-img" />
    </div>
    <div class="login-panel-right">
      <div class="login-title"><span class="lock-icon"><i class="fas fa-lock"></i></span> LOGIN</div>
      <div class="role-btns">
        <a href="Admin/adminlogin.php" class="btn cta">LOGIN AS ADMIN</a>
        <a href="user/userlogin.php" class="btn cta">LOGIN AS STAFF</a>
      </div>
    </div>
  </div>
</div>

    <main id="main" role="main">
      <section class="image-container" aria-label="Hero">
        <div class="hero">
          <div class="hero-title"><?php echo htmlspecialchars($system_name); ?></div>
          <div class="hero-desc">Search, create and manage official reports securely and efficiently.</div>

        </div>
      </section>

      <section class="features">
        <div class="features-title">WHAT YOU CAN DO</div>
        <div class="cards">
          <article class="card">
            <h3>SEARCH ARCHIVES</h3>
            <hr />
          <p>Quickly find documents by name, date or tags.</p>
          </article>
          <article class="card">
            <h3>CREATE REPORTS</h3>
            <hr>
            <p> Create and Manage Reports. </p>
          </article>
          <article class="card">
            <h3>REPORTS</h3>
            <hr>
            <p>Generate detailed reports of archived documents.</p>
          </article>
        </div>
      </section>

      <section class="welcome-box" aria-label="Welcome">
        <h2><?php echo htmlspecialchars($system_name); ?></h2>
        <hr />
        <p>
          We are glad to have you here! Explore our services and learn more
          about what we offer.
        </p>
      </section>
    </main>

    <footer role="contentinfo">
      <div class="footer-content">
        <p>&copy; Copyright 2025. All rights reserved. <?php if($contact_email) echo 'Contact: ' . htmlspecialchars($contact_email); ?></p>
      </div>
    </footer>
    <script>
  // Show login panel when LOGIN is clicked
  document.addEventListener('DOMContentLoaded', function() {
    var loginTrigger = document.getElementById('login-trigger');
    var loginPanel = document.getElementById('login-panel');
    var closeBtn = document.getElementById('close-login-panel');
    if (loginTrigger && loginPanel && closeBtn) {
      loginTrigger.addEventListener('click', function(e) {
        e.preventDefault();
        loginPanel.classList.add('active');
      });
      closeBtn.addEventListener('click', function() {
        loginPanel.classList.remove('active');
      });
      // Optional: close panel when clicking outside content
      loginPanel.addEventListener('click', function(e) {
        if (e.target === loginPanel) {
          loginPanel.classList.remove('active');
        }
      });
    }
  });
</script>

  </body>
</html>
