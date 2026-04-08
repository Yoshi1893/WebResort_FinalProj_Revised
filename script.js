/* =====================================================
   === CONSTANTS & CONFIG ===
   =====================================================
   Admin login: atob(ADMIN_EMAIL_B64) = admin@9waves.com, atob(ADMIN_PASS_B64) = admin123
   Unlocks DASHBOARD via updateNav() → openAdminPanel()
*/

// Admin credentials (base64 encoded for obfuscation)
const ADMIN_EMAIL_B64 = 'YWRtaW5AOXdhdmVzLmNvbQ==';
const ADMIN_PASS_B64 = 'YWRtaW4xMjM=';

// LocalStorage keys for users/inquiries/session
const STORAGE_KEYS = { users: '9waves_users', current: '9waves_current', inquiries: '9waves_inquiries' };

// Package pricing data (used in estimator/wizard)
const PACKAGE_DATA = {
  ripple: { name: 'Ripple Pack', base: 45000, pax: 100, extra: 350 },
  crest: { name: 'Crest Pack', base: 85000, pax: 200, extra: 450 },
  sovereign: { name: 'Sovereign Wave', base: 150000, pax: 500, extra: 600 }
};

// Status badges for dashboard tables
const STATUS_META = {
  submitted: { label: 'Submitted', className: 'status-submitted' },
  review: { label: 'Under Review', className: 'status-review' },
  proposal: { label: 'Proposal Sent', className: 'status-proposal' },
  closed: { label: 'Closed', className: 'status-closed' }
};
const $ = (id) => document.getElementById(id);
let users = loadJSON(STORAGE_KEYS.users, []);
let currentUser = null;
let inquiries = loadInquiries();
let currentStep = 1;
let trendChart;
let statusChart;
let isSyncingEstimate = false;
const wizardState = { venue: 'Pearl Ballroom' };

function loadJSON(key, fallback) {
  try { return JSON.parse(localStorage.getItem(key) || JSON.stringify(fallback)); }
  catch { return fallback; }
}
function saveUsers() { localStorage.setItem(STORAGE_KEYS.users, JSON.stringify(users)); }
function saveSession(user) { currentUser = user; }
function clearSession() { currentUser = null; localStorage.removeItem(STORAGE_KEYS.current); }
function saveInquiries() { localStorage.setItem(STORAGE_KEYS.inquiries, JSON.stringify(inquiries)); }
function escapeHTML(value) { const div = document.createElement('div'); div.textContent = value == null ? '' : String(value); return div.innerHTML; }
function formatCurrency(amount) { return `PHP ${Number(amount || 0).toLocaleString('en-PH')}`; }
function getAddOnPrice(name) {
  const input = Array.from(document.querySelectorAll('.wiz-addon, .est-addon')).find((item) => item.value === name);
  return input ? parseInt(input.dataset.price || '0', 10) : 0;
}
function calculateEstimate(packageKey, guestCount, addOns) {
  const pkg = PACKAGE_DATA[packageKey] || PACKAGE_DATA.crest;
  const baseTotal = pkg.base;
  const addOnTotal = (addOns || []).reduce((sum, addOn) => sum + getAddOnPrice(addOn), 0);
  return {
    packageName: pkg.name,
    baseTotal,
    addOnTotal,
    total: baseTotal + addOnTotal
  };
}
function isoDateFromToday(daysAhead) { const date = new Date(); date.setDate(date.getDate() + daysAhead); return date.toISOString().split('T')[0]; }
function mapLegacyStatus(status) { return status === 'pending' ? 'submitted' : status === 'confirmed' ? 'proposal' : 'closed'; }
function loadInquiries() {
  const saved = loadJSON(STORAGE_KEYS.inquiries, []);
  if (saved.length) return saved;
  const legacy = loadJSON('9waves_bookings', []);
  if (legacy.length) {
    return legacy.map((item) => ({
      id: item.id || Date.now(),
      client: item.client || 'Guest',
      customerEmail: item.email || '',
      event: item.event || 'Wedding',
      venue: item.venue || 'Pearl Ballroom',
      preferredDate: item.date || isoDateFromToday(30),
      backupDate: item.date || isoDateFromToday(37),
      packageKey: 'crest',
      guestCount: 200,
      budgetRange: 'PHP 75,000 - PHP 150,000',
      addOns: item.extras || [],
      notes: '',
      phone: item.phone || '',
      status: mapLegacyStatus(item.status),
      createdAt: new Date().toISOString()
    }));
  }
  return [
    { id: 1, client: 'Andrea Santos', customerEmail: 'andrea@example.com', event: 'Wedding', venue: 'Pearl Ballroom', preferredDate: isoDateFromToday(28), backupDate: isoDateFromToday(35), packageKey: 'crest', guestCount: 180, budgetRange: 'PHP 75,000 - PHP 150,000', addOns: ['Full Event Coordination'], notes: 'Prefers a classic ballroom setup.', phone: '+63 917 111 2222', status: 'review', createdAt: new Date().toISOString() },
    { id: 2, client: 'Lorraine Dela Cruz', customerEmail: 'lorraine@example.com', event: 'Debut', venue: 'Wavecrest Garden', preferredDate: isoDateFromToday(45), backupDate: isoDateFromToday(52), packageKey: 'ripple', guestCount: 120, budgetRange: 'Under PHP 75,000', addOns: ['Flower Wall Backdrop'], notes: 'Wants a sunset ceremony flow.', phone: '+63 917 333 4444', status: 'proposal', createdAt: new Date().toISOString() },
    { id: 3, client: 'Raphael Tan', customerEmail: 'raphael@example.com', event: 'Corporate Gala', venue: 'Pearl Ballroom', preferredDate: isoDateFromToday(60), backupDate: isoDateFromToday(67), packageKey: 'sovereign', guestCount: 320, budgetRange: 'Above PHP 250,000', addOns: ['Pro A/V Upgrade'], notes: 'Needs stage, projection, and dinner flow.', phone: '+63 917 555 6666', status: 'submitted', createdAt: new Date().toISOString() }
  ];
}
function showToast(message, background) {
  const toast = $('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.style.background = background || 'var(--sage-dark)';
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3500);
}
function openAuth() { window.location.href = 'login.php'; }
function closeAuth() {}
function closeMobile() { $('mobileMenu').classList.remove('open'); }
function clearFormErrors() { ['loginError', 'regError'].forEach((id) => { const el = $(id); if (el) { el.textContent = ''; el.style.display = 'none'; } }); }
function showError(id, message) { const el = $(id); if (el) { el.textContent = message; el.style.display = 'block'; } }
function updateAuthNote() {
  const note = $('wizardAuthNote');
  if (!note) return;
  note.textContent = 'Sign in through the login page before sending this inquiry.';
}
/**
 * Updates navigation based on login state.
 * Admin login → "Admin Dashboard" button (triggers openAdminPanel dashboard).
 * Customer login → "My Account" button.
 * Key: Generates user dropdown with dashboard access.
 */
function updateNav() {
  return;
}
document.addEventListener('click', (event) => {
  const dropBtn = $('dropBtn');
  const dropMenu = $('dropMenu');
  if (dropBtn && dropMenu && !dropBtn.contains(event.target)) {
    dropBtn.classList.remove('open');
    dropMenu.classList.remove('open');
  }
});
/**
 * 🔥 DASHBOARD ENTRY POINT
 * Opens the main admin dashboard (stats/charts/tables).
 * Call from nav dropdown after admin login.
 */
function openAdminPanel() {
  window.location.href = 'admin.php';
}
function openCustomerPanel() {
  window.location.href = 'account.php';
}
function closePanelGoTo(section) {
  setTimeout(() => { const target = $(section); if (target) target.scrollIntoView({ behavior: 'smooth' }); }, 100);
}
function signOut() {
  clearSession();
  updateAuthNote();
  window.location.href = 'index.php';
}
function refreshAdminUsers() {
  if (!$('statUsers') || !$('adminUsersBody')) return;
  $('statUsers').textContent = users.length;
  const tbody = $('adminUsersBody');
  if (!users.length) {
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-light);padding:28px 18px;">No registered customers yet.</td></tr>';
    return;
  }
  tbody.innerHTML = users.map((user) => `
    <tr>
      <td>${escapeHTML(user.firstName)} ${escapeHTML(user.lastName)}</td>
      <td>${escapeHTML(user.email)}</td>
      <td>${escapeHTML(user.phone || 'Not provided')}</td>
      <td>${escapeHTML(user.registered || 'Today')}</td>
    </tr>`).join('');
}
function getStatusInfo(status) { return STATUS_META[status] || STATUS_META.submitted; }
function nextAdminAction(status) {
  if (status === 'submitted') return { label: 'Start Review', next: 'review', className: 'tbl-confirm' };
  if (status === 'review') return { label: 'Send Proposal', next: 'proposal', className: 'tbl-confirm' };
  if (status === 'proposal') return { label: 'Close Inquiry', next: 'closed', className: 'tbl-cancel' };
  return null;
}
function refreshAdminBookings() {
  if (!$('statBookings') || !$('statPending') || !$('adminBookingsBody')) return;
  $('statBookings').textContent = inquiries.length;
  $('statPending').textContent = inquiries.filter((item) => item.status === 'review').length;
  const tbody = $('adminBookingsBody');
  if (!inquiries.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-light);padding:28px 18px;">No inquiries yet.</td></tr>';
    return;
  }
  tbody.innerHTML = [...inquiries].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)).map((item) => {
    const status = getStatusInfo(item.status);
    const action = nextAdminAction(item.status);
    const actionCell = action ? `<button class="tbl-btn ${action.className}" onclick="changeInquiryStatus(${item.id}, '${action.next}')">${action.label}</button>` : '<span style="color:#ccc;font-size:13px;">Done</span>';
    return `
      <tr>
        <td>${escapeHTML(item.client)}</td>
        <td>${escapeHTML(item.event)}</td>
        <td>${escapeHTML(item.venue)}</td>
        <td>${escapeHTML(item.preferredDate)}</td>
        <td><span class="status-badge ${status.className}">${status.label}</span></td>
        <td>${actionCell}</td>
      </tr>`;
  }).join('');
}
async function changeInquiryStatus(id, status) {
  const inquiry = inquiries.find((item) => item.id === id);
  if (!inquiry) return;
  if (status === 'closed') {
    const confirmed = await showConfirmModal('Close inquiry', `Close the inquiry for ${inquiry.client}?`);
    if (!confirmed) return;
  }
  inquiry.status = status;
  saveInquiries();
  refreshAdminBookings();
  renderCustomerInquiries();
  initAdminCharts();
  showToast(`Inquiry for ${inquiry.client} updated.`);
}
function showConfirmModal(title, message) {
  return new Promise((resolve) => {
    $('confirmTitle').textContent = title;
    $('confirmMsg').textContent = message;
    $('confirmOverlay').classList.add('open');
    const onYes = () => cleanup(true);
    const onNo = () => cleanup(false);
    function cleanup(value) {
      $('confirmOverlay').classList.remove('open');
      $('confirmYes').removeEventListener('click', onYes);
      $('confirmNo').removeEventListener('click', onNo);
      resolve(value);
    }
    $('confirmYes').addEventListener('click', onYes);
    $('confirmNo').addEventListener('click', onNo);
  });
}
function initAdminCharts() {
  const trendCtx = $('bookingTrendChart');
  const typeCtx = $('eventTypeChart');
  if (!trendCtx || !typeCtx || typeof Chart === 'undefined') return;
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const monthlyData = new Array(12).fill(0);
  inquiries.forEach((item) => {
    const month = new Date(item.preferredDate).getMonth();
    if (!Number.isNaN(month)) monthlyData[month] += 1;
  });
  const types = {};
  inquiries.forEach((item) => { types[item.event] = (types[item.event] || 0) + 1; });
  if (trendChart) trendChart.destroy();
  if (statusChart) statusChart.destroy();
  trendChart = new Chart(trendCtx, { type: 'bar', data: { labels: months, datasets: [{ label: 'Inquiries', data: monthlyData, backgroundColor: '#7a8c6e', borderRadius: 4 }] }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
  statusChart = new Chart(typeCtx, { type: 'doughnut', data: { labels: Object.keys(types), datasets: [{ data: Object.values(types), backgroundColor: ['#7a8c6e', '#c9a84c', '#4e5e45', '#e2c97e', '#1e1e1a'], borderWidth: 0 }] }, options: { responsive: true, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } } });
}
function renderCustomerInquiries() {
  const list = $('customerInquiryList');
  if (!list) return;
  if (!currentUser || currentUser.role !== 'customer') { list.innerHTML = ''; return; }
  const mine = inquiries.filter((item) => item.customerEmail === currentUser.email);
  if (!mine.length) {
    list.innerHTML = '<p class="customer-empty-state">No saved inquiries yet. Start one from the inquiry form below.</p>';
    return;
  }
  list.innerHTML = mine.map((item) => {
    const status = getStatusInfo(item.status);
    const packageName = PACKAGE_DATA[item.packageKey]?.name || 'Custom Plan';
    return `<article class="customer-inquiry-item"><div class="customer-inquiry-header"><strong>${escapeHTML(item.event)}</strong><span class="status-badge ${status.className}">${status.label}</span></div><div class="customer-inquiry-meta">${escapeHTML(item.venue)} | ${escapeHTML(item.preferredDate)}</div><div class="customer-inquiry-meta">${escapeHTML(packageName)} | ${escapeHTML(item.guestCount)} guests</div></article>`;
  }).join('');
}
function prefillInquiryContact() {
  if (!currentUser || currentUser.role !== 'customer') { updateAuthNote(); return; }
  const user = users.find((item) => item.email === currentUser.email);
  if ($('wizFirst')) $('wizFirst').value = user?.firstName || currentUser.name.split(' ')[0] || '';
  if ($('wizLast')) $('wizLast').value = user?.lastName || currentUser.name.split(' ').slice(1).join(' ') || '';
  if ($('wizEmail')) $('wizEmail').value = currentUser.email || '';
  if ($('wizPhone')) $('wizPhone').value = user?.phone || currentUser.phone || '';
  updateAuthNote();
}
function setPackageCardState(key) { document.querySelectorAll('[data-package-card]').forEach((card) => card.classList.toggle('selected-package', card.dataset.packageCard === key)); }
function getSelectedWizardAddOns() { return Array.from(document.querySelectorAll('.wiz-addon:checked')).map((input) => input.value); }
function setWizardAddOns(values) { document.querySelectorAll('.wiz-addon').forEach((input) => { input.checked = values.includes(input.value); }); }
function getSelectedEstimatorAddOns() { return Array.from(document.querySelectorAll('.est-addon:checked')).map((input) => input.value); }
function setEstimatorAddOns(values) { document.querySelectorAll('.est-addon').forEach((input) => { input.checked = values.includes(input.value); }); }
function updateBudgetField(total) {
  if (total < 75000) $('wizBudget').value = 'Under PHP 75,000';
  else if (total <= 150000) $('wizBudget').value = 'PHP 75,000 - PHP 150,000';
  else if (total <= 250000) $('wizBudget').value = 'PHP 150,000 - PHP 250,000';
  else $('wizBudget').value = 'Above PHP 250,000';
}
function updateGuestLimits() {
  const estPkg = PACKAGE_DATA[$('estPackage').value];
  const wizPkg = PACKAGE_DATA[$('wizPackage').value];
  
  const estRaw = $('estGuests').value;
  const wizRaw = $('wizGuests').value;
  const estVal = parseInt(estRaw, 10);
  const wizVal = parseInt(wizRaw, 10);
  
  // Estimator check
  if (!isNaN(estVal)) {
    if (estVal > estPkg.pax) {
      $('estGuests').classList.add('invalid-input');
      $('estGuestError').style.display = 'block';
      $('estGuestError').textContent = `⚠ Exceeds ${estPkg.name} limit of ${estPkg.pax} guests.`;
    } else if (estVal < 50 && estRaw.length > 0) {
      $('estGuests').classList.add('invalid-input');
      $('estGuestError').style.display = 'block';
      $('estGuestError').textContent = `⚠ Minimum 50 guests required.`;
    } else {
      $('estGuests').classList.remove('invalid-input');
      $('estGuestError').style.display = 'none';
    }
  } else {
    $('estGuests').classList.remove('invalid-input');
    $('estGuestError').style.display = 'none';
  }
  
  // Wizard check
  if (!isNaN(wizVal)) {
    if (wizVal > wizPkg.pax) {
      $('wizGuests').classList.add('invalid-input');
      $('wizGuestError').style.display = 'block';
      $('wizGuestError').textContent = `⚠ Exceeds ${wizPkg.name} limit of ${wizPkg.pax} guests.`;
    } else if (wizVal < 50 && wizRaw.length > 0) {
      $('wizGuests').classList.add('invalid-input');
      $('wizGuestError').style.display = 'block';
      $('wizGuestError').textContent = `⚠ Minimum 50 guests required.`;
    } else {
      $('wizGuests').classList.remove('invalid-input');
      $('wizGuestError').style.display = 'none';
    }
  } else {
    $('wizGuests').classList.remove('invalid-input');
    $('wizGuestError').style.display = 'none';
  }
  
  $('guestCountLabel').textContent = isNaN(estVal) ? '---' : estVal;
}
function updateCalculator() {
  const packageKey = $('estPackage').value;
  const guests = parseInt($('estGuests').value, 10) || 0;
  const pkg = PACKAGE_DATA[packageKey];
  
  const addOnTotal = Array.from(document.querySelectorAll('.est-addon:checked')).reduce((sum, input) => sum + parseInt(input.dataset.price, 10), 0);
  
  // Base price logic (Fixed price up to cap)
  const total = pkg.base + addOnTotal;
  
  $('totalPrice').textContent = formatCurrency(total);
  
  const isOver = guests > pkg.pax;
  $('estSummary').innerHTML = `<strong>${pkg.name}</strong> (${guests} Guests)${isOver ? ' <span style="color:var(--red)">[OVER CAPACITY]</span>' : ''}<br>Base Price: ${formatCurrency(pkg.base)}<br>Add-ons: ${formatCurrency(addOnTotal)}`;
  
  if (!isSyncingEstimate) syncWizardFromEstimator(total);
  setPackageCardState(packageKey);
  updateGuestLimits();
}
function syncWizardFromEstimator(totalOverride) {
  isSyncingEstimate = true;
  $('wizPackage').value = $('estPackage').value;
  // $('wizGuests').value = $('estGuests').value; // Stop automatic syncing to keep wizard field empty for manual input
  setWizardAddOns(getSelectedEstimatorAddOns());
  updateBudgetField(totalOverride ?? 0);
  isSyncingEstimate = false;
  updateGuestLimits();
}
function syncEstimatorFromWizard() {
  if (isSyncingEstimate) return;
  isSyncingEstimate = true;
  $('estPackage').value = $('wizPackage').value;
  $('estGuests').value = $('wizGuests').value;
  setEstimatorAddOns(getSelectedWizardAddOns());
  isSyncingEstimate = false;
  updateCalculator();
}
function choosePackage(key, scrollToContact = false) {
  if (!PACKAGE_DATA[key]) return;
  $('estPackage').value = key;
  updateCalculator();
  if (scrollToContact) closePanelGoTo('contact');
}
function captureWizardState() { wizardState.venue = document.querySelector('.wizard-venue-card.active')?.dataset.venue || 'Pearl Ballroom'; }
function getDraftInquiryData() {
  captureWizardState();
  const addOns = getSelectedWizardAddOns();
  const firstName = $('wizFirst').value.trim() || currentUser?.name?.split(' ')[0] || '';
  const lastName = $('wizLast').value.trim() || currentUser?.name?.split(' ').slice(1).join(' ') || '';
  const email = $('wizEmail').value.trim() || currentUser?.email || '';
  const phone = $('wizPhone').value.trim() || currentUser?.phone || '';
  const estimate = calculateEstimate($('wizPackage').value, $('wizGuests').value, addOns);
  return {
    client: `${firstName} ${lastName}`.trim() || 'Guest',
    firstName,
    lastName,
    email,
    phone,
    event: $('wizEvent').value,
    venue: wizardState.venue,
    preferredDate: $('wizPreferredDate').value || 'Not set',
    backupDate: $('wizBackupDate').value || 'Not set',
    packageKey: $('wizPackage').value,
    packageName: estimate.packageName,
    guestCount: $('wizGuests').value || 'Not set',
    budgetRange: $('wizBudget').value || 'Not set',
    addOns,
    notes: $('wizNotes').value.trim() || 'No additional notes provided.',
    estimate
  };
}
function populateSummary() {
  const draft = getDraftInquiryData();
  $('wizardSummary').innerHTML = `
    <div class="summary-item"><label>Venue</label><span>${escapeHTML(draft.venue)}</span></div>
    <div class="summary-item"><label>Event</label><span>${escapeHTML(draft.event)}</span></div>
    <div class="summary-item"><label>Package</label><span>${escapeHTML(draft.packageName)}</span></div>
    <div class="summary-item"><label>Guests</label><span>${escapeHTML(draft.guestCount)}</span></div>
    <div class="summary-item"><label>Preferred Date</label><span>${escapeHTML(draft.preferredDate)}</span></div>
    <div class="summary-item"><label>Backup Date</label><span>${escapeHTML(draft.backupDate)}</span></div>
    <div class="summary-item"><label>Budget</label><span>${escapeHTML(draft.budgetRange)}</span></div>
    <div class="summary-item"><label>Add-ons</label><span>${escapeHTML(draft.addOns.length ? draft.addOns.join(', ') : 'None')}</span></div>
    <div class="summary-item"><label>Estimated Total</label><span>${escapeHTML(formatCurrency(draft.estimate.total))}</span></div>`;
}
function validateCurrentStep() {
  if (currentStep === 2) {
    const packageKey = $('wizPackage').value;
    const pkg = PACKAGE_DATA[packageKey];
    const guests = parseInt($('wizGuests').value, 10);
    if (isNaN(guests) || guests < 50) { 
      showToast('Please enter a valid guest count (Minimum 50).', '#c0392b'); 
      return false; 
    }
    if (guests > pkg.pax) { showToast(`${pkg.name} is limited to ${pkg.pax} guests max.`, '#c0392b'); return false; }
  }
  if (currentStep === 3) {
    const preferred = $('wizPreferredDate').value;
    const backup = $('wizBackupDate').value;
    if (!preferred || !backup) { showToast('Add both preferred and backup dates.', '#c0392b'); return false; }
    if (preferred === backup) { showToast('Preferred and backup dates must be different.', '#c0392b'); return false; }
  }
  return true;
}
function goToStep(step) {
  if (step < 1 || step > 4) return;
  if (step > currentStep && !validateCurrentStep()) return;
  if (step === 4) { populateSummary(); prefillInquiryContact(); }
  document.querySelectorAll('.wizard-step').forEach((panel) => panel.classList.remove('active'));
  $(`wizardStep${step}`).classList.add('active');
  document.querySelectorAll('.p-step').forEach((item, index) => {
    item.classList.toggle('active', index + 1 === step);
    item.classList.toggle('completed', index + 1 < step);
  });
  $('wizardBar').style.width = `${(step / 4) * 100}%`;
  $('wizPrev').style.display = step === 1 ? 'none' : 'block';
  $('wizNext').style.display = step === 4 ? 'none' : 'block';
  if ($('wizDownloadPdf')) $('wizDownloadPdf').style.display = step === 4 ? 'block' : 'none';
  $('wizSubmit').style.display = step === 4 ? 'block' : 'none';
  currentStep = step;
}
function openPrintFallback(draft) {
  const printWindow = window.open('', '_blank', 'width=900,height=700');
  if (!printWindow) {
    showToast('Popup blocked. Allow popups to export the soft quote.', '#c0392b');
    return;
  }
  const addOns = draft.addOns.length ? draft.addOns.join(', ') : 'None';
  const html = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>9 Waves Soft Quote</title>
  <style>
    body { font-family: Arial, sans-serif; color: #222; margin: 32px; }
    h1 { margin: 0 0 6px; font-size: 28px; }
    h2 { margin: 0 0 20px; font-size: 18px; font-weight: normal; color: #6f7b63; }
    p { line-height: 1.5; }
    .note { font-size: 12px; color: #666; margin-bottom: 24px; }
    .grid { display: grid; grid-template-columns: 180px 1fr; gap: 10px 18px; margin-bottom: 28px; }
    .label { font-weight: bold; }
    .total { margin-top: 10px; font-size: 18px; font-weight: bold; }
    .footer { margin-top: 30px; font-size: 12px; color: #666; }
  </style>
</head>
<body>
  <h1>9 Waves Events Place</h1>
  <h2>Soft Quote / Inquiry Summary</h2>
  <p class="note">Planning estimate only. Final pricing and availability are confirmed after team review.</p>
  <div class="grid">
    <div class="label">Client</div><div>${escapeHTML(draft.client)}</div>
    <div class="label">Email</div><div>${escapeHTML(draft.email || 'Not provided')}</div>
    <div class="label">Phone</div><div>${escapeHTML(draft.phone || 'Not provided')}</div>
    <div class="label">Event Type</div><div>${escapeHTML(draft.event)}</div>
    <div class="label">Venue</div><div>${escapeHTML(draft.venue)}</div>
    <div class="label">Package</div><div>${escapeHTML(draft.packageName)}</div>
    <div class="label">Guest Count</div><div>${escapeHTML(draft.guestCount)}</div>
    <div class="label">Preferred Date</div><div>${escapeHTML(draft.preferredDate)}</div>
    <div class="label">Backup Date</div><div>${escapeHTML(draft.backupDate)}</div>
    <div class="label">Budget Range</div><div>${escapeHTML(draft.budgetRange)}</div>
    <div class="label">Add-ons</div><div>${escapeHTML(addOns)}</div>
    <div class="label">Base Estimate</div><div>${escapeHTML(formatCurrency(draft.estimate.baseTotal))}</div>
    <div class="label">Add-on Estimate</div><div>${escapeHTML(formatCurrency(draft.estimate.addOnTotal))}</div>
  </div>
  <div class="total">Estimated Total: ${escapeHTML(formatCurrency(draft.estimate.total))}</div>
  <p><strong>Notes:</strong> ${escapeHTML(draft.notes)}</p>
  <p class="footer">This document is a soft quote for planning purposes only. Rates may change based on final guest count, event requirements, venue availability, and coordination review.</p>
  <script>
    window.onload = function () {
      window.print();
    };
  <\/script>
</body>
</html>`;
  printWindow.document.open();
  printWindow.document.write(html);
  printWindow.document.close();
}
function downloadSoftQuote() {
  const draft = getDraftInquiryData();
  if (!window.jspdf || !window.jspdf.jsPDF) {
    openPrintFallback(draft);
    return;
  }
  if (currentStep !== 4) {
    populateSummary();
    goToStep(4);
  }
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  let y = 20;
  const addLine = (label, value) => {
    const lines = doc.splitTextToSize(String(value), 120);
    const neededHeight = Math.max(8, lines.length * 6);
    if (y + neededHeight > 280) {
      doc.addPage();
      y = 20;
    }
    doc.setFont('helvetica', 'bold');
    doc.text(label, 16, y);
    doc.setFont('helvetica', 'normal');
    doc.text(lines, 70, y);
    y += neededHeight;
  };
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(20);
  doc.text('9 Waves Events Place', 16, y);
  y += 10;
  doc.setFontSize(14);
  doc.text('Soft Quote / Inquiry Summary', 16, y);
  y += 8;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.text('Planning estimate only. Final pricing and availability are confirmed after team review.', 16, y);
  y += 12;
  doc.setDrawColor(210, 196, 170);
  doc.line(16, y, 194, y);
  y += 10;
  doc.setFontSize(11);
  addLine('Client', draft.client);
  addLine('Email', draft.email || 'Not provided');
  addLine('Phone', draft.phone || 'Not provided');
  addLine('Event Type', draft.event);
  addLine('Venue', draft.venue);
  addLine('Package', draft.packageName);
  addLine('Guest Count', draft.guestCount);
  addLine('Preferred Date', draft.preferredDate);
  addLine('Backup Date', draft.backupDate);
  addLine('Budget Range', draft.budgetRange);
  addLine('Add-ons', draft.addOns.length ? draft.addOns.join(', ') : 'None');
  addLine('Base Estimate', formatCurrency(draft.estimate.baseTotal));
  addLine('Add-on Estimate', formatCurrency(draft.estimate.addOnTotal));
  addLine('Estimated Total', formatCurrency(draft.estimate.total));
  addLine('Notes', draft.notes);
  y += 4;
  const disclaimer = doc.splitTextToSize('This document is a soft quote for planning purposes only. Rates may change based on final guest count, event requirements, venue availability, and coordination review.', 178);
  if (y + (disclaimer.length * 5) > 280) {
    doc.addPage();
    y = 20;
  }
  doc.setFontSize(9);
  doc.text(disclaimer, 16, y);
  const filenameDate = (draft.preferredDate || new Date().toISOString().split('T')[0]).replace(/[^0-9-]/g, '');
  doc.save(`9waves-soft-quote-${filenameDate || 'inquiry'}.pdf`);
}
function resetInquiryForm() {
  $('wizEvent').value = 'Wedding';
  $('wizPackage').value = $('estPackage').value;
  $('wizGuests').value = $('estGuests').value;
  $('wizBudget').value = 'PHP 75,000 - PHP 150,000';
  setWizardAddOns(getSelectedEstimatorAddOns());
  if ($('wizPreferredDate')._flatpickr) $('wizPreferredDate')._flatpickr.clear();
  else $('wizPreferredDate').value = '';
  if ($('wizBackupDate')._flatpickr) $('wizBackupDate')._flatpickr.clear();
  else $('wizBackupDate').value = '';
  $('wizNotes').value = '';
  document.querySelectorAll('.wizard-venue-card').forEach((card, index) => card.classList.toggle('active', index === 0));
  captureWizardState();
  prefillInquiryContact();
  goToStep(1);
}
function submitInquiry() {
  if (!currentUser || currentUser.role !== 'customer') {
    showToast('Sign in on the login page before sending your inquiry.', '#c0392b');
    setTimeout(() => { window.location.href = 'login.php'; }, 800);
    return;
  }
  if (!validateCurrentStep()) return;
  const firstName = $('wizFirst').value.trim();
  const lastName = $('wizLast').value.trim();
  const email = $('wizEmail').value.trim();
  const phone = $('wizPhone').value.trim();
  if (!firstName || !lastName || !email || !phone) {
    showToast('Complete your contact details first.', '#c0392b');
    return;
  }
  captureWizardState();
  const inquiry = {
    id: Date.now(),
    client: `${firstName} ${lastName}`,
    customerEmail: currentUser.email,
    event: $('wizEvent').value,
    venue: wizardState.venue,
    preferredDate: $('wizPreferredDate').value,
    backupDate: $('wizBackupDate').value,
    packageKey: $('wizPackage').value,
    guestCount: parseInt($('wizGuests').value, 10) || 0,
    budgetRange: $('wizBudget').value,
    addOns: getSelectedWizardAddOns(),
    notes: $('wizNotes').value.trim(),
    email,
    phone,
    status: 'submitted',
    createdAt: new Date().toISOString()
  };
  inquiries.push(inquiry);
  saveInquiries();
  const userIndex = users.findIndex((item) => item.email === currentUser.email);
  if (userIndex >= 0) {
    users[userIndex].phone = phone;
    users[userIndex].firstName = firstName;
    users[userIndex].lastName = lastName;
    saveUsers();
    saveSession({ name: `${firstName} ${lastName}`, email: currentUser.email, role: 'customer', phone });
  }
  refreshAdminBookings();
  refreshAdminUsers();
  renderCustomerInquiries();
  showToast('Inquiry saved. Our team will review it within 24 hours.');

  resetInquiryForm();
}
function validateInput(event) {
  const el = event.target;
  if (!el.value) { el.classList.remove('valid', 'invalid'); return; }
  let valid = el.checkValidity();
  if (el.type === 'password' && el.id !== 'loginPassword') valid = el.value.length >= 6;
  el.classList.toggle('valid', valid);
  el.classList.toggle('invalid', !valid);
}
function initDatePickers() {
  if (!window.flatpickr) return;
  flatpickr('#wizPreferredDate', { minDate: 'today', dateFormat: 'Y-m-d', altInput: true, altFormat: 'F j, Y', disableMobile: true });
  flatpickr('#wizBackupDate', { minDate: 'today', dateFormat: 'Y-m-d', altInput: true, altFormat: 'F j, Y', disableMobile: true });
}
function switchExplorerView(view) {
  const data = {
    ballroom: { title: 'The Pearl Ballroom', desc: 'A masterpiece of gold and light, featuring crystal chandeliers and elegant sightlines for grand celebrations.', cap: '500 Guests', aes: 'Grand Luxury' },
    garden: { title: 'Wavecrest Garden', desc: 'Lush greenery, soft light, and open-air ceremony potential for romantic and nature-led celebrations.', cap: '300 Guests', aes: 'Natural Elegance' }
  }[view];
  if (!data) return;
  document.querySelectorAll('.explorer-tab').forEach((tab) => tab.classList.toggle('active', tab.dataset.view === view));
  document.querySelectorAll('.explorer-bg').forEach((bg) => bg.classList.toggle('active', bg.id === `bg${view.charAt(0).toUpperCase() + view.slice(1)}`));
  $('expTitle').textContent = data.title;
  $('expDesc').textContent = data.desc;
  const stats = $('explorerDetails').querySelectorAll('strong');
  stats[0].textContent = data.cap;
  stats[1].textContent = data.aes;
}
function initLightbox() {
  const lb = $('lightbox');
  const lbImg = $('lightboxImage');
  const lbCap = $('lightboxCaption');
  const items = Array.from(document.querySelectorAll('.gallery-item')).map((item) => ({
    element: item,
    url: getComputedStyle(item.querySelector('.gallery-bg')).backgroundImage.replace(/url\(["']?/, '').replace(/["']?\)$/, ''),
    caption: item.querySelector('.gallery-item-label')?.textContent || 'Gallery'
  }));
  let currentIndex = 0;
  function updateLightbox() {
    const item = items[currentIndex];
    if (!item) return;
    lbImg.src = item.url;
    lbCap.textContent = item.caption;
  }
  items.forEach((item, index) => item.element.addEventListener('click', () => { currentIndex = index; updateLightbox(); lb.classList.add('open'); document.body.style.overflow = 'hidden'; }));
  $('lightboxClose').onclick = () => { lb.classList.remove('open'); document.body.style.overflow = ''; };
  $('lbPrev').onclick = (event) => { event.stopPropagation(); currentIndex = (currentIndex - 1 + items.length) % items.length; updateLightbox(); };
  $('lbNext').onclick = (event) => { event.stopPropagation(); currentIndex = (currentIndex + 1) % items.length; updateLightbox(); };
  lb.onclick = (event) => { if (event.target === lb) { lb.classList.remove('open'); document.body.style.overflow = ''; } };
}
function initGalleryDrag() {
  const strip = $('galleryStrip');
  let down = false;
  let startX = 0;
  let scrollLeft = 0;
  strip.addEventListener('mousedown', (event) => { down = true; startX = event.pageX - strip.offsetLeft; scrollLeft = strip.scrollLeft; });
  ['mouseleave', 'mouseup'].forEach((name) => strip.addEventListener(name, () => { down = false; }));
  strip.addEventListener('mousemove', (event) => {
    if (!down) return;
    event.preventDefault();
    strip.scrollLeft = scrollLeft - ((event.pageX - strip.offsetLeft) - startX) * 1.5;
  });
}
function initPetals() {
  const petals = $('petals');
  ['rgba(201,168,76,0.6)', 'rgba(255,255,255,0.4)', 'rgba(168,184,154,0.5)', 'rgba(201,168,76,0.3)'].forEach((color) => {
    for (let index = 0; index < 3; index += 1) {
      const petal = document.createElement('div');
      petal.className = 'petal';
      petal.style.cssText = `left:${Math.random() * 100}%;background:${color};animation-duration:${6 + Math.random() * 8}s;animation-delay:${Math.random() * 6}s;transform:rotate(${Math.random() * 360}deg);`;
      petals.appendChild(petal);
    }
  });
}
function boot() {
  saveInquiries();
  $('hamburger').onclick = () => $('mobileMenu').classList.add('open');
  $('mobileClose').onclick = closeMobile;
  window.addEventListener('scroll', () => $('navbar').classList.toggle('scrolled', window.scrollY > 60));
  document.querySelectorAll('.reveal').forEach((element) => new IntersectionObserver((entries) => entries.forEach((entry) => { if (entry.isIntersecting) entry.target.classList.add('visible'); }), { threshold: 0.1 }).observe(element));
  initPetals();
  initGalleryDrag();
  initLightbox();
  initDatePickers();
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => anchor.onclick = function onAnchor(event) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) { event.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
  document.querySelectorAll('input, select, textarea').forEach((input) => { input.addEventListener('input', validateInput); input.addEventListener('change', validateInput); });
  document.querySelectorAll('.explorer-tab').forEach((tab) => tab.onclick = () => switchExplorerView(tab.dataset.view));
  document.querySelectorAll('.wizard-venue-card').forEach((card) => card.onclick = function onVenueClick() { document.querySelectorAll('.wizard-venue-card').forEach((item) => item.classList.remove('active')); this.classList.add('active'); captureWizardState(); });
  document.querySelectorAll('[data-select-package]').forEach((button) => button.addEventListener('click', () => choosePackage(button.dataset.selectPackage, false)));
  $('estPackage').onchange = updateCalculator;
  $('estGuests').oninput = updateCalculator;
  document.querySelectorAll('.est-addon').forEach((input) => input.onchange = updateCalculator);
  $('wizPackage').onchange = syncEstimatorFromWizard;
  $('wizGuests').oninput = syncEstimatorFromWizard;
  document.querySelectorAll('.wiz-addon').forEach((input) => input.onchange = syncEstimatorFromWizard);
  $('wizNext').onclick = () => goToStep(currentStep + 1);
  $('wizPrev').onclick = () => goToStep(currentStep - 1);
  $('wizSubmit').onclick = submitInquiry;
  updateNav();
  updateCalculator();
  $('wizGuests').value = ''; // Ensure wizard starts blank
  prefillInquiryContact();
  goToStep(1);
}
boot();
