<?php
declare(strict_types=1);

/**
 * WatchNexus v3 - Module Registry
 * This file defines the modules that exist and their defaults.
 * Admin/user policies can override these defaults at runtime.
 */

return [
  // Core modules (should remain available)
  'calendar' => ['required' => true,  'default_enabled' => true],
  'browse'   => ['required' => true,  'default_enabled' => true],
  'myshows'  => ['required' => false, 'default_enabled' => true],
  'settings' => ['required' => true,  'default_enabled' => true],

  // Staff modules
  'mod'      => ['required' => false, 'default_enabled' => true],
  'admin'    => ['required' => false, 'default_enabled' => true],

  // Integrations (default OFF; user can enable unless admin forces)
  'trakt'    => ['required' => false, 'default_enabled' => false],
  'seedr'    => ['required' => false, 'default_enabled' => false],
  'jackett'  => ['required' => false, 'default_enabled' => false],
  'prowlarr' => ['required' => false, 'default_enabled' => false],

  // Theme engine (logged-in only; public gets Paper & Ink locked)
  'themes'   => ['required' => true,  'default_enabled' => true],
];
