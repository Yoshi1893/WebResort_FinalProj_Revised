document.addEventListener('DOMContentLoaded', () => {
  const store = window.MockStore;
  const $ = (id) => document.getElementById(id);

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

  function snapshot() {
    return {
      admin: store.getAdminUser(),
      users: store.getUsers(),
      inquiries: store.getInquiries(),
      packages: store.getPackages(),
      amenities: store.getAmenities(),
      venues: store.getVenues(),
      rooms: store.getRooms()
    };
  }

  function fillRoomVenueOptions() {
    const { venues } = snapshot();
    $('roomVenue').innerHTML = venues.map((venue) => `<option value="${venue.id}">${venue.name}</option>`).join('');
  }

  function renderStats() {
    const { admin, packages, amenities, users, inquiries } = snapshot();
    $('statPackages').textContent = packages.filter((item) => item.active).length;
    $('statAmenities').textContent = amenities.filter((item) => item.active).length;
    $('statUsers').textContent = users.filter((item) => item.role === 'customer').length;
    $('statInquiries').textContent = inquiries.length;
    $('adminAvatar').textContent = initialsFor(admin);
    $('adminName').textContent = `${admin.firstName} ${admin.lastName}`;
  }

  function resetPackageForm() {
    $('packageForm').reset();
    $('packageId').value = '';
    $('packageActive').value = 'true';
  }

  function resetAmenityForm() {
    $('amenityForm').reset();
    $('amenityId').value = '';
    $('amenityActive').value = 'true';
  }

  function resetVenueForm() {
    $('venueForm').reset();
    $('venueId').value = '';
    $('venueActive').value = 'true';
  }

  function resetRoomForm() {
    const { venues } = snapshot();
    $('roomForm').reset();
    $('roomId').value = '';
    $('roomActive').value = 'true';
    if (venues[0]) $('roomVenue').value = String(venues[0].id);
  }

  function renderPackages() {
    const { packages } = snapshot();
    $('packageTableBody').innerHTML = packages.map((item) => `
      <tr>
        <td>${item.name}</td>
        <td>${formatCurrency(item.basePrice)}</td>
        <td>${item.maxPrivateRooms}</td>
        <td><span class="status-badge ${item.active ? 'status-proposal' : 'status-closed'}">${item.active ? 'Active' : 'Inactive'}</span></td>
        <td><button class="tbl-btn tbl-confirm" data-package-edit="${item.id}">Edit</button></td>
      </tr>`).join('');
    document.querySelectorAll('[data-package-edit]').forEach((button) => {
      button.addEventListener('click', () => {
        const { packages } = snapshot();
        const item = packages.find((pkg) => pkg.id === Number(button.dataset.packageEdit));
        if (!item) return;
        $('packageId').value = item.id;
        $('packageName').value = item.name;
        $('packageBasePrice').value = item.basePrice;
        $('packageGuestCapacity').value = item.guestCapacity;
        $('packageRoomLimit').value = item.maxPrivateRooms;
        $('packageTagline').value = item.tagline;
        $('packageActive').value = String(item.active);
      });
    });
  }

  function renderAmenities() {
    const { amenities } = snapshot();
    $('amenityTableBody').innerHTML = amenities.map((item) => `
      <tr>
        <td>${item.name}</td>
        <td>${formatCurrency(item.price)}</td>
        <td><span class="status-badge ${item.active ? 'status-proposal' : 'status-closed'}">${item.active ? 'Active' : 'Inactive'}</span></td>
        <td><button class="tbl-btn tbl-confirm" data-amenity-edit="${item.id}">Edit</button></td>
      </tr>`).join('');
    document.querySelectorAll('[data-amenity-edit]').forEach((button) => {
      button.addEventListener('click', () => {
        const { amenities } = snapshot();
        const item = amenities.find((amenity) => amenity.id === Number(button.dataset.amenityEdit));
        if (!item) return;
        $('amenityId').value = item.id;
        $('amenityName').value = item.name;
        $('amenityPrice').value = item.price;
        $('amenityActive').value = String(item.active);
      });
    });
  }

  function renderVenues() {
    const { venues } = snapshot();
    $('venueTableBody').innerHTML = venues.map((item) => `
      <tr>
        <td>${item.name}</td>
        <td><span class="status-badge ${item.active ? 'status-proposal' : 'status-closed'}">${item.active ? 'Active' : 'Inactive'}</span></td>
        <td><button class="tbl-btn tbl-confirm" data-venue-edit="${item.id}">Edit</button></td>
      </tr>`).join('');
    document.querySelectorAll('[data-venue-edit]').forEach((button) => {
      button.addEventListener('click', () => {
        const { venues } = snapshot();
        const item = venues.find((venue) => venue.id === Number(button.dataset.venueEdit));
        if (!item) return;
        $('venueId').value = item.id;
        $('venueName').value = item.name;
        $('venueDescription').value = item.description;
        $('venueActive').value = String(item.active);
      });
    });
    fillRoomVenueOptions();
  }

  function renderRooms() {
    const { rooms, venues } = snapshot();
    $('roomTableBody').innerHTML = rooms.map((item) => {
      const venue = venues.find((entry) => entry.id === item.venueId);
      return `
        <tr>
          <td>${item.name}</td>
          <td>${venue ? venue.name : 'Unknown Venue'}</td>
          <td><span class="status-badge ${item.active ? 'status-proposal' : 'status-closed'}">${item.active ? 'Active' : 'Inactive'}</span></td>
          <td><button class="tbl-btn tbl-confirm" data-room-edit="${item.id}">Edit</button></td>
        </tr>`;
    }).join('');
    document.querySelectorAll('[data-room-edit]').forEach((button) => {
      button.addEventListener('click', () => {
        const { rooms } = snapshot();
        const item = rooms.find((room) => room.id === Number(button.dataset.roomEdit));
        if (!item) return;
        $('roomId').value = item.id;
        $('roomName').value = item.name;
        $('roomVenue').value = String(item.venueId);
        $('roomNote').value = item.note;
        $('roomActive').value = String(item.active);
      });
    });
  }

  function renderUsers() {
    const { users } = snapshot();
    const search       = $('userSearch').value.trim().toLowerCase();
    const role         = $('userRoleFilter').value;
    const statusFilter = $('userStatusFilter').value;

    const filtered = users.filter((item) => {
      const matchesSearch = !search || `${item.firstName} ${item.lastName} ${item.email}`.toLowerCase().includes(search);
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
          <td>${item.firstName} ${item.lastName}<br><span class="admin-muted">${item.email}</span></td>
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

        fetch('actions/revoke_user.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `user_id=${userId}&action=${action}&reason=${encodeURIComponent(reason)}`
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
              renderStats();
              renderUsers();
              showToast(`User ${action === 'revoke' ? 'revoked' : 'restored'} successfully.`);
            } else {
              showToast(data.message || 'Action failed.', '#c0392b');
            }
          })
          .catch(() => {
            // Fallback: frontend-only mode (no PHP backend running)
            const item = store.archiveUser(Number(userId));
            if (!item) return;
            renderStats();
            renderUsers();
            showToast(`${item.firstName} ${item.lastName} ${action}d (frontend preview).`);
          });
      });
    });
  }

  function renderInquiries() {
    const { inquiries } = snapshot();
    const search      = $('inquirySearch').value.trim().toLowerCase();
    const status      = $('inquiryStatusFilter').value;
    const eventType   = $('inquiryEventFilter').value;
    const packageKey  = $('inquiryPackageFilter').value;
    const sort        = $('inquirySort').value;

    const filtered = inquiries
      .filter((item) => {
        const haystack      = `${item.customerName} ${item.customerEmail}`.toLowerCase();
        const matchesSearch  = !search || haystack.includes(search);
        const matchesStatus  = status === 'all' || item.status === status;
        const matchesEvent   = eventType === 'all' || item.event === eventType;
        const matchesPackage = packageKey === 'all' || item.packageKey === packageKey;
        return matchesSearch && matchesStatus && matchesEvent && matchesPackage;
      })
      .sort((a, b) => sort === 'asc'
        ? new Date(a.submittedAt) - new Date(b.submittedAt)
        : new Date(b.submittedAt) - new Date(a.submittedAt));

    $('inquiryTableBody').innerHTML = filtered.map((item) => {
      const info = statusInfo(item.status);
      return `
        <tr>
          <td>${item.reference}<br><span class="admin-muted">${item.submittedAt}</span></td>
          <td>${item.customerName}<br><span class="admin-muted">${item.customerEmail}</span></td>
          <td>${item.event}<br><span class="admin-muted">${item.venue}</span></td>
          <td>${item.packageName}<br><span class="admin-muted">${item.requestedRooms} room(s)</span></td>
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
              <strong>Preferred:</strong> ${item.preferredDate} |
              <strong>Backup:</strong> ${item.backupDate} |
              <strong>Amenities:</strong> ${item.amenities.join(', ')} |
              <strong>Total:</strong> ${formatCurrency(item.estimatedTotal)} |
              <strong>Notes:</strong> ${item.notes}
            </div>
          </td>
        </tr>`;
    }).join('');

    document.querySelectorAll('[data-inquiry-save]').forEach((button) => {
      button.addEventListener('click', () => {
        const select = document.querySelector(`[data-inquiry-status="${button.dataset.inquirySave}"]`);
        if (!select) return;
        const inquiry = store.updateInquiryStatus(Number(button.dataset.inquirySave), select.value);
        if (!inquiry) return;
        renderInquiries();
        showToast(`Updated ${inquiry.reference} to ${select.value}.`);
      });
    });
  }

  $('packageForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('packageId').value);
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
    store.savePackage(payload);
    renderStats();
    renderPackages();
    resetPackageForm();
    showToast('Package saved.');
  });

  $('amenityForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('amenityId').value);
    const payload = {
      id: id || undefined,
      name: $('amenityName').value.trim(),
      price: Number($('amenityPrice').value),
      active: $('amenityActive').value === 'true'
    };
    store.saveAmenity(payload);
    renderStats();
    renderAmenities();
    resetAmenityForm();
    showToast('Amenity saved.');
  });

  $('venueForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('venueId').value);
    const payload = {
      id: id || undefined,
      name: $('venueName').value.trim(),
      description: $('venueDescription').value.trim(),
      active: $('venueActive').value === 'true'
    };
    store.saveVenue(payload);
    renderStats();
    renderVenues();
    renderRooms();
    resetVenueForm();
    showToast('Venue saved.');
  });

  $('roomForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('roomId').value);
    const payload = {
      id: id || undefined,
      venueId: Number($('roomVenue').value),
      name: $('roomName').value.trim(),
      note: $('roomNote').value.trim(),
      active: $('roomActive').value === 'true'
    };
    store.saveRoom(payload);
    renderRooms();
    resetRoomForm();
    showToast('Room saved.');
  });

  $('packageReset').addEventListener('click', resetPackageForm);
  $('amenityReset').addEventListener('click', resetAmenityForm);
  $('venueReset').addEventListener('click', resetVenueForm);
  $('roomReset').addEventListener('click', resetRoomForm);
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
  resetRoomForm();
});