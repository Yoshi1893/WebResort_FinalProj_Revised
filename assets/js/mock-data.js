window.MockData = {
  adminUser: {
    id: 1,
    firstName: 'Admin',
    lastName: 'User',
    email: 'admin@9waves.com',
    phone: '+63 917 111 0000',
    role: 'admin',
    profileImage: '',
    createdAt: '2026-04-01',
    archived: false
  },
  currentUser: {
    id: 101,
    firstName: 'Maria',
    lastName: 'Santos',
    email: 'maria.santos@example.com',
    phone: '+63 917 000 0000',
    role: 'customer',
    profileImage: '',
    createdAt: '2026-04-08',
    archived: false
  },
  users: [
    {
      id: 1,
      firstName: 'Admin',
      lastName: 'User',
      email: 'admin@9waves.com',
      phone: '+63 917 111 0000',
      role: 'admin',
      profileImage: '',
      createdAt: '2026-04-01',
      archived: false
    },
    {
      id: 101,
      firstName: 'Maria',
      lastName: 'Santos',
      email: 'maria.santos@example.com',
      phone: '+63 917 000 0000',
      role: 'customer',
      profileImage: '',
      createdAt: '2026-04-08',
      archived: false
    },
    {
      id: 102,
      firstName: 'Paolo',
      lastName: 'Reyes',
      email: 'paolo.reyes@example.com',
      phone: '+63 917 222 1111',
      role: 'customer',
      profileImage: '',
      createdAt: '2026-04-04',
      archived: false
    },
    {
      id: 103,
      firstName: 'Lara',
      lastName: 'Dela Cruz',
      email: 'lara.dc@example.com',
      phone: '+63 917 333 2222',
      role: 'customer',
      profileImage: '',
      createdAt: '2026-04-05',
      archived: true
    }
  ],
  inquiries: [
    {
      id: 1201,
      reference: 'INQ-20260408-01201',
      customerEmail: 'maria.santos@example.com',
      customerName: 'Maria Santos',
      submittedAt: '2026-04-08',
      event: 'Wedding',
      venue: 'Pearl Ballroom',
      packageKey: 'crest',
      packageName: 'Crest Pack',
      requestedRooms: 3,
      status: 'review',
      preferredDate: '2026-06-12',
      backupDate: '2026-06-19',
      amenities: ['Full Event Coordination', 'Flower Wall Backdrop'],
      estimatedTotal: 107000,
      notes: 'Would like a classic ballroom setup with ivory florals.'
    },
    {
      id: 1202,
      reference: 'INQ-20260410-01202',
      customerEmail: 'maria.santos@example.com',
      customerName: 'Maria Santos',
      submittedAt: '2026-04-10',
      event: 'Debut',
      venue: 'Wavecrest Garden',
      packageKey: 'ripple',
      packageName: 'Ripple Pack',
      requestedRooms: 1,
      status: 'submitted',
      preferredDate: '2026-08-02',
      backupDate: '2026-08-09',
      amenities: ['3-Tier Wedding Cake'],
      estimatedTotal: 53000,
      notes: 'Sunset timing preferred for photos.'
    },
    {
      id: 1203,
      reference: 'INQ-20260412-01203',
      customerEmail: 'paolo.reyes@example.com',
      customerName: 'Paolo Reyes',
      submittedAt: '2026-04-12',
      event: 'Corporate Gala',
      venue: 'Pearl Ballroom',
      packageKey: 'sovereign',
      packageName: 'Sovereign Wave',
      requestedRooms: 5,
      status: 'proposal',
      preferredDate: '2026-09-18',
      backupDate: '2026-09-25',
      amenities: ['Pro A/V Upgrade', 'Overnight Accommodation'],
      estimatedTotal: 190000,
      notes: 'Needs stage projection and executive holding rooms.'
    }
  ],
  packages: [
    { id: 1, key: 'ripple', name: 'Ripple Pack', basePrice: 45000, guestCapacity: 100, tagline: 'Best for intimate celebrations', active: true, maxPrivateRooms: 2 },
    { id: 2, key: 'crest', name: 'Crest Pack', basePrice: 85000, guestCapacity: 200, tagline: 'Ideal for mid-size signature events', active: true, maxPrivateRooms: 5 },
    { id: 3, key: 'sovereign', name: 'Sovereign Wave', basePrice: 150000, guestCapacity: 500, tagline: 'Built for grand celebrations', active: true, maxPrivateRooms: 8 }
  ],
  amenities: [
    { id: 1, name: 'Extra Event Hour', price: 5000, active: true },
    { id: 2, name: '3-Tier Wedding Cake', price: 8000, active: true },
    { id: 3, name: 'Full Event Coordination', price: 12000, active: true },
    { id: 4, name: 'Pro A/V Upgrade', price: 15000, active: true },
    { id: 5, name: 'Flower Wall Backdrop', price: 10000, active: true },
    { id: 6, name: 'Overnight Accommodation', price: 25000, active: false }
  ],
  venues: [
    { id: 1, name: 'Pearl Ballroom', description: 'Grand indoor ballroom for large celebrations.', active: true },
    { id: 2, name: 'Wavecrest Garden', description: 'Open-air garden venue with sunset appeal.', active: true },
    { id: 3, name: 'Tidal Pool Terrace', description: 'Poolside venue for intimate gatherings.', active: true }
  ],
  rooms: [
    { id: 1, venueId: 1, name: 'Bridal Suite', note: 'Private prep room near ballroom entrance.', active: true },
    { id: 2, venueId: 1, name: 'VIP Lounge', note: 'Holding room for principal sponsors and family.', active: true },
    { id: 3, venueId: 2, name: 'Garden Prep Room', note: 'Air-conditioned prep room for outdoor events.', active: true },
    { id: 4, venueId: 3, name: 'Poolside Cabana', note: 'Small lounge for hosts and coordinators.', active: false }
  ],
  packageData: {
    ripple: { name: 'Ripple Pack', base: 45000, pax: 100, extra: 350 },
    crest: { name: 'Crest Pack', base: 85000, pax: 200, extra: 450 },
    sovereign: { name: 'Sovereign Wave', base: 150000, pax: 500, extra: 600 }
  }
};
