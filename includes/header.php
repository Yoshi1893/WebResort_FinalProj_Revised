<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>9 Waves Events Place</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-modal">
    <div class="confirm-brand">Action Required</div>
    <h2 class="confirm-title" id="confirmTitle">Confirm Action</h2>
    <p class="confirm-msg" id="confirmMsg">Are you sure you want to proceed?</p>
    <div class="confirm-actions">
      <button class="confirm-btn confirm-no" id="confirmNo">Keep Inquiry</button>
      <button class="confirm-btn confirm-yes" id="confirmYes">Yes, Continue</button>
    </div>
  </div>
</div>

<nav id="navbar">
  <a href="#" class="nav-logo"><img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img"><span>9 Waves Events Place</span></a>
  <ul class="nav-links" id="navLinks">
    <li><a href="#about">About</a></li>
    <li><a href="#venues">Venues</a></li>
    <li><a href="#packages">Packages</a></li>
    <li><a href="#gallery">Gallery</a></li>
    <li><a href="#contact">Inquiry</a></li>
    <li><a href="login.php">Login</a></li>
    <li><a href="register.php" class="nav-cta">Register</a></li>
  </ul>
  <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
</nav>

<div class="mobile-menu" id="mobileMenu">
  <button class="mobile-close" id="mobileClose">X</button>
  <a href="#about" onclick="closeMobile()">About</a>
  <a href="#venues" onclick="closeMobile()">Venues</a>
  <a href="#packages" onclick="closeMobile()">Packages</a>
  <a href="#gallery" onclick="closeMobile()">Gallery</a>
  <a href="#contact" onclick="closeMobile()">Start Inquiry</a>
  <a href="login.php">Login</a>
  <a href="register.php">Register</a>
</div>
