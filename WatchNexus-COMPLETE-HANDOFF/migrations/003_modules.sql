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
