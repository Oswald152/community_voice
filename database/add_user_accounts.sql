USE community_voice;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    display_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE reports
    ADD COLUMN user_id INT NULL AFTER id,
    ADD CONSTRAINT reports_user_fk FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL;

ALTER TABLE admins
    ADD COLUMN display_name VARCHAR(100) NOT NULL DEFAULT '' AFTER username;

UPDATE admins SET display_name = username WHERE display_name = '';