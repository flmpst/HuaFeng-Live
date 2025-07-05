-- SQLite version of the database schema

-- Table structure for `groups`
CREATE TABLE `groups` (
  `group_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `group_name` TEXT NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for `live_list`
CREATE TABLE `live_list` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL,
  `pic` TEXT,
  `description` TEXT NOT NULL,
  `video_source` TEXT NOT NULL,
  `video_source_type` TEXT NOT NULL,
  `css` TEXT NOT NULL,
  `status` TEXT DEFAULT 'active'
);

-- Table structure for `messages`
CREATE TABLE `messages` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `room_id` INTEGER,
  `type` TEXT DEFAULT 'user',
  `content` TEXT NOT NULL,
  `danmaku` TEXT NOT NULL,
  `user_id` INTEGER NOT NULL,
  `user_ip` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` TEXT DEFAULT 'active'
);

-- Table structure for `users`
CREATE TABLE `users` (
  `user_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` TEXT NOT NULL,
  `avatar` TEXT,
  `password` TEXT NOT NULL,
  `email` TEXT NOT NULL UNIQUE,
  `register_ip` TEXT NOT NULL,
  `group_id` INTEGER NOT NULL DEFAULT 2,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` INTEGER NOT NULL DEFAULT 0,
  `sets` TEXT
);

-- Table structure for `user_settings`
CREATE TABLE `user_settings` (
  `uuid` TEXT PRIMARY KEY,
  `user_id` TEXT NOT NULL,
  `client_id` TEXT NOT NULL,
  `setting_name` TEXT NOT NULL,
  `setting_value` TEXT,
  `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_ip` TEXT,
  UNIQUE (`user_id`, `client_id`, `setting_name`)
);

-- Table structure for `user_tokens`
CREATE TABLE `user_tokens` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `type` TEXT NOT NULL,
  `token` TEXT NOT NULL,
  `expiration` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `extra` TEXT
);

INSERT INTO "groups" ("group_id","group_name","created_at") VALUES (1,'管理员','2025-01-28 08:34:00'),
 (2,'普通用户','2025-01-28 08:34:00');

 DROP INDEX IF EXISTS "admin_login_attempts_index";
CREATE UNIQUE INDEX "admin_login_attempts_index" ON "admin_login_attempts" (
	"id"
);
DROP INDEX IF EXISTS "events_index";
CREATE INDEX "events_index" ON "events" (
	"event_id",
	"event_type"
);
DROP INDEX IF EXISTS "groups_index";
CREATE UNIQUE INDEX "groups_index" ON "groups" (
	"group_id",
	"group_name"
);
DROP INDEX IF EXISTS "messages_index";
CREATE UNIQUE INDEX "messages_index" ON "messages" (
	"id",
	"room_id"
);
DROP INDEX IF EXISTS "room_sets_index";
CREATE UNIQUE INDEX "room_sets_index" ON "room_sets" (
	"id",
	"user_id"
);
DROP INDEX IF EXISTS "system_logs_index";
CREATE UNIQUE INDEX "system_logs_index" ON "system_logs" (
	"log_id"
);
DROP INDEX IF EXISTS "user_tokens_index";
CREATE UNIQUE INDEX "user_tokens_index" ON "user_tokens" (
	"id",
	"user_id"
);
DROP INDEX IF EXISTS "users_index";
CREATE UNIQUE INDEX "users_index" ON "users" (
	"user_id",
	"email",
	"username",
	"register_ip"
);
COMMIT;
