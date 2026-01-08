# Phase 8 - Quick Install Guide

**Package:** WN-Phase-8-FIXED.zip  
**Contains:** app/, public/, migrations/005_system_config.sql

---

## 🚀 INSTALLATION

### **Step 1: Run Migration**
```sql
SOURCE migrations/005_system_config.sql;
```

### **Step 2: Upload ZIP**
- Extract to your server
- Overwrites Phase 7 files
- **CTRL+F5** to clear browser cache

### **Step 3: Test**
1. **Calendar** → Should see dropdown "Calendar Grid / List View"
2. **Admin** → Should see Data Sources, Import Activity, Database Stats
3. **Settings** → Trakt should be GONE (moved to Admin)

---

## ✅ WHAT'S INCLUDED

### **Calendar:**
- Visual calendar grid (7-day week × 5-6 weeks)
- Month navigation (◀ Prev / Next ▶)
- View toggle: Grid or List
- Click days to see events
- Today highlighted in blue

### **Admin Dashboard:**
- **System Health:** PHP, DB, disk, memory
- **Database Stats:** show/event/user counts
- **Database Integrity Checker:** find issues
- **Data Sources:**
  - TVMaze (test connection)
  - Trakt (system-wide config with Client ID/Secret)
  - TheTVDB (placeholder)
- **Import Activity:** Real-time progress bars
- **User Activity:** Last 7 days stats
- **Module Policy:** Control features

### **Import Progress:**
- TVMaze shows progress bar
- Sonarr shows progress bar
- Updates every 2 seconds
- Visible in Admin → Import Activity

---

## 🐛 IF CALENDAR STILL DOESN'T WORK

### Check Browser Console (F12):
Look for JavaScript errors

### Check Network Tab:
See if `/api/events.php` is being called and returns data

### Verify Database:
```sql
SELECT COUNT(*) FROM events;
-- Should be > 0 if you imported TV schedule
```

### Clear ALL Cache:
1. CTRL+F5 (hard refresh)
2. Clear browser data for your domain
3. Try incognito/private window

---

## 🔍 TROUBLESHOOTING

**"Calendar shows nothing"**
→ Run TVMaze import first (Mod Tools)
→ Check: `SELECT COUNT(*) FROM events;`

**"Admin missing items"**
→ Hard refresh (CTRL+F5)
→ Check console for JS errors

**"Trakt still in Settings"**
→ Old settings.js cached
→ Hard refresh
→ Clear cache

**"Import progress not showing"**
→ Start an import first
→ Go to Admin → Import Activity
→ Should auto-refresh every 2s

---

## 📝 WHAT CHANGED

**Files Modified:**
- `app/views/calendar.php` - Complete rewrite with grid view
- `app/views/admin.php` - Complete dashboard
- `public/api/import_tvmaze.php` - Added progress tracking
- `public/api/import_sonarr.php` - Added progress tracking

**Files Created:**
- `public/api/system_health.php`
- `public/api/admin_integrity.php`
- `public/api/admin_activity.php`
- `public/api/admin_trakt_config.php`
- `public/api/import_status.php`
- `migrations/005_system_config.sql`

---

If it STILL doesn't work after:
1. Running migration
2. Uploading files
3. Hard refresh (CTRL+F5)
4. Checking browser console

Then send me the browser console error messages (F12 → Console tab)
