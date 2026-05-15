CREATE DATABASE IF NOT EXISTS task_tracker3;
USE task_tracker3;

CREATE TABLE IF NOT EXISTS users (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    email      VARCHAR(255) NOT NULL DEFAULT '' UNIQUE,
    password   VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL DEFAULT '',
    last_name  VARCHAR(100) NOT NULL DEFAULT ''
);

-- Password reset OTP table
CREATE TABLE IF NOT EXISTS password_resets (
    id           INT          AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    otp_code     VARCHAR(6)   NOT NULL,
    otp_hash     VARCHAR(255) NOT NULL,          -- bcrypt hash of the OTP
    expires_at   DATETIME     NOT NULL,
    used         TINYINT(1)   NOT NULL DEFAULT 0,
    attempts     TINYINT      NOT NULL DEFAULT 0, -- wrong-guess counter
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Safe migrations for existing databases:
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL DEFAULT '';
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NOT NULL DEFAULT '';
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS last_name  VARCHAR(100) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS tasks (
    id       INT          AUTO_INCREMENT PRIMARY KEY,
    user_id  INT          NOT NULL,
    title    VARCHAR(255) NOT NULL,
    deadline DATE         NOT NULL,
    status   VARCHAR(20)  NOT NULL DEFAULT 'pending',
    category VARCHAR(100) NOT NULL DEFAULT '',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categories table — one row per user-defined category
CREATE TABLE IF NOT EXISTS categories (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    icon        VARCHAR(10)  NOT NULL DEFAULT '🏷️',
    color       VARCHAR(20)  NOT NULL DEFAULT '#f1f5f9',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_category (user_id, name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed the five built-in categories for existing users (safe to run multiple times)
-- Run once per user after creating the table:
-- INSERT IGNORE INTO categories (user_id, name, description, icon, color)
-- SELECT id, 'School',   'Academic assignments and study tasks',    '📚', '#dbeafe' FROM users;
-- INSERT IGNORE INTO categories (user_id, name, description, icon, color)
-- SELECT id, 'Personal', 'Personal goals and daily habits',         '👤', '#fce7f3' FROM users;
-- INSERT IGNORE INTO categories (user_id, name, description, icon, color)
-- SELECT id, 'Work',     'Professional and work-related tasks',     '💼', '#d1fae5' FROM users;
-- INSERT IGNORE INTO categories (user_id, name, description, icon, color)
-- SELECT id, 'Project',  'Project milestones and deliverables',     '🚀', '#ede9fe' FROM users;
-- INSERT IGNORE INTO categories (user_id, name, description, icon, color)
-- SELECT id, 'Health',   'Health, fitness, and wellness tasks',     '❤️', '#fef3c7' FROM users;

-- Run this if the tasks table already exists without the category column:
-- ALTER TABLE tasks ADD COLUMN IF NOT EXISTS category VARCHAR(100) NOT NULL DEFAULT '';
