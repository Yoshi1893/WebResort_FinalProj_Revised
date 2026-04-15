document.addEventListener('DOMContentLoaded', () => {
  const store = window.MockStore;
  const $ = (id) => document.getElementById(id);
  const asArray = (value) => Array.isArray(value) ? value : [];
  const asNumber = (value) => Number(value || 0);
  let dbUsers = null;
  let dbInquiries = null;
  let dbPackages = null;
  let dbAmenities = null;
  let dbVenues = null;

  function showToast(message, background) {
    const toast = $('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.background = background || 'var(--sage-dark)';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
  }

  function formatCurrency(amount) {
    return `PHP ${Number(amount || 0).toLocaleString('en-PH')}`;
  }

  function statusInfo(status) {
    const map = {
      submitted: { label: 'Submitted', className: 'status-submitted' },
      review: { label: 'Under Review', className: 'status-review' },
      proposal: { label: 'Proposal Sent', className: 'status-proposal' },
      closed: { label: 'Closed', className: 'status-closed' }
    };
    return map[status] || map.submitted;
  }

  function initialsFor(item) {
    return `${(item.firstName || '').charAt(0)}${(item.lastName || '').charAt(0)}`.toUpperCase() || 'AD';
  }

  function normalizeUser(item) {
    return {
      id: asNumber(item.id),
      firstName: item.firstName || item.first_name || '',
      lastName: item.lastName || item.last_name || '',
      email: item.email || '',
      phone: item.phone || '',
      role: (item.role || 'customer').toLowerCase(),
      status: item.status || (item.archived ? 'revoked' : 'active'),
      archived: item.archived === true || item.archived === 1 || item.archived === '1'
    };
  }

  function normalizeInquiry(item) {
    return {
      id: asNumber(item.id),
      reference: item.reference || 'N/A',
      customerName: item.customerName || item.customer_name || item.full_name || 'Unknown Customer',
      customerEmail: item.customerEmail || item.customer_email || item.email || 'No email',
      event: item.event || item.event_type || 'Not specified',
      venue: item.venue || item.venue_name || 'No venue',
      packageName: item.packageName || item.package_name || 'No package',
      packageKey: item.packageKey || item.package_key || '',
      requestedRooms: item.requestedRooms ?? item.requested_rooms ?? 0,
      status: item.status || 'submitted',
      preferredDate: item.preferredDate || item.preferred_date || item.event_date || 'Not set',
      backupDate: item.backupDate || item.backup_date || 'Not set',
      amenities: Array.isArray(item.amenities)
        ? item.amenities
        : (typeof item.amenities === 'string' && item.amenities.trim() !== '' ? [item.amenities] : []),
      estimatedTotal: item.estimatedTotal ?? item.estimated_total ?? 0,
      notes: item.notes || 'No notes provided.',
      submittedAt: item.submittedAt || item.created_at || 'Unknown date'
    };
  }

  function snapshot() {
    const safeCall = (fn, fallback) => {
      try {
        return typeof fn === 'function' ? fn() : fallback;
      } catch (error) {
        return fallback;
      }
    };

    return {
      admin: safeCall(store?.getAdminUser, { firstName: 'Admin', lastName: 'User' }),
      users: asArray(safeCall(store?.getUsers, [])),
      inquiries: asArray(safeCall(store?.getInquiries, [])),
      packages: asArray(safeCall(store?.getPackages, [])),
      amenities: asArray(safeCall(store?.getAmenities, [])),
      venues: asArray(safeCall(store?.getVenues, [])),
      rooms: asArray(safeCall(store?.getRooms, []))
    };
  }

  function getUsersSource() {
    if (Array.isArray(dbUsers)) return dbUsers;
    const { users } = snapshot();
    return users.map(normalizeUser);
  }

  function getInquiriesSource() {
    if (Array.isArray(dbInquiries)) return dbInquiries;
    const { inquiries } = snapshot();
    return inquiries.map(normalizeInquiry);
  }

  function getPackagesSource() {
    if (Array.isArray(dbPackages)) return dbPackages;
    const { packages } = snapshot();
    return packages;
  }

  function getAmenitiesSource() {
    if (Array.isArray(dbAmenities)) return dbAmenities;
    const { amenities } = snapshot();
    return amenities;
  }

  function getVenuesSource() {
    if (Array.isArray(dbVenues)) return dbVenues;
    const { venues } = snapshot();
    return venues;
  }

  async function loadUsersFromDb() {
    try {
      const response = await fetch('assets/actions/get_admin_users.php', { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (data.success && Array.isArray(data.users)) {
        dbUsers = data.users.map(normalizeUser);
      }
    } catch (error) {
      dbUsers = null;
    }
    renderStats();
    renderUsers();
  }

  async function loadInquiriesFromDb() {
    try {
      const response = await fetch('assets/actions/get_admin_inquiries.php', { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (data.success && Array.isArray(data.inquiries)) {
        dbInquiries = data.inquiries.map(normalizeInquiry);
      } else {
        dbInquiries = [];
        showToast(data.message || 'Failed to load inquiry logs from DB.', '#c0392b');
      }
    } catch (error) {
      dbInquiries = [];
      showToast('Could not reach inquiry logs endpoint.', '#c0392b');
    }
    renderStats();
    renderInquiries();
  }

  async function loadPackagesFromDb() {
    try {
      const response = await fetch('assets/actions/get_admin_packages.php', { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (data.success && Array.isArray(data.packages)) {
        dbPackages = data.packages;
      }
    } catch (error) {
      dbPackages = null;
    }
    renderStats();
    renderPackages();
  }

  async function loadAmenitiesFromDb() {
    try {
      const response = await fetch('assets/actions/get_admin_amenities.php', { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (data.success && Array.isArray(data.amenities)) {
        dbAmenities = data.amenities;
      }
    } catch (error) {
      dbAmenities = null;
    }
    renderStats();
    renderAmenities();
  }

  async function loadVenuesFromDb() {
    try {
      const response = await fetch('assets/actions/get_admin_venues.php', { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (data.success && Array.isArray(data.venues)) {
        dbVenues = data.venues;
      }
    } catch (error) {
      dbVenues = null;
    }
    renderVenues();
  }

  function renderStats() {
    const { admin } = snapshot();
    const packages = getPackagesSource();
    const amenities = getAmenitiesSource();
    const users = getUsersSource();
    const inquiries = getInquiriesSource();
    $('statPackages').textContent = packages.filter((item) => (item.active ?? item.is_active) === true).length;
    $('statAmenities').textContent = amenities.filter((item) => (item.active ?? item.is_active) === true).length;
    $('statUsers').textContent = users.filter((item) => (item.role || '').toLowerCase() === 'customer').length;
    $('statInquiries').textContent = inquiries.length;
    $('adminAvatar').textContent = initialsFor(admin);
    $('adminName').textContent = `${admin.firstName || admin.first_name || 'Admin'} ${admin.lastName || admin.last_name || 'User'}`;
  }

  function resetPackageForm() {
    $('packageForm').reset();
    $('packageId').value = '';
    $('packageActive').value = 'true';
    setPackageFormEditing(false);
  }

  function setPackageFormEditing(isEditing) {
    const fieldIds = [
      'packageName',
      'packageBasePrice',
      'packageGuestCapacity',
      'packageRoomLimit',
      'packageTagline',
      'packageActive'
    ];
    fieldIds.forEach((fieldId) => {
      const field = $(fieldId);
      if (field) field.disabled = !isEditing;
    });
    const submitBtn = $('packageSubmit');
    const resetBtn = $('packageReset');
    if (submitBtn) submitBtn.disabled = !isEditing;
    if (resetBtn) resetBtn.disabled = !isEditing;
  }

  function resetAmenityForm() {
    $('amenityForm').reset();
    $('amenityId').value = '';
    $('amenityActive').value = 'true';
    setAmenityFormEditing(false);
  }

  function setAmenityFormEditing(isEditing) {
    const fieldIds = ['amenityName', 'amenityPrice', 'amenityActive'];
    fieldIds.forEach((fieldId) => {
      const field = $(fieldId);
      if (field) field.disabled = !isEditing;
    });
    const submitBtn = $('amenitySubmit');
    const resetBtn = $('amenityReset');
    if (submitBtn) submitBtn.disabled = !isEditing;
    if (resetBtn) resetBtn.disabled = !isEditing;
  }

  function resetVenueForm() {
    $('venueForm').reset();
    $('venueId').value = '';
    $('venueCapacity').value = '';
    $('venueActive').value = 'true';
    setVenueFormEditing(false);
  }

  function setVenueFormEditing(isEditing) {
    const fieldIds = ['venueName', 'venueDescription', 'venueCapacity', 'venueActive'];
    fieldIds.forEach((fieldId) => {
      const field = $(fieldId);
      if (field) field.disabled = !isEditing;
    });
    const submitBtn = $('venueSubmit');
    const resetBtn = $('venueReset');
    if (submitBtn) submitBtn.disabled = !isEditing;
    if (resetBtn) resetBtn.disabled = !isEditing;
  }


  function renderPackages() {
    const packages = getPackagesSource();
    $('packageTableBody').innerHTML = packages.map((item) => `
      <tr>
        <td>${item.name || 'Unnamed Package'}</td>
        <td>${formatCurrency(item.basePrice ?? item.base_price)}</td>
        <td>${Number(item.guestCapacity ?? item.guest_capacity ?? 0)} guests</td>
        <td>${item.maxPrivateRooms ?? item.max_private_rooms ?? 0}</td>
        <td><span class="status-badge ${(item.active ?? item.is_active) ? 'status-proposal' : 'status-closed'}">${(item.active ?? item.is_active) ? 'Active' : 'Inactive'}</span></td>
        <td><button class="tbl-btn tbl-confirm" data-package-edit="${item.id}">Edit</button></td>
      </tr>`).join('');
    document.querySelectorAll('[data-package-edit]').forEach((button) => {
      button.addEventListener('click', () => {
        const { packages } = snapshot();
        const source = getPackagesSource();
        const item = source.find((pkg) => pkg.id === Number(button.dataset.packageEdit));
        if (!item) return;
        $('packageId').value = item.id;
        $('packageName').value = item.name || '';
        $('packageBasePrice').value = item.basePrice ?? item.base_price ?? 0;
        $('packageGuestCapacity').value = item.guestCapacity ?? item.guest_capacity ?? 0;
        $('packageRoomLimit').value = item.maxPrivateRooms ?? item.max_private_rooms ?? 0;
        $('packageTagline').value = item.tagline || '';
        $('packageActive').value = String(item.active ?? item.is_active ?? true);
        setPackageFormEditing(true);
      });
    });
  }

  function renderAmenities() {
    const amenities = getAmenitiesSource();
    $('amenityTableBody').innerHTML = amenities.map((item) => `
      <tr>
        <td>${item.name || 'Unnamed Amenity'}</td>
        <td>${formatCurrency(item.price)}</td>
        <td><span class="status-badge ${(item.active ?? item.is_active) ? 'status-proposal' : 'status-closed'}">${(item.active ?? item.is_active) ? 'Active' : 'Inactive'}</span></td>
        <td><button class="tbl-btn tbl-confirm" data-amenity-edit="${item.id}">Edit</button></td>
      </tr>`).join('');
    document.querySelectorAll('[data-amenity-edit]').forEach((button) => {
      button.addEventListener('click', () => {
        const source = getAmenitiesSource();
        const item = source.find((amenity) => amenity.id === Number(button.dataset.amenityEdit));
        if (!item) return;
        $('amenityId').value = item.id;
        $('amenityName').value = item.name || '';
        $('amenityPrice').value = item.price ?? 0;
        $('amenityActive').value = String(item.active ?? item.is_active ?? true);
        setAmenityFormEditing(true);
      });
    });
  }

  function renderVenues() {
    const table = $('venueTableBody');
    if (!table) return;
    const venues = getVenuesSource();
    table.innerHTML = venues.map((item) => `
      <tr>
        <td>${item.name || 'Unnamed Venue'}</td>
        <td>${item.description || item.note || 'No description'}</td>
        <td>${Number(item.guestCapacity ?? item.guest_capacity ?? item.capacity ?? 0)} guests</td>
        <td><span class="status-badge ${(item.active ?? item.is_active) ? 'status-proposal' : 'status-closed'}">${(item.active ?? item.is_active) ? 'Active' : 'Inactive'}</span></td>
        <td><button class="tbl-btn tbl-confirm" data-venue-edit="${item.id}">Edit</button></td>
      </tr>`).join('');
    document.querySelectorAll('[data-venue-edit]').forEach((button) => {
      button.addEventListener('click', () => {
        const source = getVenuesSource();
        const item = source.find((venue) => venue.id === Number(button.dataset.venueEdit));
        if (!item) return;
        $('venueId').value = item.id;
        $('venueName').value = item.name || '';
        $('venueDescription').value = item.description || item.note || '';
        $('venueCapacity').value = Number(item.guestCapacity ?? item.guest_capacity ?? item.capacity ?? 0);
        $('venueActive').value = String(item.active ?? item.is_active ?? true);
        setVenueFormEditing(true);
      });
    });
  }

  function renderRooms() {
    const table = $('roomTableBody');
    if (!table) return;
    const { rooms, venues } = snapshot();
    table.innerHTML = rooms.map((item) => {
      const venue = venues.find((entry) => Number(entry.id) === Number(item.venueId ?? item.venue_id));
      return `
        <tr>
          <td>${item.name || 'Unnamed Room'}</td>
          <td>${venue ? venue.name : 'Unknown Venue'}</td>
          <td><span class="status-badge ${(item.active ?? item.is_active) ? 'status-proposal' : 'status-closed'}">${(item.active ?? item.is_active) ? 'Active' : 'Inactive'}</span></td>
        </tr>`;
    }).join('');
  }


  function renderUsers() {
    const users = getUsersSource();
    const search       = $('userSearch').value.trim().toLowerCase();
    const role         = $('userRoleFilter').value;
    const statusFilter = $('userStatusFilter').value;

    const filtered = users.filter((item) => {
      const firstName = item.firstName || item.first_name || '';
      const lastName = item.lastName || item.last_name || '';
      const email = item.email || '';
      const matchesSearch = !search || `${firstName} ${lastName} ${email}`.toLowerCase().includes(search);
      const matchesRole   = role === 'all' || item.role === role;
      // Support both mock (archived bool) and real DB (status string)
      const userStatus    = item.status || (item.archived ? 'revoked' : 'active');
      const matchesStatus = statusFilter === 'all' || userStatus === statusFilter;
      return matchesSearch && matchesRole && matchesStatus;
    });

    $('userTableBody').innerHTML = filtered.map((item) => {
      const userStatus   = item.status || (item.archived ? 'revoked' : 'active');
      const isRevoked    = userStatus === 'revoked';
      const isDeleted    = userStatus === 'deleted';
      const badgeClass   = userStatus === 'active' ? 'status-proposal' : 'status-closed';
      const badgeLabel   = userStatus.charAt(0).toUpperCase() + userStatus.slice(1);

      let actionCell = '<span class="admin-muted">Restricted</span>';
      if (item.role === 'customer' && !isDeleted) {
        actionCell = `
          <button
            class="tbl-btn ${isRevoked ? 'tbl-confirm' : 'tbl-cancel'}"
            data-user-revoke="${item.id}"
            data-current-status="${userStatus}">
            ${isRevoked ? 'Restore' : 'Revoke'}
          </button>`;
      } else if (isDeleted) {
        actionCell = '<span class="admin-muted">Deleted</span>';
      }

      return `
        <tr>
          <td>${item.firstName || item.first_name || ''} ${item.lastName || item.last_name || ''}<br><span class="admin-muted">${item.email || ''}</span></td>
          <td>${item.role}</td>
          <td>${item.phone || 'Not provided'}</td>
          <td><span class="status-badge ${badgeClass}">${badgeLabel}</span></td>
          <td>${actionCell}</td>
        </tr>`;
    }).join('');

    // REVOKE / RESTORE — calls backend, falls back to mock if unavailable
    document.querySelectorAll('[data-user-revoke]').forEach((button) => {
      button.addEventListener('click', () => {
        const userId       = button.dataset.userRevoke;
        const currentStatus = button.dataset.currentStatus;
        const action       = currentStatus === 'revoked' ? 'restore' : 'revoke';
        const reason       = action === 'revoke'
          ? (prompt('Reason for revoking access (optional):') || 'No reason provided')
          : 'Access restored by admin';

        fetch('assets/actions/revoke_user.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `user_id=${userId}&action=${action}&reason=${encodeURIComponent(reason)}`
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
              loadUsersFromDb();
              showToast(`User ${action === 'revoke' ? 'revoked' : 'restored'} successfully.`);
            } else {
              showToast(data.message || 'Action failed.', '#c0392b');
            }
          })
          .catch(() => {
            showToast('Could not reach revoke/restore endpoint. No changes were applied.', '#c0392b');
          });
      });
    });
  }

  function renderInquiries() {
    const inquiries = getInquiriesSource();
    const search      = $('inquirySearch').value.trim().toLowerCase();
    const status      = $('inquiryStatusFilter').value;
    const eventType   = $('inquiryEventFilter').value;
    const packageKey  = $('inquiryPackageFilter').value;
    const sort        = $('inquirySort').value;

    const filtered = inquiries
      .filter((item) => {
        const customerName = item.customerName || item.customer_name || item.full_name || 'Unknown Customer';
        const customerEmail = item.customerEmail || item.customer_email || item.email || 'No email';
        const event = item.event || item.event_type || 'Not specified';
        const inquiryPackageKey = item.packageKey || item.package_key || '';
        const haystack      = `${customerName} ${customerEmail}`.toLowerCase();
        const matchesSearch  = !search || haystack.includes(search);
        const matchesStatus  = status === 'all' || item.status === status;
        const matchesEvent   = eventType === 'all' || event === eventType;
        const matchesPackage = packageKey === 'all' || inquiryPackageKey === packageKey;
        return matchesSearch && matchesStatus && matchesEvent && matchesPackage;
      })
      .sort((a, b) => sort === 'asc'
        ? new Date(a.submittedAt || a.created_at || 0) - new Date(b.submittedAt || b.created_at || 0)
        : new Date(b.submittedAt || b.created_at || 0) - new Date(a.submittedAt || a.created_at || 0));

    $('inquiryTableBody').innerHTML = filtered.map((item) => {
      const info = statusInfo(item.status);
      const customerName = item.customerName || item.customer_name || item.full_name || 'Unknown Customer';
      const customerEmail = item.customerEmail || item.customer_email || item.email || 'No email';
      const submittedAt = item.submittedAt || item.created_at || 'Unknown date';
      const event = item.event || item.event_type || 'Not specified';
      const venue = item.venue || item.venue_name || 'No venue';
      const packageName = item.packageName || item.package_name || 'No package';
      const requestedRooms = item.requestedRooms ?? item.requested_rooms ?? 0;
      const preferredDate = item.preferredDate || item.preferred_date || item.event_date || 'Not set';
      const backupDate = item.backupDate || item.backup_date || 'Not set';
      const amenities = Array.isArray(item.amenities)
        ? item.amenities
        : (typeof item.amenities === 'string' && item.amenities.trim() !== '' ? [item.amenities] : []);
      const estimatedTotal = item.estimatedTotal ?? item.estimated_total ?? 0;
      const notes = item.notes || 'No notes provided.';
      return `
        <tr>
          <td>${item.reference || 'N/A'}<br><span class="admin-muted">${submittedAt}</span></td>
          <td>${customerName}<br><span class="admin-muted">${customerEmail}</span></td>
          <td>${event}<br><span class="admin-muted">${venue}</span></td>
          <td>${packageName}<br><span class="admin-muted">${requestedRooms} room(s)</span></td>
          <td><span class="status-badge ${info.className}">${info.label}</span></td>
          <td>
            <select class="admin-status-select" data-inquiry-status="${item.id}">
              <option value="submitted" ${item.status === 'submitted' ? 'selected' : ''}>Submitted</option>
              <option value="review"    ${item.status === 'review'    ? 'selected' : ''}>Under Review</option>
              <option value="proposal"  ${item.status === 'proposal'  ? 'selected' : ''}>Proposal Sent</option>
              <option value="closed"    ${item.status === 'closed'    ? 'selected' : ''}>Closed</option>
            </select>
            <button class="tbl-btn tbl-confirm" data-inquiry-save="${item.id}">Save</button>
          </td>
        </tr>
        <tr class="admin-detail-row">
          <td colspan="6">
            <div class="admin-inquiry-detail">
              <strong>Preferred:</strong> ${preferredDate} |
              <strong>Backup:</strong> ${backupDate} |
              <strong>Amenities:</strong> ${amenities.length ? amenities.join(', ') : 'None'} |
              <strong>Total:</strong> ${formatCurrency(estimatedTotal)} |
              <strong>Notes:</strong> ${notes}
            </div>
          </td>
        </tr>`;
    }).join('');

    document.querySelectorAll('[data-inquiry-save]').forEach((button) => {
      button.addEventListener('click', () => {
        const select = document.querySelector(`[data-inquiry-status="${button.dataset.inquirySave}"]`);
        if (!select) return;
        const inquiryId = Number(button.dataset.inquirySave);
        const newStatus = select.value;

        if (Array.isArray(dbInquiries)) {
          fetch('assets/actions/update_inquiry_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `inquiry_id=${inquiryId}&status=${encodeURIComponent(newStatus)}`
          })
            .then((res) => res.json())
            .then((data) => {
              if (!data.success) {
                showToast(data.message || 'Failed to update inquiry status.', '#c0392b');
                return;
              }
              loadInquiriesFromDb();
              showToast(`Inquiry updated to ${newStatus}.`);
            })
            .catch(() => {
              showToast('Could not update inquiry status in database.', '#c0392b');
            });
          return;
        }

        const inquiry = store.updateInquiryStatus(inquiryId, newStatus);
        if (!inquiry) return;
        renderInquiries();
        showToast(`Updated ${inquiry.reference} to ${newStatus}.`);
      });
    });
  }

  $('packageForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('packageId').value);
    if (!id) {
      showToast('Select an existing package from the table first (Edit button).', '#c0392b');
      return;
    }
    const payload = {
      id: id || undefined,
      key: $('packageName').value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-'),
      name: $('packageName').value.trim(),
      basePrice: Number($('packageBasePrice').value),
      guestCapacity: Number($('packageGuestCapacity').value),
      tagline: $('packageTagline').value.trim(),
      active: $('packageActive').value === 'true',
      maxPrivateRooms: Number($('packageRoomLimit').value)
    };
    fetch('assets/actions/save_package.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id || ''}&key=${encodeURIComponent(payload.key)}&name=${encodeURIComponent(payload.name)}&basePrice=${payload.basePrice}&guestCapacity=${payload.guestCapacity}&tagline=${encodeURIComponent(payload.tagline)}&active=${payload.active}&maxPrivateRooms=${payload.maxPrivateRooms}`
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) {
          showToast(data.message || 'Failed to save package.', '#c0392b');
          return;
        }
        loadPackagesFromDb();
        resetPackageForm();
        showToast('Package saved.');
      })
      .catch(() => {
        store.savePackage(payload);
        dbPackages = null;
        renderStats();
        renderPackages();
        resetPackageForm();
        showToast('Package saved (frontend preview).');
      });
  });

  $('amenityForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('amenityId').value);
    if (!id) {
      showToast('Select an existing amenity from the table first (Edit button).', '#c0392b');
      return;
    }
    const payload = {
      id: id || undefined,
      name: $('amenityName').value.trim(),
      price: Number($('amenityPrice').value),
      active: $('amenityActive').value === 'true'
    };
    fetch('assets/actions/save_amenity.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id || ''}&name=${encodeURIComponent(payload.name)}&price=${payload.price}&active=${payload.active}`
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) {
          showToast(data.message || 'Failed to save amenity.', '#c0392b');
          return;
        }
        loadAmenitiesFromDb();
        resetAmenityForm();
        showToast('Amenity saved.');
      })
      .catch(() => {
        store.saveAmenity(payload);
        dbAmenities = null;
        renderStats();
        renderAmenities();
        resetAmenityForm();
        showToast('Amenity saved (frontend preview).');
      });
  });

  $('venueForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('venueId').value);
    if (!id) {
      showToast('Select an existing venue from the table first (Edit button).', '#c0392b');
      return;
    }
    const payload = {
      id: id || undefined,
      name: $('venueName').value.trim(),
      description: $('venueDescription').value.trim(),
      guestCapacity: Number($('venueCapacity').value || 0),
      active: $('venueActive').value === 'true'
    };
    fetch('assets/actions/save_venue.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id || ''}&name=${encodeURIComponent(payload.name)}&description=${encodeURIComponent(payload.description)}&guestCapacity=${payload.guestCapacity}&active=${payload.active}`
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) {
          showToast(data.message || 'Failed to save venue.', '#c0392b');
          return;
        }
        loadVenuesFromDb();
        resetVenueForm();
        showToast('Venue saved.');
      })
      .catch(() => {
        store.saveVenue(payload);
        dbVenues = null;
        renderVenues();
        resetVenueForm();
        showToast('Venue saved (frontend preview).');
      });
  });

  $('packageReset').addEventListener('click', resetPackageForm);
  $('amenityReset').addEventListener('click', resetAmenityForm);
  $('venueReset').addEventListener('click', resetVenueForm);
  $('userSearch').addEventListener('input', renderUsers);
  $('userRoleFilter').addEventListener('change', renderUsers);
  $('userStatusFilter').addEventListener('change', renderUsers);
  $('inquirySearch').addEventListener('input', renderInquiries);
  $('inquiryStatusFilter').addEventListener('change', renderInquiries);
  $('inquiryEventFilter').addEventListener('change', renderInquiries);
  $('inquiryPackageFilter').addEventListener('change', renderInquiries);
  $('inquirySort').addEventListener('change', renderInquiries);

  renderStats();
  renderPackages();
  renderAmenities();
  renderVenues();
  renderRooms();
  renderUsers();
  renderInquiries();
  resetPackageForm();
  resetAmenityForm();
  resetVenueForm();
  setAmenityFormEditing(false);
  setVenueFormEditing(false);
  loadUsersFromDb();
  loadInquiriesFromDb();
  loadPackagesFromDb();
  loadAmenitiesFromDb();
  loadVenuesFromDb();
});