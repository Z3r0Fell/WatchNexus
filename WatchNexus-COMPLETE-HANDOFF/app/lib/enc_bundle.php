INSERT INTO user_integrations (user_id, integration_type, api_variant, enabled, last_test_status)
VALUES
  (1, 'trakt',    'auto', 0, 'never'),
  (1, 'seedr',    'auto', 0, 'never'),
  (1, 'jackett',  'auto', 0, 'never'),
  (1, 'prowlarr', 'auto', 0, 'never')
ON DUPLICATE KEY UPDATE user_id = user_id;
