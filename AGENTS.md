# AGENTS.md

## Project Summary

This repository is a small resort/events booking website for **9 Waves Events Place**.

Current files are a mix of:

- a public landing page in `index.html`
- one global stylesheet in `styles.css`
- one large frontend script in `script.js`
- standalone PHP pages for auth/inquiry work:
  - `login.php`
  - `register.php`
  - `inquiry.php`
  - `db.php`
  - `setup.sql`

Static assets live under `image/`.

The current approved direction for this repo is a **frontend-first refactor**:

- segment the landing page into readable PHP includes/partials
- keep the current visual design largely intact
- keep the inquiry wizard on the landing page
- add standalone `account.php`, `admin.php`, and `inquiry-success.php`
- remove the embedded landing-page auth/customer/admin overlays
- remove the admin charts
- remove the PDF download flow
- prepare clean frontend surfaces for later backend integration

## Architecture Reality

There are effectively **two parallel implementations** in this repo:

1. `index.html` + `script.js`
   - Main user-facing landing page
   - Multi-step inquiry wizard
   - Client-side auth modal
   - Client-side customer account panel
   - Client-side admin dashboard
   - Persists data in `localStorage`

2. PHP pages + MySQL
   - Traditional form-post pages
   - Uses PDO through `db.php`
   - Persists users/inquiries in MySQL

These two paths are **not integrated**. The SPA-like flow on `index.html` does **not** submit to the PHP endpoints. The admin dashboard in `script.js` reads browser `localStorage`, not the MySQL tables.

For this current refactor, assume the work is **frontend-owned** unless explicitly stated otherwise. That means:

- do not attempt a full backend migration unless requested
- do not invent new fake auth/session flows
- build frontend-ready pages and interactions with clear backend handoff points

Any change involving auth, registration, inquiries, or dashboard behavior must first decide whether it belongs to:

- the `localStorage` prototype path,
- the PHP/MySQL path,
- or a proper integration of both.

Do not assume the database is the source of truth for the landing page unless you explicitly wire it in.

## Key Files

- `index.html`: current landing page markup. This is planned to become `index.php` and be segmented into includes.
- `script.js`: current monolithic frontend behavior. This is planned to be split by page responsibility.
- `styles.css`: all styling for the site.
- `db.php`: shared PDO connection. Assumes MySQL on `localhost:3307`, DB name `9waves_db`, username `root`, empty password.
- `login.php`: DB-backed login page.
- `register.php`: DB-backed registration page.
- `inquiry.php`: DB-backed inquiry submission page.
- `setup.sql`: schema for `users` and `inquiries`, plus sample rows.
- `AGENTS.md`: current repo guidance and approved direction summary.

## Frontend Conventions

- There is no bundler, package manager, or build step.
- External libraries are loaded from CDNs in `index.html`.
- The front end is plain DOM-manipulation JavaScript, not React/Vue/etc.
- `script.js` uses a small helper: `const $ = (id) => document.getElementById(id);`
- Client state is stored in `localStorage` under:
  - `9waves_users`
  - `9waves_current`
  - `9waves_inquiries`
- The admin login in the front-end prototype is hardcoded via base64-encoded constants in `script.js`.

Approved frontend refactor direction:

- keep one shared stylesheet for now
- split JS by page instead of keeping one giant script
- segment the landing page into PHP partials/includes
- keep the landing-page inquiry wizard
- keep marketing content mostly static in phase 1
- use a centralized mock-data layer for frontend interactions instead of scattering `localStorage` auth/session behavior

Planned file direction:

- `index.php`
- `account.php`
- `admin.php`
- `inquiry-success.php`
- `includes/header.php`
- `includes/footer.php`
- `includes/sections/*.php`
- `assets/js/main.js`
- `assets/js/account.js`
- `assets/js/admin.js`
- `assets/js/mock-data.js`

Avoid introducing a framework or build tooling unless explicitly requested.

## Backend Conventions

- PHP uses PDO with prepared statements.
- `db.php` is included directly from the endpoint pages.
- Schema setup is manual through `setup.sql`.
- The expected local database is `9waves_db`.
- The configured MySQL host is `localhost:3307`, which strongly suggests a XAMPP-style local setup.

If you change database fields, update both:

- the relevant PHP files
- `setup.sql`

## Local Development Notes

For front-end-only work:

- opening the public page in a browser is usually enough
- CDN dependencies still require internet access

For PHP/database work:

1. Run Apache/PHP locally, or use PHP’s built-in server.
2. Make sure MySQL is available on port `3307`.
3. Create/import `9waves_db` using `setup.sql`.
4. Verify `db.php` matches the local DB configuration.

Current environment note:

- PHP 8.3 has been installed in user scope via `winget`.
- The current shell may need a restart before `php` resolves from `PATH`.
- Direct installed binary path:
  - `C:\Users\Jude Sangalang\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe`

## Validation Expectations

There is no automated test suite in this repository.

Use manual verification after changes:

- load the landing page and confirm section rendering after segmentation
- test mobile navigation and section scrolling
- test the inquiry wizard UI flow
- test account page UI sections
- test admin page UI sections
- test search/filter behavior against mock data
- confirm charts are removed from admin UI
- confirm PDF download flow is removed
- if PHP becomes available locally, verify `login.php`, `register.php`, and the new PHP pages render correctly

## Known Constraints And Risks

- The main landing page is a client-side prototype and is not server-backed.
- Passwords for the front-end prototype are stored in plain text in `localStorage`.
- The front-end and PHP flows can drift because they duplicate business concepts independently.
- PHP may not resolve from `PATH` until a new shell session is started.
- Backend persistence/auth should not be faked again during the current frontend-first pass.

## Guidance For Future Agents

- Read `index.html`, `script.js`, and the relevant PHP file before changing behavior.
- Be explicit about whether a requested feature is for the current frontend pass or for later backend integration.
- Preserve the current visual design unless the user asks for redesign.
- Prefer page segmentation and responsibility cleanup over a full-stack rewrite in this phase.
- Remove duplicate embedded UI rather than keeping multiple versions of the same feature.
- If backend integration later becomes the scope, document the source of truth clearly in this file.

## Approved Phase Plan

### Phase 1

- Convert `index.html` into `index.php`
- Segment the landing page into includes
- Preserve the current public UI and wizard placement

### Phase 2

- Remove landing-page auth modal
- Remove embedded customer panel
- Remove embedded admin panel
- Remove admin charts
- Remove PDF flow
- Update nav links

### Phase 3

- Split JS by page responsibility
- Keep one shared stylesheet
- Remove obsolete modal/panel/chart/PDF logic

### Phase 4

- Build `account.php`
- Add profile, change-password, inquiry-history, and archive-account sections

### Phase 5

- Build `admin.php`
- Add management UI for packages, amenities, venues, rooms, users, and inquiry logs

### Phase 6

- Align `login.php` and `register.php` visually
- Add `inquiry-success.php`

### Phase 7

- Centralize frontend mock data
- Wire interactive filters/search/editing to that mock layer

### Phase 8

- Polish, clean dead code, and manually verify the frontend pass
