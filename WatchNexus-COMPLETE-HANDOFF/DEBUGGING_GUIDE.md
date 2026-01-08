# DEBUGGING GUIDE - If Calendar/Admin Still Broken

## 🔍 Step 1: Check Browser Console

Press **F12** (or right-click → Inspect → Console tab)

### **What to Look For:**

**Red Errors:**
```
Uncaught SyntaxError: ...
Uncaught ReferenceError: ...
Failed to fetch: ...
```

**Copy the EXACT error message** and send it to me.

---

## 🔍 Step 2: Check Network Tab

F12 → Network tab → Reload page (F5)

### **Look for these requests:**

**Calendar Page:**
- `/api/events.php?start=...&end=...` - Should be status 200
- If 404 → API file missing
- If 500 → Server error, check PHP logs

**Admin Page:**
- `/api/system_health.php` - Should be status 200
- `/api/admin_activity.php` - Should be status 200
- `/api/admin_modules_get.php` - Should be status 200
- `/api/import_status.php` - Should be status 200

**Click on any failed request** → Preview tab → See error message

---

## 🔍 Step 3: Check PHP Logs

**Where are PHP logs?**
- cPanel: Error Logs
- Server: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- PHP-FPM: `/var/log/php-fpm/error.log`

**Look for:**
```
PHP Fatal error: ...
PHP Warning: ...
```

**Copy the error** and send to me.

---

## 🔍 Step 4: Check Database

Run these queries:

```sql
-- Check if events table exists
SHOW TABLES LIKE 'events';

-- Check if events exist
SELECT COUNT(*) FROM events;

-- Check if system_config table exists
SHOW TABLES LIKE 'system_config';

-- Check migration 005 ran
SELECT * FROM system_config;
```

**If any fail** → Send me the error message.

---

## 🔍 Step 5: Test API Directly

Open these URLs in your browser:

**Test Events API:**
```
https://yoursite.com/api/events.php?start=2026-01-01&end=2026-01-31
```
**Should return:** `{"ok":true,"events":[...]}`

**Test System Health:**
```
https://yoursite.com/api/system_health.php
```
**Should return:** `{"ok":true,"disk_free":...}`

**If you see errors** → Copy the JSON error message.

---

## 🔍 Step 6: Verify File Upload

Check that these files exist on your server:

```
app/views/calendar.php
app/views/admin.php
public/api/events.php
public/api/system_health.php
public/api/admin_activity.php
public/api/admin_integrity.php
public/api/admin_trakt_config.php
public/api/import_status.php
migrations/005_system_config.sql
```

**If any are missing** → Re-upload the ZIP.

---

## 🔍 Step 7: Clear Everything

**Nuclear option:**

1. **Clear browser cache:**
   - Chrome: Settings → Privacy → Clear browsing data → Cached images and files
   - Firefox: Settings → Privacy → Clear Data → Cache
   - Safari: Develop → Empty Caches

2. **Clear server cache (if using):**
   - Cloudflare: Purge everything
   - Opcache: `opcache_reset()`
   - APCu: `apcu_clear_cache()`

3. **Hard refresh:**
   - Windows: CTRL + F5
   - Mac: CMD + SHIFT + R

4. **Try incognito/private window**

---

## 🔍 What to Send Me

If it's **STILL broken**, send me:

1. **Browser console errors** (F12 → Console → screenshot or copy text)
2. **Network tab errors** (F12 → Network → failed requests → screenshot)
3. **PHP error logs** (last 20 lines)
4. **Database query results** (from Step 4)
5. **API test results** (from Step 5)

With this info, I can tell you EXACTLY what's wrong.

---

## 📊 Token Count

This debugging session: **~700 tokens**
Total session used: **~110,000 tokens** (58%)
Remaining: **~80,000 tokens** (42%)

Still plenty of budget to fix any issues!
