(() => {
  const $ = (id) => document.getElementById(id);
  let PACKAGE_DATA = window.MockStore?.getPackageData() || {};
  let PACKAGE_ID_BY_KEY = {};
  let VENUE_ID_BY_NAME = {};
  let UNAVAILABLE_PREFERRED_DATES = [];
  let preferredDatePicker = null;
  let backupDatePicker = null;
  const SESSION_USER = window.AppSessionUser || null;
  let currentStep = 1;
  let isSyncingEstimate = false;
  const wizardState = { venue: 'Pearl Ballroom', venueId: 0 };
  let EXPLORER_DATA = {
    ballroom: {
      title: 'The Pearl Ballroom',
      desc: 'A masterpiece of gold and light, featuring crystal chandeliers and elegant sightlines for grand celebrations.',
      cap: '500 Guests',
      aes: 'Grand Luxury',
      image: 'image/ballroomVenue.jpg'
    },
    garden: {
      title: 'Wavecrest Garden',
      desc: 'Lush greenery, soft light, and open-air ceremony potential for romantic and nature-led celebrations.',
      cap: '300 Guests',
      aes: 'Natural Elegance',
      image: 'image/gardenWedding.jpg'
    },
    poolside: {
      title: 'Tidal Pool Terrace',
      desc: 'A poolside terrace with shimmering water views and relaxed evening ambiance, suited for cocktail receptions and intimate gatherings.',
      cap: '150 Guests',
      aes: 'Poolside Bliss',
      image: 'image/poolVenue.jpg'
    }
  };

  function getPackageEntries() {
    const entries = Object.entries(PACKAGE_DATA || {});
    return entries.length ? entries : [
      ['ripple', { name: 'Ripple Pack', base: 45000, pax: 100, roomLimit: 2 }],
      ['crest', { name: 'Crest Pack', base: 85000, pax: 200, roomLimit: 5 }],
      ['sovereign', { name: 'Sovereign Wave', base: 150000, pax: 500, roomLimit: 8 }]
    ];
  }

  function normalizePackageKey(rawKey, rawName) {
    const key = String(rawKey || '').toLowerCase();
    const name = String(rawName || '').toLowerCase();
    const source = `${key} ${name}`;
    if (source.includes('ripple')) return 'ripple';
    if (source.includes('crest')) return 'crest';
    if (source.includes('sovereign')) return 'sovereign';
    return key || name.replace(/\s+/g, '-');
  }

  function getPackageCapacity(packageKey) {
    const pkg = PACKAGE_DATA[packageKey] || getPackageEntries().find(([key]) => key === packageKey)?.[1] || null;
    const capacity = Number(pkg?.pax ?? pkg?.guestCapacity ?? pkg?.guest_capacity ?? 0);
    return Number.isFinite(capacity) && capacity > 0 ? capacity : 100;
  }

  function syncGuestCountsToPackage(packageKey) {
    const capacity = getPackageCapacity(packageKey);
    const estGuests = $('estGuests');
    const wizGuests = $('wizGuests');
    if (estGuests) estGuests.value = String(capacity);
    if (wizGuests) wizGuests.value = String(capacity);
    $('guestCountLabel').textContent = String(capacity);
  }

  function addonIcon(name) {
    return (name || '')
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('') || '+';
  }

  function packageIcon(name) {
    return (name || '')
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('') || 'PK';
  }

  function packageContentProfile(key, packageData) {
    const packageKey = String(key || '').toLowerCase();
    const name = packageData?.name || '';
    const capacity = Number(packageData?.guestCapacity ?? packageData?.guest_capacity ?? packageData?.pax ?? 0);
    const base = Number(packageData?.basePrice ?? packageData?.base_price ?? packageData?.base ?? 0);
    const tagline = packageData?.tagline || '';

    const profiles = {
      ripple: {
        badge: '',
        summary: tagline || 'Best for intimate celebrations',
        features: [
          `Good for up to ${capacity || 100} guests`,
          'Venue styling essentials',
          'Planning coordination support',
          'Flexible add-ons through the estimator'
        ]
      },
      crest: {
        badge: 'Most Requested',
        summary: tagline || 'Ideal for mid-size signature events',
        features: [
          `Good for up to ${capacity || 200} guests`,
          'Expanded styling and production support',
          'Balanced inclusions for weddings and debuts',
          'Best paired with tailored add-ons'
        ]
      },
      sovereign: {
        badge: '',
        summary: tagline || 'Built for grand celebrations',
        features: [
          `Good for up to ${capacity || 500} guests`,
          'Premium production flexibility',
          'Designed for ballroom-scale events',
          'Ideal base for custom proposal refinement'
        ]
      }
    };

    const fallback = {
      badge: '',
      summary: tagline || 'Flexible planning starting point',
      features: [
        `Good for up to ${capacity || 0} guests`,
        'Tailored event planning support',
        'Customizable inclusions',
        'Use with the estimator for add-ons'
      ]
    };

    const profile = profiles[packageKey] || fallback;
    return {
      name,
      icon: packageIcon(name),
      summary: profile.summary,
      badge: profile.badge,
      features: profile.features,
      priceLine: `Starts at ${formatCurrency(base)} | ${profile.summary}`
    };
  }

  function bindPackageSelectButtons() {
    document.querySelectorAll('[data-select-package]').forEach((button) => {
      button.onclick = () => {
        const packageKey = button.dataset.selectPackage;
        const estimator = $('estPackage');
        if (estimator) estimator.value = packageKey;
        const wizard = $('wizPackage');
        if (wizard) wizard.value = packageKey;
        syncEstimatorFromWizard();
      };
    });
  }

  function renderPackageUI(packages) {
    if (!Array.isArray(packages) || packages.length === 0) {
      bindPackageSelectButtons();
      return;
    }

    const summaryByKey = {
      ripple: 'Best for intimate celebrations',
      crest: 'Ideal for mid-size signature events',
      sovereign: 'Built for grand celebrations'
    };

    packages.forEach((pkg) => {
      const key = normalizePackageKey(pkg.key || pkg.package_key, pkg.name);
      const card = document.querySelector(`[data-package-card="${key}"]`);
      if (!card) return;

      const name = pkg.name || 'Package';
      const base = Number(pkg.basePrice ?? pkg.base_price ?? 0);
      const capacity = Number(pkg.guestCapacity ?? pkg.guest_capacity ?? 0) || 0;
      const tagline = (pkg.tagline || '').trim();
      const summary = tagline || summaryByKey[key] || 'Flexible planning starting point';

      const nameEl = card.querySelector('.package-name');
      if (nameEl) nameEl.textContent = name;

      const priceEl = card.querySelector('.package-price');
      if (priceEl) priceEl.textContent = `Starts at ${formatCurrency(base)} | ${summary}`;

      const firstFeature = card.querySelector('.package-features li');
      if (firstFeature) firstFeature.textContent = `Good for up to ${capacity} guests`;

      const selectBtn = card.querySelector('[data-select-package]');
      if (selectBtn) selectBtn.dataset.selectPackage = key;
    });

    bindPackageSelectButtons();
  }

  function venueImageByIndex(index) {
    const images = ['image/gardenWedding.jpg', 'image/ballroomVenue.jpg', 'image/poolVenue.jpg'];
    return images[index % images.length];
  }

  function getVenueVisualProfile(venueName, index, guestCapacity = null) {
    const capacity = Number(guestCapacity);
    const hasCapacity = Number.isFinite(capacity) && capacity > 0;
    const capacityLabel = hasCapacity ? `${capacity} Guests` : null;
    const metaCapacityLabel = hasCapacity ? `Up to ${capacity} guests` : null;
    const name = String(venueName || '').toLowerCase();
    if (name.includes('pearl') || name.includes('ballroom')) {
      return {
        key: 'ballroom',
        image: 'image/ballroomVenue.jpg',
        tag: 'Ballroom',
        subtitle: 'Grand Luxury',
        cap: capacityLabel || '500 Guests',
        aes: 'Grand Luxury',
        metaA: metaCapacityLabel || 'Up to 500 guests',
        metaB: '<span>Hall</span> Indoor',
        order: 1
      };
    }
    if (name.includes('wavecrest') || name.includes('garden')) {
      return {
        key: 'garden',
        image: 'image/gardenWedding.jpg',
        tag: 'Garden Venue',
        subtitle: 'Natural Elegance',
        cap: capacityLabel || '300 Guests',
        aes: 'Natural Elegance',
        metaA: metaCapacityLabel || 'Up to 300 guests',
        metaB: '<span>Open</span> Outdoor',
        order: 2
      };
    }
    if (name.includes('tidal') || name.includes('pool')) {
      return {
        key: 'poolside',
        image: 'image/poolVenue.jpg',
        tag: 'Poolside',
        subtitle: 'Poolside Bliss',
        cap: capacityLabel || '150 Guests',
        aes: 'Poolside Bliss',
        metaA: metaCapacityLabel || 'Up to 150 guests',
        metaB: '<span>Deck</span> Semi-outdoor',
        order: 3
      };
    }

    return {
      key: `venue-${index + 1}`,
      image: venueImageByIndex(index),
      tag: 'Event Space',
      subtitle: 'Signature Space',
      cap: capacityLabel || 'Custom Capacity',
      aes: 'Signature Space',
      metaA: metaCapacityLabel || 'Capacity by request',
      metaB: '<span>Custom</span> Flexible setup',
      order: 99 + index
    };
  }

  function orderVenuesForDisplay(venues) {
    return [...venues].sort((a, b) => {
      const aProfile = getVenueVisualProfile(a.name, 0);
      const bProfile = getVenueVisualProfile(b.name, 0);
      if (aProfile.order !== bProfile.order) return aProfile.order - bProfile.order;
      return Number(a.id || 0) - Number(b.id || 0);
    });
  }

  function renderVenueUI(venues) {
    if (!Array.isArray(venues) || venues.length === 0) return;
    const displayVenues = orderVenuesForDisplay(venues);

    const venuesGrid = document.querySelector('.venues-grid');
    if (venuesGrid) {
      venuesGrid.innerHTML = displayVenues.map((venue, index) => {
        const profile = getVenueVisualProfile(venue.name, index, venue.guestCapacity ?? venue.guest_capacity);
        return `
          <div class="venue-card">
            <div class="venue-img"><div class="venue-img-bg" style="background:url('${profile.image}') center/cover;"></div></div>
            <div class="venue-info">
              <div class="venue-tag">${profile.tag}</div>
              <div class="venue-name">${venue.name}</div>
              <div class="venue-desc">${venue.description || 'A signature venue for memorable celebrations.'}</div>
              <div class="venue-meta">
                <div class="venue-meta-item">${profile.metaA}</div>
                <div class="venue-meta-item">${profile.metaB}</div>
              </div>
            </div>
            <div class="venue-overlay"><div class="venue-overlay-text">${venue.name}</div><a href="#contact">Inquire About This Venue</a></div>
          </div>
        `;
      }).join('');
    }

    const wizardVenueGrid = document.querySelector('.venue-cards.triple-grid');
    if (wizardVenueGrid) {
      wizardVenueGrid.innerHTML = displayVenues.map((venue, index) => {
        const profile = getVenueVisualProfile(venue.name, index, venue.guestCapacity ?? venue.guest_capacity);
        return `
          <div class="wizard-venue-card${index === 0 ? ' active' : ''}" data-venue="${venue.name}" data-venue-id="${Number(venue.id || 0)}">
            <div class="venue-card-img" style="background:url('${profile.image}') center/cover"></div>
            <div class="venue-card-info">
              <h4>${venue.name}</h4>
              <p>${profile.subtitle} | ${profile.cap}</p>
            </div>
          </div>
        `;
      }).join('');
      wizardState.venue = displayVenues[0].name;
      wizardState.venueId = Number(displayVenues[0].id || 0);
    }
  }

  function bindWizardVenueListeners() {
    document.querySelectorAll('.wizard-venue-card').forEach((card) => {
      card.onclick = function onVenueClick() {
        document.querySelectorAll('.wizard-venue-card').forEach((item) => item.classList.remove('active'));
        this.classList.add('active');
        captureWizardState();
      };
    });
  }

  function renderExplorerFromVenues(venues) {
    if (!Array.isArray(venues) || venues.length === 0) return;
    const displayVenues = orderVenuesForDisplay(venues);

    EXPLORER_DATA = {};
    displayVenues.forEach((venue, index) => {
      const key = `venue${index + 1}`;
      const profile = getVenueVisualProfile(venue.name, index, venue.guestCapacity ?? venue.guest_capacity);
      EXPLORER_DATA[key] = {
        title: venue.name || `Venue ${index + 1}`,
        desc: venue.description || 'A signature venue for memorable celebrations.',
        cap: profile.cap,
        aes: profile.aes,
        image: profile.image
      };
    });

    const controls = document.querySelector('.explorer-controls');
    if (controls) {
      controls.innerHTML = Object.entries(EXPLORER_DATA).map(([key, item], index) => (
        `<button class="explorer-tab${index === 0 ? ' active' : ''}" data-view="${key}">${item.title}</button>`
      )).join('');
    }

    const stage = $('explorerStage');
    if (stage) {
      stage.innerHTML = Object.entries(EXPLORER_DATA).map(([key, item], index) => (
        `<div class="explorer-bg${index === 0 ? ' active' : ''}" data-view="${key}" style="background-image:url('${item.image}')"></div>`
      )).join('');
    }

    const firstKey = Object.keys(EXPLORER_DATA)[0];
    if (firstKey) switchExplorerView(firstKey);
    bindExplorerTabListeners();
  }

  function bindExplorerTabListeners() {
    document.querySelectorAll('.explorer-tab').forEach((tab) => {
      tab.onclick = () => switchExplorerView(tab.dataset.view);
    });
  }

  function renderCatalogUI(catalog) {
    const packages = Array.isArray(catalog?.packages) ? catalog.packages : [];
    const amenities = Array.isArray(catalog?.amenities) ? catalog.amenities : [];
    const venues = Array.isArray(catalog?.venues) ? catalog.venues : [];
    UNAVAILABLE_PREFERRED_DATES = Array.isArray(catalog?.unavailablePreferredDates)
      ? catalog.unavailablePreferredDates.filter((value) => /^\d{4}-\d{2}-\d{2}$/.test(String(value || '').trim()))
      : [];

    PACKAGE_ID_BY_KEY = {};
    packages.forEach((pkg) => {
      const id = Number(pkg.id || 0);
      if (!id) return;
      const normalized = normalizePackageKey(pkg.key || pkg.package_key, pkg.name);
      if (normalized) PACKAGE_ID_BY_KEY[String(normalized).toLowerCase()] = id;
      const rawKey = String(pkg.key || pkg.package_key || '').toLowerCase();
      if (rawKey) PACKAGE_ID_BY_KEY[rawKey] = id;
    });

    VENUE_ID_BY_NAME = {};
    venues.forEach((venue) => {
      const id = Number(venue.id || 0);
      const name = String(venue.name || '').trim().toLowerCase();
      if (id && name) VENUE_ID_BY_NAME[name] = id;
    });

    const packageEntries = packages.length
      ? packages.map((pkg) => [normalizePackageKey(pkg.key, pkg.name), {
          name: pkg.name,
          base: Number(pkg.basePrice || 0),
          pax: Number(pkg.guestCapacity || 0),
          roomLimit: Number(pkg.maxPrivateRooms || 0)
        }])
      : getPackageEntries();

    if (packageEntries.length) {
      PACKAGE_DATA = Object.fromEntries(packageEntries.map(([key, data]) => [key, data]));
    } else if (catalog?.packageData && Object.keys(catalog.packageData).length) {
      PACKAGE_DATA = catalog.packageData;
    }

    const estPackage = $('estPackage');
    const wizPackage = $('wizPackage');
    if (estPackage && wizPackage) {
      const currentEst = estPackage.value;
      const currentWiz = wizPackage.value;
      const options = packageEntries.map(([key, data]) => (
        `<option value="${key}">${data.name} (${formatCurrency(data.base)})</option>`
      )).join('');
      estPackage.innerHTML = options;
      wizPackage.innerHTML = packageEntries.map(([key, data]) => (
        `<option value="${key}">${data.name}</option>`
      )).join('');
      estPackage.value = packageEntries.some(([key]) => key === currentEst) ? currentEst : packageEntries[0][0];
      wizPackage.value = packageEntries.some(([key]) => key === currentWiz) ? currentWiz : packageEntries[0][0];
    }

    renderPackageUI(packages);

    const estGrid = document.querySelector('.est-grid');
    const wizGrid = document.querySelector('.extras-grid');
    if (amenities.length && estGrid && wizGrid) {
      estGrid.innerHTML = amenities.map((item) => `
        <label class="est-check-container">
          <input type="checkbox" class="est-addon" data-price="${Number(item.price || 0)}" value="${item.name}">
          <span class="est-checkmark"></span> ${item.name} (${formatCurrency(item.price || 0)})
        </label>
      `).join('');

      wizGrid.innerHTML = amenities.map((item) => `
        <label class="extra-item">
          <input type="checkbox" class="wiz-addon" data-price="${Number(item.price || 0)}" value="${item.name}">
          <div class="extra-box">
            <span class="extra-icon">${addonIcon(item.name)}</span>
            <div class="extra-text">
              <strong>${item.name}</strong>
              <span>${formatCurrency(item.price || 0)}</span>
            </div>
          </div>
        </label>
      `).join('');
    }

    renderVenueUI(venues);
    renderExplorerFromVenues(venues);
    bindWizardVenueListeners();
    applyDateAvailabilityRules();
  }

  async function loadCatalogFromDb() {
    try {
      const response = await fetch('assets/actions/get_catalog_data.php', { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (data.success) {
        renderCatalogUI(data);
        bindAddonSyncListeners();
        updateCalculator();
      }
    } catch (error) {
      // Keep existing static/mock catalog if API is unavailable.
    }
  }

  function formatCurrency(amount) {
    return `PHP ${Number(amount || 0).toLocaleString('en-PH')}`;
  }

  function getAddOnPrice(name) {
    const input = Array.from(document.querySelectorAll('.wiz-addon, .est-addon')).find((item) => item.value === name);
    return input ? parseInt(input.dataset.price || '0', 10) : 0;
  }

  function calculateEstimate(packageKey, addOns) {
    const pkg = PACKAGE_DATA[packageKey] || PACKAGE_DATA.crest || getPackageEntries()[0][1];
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
    if (SESSION_USER?.isLoggedIn) {
      note.textContent = `Signed in as ${SESSION_USER.firstName || 'User'} ${SESSION_USER.lastName || ''}. You can submit this inquiry now.`.trim();
      return;
    }
    note.textContent = 'Sign in through the login page before sending this inquiry.';
  }

  function prefillWizardUserFields() {
    if (!SESSION_USER?.isLoggedIn) return;
    const first = $('wizFirst');
    const last = $('wizLast');
    const email = $('wizEmail');
    const phone = $('wizPhone');
    if (first && !first.value.trim()) first.value = SESSION_USER.firstName || '';
    if (last && !last.value.trim()) last.value = SESSION_USER.lastName || '';
    if (email && !email.value.trim()) email.value = SESSION_USER.email || '';
    if (phone && !phone.value.trim()) phone.value = SESSION_USER.phone || '';
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
    const pkg = PACKAGE_DATA[packageKey] || getPackageEntries()[0][1];
    const addOnTotal = Array.from(document.querySelectorAll('.est-addon:checked')).reduce((sum, input) => sum + parseInt(input.dataset.price, 10), 0);
    const total = pkg.base + addOnTotal;

    $('totalPrice').textContent = formatCurrency(total);
    const isOver = guests > pkg.pax;
    $('estSummary').innerHTML = `<strong>${pkg.name}</strong> (${guests} Guests)${isOver ? ' <span style="color:var(--red)">[OVER CAPACITY]</span>' : ''}<br>Base Price: ${formatCurrency(pkg.base)}<br>Add-ons: ${formatCurrency(addOnTotal)}`;

    if (!isSyncingEstimate) syncWizardFromEstimator(total);
    setPackageCardState(packageKey);
    updateGuestLimits();
  }

  function sanitizeWizardGuestInput() {
    const guestInput = $('wizGuests');
    if (!guestInput) return;
    guestInput.value = String(guestInput.value || '').replace(/\D+/g, '');
  }

  function syncEstimatorFromWizard() {
    if (isSyncingEstimate) return;
    sanitizeWizardGuestInput();
    isSyncingEstimate = true;
    $('estPackage').value = $('wizPackage').value;
    $('estGuests').value = $('wizGuests').value;
    setEstimatorAddOns(getSelectedWizardAddOns());
    isSyncingEstimate = false;
    updateCalculator();
  }

  function bindAddonSyncListeners() {
    document.querySelectorAll('.est-addon').forEach((input) => { input.onchange = updateCalculator; });
    document.querySelectorAll('.wiz-addon').forEach((input) => { input.onchange = syncEstimatorFromWizard; });
  }

  function captureWizardState() {
    const activeVenue = document.querySelector('.wizard-venue-card.active');
    wizardState.venue = activeVenue?.dataset.venue || 'Pearl Ballroom';
    wizardState.venueId = Number(activeVenue?.dataset.venueId || VENUE_ID_BY_NAME[String(wizardState.venue || '').trim().toLowerCase()] || 0);
  }

  function getDraftInquiryData() {
    captureWizardState();
    const packageKey = $('wizPackage').value;
    const addOns = getSelectedWizardAddOns();
    const estimate = calculateEstimate(packageKey, addOns);
    return {
      packageKey,
      packageId: Number(PACKAGE_ID_BY_KEY[String(packageKey || '').toLowerCase()] || 0),
      event: $('wizEvent').value,
      venue: wizardState.venue,
      venueId: Number(wizardState.venueId || 0),
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
      if (backup < preferred) {
        showToast('Backup date must be after the preferred date.', '#c0392b');
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
    if (SESSION_USER?.isLoggedIn) {
      const first = $('wizFirst').value.trim();
      const last = $('wizLast').value.trim();
      const email = $('wizEmail').value.trim();
      const phone = $('wizPhone').value.trim();

      if (!first || !last || !email || !phone) {
        showToast('Please complete all contact fields before submitting.', '#c0392b');
        return;
      }

      const draft = getDraftInquiryData();
      const selectedAddOns = Array.from(new Set([
        ...getSelectedWizardAddOns(),
        ...getSelectedEstimatorAddOns()
      ]));
      fetch('assets/actions/submit_inquiry.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          event: draft.event,
          venue: draft.venue,
          venueId: draft.venueId,
          packageKey: draft.packageKey,
          packageId: draft.packageId,
          preferredDate: draft.preferredDate,
          backupDate: draft.backupDate,
          packageName: draft.packageName,
          guestCount: parseInt(String(draft.guestCount || '').replace(/[^0-9]/g, ''), 10) || 0,
          budgetRange: draft.budgetRange,
          requestedRooms: 0,
          estimatedTotal: draft.estimate.total,
          notes: $('wizNotes').value.trim(),
          addOns: selectedAddOns,
          contact: { first, last, email, phone }
        })
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            showToast(data.message || 'Failed to submit inquiry.', '#c0392b');
            return;
          }
          const params = new URLSearchParams({
            ref: data.reference || `INQ-${Date.now()}`,
            package: data.package || draft.packageName,
            venue: data.venue || draft.venue || 'Not set',
            amenities: data.amenities || (draft.addOns.length ? draft.addOns.join(', ') : 'None'),
            total: data.total || formatCurrency(draft.estimate.total)
          });
          showToast('Inquiry submitted successfully. Redirecting...');
          setTimeout(() => { window.location.href = `inquiry-success.php?${params.toString()}`; }, 800);
        })
        .catch(() => {
          showToast('Could not submit inquiry to server.', '#c0392b');
        });
      return;
    }
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
    preferredDatePicker = flatpickr('#wizPreferredDate', {
      minDate: 'today',
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'F j, Y',
      disableMobile: true,
      onChange: (selectedDates, dateStr) => {
        if (!backupDatePicker) return;
        const nextMin = dateStr || 'today';
        backupDatePicker.set('minDate', nextMin);
        const backupValue = $('wizBackupDate').value;
        if (backupValue && backupValue <= dateStr) {
          backupDatePicker.clear();
          showToast('Please pick a backup date after your preferred date.', '#c0392b');
        }
        applyDateAvailabilityRules();
      }
    });
    backupDatePicker = flatpickr('#wizBackupDate', {
      minDate: 'today',
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'F j, Y',
      disableMobile: true,
      onClose: () => {
        const preferred = $('wizPreferredDate').value;
        const backup = $('wizBackupDate').value;
        if (preferred && backup && backup <= preferred) {
          backupDatePicker.clear();
          showToast('Backup date must be after your preferred date.', '#c0392b');
        }
      }
    });
    applyDateAvailabilityRules();
  }

  function applyDateAvailabilityRules() {
    const unavailable = Array.from(new Set(UNAVAILABLE_PREFERRED_DATES));
    const preferred = $('wizPreferredDate')?.value || '';

    if (preferredDatePicker) {
      preferredDatePicker.set('disable', unavailable);
    }

    if (backupDatePicker) {
      const backupDisabled = preferred ? unavailable.filter((date) => date !== preferred) : unavailable;
      backupDatePicker.set('disable', backupDisabled);
      backupDatePicker.set('minDate', preferred || 'today');
    }
  }

  function switchExplorerView(view) {
    const data = EXPLORER_DATA[view];
    if (!data) return;
    document.querySelectorAll('.explorer-tab').forEach((tab) => tab.classList.toggle('active', tab.dataset.view === view));
    document.querySelectorAll('.explorer-bg').forEach((bg) => bg.classList.toggle('active', bg.dataset.view === view));
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
    bindPackageSelectButtons();
    bindExplorerTabListeners();
    bindWizardVenueListeners();
    $('estPackage').onchange = updateCalculator;
    $('estGuests').oninput = updateCalculator;
    $('wizPackage').onchange = syncEstimatorFromWizard;
    $('wizGuests').oninput = () => {
      sanitizeWizardGuestInput();
      syncEstimatorFromWizard();
    };
    bindAddonSyncListeners();
    $('wizNext').onclick = () => goToStep(currentStep + 1);
    $('wizPrev').onclick = () => goToStep(currentStep - 1);
    $('wizSubmit').onclick = submitInquiry;
    updateCalculator();
    $('wizGuests').value = '';
    prefillWizardUserFields();
    updateAuthNote();
    goToStep(1);
    loadCatalogFromDb();
  }

  window.closePanelGoTo = closePanelGoTo;
  boot();
})();
