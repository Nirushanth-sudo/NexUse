-- NexUse — migration 002: messaging and email verification
--
-- Additive only: run this on an existing database and nothing is lost.
--
--   mysql -u root -p nexuse < database\migration_002_features.sql
--
-- Adds:
--   · conversations + messages   — buyer/seller chat (feature 4)
--   · email_otps                 — one-time codes for email verification (feature 5)
--   · users.email_verified_at    — when the address was confirmed
--   · notifications.type gains 'message'

USE nexuse;


-- ------------------------------------------------- users: verification ----
-- Existing accounts are treated as already verified so nobody is locked out.
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = 'nexuse' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL AFTER email',
  'SELECT "users.email_verified_at already present" AS note');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL;


-- --------------------------------------------------- notifications: type ----
ALTER TABLE notifications
  MODIFY COLUMN type ENUM('request','accepted','rejected','withdrawn','returned',
                          'review','complaint','broadcast','message','system')
         NOT NULL DEFAULT 'system';


-- ------------------------------------------------------- conversations ----
-- One thread per (listing, interested member). The owner is denormalised onto
-- the row so inbox queries do not need to join listings every time.
CREATE TABLE IF NOT EXISTS conversations (
  conversation_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id      INT UNSIGNED NOT NULL,
  buyer_id        INT UNSIGNED NOT NULL,
  owner_id        INT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (conversation_id),
  UNIQUE KEY uq_conversation (listing_id, buyer_id),
  KEY idx_conversations_buyer (buyer_id),
  KEY idx_conversations_owner (owner_id),
  KEY idx_conversations_recent (last_message_at),
  CONSTRAINT fk_conversations_listing
    FOREIGN KEY (listing_id) REFERENCES listings (listing_id) ON DELETE CASCADE,
  CONSTRAINT fk_conversations_buyer
    FOREIGN KEY (buyer_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_conversations_owner
    FOREIGN KEY (owner_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------ messages ----
CREATE TABLE IF NOT EXISTS messages (
  message_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id INT UNSIGNED NOT NULL,
  sender_id       INT UNSIGNED NOT NULL,
  body            VARCHAR(2000) NOT NULL,
  is_read         TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id),
  KEY idx_messages_conversation (conversation_id, created_at),
  KEY idx_messages_unread (conversation_id, sender_id, is_read),
  CONSTRAINT fk_messages_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations (conversation_id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_sender
    FOREIGN KEY (sender_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------- email_otps ----
-- The code itself is never stored: only a hash, exactly like a password.
CREATE TABLE IF NOT EXISTS email_otps (
  otp_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  code_hash   VARCHAR(255) NOT NULL,
  purpose     ENUM('verify_email') NOT NULL DEFAULT 'verify_email',
  attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at  DATETIME NOT NULL,
  consumed_at DATETIME DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (otp_id),
  KEY idx_otps_user (user_id, purpose, consumed_at),
  KEY idx_otps_expiry (expires_at),
  CONSTRAINT fk_otps_user
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SELECT 'Migration 002 applied — conversations, messages, email_otps.' AS status;
