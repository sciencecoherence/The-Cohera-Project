-- TRANSMISSION community schema.
-- Run once in phpMyAdmin (hPanel -> Databases -> MySQL -> Enter phpMyAdmin),
-- with your database selected, via the SQL tab.

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(40) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('article','note') NOT NULL DEFAULT 'article',
  title VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  excerpt VARCHAR(280) NOT NULL DEFAULT '',
  body MEDIUMTEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at DATETIME NULL,
  CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_posts_status ON posts (status, approved_at);
