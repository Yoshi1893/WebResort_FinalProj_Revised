document.addEventListener('DOMContentLoaded', () => {
  const data = window.MockData || {};
  const admin = data.adminUser;
  const users = data.users || [];
  const inquiries = data.inquiries || [];
  const packages = data.packages || [];
  const amenities = data.amenities || [];
  const venues = data.venues || [];
  const rooms = data.rooms || [];
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

  function nextId(items) {
    return items.length ? Math.max(...items.map((item) => item.id)) + 1 : 1;
  }

  function fillRoomVenueOptions() {
    $('roomVenue').innerHTML = venues.map((venue) => `<option value="${venue.id}">${venue.name}</option>`).join('');
  }

  function renderStats() {
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
    $('roomForm').reset();
    $('roomId').value = '';
    $('roomActive').value = 'true';
    if (venues[0]) $('roomVenue').value = String(venues[0].id);
  }

  function renderPackages() {
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
    $('amenityTableBody').innerHTML = amenities.map((item) => `
      <tr>
        <td>${item.name}</td>
        <td>${formatCurrency(item.price)}</td>
        <td><span class="status-badge ${item.active ? 'status-proposal' : 'status-closed'}">${item.active ? 'Active' : 'Inactive'}</span></td>
        <td><button class="tbl-btn tbl-confirm" data-amenity-edit="${item.id}">Edit</button></td>
      </tr>`).join('');
    document.querySelectorAll('[data-amenity-edit]').forEach((button) => {
      button.addEventListener('click', () => {
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
    $('venueTableBody').innerHTML = venues.map((item) => `
      <tr>
        <td>${item.name}</td>
        <td><span class="status-badge ${item.active ? 'status-proposal' : 'status-closed'}">${item.active ? 'Active' : 'Inactive'}</span></td>
        <td><button class="tbl-btn tbl-confirm" data-venue-edit="${item.id}">Edit</button></td>
      </tr>`).join('');
    document.querySelectorAll('[data-venue-edit]').forEach((button) => {
      button.addEventListener('click', () => {
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
    const search = $('userSearch').value.trim().toLowerCase();
    const role = $('userRoleFilter').value;
    const archive = $('userArchiveFilter').value;
    const filtered = users.filter((item) => {
      const matchesSearch = !search || `${item.firstName} ${item.lastName} ${item.email}`.toLowerCase().includes(search);
      const matchesRole = role === 'all' || item.role === role;
      const matchesArchive = archive === 'all' || (archive === 'archived' ? item.archived : !item.archived);
      return matchesSearch && matchesRole && matchesArchive;
    });

    $('userTableBody').innerHTML = filtered.map((item) => `
      <tr>
        <td>${item.firstName} ${item.lastName}<br><span class="admin-muted">${item.email}</span></td>
        <td>${item.role}</td>
        <td>${item.phone || 'Not provided'}</td>
        <td><span class="status-badge ${item.archived ? 'status-closed' : 'status-proposal'}">${item.archived ? 'Archived' : 'Active'}</span></td>
        <td>${item.role === 'customer' ? `<button class="tbl-btn ${item.archived ? 'tbl-confirm' : 'tbl-cancel'}" data-user-archive="${item.id}">${item.archived ? 'Archived' : 'Archive'}</button>` : '<span class="admin-muted">Restricted</span>'}</td>
      </tr>`).join('');

    document.querySelectorAll('[data-user-archive]').forEach((button) => {
      button.addEventListener('click', () => {
        const item = users.find((user) => user.id === Number(button.dataset.userArchive));
        if (!item || item.role !== 'customer') return;
        item.archived = true;
        renderUsers();
        showToast(`Archived ${item.firstName} ${item.lastName}.`);
      });
    });
  }

  function renderInquiries() {
    const search = $('inquirySearch').value.trim().toLowerCase();
    const status = $('inquiryStatusFilter').value;
    const eventType = $('inquiryEventFilter').value;
    const packageKey = $('inquiryPackageFilter').value;
    const sort = $('inquirySort').value;

    const filtered = inquiries
      .filter((item) => {
        const haystack = `${item.customerName} ${item.customerEmail}`.toLowerCase();
        const matchesSearch = !search || haystack.includes(search);
        const matchesStatus = status === 'all' || item.status === status;
        const matchesEvent = eventType === 'all' || item.event === eventType;
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
              <option value="review" ${item.status === 'review' ? 'selected' : ''}>Under Review</option>
              <option value="proposal" ${item.status === 'proposal' ? 'selected' : ''}>Proposal Sent</option>
              <option value="closed" ${item.status === 'closed' ? 'selected' : ''}>Closed</option>
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
        const inquiry = inquiries.find((item) => item.id === Number(button.dataset.inquirySave));
        const select = document.querySelector(`[data-inquiry-status="${button.dataset.inquirySave}"]`);
        if (!inquiry || !select) return;
        inquiry.status = select.value;
        renderInquiries();
        showToast(`Updated ${inquiry.reference} to ${select.value}.`);
      });
    });
  }

  $('packageForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('packageId').value);
    const payload = {
      id: id || nextId(packages),
      key: $('packageName').value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-'),
      name: $('packageName').value.trim(),
      basePrice: Number($('packageBasePrice').value),
      guestCapacity: Number($('packageGuestCapacity').value),
      tagline: $('packageTagline').value.trim(),
      active: $('packageActive').value === 'true',
      maxPrivateRooms: Number($('packageRoomLimit').value)
    };
    const existing = packages.findIndex((item) => item.id === id);
    if (existing >= 0) packages[existing] = payload;
    else packages.push(payload);
    renderStats();
    renderPackages();
    resetPackageForm();
    showToast('Package saved.');
  });

  $('amenityForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('amenityId').value);
    const payload = {
      id: id || nextId(amenities),
      name: $('amenityName').value.trim(),
      price: Number($('amenityPrice').value),
      active: $('amenityActive').value === 'true'
    };
    const existing = amenities.findIndex((item) => item.id === id);
    if (existing >= 0) amenities[existing] = payload;
    else amenities.push(payload);
    renderStats();
    renderAmenities();
    resetAmenityForm();
    showToast('Amenity saved.');
  });

  $('venueForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('venueId').value);
    const payload = {
      id: id || nextId(venues),
      name: $('venueName').value.trim(),
      description: $('venueDescription').value.trim(),
      active: $('venueActive').value === 'true'
    };
    const existing = venues.findIndex((item) => item.id === id);
    if (existing >= 0) venues[existing] = payload;
    else venues.push(payload);
    renderVenues();
    renderRooms();
    resetVenueForm();
    showToast('Venue saved.');
  });

  $('roomForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = Number($('roomId').value);
    const payload = {
      id: id || nextId(rooms),
      venueId: Number($('roomVenue').value),
      name: $('roomName').value.trim(),
      note: $('roomNote').value.trim(),
      active: $('roomActive').value === 'true'
    };
    const existing = rooms.findIndex((item) => item.id === id);
    if (existing >= 0) rooms[existing] = payload;
    else rooms.push(payload);
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
  $('userArchiveFilter').addEventListener('change', renderUsers);
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
