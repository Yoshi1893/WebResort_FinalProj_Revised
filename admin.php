<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php';

if ($pdo) checkAdminAccess($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - 9 Waves Events Place</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page">
  <div class="admin-topbar admin-page-topbar">
    <div class="admin-topbar-title"><img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img">9 Waves <em>Admin</em></div>
    <div class="admin-topbar-right">
      <div class="admin-chip">
        <div class="admin-chip-avatar" id="adminAvatar">AD</div>
        <span id="adminName">Admin User</span>
      </div>
      <a href="index.php" class="panel-signout">Back to Website</a>
    </div>
  </div>

  <main class="admin-body">
    <section class="panel-welcome">
      <h2>Admin <em>Workspace</em></h2>
      <p>Manage frontend-ready package pricing, amenities, users, and inquiry logs from one page.</p>
    </section>

    <section class="admin-stats-row">
      <div class="admin-stat-card"><div class="admin-stat-num" id="statPackages">0</div><div class="admin-stat-lbl">Active Packages</div></div>
      <div class="admin-stat-card"><div class="admin-stat-num" id="statAmenities">0</div><div class="admin-stat-lbl">Active Amenities</div></div>
      <div class="admin-stat-card"><div class="admin-stat-num" id="statUsers">0</div><div class="admin-stat-lbl">Customers</div></div>
      <div class="admin-stat-card"><div class="admin-stat-num" id="statInquiries">0</div><div class="admin-stat-lbl">Inquiry Logs</div></div>
    </section>

    <section class="admin-management-grid">
      <article class="cust-card admin-manage-card">
        <div class="cust-card-title">Packages</div>
        <div class="cust-card-sub">Edit existing package pricing, guest limits, and room allowance</div>
        <div class="admin-edit-note">Click <strong>Edit</strong> on a package row to load it into this form.</div>
        <form class="admin-form" id="packageForm">
          <input type="hidden" id="packageId">
          <div class="form-row">
            <div class="form-group"><label for="packageName">Package Name</label><input type="text" id="packageName" required disabled></div>
            <div class="form-group"><label for="packageBasePrice">Base Price</label><input type="number" id="packageBasePrice" required disabled></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label for="packageGuestCapacity">Guest Capacity</label><input type="number" id="packageGuestCapacity" required disabled></div>
            <div class="form-group"><label for="packageRoomLimit">Max Private Rooms</label><input type="number" id="packageRoomLimit" required disabled></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label for="packageTagline">Description / Tagline</label><input type="text" id="packageTagline" required disabled></div>
            <div class="form-group"><label for="packageActive">Status</label><select id="packageActive" disabled><option value="true">Active</option><option value="false">Inactive</option></select></div>
          </div>
          <div class="admin-form-actions"><button class="auth-btn" id="packageSubmit" type="submit" disabled>Update Package</button><button class="btn-outline" id="packageReset" type="button" disabled>Cancel Edit</button></div>
        </form>
        <div class="table-wrap admin-mini-table">
          <table class="data-table">
            <thead><tr><th>Name</th><th>Price</th><th>Guests</th><th>Rooms</th><th>Status</th><th>Action</th></tr></thead>
            <tbody id="packageTableBody"></tbody>
          </table>
        </div>
      </article>

      <article class="cust-card admin-manage-card">
        <div class="cust-card-title">Amenities</div>
        <div class="cust-card-sub">Edit existing add-on pricing shown to customers</div>
        <div class="admin-edit-note">Click <strong>Edit</strong> on an amenity row to load it into this form.</div>
        <form class="admin-form" id="amenityForm">
          <input type="hidden" id="amenityId">
          <div class="form-row">
            <div class="form-group"><label for="amenityName">Amenity Name</label><input type="text" id="amenityName" required disabled></div>
            <div class="form-group"><label for="amenityPrice">Price</label><input type="number" id="amenityPrice" required disabled></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label for="amenityActive">Status</label><select id="amenityActive" disabled><option value="true">Active</option><option value="false">Inactive</option></select></div>
          </div>
          <div class="admin-form-actions"><button class="auth-btn" id="amenitySubmit" type="submit" disabled>Update Amenity</button><button class="btn-outline" id="amenityReset" type="button" disabled>Cancel Edit</button></div>
        </form>
        <div class="table-wrap admin-mini-table">
          <table class="data-table">
            <thead><tr><th>Name</th><th>Price</th><th>Status</th><th>Action</th></tr></thead>
            <tbody id="amenityTableBody"></tbody>
          </table>
        </div>
      </article>
    </section>

    <section class="admin-management-grid">
      <article class="cust-card admin-manage-card">
        <div class="cust-card-title">Venues</div>
        <div class="cust-card-sub">Edit existing venue details used in inquiry mapping</div>
        <div class="admin-edit-note">Click <strong>Edit</strong> on a venue row to load it into this form.</div>
        <form class="admin-form" id="venueForm">
          <input type="hidden" id="venueId">
          <div class="form-row">
            <div class="form-group"><label for="venueName">Venue Name</label><input type="text" id="venueName" required disabled></div>
            <div class="form-group"><label for="venueDescription">Description</label><input type="text" id="venueDescription" disabled></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label for="venueCapacity">Guest Capacity</label><input type="number" id="venueCapacity" min="0" step="1" disabled></div>
            <div class="form-group"><label for="venueActive">Status</label><select id="venueActive" disabled><option value="true">Active</option><option value="false">Inactive</option></select></div>
          </div>
          <div class="admin-form-actions"><button class="auth-btn" id="venueSubmit" type="submit" disabled>Update Venue</button><button class="btn-outline" id="venueReset" type="button" disabled>Cancel Edit</button></div>
        </form>
        <div class="table-wrap admin-mini-table">
          <table class="data-table">
            <thead><tr><th>Venue</th><th>Description</th><th>Capacity</th><th>Status</th><th>Action</th></tr></thead>
            <tbody id="venueTableBody"></tbody>
          </table>
        </div>
      </article>
    </section>

    <section>
      <div class="panel-section-title">Users</div>
      <div class="admin-filter-bar">
        <input type="search" id="userSearch" placeholder="Search by name or email">
        <select id="userRoleFilter"><option value="all">All Roles</option><option value="customer">Customer</option><option value="admin">Admin</option></select>
        <select id="userStatusFilter"><option value="all">All States</option><option value="active">Active</option><option value="revoked">Revoked</option><option value="deleted">Deleted</option></select>
      </div>
      <div class="table-wrap admin-inquiry-scroll">
        <table class="data-table">
          <thead><tr><th>User</th><th>Role</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="userTableBody"></tbody>
        </table>
      </div>
    </section>

    <section>
      <div class="panel-section-title">Inquiry Logs</div>
      <div class="admin-filter-bar">
        <input type="search" id="inquirySearch" placeholder="Search by customer or email">
        <select id="inquiryStatusFilter"><option value="all">All Statuses</option><option value="submitted">Submitted</option><option value="review">Under Review</option><option value="proposal">Proposal Sent</option><option value="closed">Closed</option></select>
        <select id="inquiryEventFilter"><option value="all">All Events</option><option value="Wedding">Wedding</option><option value="Debut">Debut</option><option value="Corporate Gala">Corporate Gala</option><option value="Social Event">Social Event</option></select>
        <select id="inquiryPackageFilter"><option value="all">All Packages</option><option value="ripple">Ripple Pack</option><option value="crest">Crest Pack</option><option value="sovereign">Sovereign Wave</option></select>
        <select id="inquirySort"><option value="desc">Newest First</option><option value="asc">Oldest First</option></select>
      </div>
      <div class="table-wrap admin-inquiry-scroll">
        <table class="data-table">
          <thead><tr><th>Reference</th><th>Client</th><th>Event</th><th>Package</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="inquiryTableBody"></tbody>
        </table>
      </div>
    </section>
  </main>

  <div class="toast" id="toast"></div>

  <script src="assets/js/mock-data.js"></script>
  <script src="assets/js/admin.js"></script>
</body>
</html>