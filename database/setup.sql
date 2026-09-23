-- NexUse — one-time database and application user setup.
--
-- Run this ONCE with your MySQL root account:
--
--   "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p < database\setup.sql
--
-- It creates the `nexuse` database and the `nexuse_app` account the application
-- connects with. After this, root is never needed again.

CREATE DATABASE IF NOT EXISTS nexuse
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'nexuse_app'@'localhost'
  IDENTIFIED WITH mysql_native_password BY 'NexUse_Local_2026';

GRANT ALL PRIVILEGES ON nexuse.* TO 'nexuse_app'@'localhost';

FLUSH PRIVILEGES;

SELECT 'nexuse database and nexuse_app user are ready.' AS status;
