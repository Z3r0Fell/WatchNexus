<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

if (!has_role('mod')) {
  die('Mod access required');
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>API Test Page</title>
  <style>
    body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
    .test { border: 1px solid #333; padding: 15px; margin: 10px 0; border-radius: 8px; }
    .pass { border-color: #0f0; background: rgba(0,255,0,0.1); }
    .fail { border-color: #f00; background: rgba(255,0,0,0.1); }
    button { padding: 10px 20px; margin: 5px; cursor: pointer; background: #333; color: #fff; border: 1px solid #666; border-radius: 6px; }
    pre { background: #000; padding: 10px; border-radius: 4px; overflow-x: auto; }
  </style>
</head>
<body>
  <h1>WatchNexus API Test Page</h1>
  <p>Testing all API endpoints to find what's broken...</p>

  <button onclick="testAll()">Test All APIs</button>
  <button onclick="location.reload()">Refresh Page</button>

  <div id="results"></div>

  <script>
  async function testAPI(name, url, options = {}) {
    const resultDiv = document.getElementById('results');
    const testDiv = document.createElement('div');
    testDiv.className = 'test';
    testDiv.innerHTML = `<h3>Testing: ${name}</h3><p>URL: ${url}</p><p>Status: Testing...</p>`;
    resultDiv.appendChild(testDiv);

    try {
      const resp = await fetch(url, options);
      const text = await resp.text();
      let data;
      
      try {
        data = JSON.parse(text);
      } catch {
        data = { raw: text };
      }

      if (resp.ok && (data.ok === true || resp.status === 200)) {
        testDiv.className = 'test pass';
        testDiv.innerHTML = `
          <h3>✓ ${name} - PASS</h3>
          <p>Status: ${resp.status}</p>
          <pre>${JSON.stringify(data, null, 2).substring(0, 500)}</pre>
        `;
      } else {
        testDiv.className = 'test fail';
        testDiv.innerHTML = `
          <h3>✗ ${name} - FAIL</h3>
          <p>Status: ${resp.status}</p>
          <pre>${text.substring(0, 1000)}</pre>
        `;
      }
    } catch (err) {
      testDiv.className = 'test fail';
      testDiv.innerHTML = `
        <h3>✗ ${name} - ERROR</h3>
        <p>Error: ${err.message}</p>
      `;
    }
  }

  async function testAll() {
    document.getElementById('results').innerHTML = '<h2>Running tests...</h2>';

    // Test basic endpoints
    await testAPI('System Health', '/api/system_health.php');
    await testAPI('Admin Activity', '/api/admin_activity.php');
    await testAPI('Admin Integrity', '/api/admin_integrity.php');
    await testAPI('Import Status', '/api/import_status.php');
    await testAPI('Events API', '/api/events.php?start=2026-01-01&end=2026-01-31');
    await testAPI('My Shows', '/api/myshows.php');
    
    // Test admin endpoints
    await testAPI('Admin Modules Get', '/api/admin_modules_get.php');
    
    // Test a simple TVMaze import (1 day only)
    const today = new Date().toISOString().split('T')[0];
    await testAPI('TVMaze Import (1 day)', `/api/import_tvmaze.php?start=${today}&end=${today}&country=US`);
  }

  // Auto-run on load
  setTimeout(testAll, 500);
  </script>
</body>
</html>
