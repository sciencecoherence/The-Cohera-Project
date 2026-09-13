-- Community v2: media attachments + site channel.
-- Run once in phpMyAdmin (SQL tab, your database selected), AFTER schema.sql.

ALTER TABLE posts MODIFY type ENUM('article','note','transmission') NOT NULL DEFAULT 'article';
ALTER TABLE posts ADD channel ENUM('community','site') NOT NULL DEFAULT 'community' AFTER type;

CREATE TABLE media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  kind ENUM('audio','video') NOT NULL,
  filename VARCHAR(60) NOT NULL,
  original_name VARCHAR(190) NOT NULL DEFAULT '',
  mime VARCHAR(100) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_media_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
