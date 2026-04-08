<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Account - 9 Waves Events Place</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="account-page">
  <div class="customer-topbar account-topbar">
    <div class="customer-topbar-title"><img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img">9 Waves <em>My Account</em></div>
    <div class="account-topbar-actions">
      <a href="index.php" class="cust-signout">Back to Website</a>
    </div>
  </div>

  <main class="customer-body account-body">
    <section class="panel-welcome account-hero">
      <h2>Hello, <em id="accountGreeting">Guest</em></h2>
      <p>Update your profile, review your inquiry history, and manage your account from one place.</p>
    </section>

    <section class="cust-cards account-grid">
      <article class="cust-card account-profile-card">
        <div class="cust-card-title">Profile Details</div>
        <div class="cust-card-sub">Customize your picture and contact information</div>
        <div class="account-avatar-wrap">
          <div class="account-avatar" id="accountAvatarPreview">MS</div>
          <div class="account-avatar-actions">
            <label class="panel-btn panel-btn-gold account-upload-btn" for="profileImageInput">Upload Picture</label>
            <button class="btn-outline account-remove-photo" id="removePhotoBtn" type="button">Remove Picture</button>
            <input type="file" id="profileImageInput" accept="image/*" hidden>
            <div class="account-helper-text">Frontend preview only for now. Backend upload will be wired later.</div>
          </div>
        </div>
        <form class="account-form" id="profileForm">
          <div class="form-row">
            <div class="form-group">
              <label for="accountFirstName">First Name</label>
              <input type="text" id="accountFirstName" required>
            </div>
            <div class="form-group">
              <label for="accountLastName">Last Name</label>
              <input type="text" id="accountLastName" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="accountEmail">Email Address</label>
              <input type="email" id="accountEmail" readonly>
            </div>
            <div class="form-group">
              <label for="accountPhone">Phone Number</label>
              <input type="tel" id="accountPhone" required>
            </div>
          </div>
          <div class="account-meta">
            <div class="profile-row"><span class="profile-label">Role</span><span class="profile-value" id="accountRole">Customer</span></div>
            <div class="profile-row"><span class="profile-label">Joined</span><span class="profile-value" id="accountCreatedAt">2026-04-08</span></div>
          </div>
          <button class="auth-btn" type="submit">Save Profile</button>
        </form>
      </article>

      <article class="cust-card">
        <div class="cust-card-title">Change Password</div>
        <div class="cust-card-sub">Update your password through a separate secure action</div>
        <form class="account-form" id="passwordForm">
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <input type="password" id="currentPassword" placeholder="Enter current password" required>
          </div>
          <div class="form-group">
            <label for="newPassword">New Password</label>
            <input type="password" id="newPassword" placeholder="Minimum 6 characters" required>
          </div>
          <div class="form-group">
            <label for="confirmNewPassword">Confirm New Password</label>
            <input type="password" id="confirmNewPassword" placeholder="Re-enter new password" required>
          </div>
          <button class="auth-btn" type="submit">Update Password</button>
        </form>
      </article>
    </section>

    <section class="cust-card account-history-card">
      <div class="cust-card-title">Inquiry History</div>
      <div class="cust-card-sub">Compact list with expandable inquiry details</div>
      <div class="customer-inquiry-list account-inquiry-list" id="accountInquiryList"></div>
    </section>

    <section class="cust-cta-card account-archive-card">
      <h3>Archive This <em>Account</em></h3>
      <p>Archiving hides the account from active use. Inquiries remain preserved for records, based on the agreed frontend flow.</p>
      <div class="account-archive-actions">
        <button class="panel-btn panel-btn-outline-white" id="archiveAccountBtn" type="button">Archive My Account</button>
      </div>
    </section>
  </main>

  <div class="toast" id="toast"></div>

  <script src="assets/js/mock-data.js"></script>
  <script src="assets/js/account.js"></script>
</body>
</html>
