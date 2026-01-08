-- WatchNexus v3 — Schema alignment fixes
-- This migration aligns the database schema with what the code expects.
-- Run this AFTER 001/002/003 if you already ran those, or just run this alone.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Fix users table: code expects 'status' not 'is_disabled'
-- Check if status column exists, if not add it
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'status';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE users ADD COLUMN status ENUM(''active'',''disabled'',''pending'') NOT NULL DEFAULT ''active'' AFTER password_hash',
  'SELECT "status column already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrate is_disabled to status if is_disabled column exists
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'is_disabled';

SET @sql = IF(@col_exists > 0,
  'UPDATE users SET status = IF(is_disabled = 1, ''disabled'', ''active'')',
  'SELECT "is_disabled column does not exist, skipping migration" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create user_integrations table (what code actually uses, not 'integrations')
CREATE TABLE IF NOT EXISTS user_integrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(32) NOT NULL,
  integration_type VARCHAR(32) NOT NULL,
  api_variant VARCHAR(16) NOT NULL DEFAULT 'auto',
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  base_url VARCHAR(512) NULL,
  config_json JSON NULL,
  
  -- Encrypted secrets (libsodium secretbox format: "v1:nonce_b64:cipher_b64")
  api_key_enc TEXT NULL,
  username_enc TEXT NULL,
  password_enc TEXT NULL,
  access_token_enc TEXT NULL,
  refresh_token_enc TEXT NULL,
  token_expires_at TIMESTAMP NULL,
  
  -- Test status tracking
  last_test_status VARCHAR(16) NOT NULL DEFAULT 'never',
  last_test_message TEXT NULL,
  last_test_at TIMESTAMP NULL,
  
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_provider (user_id, provider),
  KEY ix_integration_type (integration_type),
  CONSTRAINT fk_user_int_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rename global_module_policy to module_policy (what code expects)
-- If global_module_policy exists, rename it; if module_policy exists, do nothing
CREATE TABLE IF NOT EXISTS module_policy (
  module_id VARCHAR(64) NOT NULL,
  force_enabled TINYINT(1) NOT NULL DEFAULT 0,
  enabled_by_default TINYINT(1) NOT NULL DEFAULT 1,
  disabled_globally TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed module_policy with sensible defaults
INSERT IGNORE INTO module_policy (module_id, force_enabled, enabled_by_default, disabled_globally) VALUES
  ('calendar', 1, 1, 0),   -- forced ON
  ('browse', 0, 1, 0),     -- enabled by default, user can disable
  ('myshows', 0, 1, 0),    -- enabled by default, user can disable
  ('settings', 1, 1, 0),   -- forced ON
  ('mod', 0, 0, 0),        -- disabled by default (needs role anyway)
  ('admin', 0, 0, 0),      -- disabled by default (needs role anyway)
  ('trakt', 0, 0, 0),      -- disabled by default, user can enable
  ('seedr', 0, 0, 0),      -- disabled by default, user can enable
  ('jackett', 0, 0, 0),    -- disabled by default, user can enable
  ('prowlarr', 0, 0, 0);   -- disabled by default, user can enable

-- Ensure user_module_overrides exists (it should from 003, but just in case)
CREATE TABLE IF NOT EXISTS user_module_overrides (
  user_id BIGINT UNSIGNED NOT NULL,
  module_id VARCHAR(64) NOT NULL,
  enabled TINYINT(1) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, module_id),
  CONSTRAINT fk_umo_user_fix FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
