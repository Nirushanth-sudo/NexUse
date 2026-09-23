-- NexUse — schema
-- Ten tables covering the interim scope plus messaging.
--
-- Run:  mysql -u nexuse_app -p nexuse < database\schema.sql
--
-- Note: the column holding an item's condition is `item_condition`, not `condition`,
-- because CONDITION is a reserved word in MySQL.

USE nexuse;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS listing_images;
DROP TABLE IF EXISTS listings;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;


-- ---------------------------------------------------------------------------
-- users — every account. Six of the proposal's seven actors are transaction
-- roles, not account types, so only 'admin' and 'member' are stored here.
-- ---------------------------------------------------------------------------
CREATE TABLE users (
  user_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name           VARCHAR(100)  NOT NULL,
  email          VARCHAR(190)  NOT NULL,
  password_hash  VARCHAR(255)  NOT NULL,
  phone          VARCHAR(20)   DEFAULT NULL,
  address        VARCHAR(255)  DEFAULT NULL,
  city           VARCHAR(80)   DEFAULT NULL,
  bio            TEXT          DEFAULT NULL,
  avatar_path    VARCHAR(255)  DEFAULT NULL,
  role           ENUM('admin','member')            NOT NULL DEFAULT 'member',
  status         ENUM('active','suspended')        NOT NULL DEFAULT 'active',
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- categories — item categories, managed by admin
-- ---------------------------------------------------------------------------
CREATE TABLE categories (
  category_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(80)  NOT NULL,
  slug         VARCHAR(80)  NOT NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- listings — items offered to sell, rent, share or donate.
-- listing_type is what lets one table serve all four exchange types.
-- ---------------------------------------------------------------------------
CREATE TABLE listings (
  listing_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  category_id     INT UNSIGNED DEFAULT NULL,
  title           VARCHAR(150) NOT NULL,
  description     TEXT         NOT NULL,
  item_condition  ENUM('new','like_new','good','fair','poor')   NOT NULL DEFAULT 'good',
  listing_type    ENUM('sell','rent','share','donate')          NOT NULL,
  price           DECIMAL(10,2) DEFAULT NULL,  -- sale price, or rent per day
  location        VARCHAR(120) DEFAULT NULL,
  status          ENUM('available','reserved','completed','removed') NOT NULL DEFAULT 'available',
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (listing_id),
  KEY idx_listings_user (user_id),
  KEY idx_listings_category (category_id),
  KEY idx_listings_status (status),
  KEY idx_listings_type (listing_type),
  CONSTRAINT fk_listings_user
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_listings_category
    FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- listing_images — uploaded photos. image_path is always relative.
-- ---------------------------------------------------------------------------
CREATE TABLE listing_images (
  image_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id  INT UNSIGNED NOT NULL,
  image_path  VARCHAR(255) NOT NULL,
  is_primary  TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (image_id),
  KEY idx_images_listing (listing_id),
  CONSTRAINT fk_images_listing
    FOREIGN KEY (listing_id) REFERENCES listings (listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- requests — buy, rent, borrow and donation requests against a listing.
-- Status flow: pending -> accepted -> completed
--              pending -> rejected | withdrawn
-- ---------------------------------------------------------------------------
CREATE TABLE requests (
  request_id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id         INT UNSIGNED NOT NULL,
  requester_id       INT UNSIGNED NOT NULL,
  owner_id           INT UNSIGNED NOT NULL,
  request_type       ENUM('buy','rent','borrow','donation') NOT NULL,
  message            TEXT         DEFAULT NULL,
  status             ENUM('pending','accepted','rejected','withdrawn','completed')
                       NOT NULL DEFAULT 'pending',
  start_date         DATE         DEFAULT NULL,
  return_date        DATE         DEFAULT NULL,
  actual_return_date DATE         DEFAULT NULL,
  return_condition   ENUM('as_given','minor_damage','major_damage','not_returned')
                       DEFAULT NULL,
  owner_note         VARCHAR(500) DEFAULT NULL,
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (request_id),
  KEY idx_requests_listing (listing_id),
  KEY idx_requests_requester (requester_id),
  KEY idx_requests_owner (owner_id),
  KEY idx_requests_status (status),
  CONSTRAINT fk_requests_listing
    FOREIGN KEY (listing_id) REFERENCES listings (listing_id) ON DELETE CASCADE,
  CONSTRAINT fk_requests_requester
    FOREIGN KEY (requester_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_requests_owner
    FOREIGN KEY (owner_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- reviews — one review per reviewer per completed request.
-- ---------------------------------------------------------------------------
CREATE TABLE reviews (
  review_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id   INT UNSIGNED NOT NULL,
  reviewer_id  INT UNSIGNED NOT NULL,
  reviewee_id  INT UNSIGNED NOT NULL,
  rating       TINYINT UNSIGNED NOT NULL,
  comment      TEXT         DEFAULT NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  UNIQUE KEY uq_review_per_request (request_id, reviewer_id),
  KEY idx_reviews_reviewee (reviewee_id),
  CONSTRAINT fk_reviews_request
    FOREIGN KEY (request_id) REFERENCES requests (request_id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_reviewer
    FOREIGN KEY (reviewer_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_reviewee
    FOREIGN KEY (reviewee_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- complaints — disputes reported against a user or listing.
-- The platform records and routes them; it does not mediate (proposal, §3).
-- ---------------------------------------------------------------------------
CREATE TABLE complaints (
  complaint_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  complainant_id   INT UNSIGNED NOT NULL,
  against_user_id  INT UNSIGNED DEFAULT NULL,
  listing_id       INT UNSIGNED DEFAULT NULL,
  subject          VARCHAR(150) NOT NULL,
  description      TEXT         NOT NULL,
  status           ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
  admin_note       VARCHAR(500) DEFAULT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (complaint_id),
  KEY idx_complaints_complainant (complainant_id),
  KEY idx_complaints_against (against_user_id),
  KEY idx_complaints_status (status),
  CONSTRAINT fk_complaints_complainant
    FOREIGN KEY (complainant_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_complaints_against
    FOREIGN KEY (against_user_id) REFERENCES users (user_id) ON DELETE SET NULL,
  CONSTRAINT fk_complaints_listing
    FOREIGN KEY (listing_id) REFERENCES listings (listing_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- notifications — per-user alerts raised by system events and admin broadcasts.
-- ---------------------------------------------------------------------------
CREATE TABLE notifications (
  notification_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          INT UNSIGNED NOT NULL,
  type             ENUM('request','accepted','rejected','withdrawn','returned',
                        'review','complaint','broadcast','message','system')
                     NOT NULL DEFAULT 'system',
  title            VARCHAR(150) NOT NULL,
  message          VARCHAR(500) DEFAULT NULL,
  link             VARCHAR(255) DEFAULT NULL,
  is_read          TINYINT(1)   NOT NULL DEFAULT 0,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notification_id),
  KEY idx_notifications_user_read (user_id, is_read),
  KEY idx_notifications_created (created_at),
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- conversations — one message thread per (listing, interested member).
-- The other party is always the listing owner, whichever exchange type it is.
-- ---------------------------------------------------------------------------
CREATE TABLE conversations (
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


-- ---------------------------------------------------------------------------
-- messages — the lines inside a conversation.
-- ---------------------------------------------------------------------------
CREATE TABLE messages (
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


SELECT 'NexUse schema created — 10 tables.' AS status;
