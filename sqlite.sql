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

-- Table structure for third_party_apps
CREATE TABLE `third_party_apps` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `app_id` TEXT NOT NULL,
  `app_secret` TEXT NOT NULL,
  `app_name` TEXT NOT NULL,
  `app_description` TEXT,
  `redirect_uri` TEXT,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  UNIQUE (`app_id`)
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

-- 1. groups 表索引
DROP INDEX IF EXISTS "groups_index";
CREATE UNIQUE INDEX "groups_index" ON "groups" (
    "group_id",
    "group_name"
);

-- 2. live_list 表索引
DROP INDEX IF EXISTS "live_list_index";
CREATE INDEX "live_list_user_id_index" ON "live_list" ("user_id");
CREATE INDEX "live_list_status_index" ON "live_list" ("status");
CREATE INDEX "live_list_video_source_type_index" ON "live_list" ("video_source_type");

-- 3. third_party_apps 表索引
CREATE INDEX "third_party_apps_user_id_index" ON "third_party_apps" ("user_id");
CREATE INDEX "third_party_apps_created_at_index" ON "third_party_apps" ("created_at");
CREATE INDEX "third_party_apps_updated_at_index" ON "third_party_apps" ("updated_at");

-- 4. messages 表索引
DROP INDEX IF EXISTS "messages_index";
CREATE INDEX "messages_room_id_created_at_index" ON "messages" ("room_id", "created_at");
CREATE INDEX "messages_user_id_index" ON "messages" ("user_id");
CREATE INDEX "messages_status_index" ON "messages" ("status");
CREATE INDEX "messages_created_at_index" ON "messages" ("created_at");
CREATE INDEX "messages_type_index" ON "messages" ("type");

-- 5. users 表索引
DROP INDEX IF EXISTS "users_index";
CREATE UNIQUE INDEX "users_index" ON "users" (
    "user_id",
    "email",
    "username",
    "register_ip"
);
CREATE INDEX "users_group_id_index" ON "users" ("group_id");
CREATE INDEX "users_status_index" ON "users" ("status");
CREATE INDEX "users_created_at_index" ON "users" ("created_at");
CREATE INDEX "users_username_index" ON "users" ("username");

-- 6. user_settings 表索引
CREATE INDEX "user_settings_client_id_index" ON "user_settings" ("client_id");
CREATE INDEX "user_settings_setting_name_index" ON "user_settings" ("setting_name");
CREATE INDEX "user_settings_update_time_index" ON "user_settings" ("update_time");

-- 7. user_tokens 表索引
DROP INDEX IF EXISTS "user_tokens_index";
CREATE UNIQUE INDEX "user_tokens_index" ON "user_tokens" (
    "id",
    "user_id"
);
CREATE INDEX "user_tokens_token_index" ON "user_tokens" ("token");
CREATE INDEX "user_tokens_expiration_index" ON "user_tokens" ("expiration");
CREATE INDEX "user_tokens_type_index" ON "user_tokens" ("type");
CREATE INDEX "user_tokens_user_id_type_index" ON "user_tokens" ("user_id", "type");
COMMIT;
