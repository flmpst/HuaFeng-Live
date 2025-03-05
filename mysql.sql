CREATE TABLE admin_login_attempts (
    id INT NOT NULL AUTO_INCREMENT,
    ip_address VARCHAR(255) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_blocked INT NOT NULL DEFAULT 0,
    PRIMARY KEY(id)
);

CREATE TABLE events (
    event_id INT NOT NULL AUTO_INCREMENT,
    event_type VARCHAR(100) NOT NULL,
    user_id INT NOT NULL,
    target_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    additional_data VARCHAR(255),
    PRIMARY KEY(event_id)
);

CREATE TABLE groups (
    group_id INT NOT NULL AUTO_INCREMENT,
    group_name VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE messages (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT,
    type VARCHAR(255) DEFAULT 'user',
    content VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    user_ip VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(255) DEFAULT 'active',
    PRIMARY KEY(id)
);

CREATE TABLE room_sets (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT,
    value VARCHAR(255),
    status VARCHAR(255) DEFAULT 'active',
    PRIMARY KEY(id)
);

CREATE TABLE system_logs (
    log_id INT NOT NULL AUTO_INCREMENT,
    log_type VARCHAR(255) NOT NULL,
    message VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(log_id)
);

CREATE TABLE user_tokens (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  type INT NOT NULL,
  token VARCHAR(256) NOT NULL,
  expiration DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE users (
    user_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    register_ip FLOAT NOT NULL,
    group_id INT NOT NULL DEFAULT 2,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status INT NOT NULL DEFAULT 0,
    sets VARCHAR(255),
    PRIMARY KEY(user_id)
);

INSERT INTO groups (group_id,group_name,created_at) VALUES (1,'管理员','2025-01-28 08:34:00'),
 (2,'普通用户','2025-01-28 08:34:00');