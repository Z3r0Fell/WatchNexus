# WatchNexus v3 — Restart Base (Known‑Good) — Phase 11.2

**Built:** 2026‑01‑09  
**Target host:** IONOS Webhosting (shared hosting friendly)  
**Goal:** A clean, working baseline where **Calendar / Browse / Mod Tools imports / Integrations** actually function reliably.

---

## What was broken (and what this restart fixes)

### 1) Calendar “empty” after TVMaze import  
Root cause: TVMaze `airstamp` is ISO8601 (often includes timezone), but the DB column `events.start_utc` is `DATETIME`.  
If you insert ISO strings into a `DATETIME`, MySQL can silently coerce them into junk → Calendar queries return nothing.

✅ Fix in this base: **TVMaze import now normalizes every timestamp to UTC MySQL format** `YYYY‑MM‑DD HH:MM:SS` before insert.

**If you imported using an older build:**  
- `TRUNCATE events;`  
- rerun TVMaze import (Mod Tools)  
…and Calendar will populate properly.

---

### 2) Seedr integration “missing”  
Root cause: the download action API had Seedr hard-coded as “not implemented”.

✅ Fix in this base: Seedr is now implemented:
- **Seedr test:** `GET /rest/user`
- **Seedr add:** `POST /rest/transfer/magnet` (or `/rest/transfer/url`)
- Uses **Basic Auth** (username/password)
- Uses **Prowlarr (preferred) or Jackett** as the search provider, then sends the best magnet/url to Seedr.

> Legal note: Seedr can be used for legitimate transfers. You’re responsible for what you queue.

---

### 3) Sonarr import not matching shows  
Root cause: Sonarr import tried to match external IDs using provider `'tvdb'`, but the DB uses `'thetvdb'`.

✅ Fix in this base: Sonarr importer now matches `provider IN ('thetvdb','tvdb')`.

**Host requirements for Sonarr import**
- `ZipArchive` (php-zip)
- `pdo_sqlite`

If your host lacks these, the importer now fails **cleanly** with a readable JSON error.

---

### 4) Session fixation hardening (quick win)
✅ Added `session_regenerate_id(true)` on login.

---

## Files included in this restart pack

- Full WatchNexus source (`app/`, `public/`, `migrations/`, etc.)
- **MASTER_SCHEMA.sql** (single import = no migration mismatch pain)
- This handoff doc

---

## IONOS deployment checklist (do this in order)

### A) Configure config.local.php
1. Copy:
   - `app/config/config.local.example.php` → `app/config/config.local.php`
2. Fill in DB credentials.
3. Set a strong secret key:
   - Recommended: set env var `WNX_SECRET_KEY` (base64 32 bytes)
   - Fallback: set `WNX_SECRET_KEY_B64` inside config.local.php

### B) Import the database
1. Create an empty MySQL database
2. Import `MASTER_SCHEMA.sql` (provided in the root of the zip)
3. Confirm tables exist:
   - `shows`, `events`, `user_integrations`, `modules`, `module_policy`, etc.

### C) First run sanity checks
- Visit: `/health.php` → should return `OK`
- Visit: `/test_apis.php` → should show green for core endpoints

### D) Create admin user
1. Register normally
2. Promote via SQL:
```sql
INSERT INTO user_roles (user_id, role) VALUES (1, 'admin');
```

---

## How to validate “it’s actually working”

### 1) Import TVMaze
- Go to **Mod Tools**
- Run TVMaze import (start with 7 days; then go bigger)
- Verify:
```sql
SELECT COUNT(*) FROM shows;
SELECT COUNT(*) FROM events;
```
Both should be > 0.

### 2) Calendar
- Open Calendar → you should see events populated for the imported date range

### 3) Browse
- Browse should show imported shows (search + track/untrack should function)

### 4) Integrations
- Settings → Integrations:
  - Configure Jackett or Prowlarr (search)
  - Configure Seedr (destination)
  - Use “Test” buttons (now includes Seedr live test)

---

## Troubleshooting (fast kills)

### “Loading forever”
1. Visit `/test_apis.php`
2. If an endpoint is red, open it directly in a new tab to see JSON error
3. Check IONOS PHP error logs for the stack trace

### “Calendar empty but shows exist”
- Your `events.start_utc` data is bad.
- Fix:
```sql
TRUNCATE events;
```
Then rerun TVMaze import.

### “Sonarr import fails”
- If error mentions ZipArchive or SQLite, your host is missing PHP extensions.
- Options:
  - Enable those extensions (best)
  - Or skip Sonarr import and rely on TVMaze/TheTVDB enrichment.

---

## What’s next (after this restart is stable)

**Phase 12 Security / Hardening**
- CSRF tokens for admin/mod actions
- Rate limiting on login and sensitive APIs
- Standardized JSON error responses (no file/line in prod)
- Optional audit logging hooks (table already present in schema)

**Feature polish**
- Pagination for Browse (don’t load 10k shows in one go)
- Better import UX (status persists after run completes)

---

## “If a new AI takes over”
If you’re reading this as a new assistant:
- Treat this repo as the source of truth
- Use `MASTER_SCHEMA.sql` only (don’t mix old migrations unless explicitly asked)
- First debugging step is always `/test_apis.php`
- Most “it’s empty” reports are either **bad events timestamps** or **JS not executing** — verify those first.
