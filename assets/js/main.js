(() => {
  const $ = (id) => document.getElementById(id);
  const PACKAGE_DATA = window.MockStore?.getPackageData() || {};
  let currentStep = 1;
  let isSyncingEstimate = false;
  const wizardState = { venue: 'Pearl Ballroom' };

  function formatCurrency(amount) {
    return `PHP ${Number(amount || 0).toLocaleString('en-PH')}`;
  }

  function getAddOnPrice(name) {
    const input = Array.from(document.querySelectorAll('.wiz-addon, .est-addon')).find((item) => item.value === name);
    return input ? parseInt(input.dataset.price || '0', 10) : 0;
  }

  function calculateEstimate(packageKey, addOns) {
    const pkg = PACKAGE_DATA[packageKey] || PACKAGE_DATA.crest;
    const addOnTotal = (addOns || []).reduce((sum, addOn) => sum + getAddOnPrice(addOn), 0);
    return {
      packageName: pkg.name,
      baseTotal: pkg.base,
      addOnTotal,
      total: pkg.base + addOnTotal
    };
  }

  function showToast(message, background) {
    const toast = $('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.background = background || 'var(--sage-dark)';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
  }

  function closeMobile() {
    const mobileMenu = $('mobileMenu');
    if (mobileMenu) mobileMenu.classList.remove('open');
  }

  function closePanelGoTo(section) {
    const target = $(section);
    if (target) target.scrollIntoView({ behavior: 'smooth' });
  }

  function updateAuthNote() {
    const note = $('wizardAuthNote');
    if (!note) return;
    note.textContent = 'Sign in through the login page before sending this inquiry.';
  }

  function setPackageCardState(key) {
    document.querySelectorAll('[data-package-card]').forEach((card) => {
      card.classList.toggle('selected-package', card.dataset.packageCard === key);
    });
  }

  function getSelectedWizardAddOns() {
    return Array.from(document.querySelectorAll('.wiz-addon:checked')).map((input) => input.value);
  }

  function setWizardAddOns(values) {
    document.querySelectorAll('.wiz-addon').forEach((input) => { input.checked = values.includes(input.value); });
  }

  function getSelectedEstimatorAddOns() {
    return Array.from(document.querySelectorAll('.est-addon:checked')).map((input) => input.value);
  }

  function setEstimatorAddOns(values) {
    document.querySelectorAll('.est-addon').forEach((input) => { input.checked = values.includes(input.value); });
  }

  function updateBudgetField(total) {
    const budget = $('wizBudget');
    if (!budget) return;
    if (total < 75000) budget.value = 'Under PHP 75,000';
    else if (total <= 150000) budget.value = 'PHP 75,000 - PHP 150,000';
    else if (total <= 250000) budget.value = 'PHP 150,000 - PHP 250,000';
    else budget.value = 'Above PHP 250,000';
  }

  function updateGuestLimits() {
    const estPkg = PACKAGE_DATA[$('estPackage').value];
    const wizPkg = PACKAGE_DATA[$('wizPackage').value];
    const estRaw = $('estGuests').value;
    const wizRaw = $('wizGuests').value;
    const estVal = parseInt(estRaw, 10);
    const wizVal = parseInt(wizRaw, 10);

    if (!Number.isNaN(estVal)) {
      if (estVal > estPkg.pax) {
        $('estGuests').classList.add('invalid-input');
        $('estGuestError').style.display = 'block';
        $('estGuestError').textContent = `Exceeds ${estPkg.name} limit of ${estPkg.pax} guests.`;
      } else if (estVal < 50 && estRaw.length > 0) {
        $('estGuests').classList.add('invalid-input');
        $('estGuestError').style.display = 'block';
        $('estGuestError').textContent = 'Minimum 50 guests required.';
      } else {
        $('estGuests').classList.remove('invalid-input');
        $('estGuestError').style.display = 'none';
      }
    } else {
      $('estGuests').classList.remove('invalid-input');
      $('estGuestError').style.display = 'none';
    }

    if (!Number.isNaN(wizVal)) {
      if (wizVal > wizPkg.pax) {
        $('wizGuests').classList.add('invalid-input');
        $('wizGuestError').style.display = 'block';
        $('wizGuestError').textContent = `Exceeds ${wizPkg.name} limit of ${wizPkg.pax} guests.`;
      } else if (wizVal < 50 && wizRaw.length > 0) {
        $('wizGuests').classList.add('invalid-input');
        $('wizGuestError').style.display = 'block';
        $('wizGuestError').textContent = 'Minimum 50 guests required.';
      } else {
        $('wizGuests').classList.remove('invalid-input');
        $('wizGuestError').style.display = 'none';
      }
    } else {
      $('wizGuests').classList.remove('invalid-input');
      $('wizGuestError').style.display = 'none';
    }

    $('guestCountLabel').textContent = Number.isNaN(estVal) ? '---' : estVal;
  }

  function syncWizardFromEstimator(totalOverride) {
    isSyncingEstimate = true;
    $('wizPackage').value = $('estPackage').value;
    setWizardAddOns(getSelectedEstimatorAddOns());
    updateBudgetField(totalOverride ?? 0);
    isSyncingEstimate = false;
    updateGuestLimits();
  }

  function updateCalculator() {
    const packageKey = $('estPackage').value;
    const guests = parseInt($('estGuests').value, 10) || 0;
    const pkg = PACKAGE_DATA[packageKey];
    const addOnTotal = Array.from(document.querySelectorAll('.est-addon:checked')).reduce((sum, input) => sum + parseInt(input.dataset.price, 10), 0);
    const total = pkg.base + addOnTotal;

    $('totalPrice').textContent = formatCurrency(total);
    const isOver = guests > pkg.pax;
    $('estSummary').innerHTML = `<strong>${pkg.name}</strong> (${guests} Guests)${isOver ? ' <span style="color:var(--red)">[OVER CAPACITY]</span>' : ''}<br>Base Price: ${formatCurrency(pkg.base)}<br>Add-ons: ${formatCurrency(addOnTotal)}`;

    if (!isSyncingEstimate) syncWizardFromEstimator(total);
    setPackageCardState(packageKey);
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

  function captureWizardState() {
    wizardState.venue = document.querySelector('.wizard-venue-card.active')?.dataset.venue || 'Pearl Ballroom';
  }

  function getDraftInquiryData() {
    captureWizardState();
    const addOns = getSelectedWizardAddOns();
    const estimate = calculateEstimate($('wizPackage').value, addOns);
    return {
      event: $('wizEvent').value,
      venue: wizardState.venue,
      preferredDate: $('wizPreferredDate').value || 'Not set',
      backupDate: $('wizBackupDate').value || 'Not set',
      packageName: estimate.packageName,
      guestCount: $('wizGuests').value || 'Not set',
      budgetRange: $('wizBudget').value || 'Not set',
      addOns,
      estimate
    };
  }

  function populateSummary() {
    const draft = getDraftInquiryData();
    $('wizardSummary').innerHTML = `
      <div class="summary-item"><label>Venue</label><span>${draft.venue}</span></div>
      <div class="summary-item"><label>Event</label><span>${draft.event}</span></div>
      <div class="summary-item"><label>Package</label><span>${draft.packageName}</span></div>
      <div class="summary-item"><label>Guests</label><span>${draft.guestCount}</span></div>
      <div class="summary-item"><label>Preferred Date</label><span>${draft.preferredDate}</span></div>
      <div class="summary-item"><label>Backup Date</label><span>${draft.backupDate}</span></div>
      <div class="summary-item"><label>Budget</label><span>${draft.budgetRange}</span></div>
      <div class="summary-item"><label>Add-ons</label><span>${draft.addOns.length ? draft.addOns.join(', ') : 'None'}</span></div>
      <div class="summary-item"><label>Estimated Total</label><span>${formatCurrency(draft.estimate.total)}</span></div>`;
  }

  function validateCurrentStep() {
    if (currentStep === 2) {
      const pkg = PACKAGE_DATA[$('wizPackage').value];
      const guests = parseInt($('wizGuests').value, 10);
      if (Number.isNaN(guests) || guests < 50) {
        showToast('Please enter a valid guest count (minimum 50).', '#c0392b');
        return false;
      }
      if (guests > pkg.pax) {
        showToast(`${pkg.name} is limited to ${pkg.pax} guests max.`, '#c0392b');
        return false;
      }
    }
    if (currentStep === 3) {
      const preferred = $('wizPreferredDate').value;
      const backup = $('wizBackupDate').value;
      if (!preferred || !backup) {
        showToast('Add both preferred and backup dates.', '#c0392b');
        return false;
      }
      if (preferred === backup) {
        showToast('Preferred and backup dates must be different.', '#c0392b');
        return false;
      }
    }
    return true;
  }

  function goToStep(step) {
    if (step < 1 || step > 4) return;
    if (step > currentStep && !validateCurrentStep()) return;
    if (step === 4) populateSummary();

    document.querySelectorAll('.wizard-step').forEach((panel) => panel.classList.remove('active'));
    $(`wizardStep${step}`).classList.add('active');
    document.querySelectorAll('.p-step').forEach((item, index) => {
      item.classList.toggle('active', index + 1 === step);
      item.classList.toggle('completed', index + 1 < step);
    });
    $('wizardBar').style.width = `${(step / 4) * 100}%`;
    $('wizPrev').style.display = step === 1 ? 'none' : 'block';
    $('wizNext').style.display = step === 4 ? 'none' : 'block';
    $('wizSubmit').style.display = step === 4 ? 'block' : 'none';
    currentStep = step;
  }

  function submitInquiry() {
    showToast('Sign in on the login page before sending your inquiry.', '#c0392b');
    setTimeout(() => { window.location.href = 'login.php'; }, 800);
  }

  function validateInput(event) {
    const el = event.target;
    if (!el.value) {
      el.classList.remove('valid', 'invalid');
      return;
    }
    const valid = el.checkValidity();
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

    items.forEach((item, index) => item.element.addEventListener('click', () => {
      currentIndex = index;
      updateLightbox();
      lb.classList.add('open');
      document.body.style.overflow = 'hidden';
    }));
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
    $('hamburger').onclick = () => $('mobileMenu').classList.add('open');
    $('mobileClose').onclick = closeMobile;
    window.addEventListener('scroll', () => $('navbar').classList.toggle('scrolled', window.scrollY > 60));
    document.querySelectorAll('.reveal').forEach((element) => new IntersectionObserver((entries) => entries.forEach((entry) => { if (entry.isIntersecting) entry.target.classList.add('visible'); }), { threshold: 0.1 }).observe(element));
    initPetals();
    initGalleryDrag();
    initLightbox();
    initDatePickers();
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.onclick = function onAnchor(event) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          event.preventDefault();
          target.scrollIntoView({ behavior: 'smooth' });
        }
      };
    });
    document.querySelectorAll('input, select, textarea').forEach((input) => {
      input.addEventListener('input', validateInput);
      input.addEventListener('change', validateInput);
    });
    document.querySelectorAll('.explorer-tab').forEach((tab) => { tab.onclick = () => switchExplorerView(tab.dataset.view); });
    document.querySelectorAll('.wizard-venue-card').forEach((card) => {
      card.onclick = function onVenueClick() {
        document.querySelectorAll('.wizard-venue-card').forEach((item) => item.classList.remove('active'));
        this.classList.add('active');
        captureWizardState();
      };
    });
    document.querySelectorAll('[data-select-package]').forEach((button) => {
      button.addEventListener('click', () => {
        $('estPackage').value = button.dataset.selectPackage;
        updateCalculator();
      });
    });
    $('estPackage').onchange = updateCalculator;
    $('estGuests').oninput = updateCalculator;
    document.querySelectorAll('.est-addon').forEach((input) => { input.onchange = updateCalculator; });
    $('wizPackage').onchange = syncEstimatorFromWizard;
    $('wizGuests').oninput = syncEstimatorFromWizard;
    document.querySelectorAll('.wiz-addon').forEach((input) => { input.onchange = syncEstimatorFromWizard; });
    $('wizNext').onclick = () => goToStep(currentStep + 1);
    $('wizPrev').onclick = () => goToStep(currentStep - 1);
    $('wizSubmit').onclick = submitInquiry;
    updateCalculator();
    $('wizGuests').value = '';
    updateAuthNote();
    goToStep(1);
  }

  window.closePanelGoTo = closePanelGoTo;
  boot();
})();
