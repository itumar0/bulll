-- «Бильярд Старт» — MySQL 5.7+/8.0 / MariaDB 10.3+ schema.
-- Import once via phpMyAdmin (InfinityFree: Control Panel → MySQL Databases → phpMyAdmin → Import).
-- All times are stored in UTC.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pool_tables (
  id         VARCHAR(8)   NOT NULL,
  name       VARCHAR(32)  NOT NULL,
  type       VARCHAR(10)  NOT NULL,          -- REGULAR | VIP
  capacity   TINYINT UNSIGNED NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  table_id   VARCHAR(8)   NOT NULL,
  name       VARCHAR(60)  NOT NULL,
  phone      VARCHAR(16)  NOT NULL,          -- +996XXXXXXXXX
  guests     TINYINT UNSIGNED NOT NULL,
  comment    VARCHAR(500) NULL,
  start_at   DATETIME     NOT NULL,          -- UTC
  end_at     DATETIME     NOT NULL,          -- UTC
  status     VARCHAR(10)  NOT NULL DEFAULT 'PENDING', -- PENDING | CONFIRMED | COMPLETED | CANCELLED
  source     VARCHAR(10)  NOT NULL DEFAULT 'WEBSITE', -- WEBSITE | ADMIN
  price_per_hour INT UNSIGNED NULL,           -- price at the moment of booking (som/hour), for reports
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_table_time (table_id, start_at, end_at),
  KEY idx_start (start_at),
  KEY idx_status (status),
  CONSTRAINT fk_booking_table FOREIGN KEY (table_id) REFERENCES pool_tables (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting (login brute force, booking spam). Keys are SHA-256 hashes, no raw IPs.
CREATE TABLE IF NOT EXISTS rate_limits (
  k             CHAR(64)     NOT NULL,
  hits          INT UNSIGNED NOT NULL DEFAULT 0,
  window_start  INT UNSIGNED NOT NULL,
  blocked_until INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (k)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;

INSERT INTO pool_tables (id, name, type, capacity, sort_order) VALUES
  ('t1', 'Стол 1', 'REGULAR', 6, 1),
  ('t2', 'Стол 2', 'REGULAR', 6, 2),
  ('t3', 'Стол 3', 'REGULAR', 6, 3),
  ('t4', 'Стол 4', 'REGULAR', 6, 4),
  ('t5', 'Стол 5', 'REGULAR', 6, 5),
  ('t6', 'Стол 6', 'REGULAR', 6, 6),
  ('vip1', 'VIP 1', 'VIP', 10, 7),
  ('vip2', 'VIP 2', 'VIP', 10, 8)
ON DUPLICATE KEY UPDATE name = VALUES(name), type = VALUES(type), capacity = VALUES(capacity), sort_order = VALUES(sort_order);
