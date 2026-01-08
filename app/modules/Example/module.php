<?php
declare(strict_types=1);

// Demonstrates auto-discovery. In production you would ship a full module here.
return [
  'id' => 'example_discovered',
  'name' => 'Example Module (discovered)',
  'min_role' => 'user',
  'required' => false,
  'default_mode' => 'default_off',
  'user_toggle' => true,
  'category' => 'utility',
];
