-- OAuth Migration: Add OAuth support to existing users table
-- Run this if you already have an existing database

ALTER TABLE `users` 
  ADD COLUMN `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `username`,
  ADD COLUMN `oauth_provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `password_hash`,
  ADD COLUMN `oauth_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `oauth_provider`,
  ADD COLUMN `oauth_token` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `oauth_id`,
  ADD COLUMN `oauth_refresh_token` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `oauth_token`,
  ADD COLUMN `avatar_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `oauth_refresh_token`,
  ADD COLUMN `last_login` datetime DEFAULT NULL AFTER `created_at`,
  MODIFY COLUMN `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD UNIQUE KEY `oauth_provider_id` (`oauth_provider`, `oauth_id`),
  ADD KEY `idx_email` (`email`);
