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
