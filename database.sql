BEGIN TRANSACTION;
DROP TABLE IF EXISTS "admin_login_attempts";
CREATE TABLE "admin_login_attempts" (
	"id"	INTEGER NOT NULL UNIQUE,
	"ip_address"	TEXT NOT NULL,
	"attempts"	INTEGER NOT NULL DEFAULT 0,
	"last_attempt"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	"is_blocked"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "events";
CREATE TABLE "events" (
	"event_id"	INTEGER,
	"event_type"	VARCHAR(100) NOT NULL,
	"user_id"	INT NOT NULL,
	"target_id"	INT NOT NULL,
	"created_at"	DATETIME NOT NULL,
	"additional_data"	TEXT,
	PRIMARY KEY("event_id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "groups";
CREATE TABLE "groups" (
	"group_id"	INTEGER NOT NULL UNIQUE,
	"group_name"	TEXT NOT NULL UNIQUE,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("group_id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "messages";
CREATE TABLE "messages" (
	"id"	INTEGER NOT NULL UNIQUE,
	"room_id"	INTEGER,
	"type"	TEXT DEFAULT 'user',
	"content"	TEXT NOT NULL,
	"user_id"	INTEGER NOT NULL,
	"user_ip"	TEXT,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	"status"	TEXT DEFAULT 'active',
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "room_sets";
CREATE TABLE "room_sets" (
	"id"	INTEGER UNIQUE,
	"user_id"	INTEGER,
	"value"	TEXT,
	"status"	TEXT DEFAULT 'active',
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "system_logs";
CREATE TABLE "system_logs" (
	"log_id"	INTEGER NOT NULL UNIQUE,
	"log_type"	TEXT NOT NULL,
	"message"	TEXT NOT NULL,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("log_id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "user_tokens";
CREATE TABLE "user_tokens" (
	"id"	INTEGER NOT NULL UNIQUE,
	"user_id" INTEGER NOT NULL UNIQUE,
	"token"	VARCHAR(256) NOT NULL,
	"expiration"	DATETIME,
	"created_at"	DATETIME,
	"updated_at"	DATETIME,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "users";
CREATE TABLE "users" (
	"user_id"	INTEGER NOT NULL UNIQUE,
	"username"	TEXT NOT NULL UNIQUE,
	"password"	TEXT NOT NULL,
	"email"	TEXT,
	"register_ip"	REAL,
	"group_id"	INTEGER NOT NULL DEFAULT 2,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	"status"	INTEGER DEFAULT 0,
	"sets"	TEXT,
	PRIMARY KEY("user_id" AUTOINCREMENT)
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
