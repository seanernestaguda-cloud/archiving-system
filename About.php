<?php
include 'connection.php';
// Fetch settings from the database

$sql = "SELECT * FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result ? $result->fetch_assoc() : [];
$system_name = $settings['system_name'] ?? 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
$logo = !empty($settings['logo']) ? 'webfonts/' . $settings['logo'] : 'REPORT.png';
$contact_email = $settings['contact_email'] ?? '';
$about_content = $settings['about'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About Us — Bureau of Fire Protection Archiving System</title>
    <link rel="icon" type="image/png" href="REPORT.png" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="css/all.min.css" />
    <link rel="stylesheet" href="css/fontawesome.min.css" />
    <style>
      .about-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 30px 20px;
        background: #f7f7f7;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      }
      .about-container h1 {
        font-size: 28px;
        margin-bottom: 18px;
        color: #d32f2f;
      }
      .about-container p {
        margin-bottom: 16px;
        font-size: 17px;
        color: #333;
      }
    </style>
  </head>
  <body>
    <header role="banner">
      <nav class="navbar" role="navigation" aria-label="Primary">
    <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px; color:#f7f7f7; text-decoration: none; font-weight: bold; font-size: 20px;">
        <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" style="height: 40px; width: auto; border: 1px solid #fff; border-radius:30px; background-color: #f7f7f7;">
        <?php echo htmlspecialchars($system_name); ?>
      </a>
        <ul class="nav-links">
          <li><a href="index.php">HOME</a></li>
          <li><a href="user/signup.php">SIGN UP</a></li>
          <li><a href="login.html">LOGIN</a></li>

        </ul>
      </nav>
      <nav class="navbar2">
      </nav>
    </header>
    <main>
      <div class="about-container">
        <h1>About Us</h1>
        <?php
          // Output About content from settings, allow HTML
          echo !empty($about_content)
            ? $about_content
            : '<p>The Bureau of Fire Protection Archiving System is designed to securely manage, store, and retrieve official fire protection reports and documents. Our platform streamlines the archiving process, ensuring efficiency, accuracy, and accessibility for authorized personnel.</p><p><strong>Features:</strong><ul><li>Search and retrieve archived reports quickly</li><li>Create and manage new archive entries</li><li>Generate and export comprehensive reports</li><li>Role-based access for employees and administrators</li></ul></p><p>We are committed to providing a reliable and user-friendly system to support the Bureau of Fire Protection\'s mission of safeguarding lives and property.</p>';
        ?>
      </div>
    </main>
    <footer>
      <div class="footer-content">
        <p>&copy; Copyright 2025. All rights reserved.</p>
      </div>
    </footer>
  </body>
</html>