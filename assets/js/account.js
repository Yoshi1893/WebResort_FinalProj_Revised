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

  function initialsFor(current) {
    return `${(current.firstName || '').charAt(0)}${(current.lastName || '').charAt(0)}`.toUpperCase() || 'U';
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

  function updateAvatar() {
    const user = store.getCurrentUser();
    const avatar = $('accountAvatarPreview');
    if (!avatar) return;
    if (user.profileImage) {
      avatar.innerHTML = `<img src="${user.profileImage}" alt="Profile Picture">`;
      avatar.classList.add('has-image');
      return;
    }
    avatar.textContent = initialsFor(user);
    avatar.classList.remove('has-image');
  }

  function populateProfile() {
    const user = store.getCurrentUser();
    $('accountGreeting').textContent = user.firstName || 'Guest';
    $('accountFirstName').value = user.firstName || '';
    $('accountLastName').value = user.lastName || '';
    $('accountEmail').value = user.email || '';
    $('accountPhone').value = user.phone || '';
    $('accountRole').textContent = user.role || 'Customer';
    $('accountCreatedAt').textContent = user.createdAt || 'Not set';
    updateAvatar();
  }

  function renderInquiries() {
    const user = store.getCurrentUser();
    const inquiries = store.getInquiriesByEmail(user.email);
    const list = $('accountInquiryList');
    if (!list) return;
    if (!inquiries.length) {
      list.innerHTML = '<p class="customer-empty-state">No saved inquiries yet.</p>';
      return;
    }
    list.innerHTML = inquiries.map((item, index) => {
      const status = statusInfo(item.status);
      return `
        <article class="customer-inquiry-item account-inquiry-item">
          <button class="account-inquiry-toggle" type="button" data-inquiry-toggle="${index}">
            <div class="customer-inquiry-header">
              <strong>${item.reference}</strong>
              <span class="status-badge ${status.className}">${status.label}</span>
            </div>
            <div class="customer-inquiry-meta">${item.submittedAt} | ${item.event} | ${item.venue}</div>
            <div class="customer-inquiry-meta">${item.packageName} | ${item.requestedRooms} room(s) | ${formatCurrency(item.estimatedTotal)}</div>
          </button>
          <div class="account-inquiry-details" id="inquiryDetail${index}">
            <div class="summary-item"><label>Preferred Date</label><span>${item.preferredDate}</span></div>
            <div class="summary-item"><label>Backup Date</label><span>${item.backupDate}</span></div>
            <div class="summary-item"><label>Amenities</label><span>${item.amenities.length ? item.amenities.join(', ') : 'None'}</span></div>
            <div class="summary-item"><label>Notes</label><span>${item.notes || 'No notes provided.'}</span></div>
          </div>
        </article>`;
    }).join('');

    document.querySelectorAll('[data-inquiry-toggle]').forEach((button, index) => {
      button.addEventListener('click', () => {
        const detail = $(`inquiryDetail${index}`);
        if (!detail) return;
        detail.classList.toggle('open');
        button.classList.toggle('open');
      });
    });
  }

  $('profileForm').addEventListener('submit', (event) => {
    event.preventDefault();
    store.saveCurrentUserProfile({
      firstName: $('accountFirstName').value.trim(),
      lastName: $('accountLastName').value.trim(),
      phone: $('accountPhone').value.trim()
    });
    populateProfile();
    showToast('Profile details updated in the frontend preview.');
  });

  $('passwordForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const next = $('newPassword').value;
    const confirm = $('confirmNewPassword').value;
    if (next.length < 6) {
      showToast('Password must be at least 6 characters.', '#c0392b');
      return;
    }
    if (next !== confirm) {
      showToast('New passwords do not match.', '#c0392b');
      return;
    }
    event.target.reset();
    showToast('Password change submitted for the frontend preview.');
  });

  $('profileImageInput').addEventListener('change', (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      store.setCurrentUserProfileImage(reader.result);
      updateAvatar();
      showToast('Profile picture updated in the frontend preview.');
    };
    reader.readAsDataURL(file);
  });

  $('removePhotoBtn').addEventListener('click', () => {
    store.setCurrentUserProfileImage('');
    $('profileImageInput').value = '';
    updateAvatar();
    showToast('Profile picture removed.');
  });

  $('archiveAccountBtn').addEventListener('click', () => {
    store.archiveCurrentUser();
    populateProfile();
    showToast('Account archive flow confirmed for the frontend preview.');
  });

  populateProfile();
  renderInquiries();
});
