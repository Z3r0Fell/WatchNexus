-- WatchNexus v3 MASTER SCHEMA (Phase 11.2 FIXALL)
-- Built: 2026-01-09 17:50:46Z
-- This file DROPS existing WatchNexus tables and recreates them.
-- Use on a dedicated database.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `module_policy`;
DROP TABLE IF EXISTS `user_integrations`;
DROP TABLE IF EXISTS `system_config`;
DROP TABLE IF EXISTS `user_module_overrides`;
DROP TABLE IF EXISTS `global_module_policy`;
DROP TABLE IF EXISTS `modules`;
DROP TABLE IF EXISTS `auth_sessions`;
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `user_tracked_shows`;
DROP TABLE IF EXISTS `show_external_ids`;
DROP TABLE IF EXISTS `shows`;
DROP TABLE IF EXISTS `integrations`;
DROP TABLE IF EXISTS `user_settings`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS=1;

-- WatchNexus v3 — MySQL schema (fresh rebuild)
-- Engine: InnoDB, Charset: utf8mb4
-- NOTE: This schema assumes secrets are encrypted at rest by the application (libsodium),
-- and passwords are hashed (Argon2id/bcrypt) — not reversible.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(254) NOT NULL,
  display_name VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_disabled TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(20) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (id, name) VALUES
  (1, 'user'),
  (2, 'mod'),
  (3, 'admin');

CREATE TABLE IF NOT EXISTS user_roles (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_settings (
  user_id BIGINT UNSIGNED NOT NULL,
  theme_id VARCHAR(64) NOT NULL DEFAULT 'midnight_signal',
  theme_mode ENUM('system','light','dark') NOT NULL DEFAULT 'system',
  fx_scanlines TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_user_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Integrations: config_json is non-secret. secret_blob holds encrypted packed secrets.
CREATE TABLE IF NOT EXISTS integrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  provider ENUM('trakt','seedr','jackett','prowlarr') NOT NULL,
  status ENUM('disabled','enabled','error') NOT NULL DEFAULT 'disabled',
  config_json JSON NULL,
  secret_blob MEDIUMTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_integrations_user_provider (user_id, provider),
  CONSTRAINT fk_integrations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shows (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  show_type ENUM('tv','anime','movie','other') NOT NULL DEFAULT 'tv',
  status ENUM('unknown','running','ended','hiatus') NOT NULL DEFAULT 'unknown',
  poster_url VARCHAR(1000) NULL,
  description TEXT NULL,
  premiered DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_shows_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stable provider mapping prevents “ID drift”
CREATE TABLE IF NOT EXISTS show_external_ids (
  show_id BIGINT UNSIGNED NOT NULL,
  provider ENUM('tvmaze','anilist','trakt','thetvdb','imdb','tmdb','sonarr') NOT NULL,
  external_id VARCHAR(128) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (show_id, provider),
  UNIQUE KEY uq_show_ext (provider, external_id),
  CONSTRAINT fk_show_ext_show FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_tracked_shows (
  user_id BIGINT UNSIGNED NOT NULL,
  show_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, show_id),
  CONSTRAINT fk_track_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_track_show FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Calendar events
CREATE TABLE IF NOT EXISTS events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  show_id BIGINT UNSIGNED NOT NULL,
  event_type ENUM('airing','drop','special') NOT NULL DEFAULT 'airing',
  start_utc DATETIME NOT NULL,
  season SMALLINT UNSIGNED NULL,
  episode SMALLINT UNSIGNED NULL,
  episode_title VARCHAR(255) NULL,
  platform VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_events_start (start_utc),
  KEY ix_events_show (show_id, start_utc),
  CONSTRAINT fk_events_show FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WatchNexus v3 — security & auditing add-ons

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Basic audit log (expand later)
CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(64) NOT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  meta JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_audit_user (user_id, created_at),
  KEY ix_audit_action (action, created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Session table (optional if you want DB-backed sessions)
CREATE TABLE IF NOT EXISTS auth_sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  session_id VARCHAR(128) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_session_id (session_id),
  KEY ix_sessions_user (user_id, expires_at),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WatchNexus v3 — module policy tables (optional, for DB-backed modularity)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS modules (
  id VARCHAR(64) NOT NULL,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(64) NOT NULL DEFAULT 'feature',
  min_role ENUM('public','user','mod','admin') NOT NULL DEFAULT 'user',
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  user_toggle TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Global policy mode:
-- default_on: enabled unless user disables
-- default_off: disabled unless user enables
-- forced_on: enabled and user cannot disable
-- disabled_globally: hidden/disabled for everyone
CREATE TABLE IF NOT EXISTS global_module_policy (
  module_id VARCHAR(64) NOT NULL,
  mode ENUM('default_on','default_off','forced_on','disabled_globally') NOT NULL DEFAULT 'default_off',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (module_id),
  CONSTRAINT fk_gmp_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_module_overrides (
  user_id BIGINT UNSIGNED NOT NULL,
  module_id VARCHAR(64) NOT NULL,
  enabled TINYINT(1) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, module_id),
  CONSTRAINT fk_umo_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_umo_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed core modules (expand as you add new ones)
INSERT IGNORE INTO modules (id, name, category, min_role, is_required, user_toggle) VALUES
  ('calendar', 'Calendar', 'core', 'public', 1, 0),
  ('browse', 'Browse', 'core', 'public', 0, 0),
  ('myshows', 'My Shows', 'core', 'user', 0, 0),
  ('settings', 'Settings', 'core', 'user', 0, 0),
  ('trakt', 'Trakt', 'integration', 'user', 0, 1),
  ('seedr', 'Seedr', 'integration', 'user', 0, 1),
  ('jackett', 'Jackett', 'integration', 'user', 0, 1),
  ('prowlarr', 'Prowlarr', 'integration', 'user', 0, 1);

INSERT IGNORE INTO global_module_policy (module_id, mode) VALUES
  ('calendar', 'forced_on'),
  ('browse', 'default_on'),
  ('myshows', 'default_on'),
  ('settings', 'default_on'),
  ('trakt', 'default_off'),
  ('seedr', 'default_off'),
  ('jackett', 'default_off'),
  ('prowlarr', 'default_off');

-- System configuration table for admin settings
-- Store Trakt client ID/secret, API keys, etc.

CREATE TABLE IF NOT EXISTS system_config (
  config_key VARCHAR(64) NOT NULL,
  config_value_enc TEXT NULL,
  config_value_plain TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default Trakt config (empty, will be filled by admin)
INSERT IGNORE INTO system_config (config_key, config_value_enc) VALUES
  ('trakt_client_id', NULL),
  ('trakt_client_secret', NULL);

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
  ('mod', 0, 1, 0),        -- enabled by default (RBAC still hides if not mod/admin)
  ('admin', 0, 1, 0),      -- enabled by default (RBAC still hides if not admin)
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

-- Add missing shows.description and shows.premiered (schema mismatch fix)
SET @has_desc := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE table_schema = DATABASE() AND table_name = 'shows' AND column_name = 'description'
);
SET @sql := IF(@has_desc = 0,
  'ALTER TABLE shows ADD COLUMN description TEXT NULL AFTER poster_url',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_prem := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE table_schema = DATABASE() AND table_name = 'shows' AND column_name = 'premiered'
);
SET @sql := IF(@has_prem = 0,
  'ALTER TABLE shows ADD COLUMN premiered DATE NULL AFTER description',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
