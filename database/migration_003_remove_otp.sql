-- NexUse — migration 003: remove email verification (OTP)
--
-- Feature round 6 removed the one-time-code step from registration. This
-- takes out what migration 002 added for it:
--   · the email_otps table
--   · users.email_verified_at
--
-- Safe to run more than once.
--
--   mysql -u root -p nexuse < database\migration_003_remove_otp.sql

USE nexuse;

DROP TABLE IF EXISTS email_otps;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = 'nexuse' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at'
);
SET @sql := IF(@col = 1,
  'ALTER TABLE users DROP COLUMN email_verified_at',
  'SELECT "users.email_verified_at already removed" AS note');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Migration 003 applied — email_otps and users.email_verified_at removed.' AS status;
