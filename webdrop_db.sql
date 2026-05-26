-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 26, 2026 at 11:18 AM
-- Server version: 8.0.30
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webdrop_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_create_project` (IN `p_id_user` BIGINT UNSIGNED, IN `p_project_name` VARCHAR(120), IN `p_slug` VARCHAR(140), IN `p_workspace_path` VARCHAR(255))   BEGIN
    INSERT INTO projects (
        id_user,
        project_name,
        slug,
        workspace_path,
        status
    ) VALUES (
        p_id_user,
        p_project_name,
        p_slug,
        p_workspace_path,
        'draft'
    );

    SELECT LAST_INSERT_ID() AS id_project;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_mark_project_published` (IN `p_id_project` BIGINT UNSIGNED, IN `p_published_path` VARCHAR(255), IN `p_public_url` VARCHAR(255), IN `p_id_user` BIGINT UNSIGNED)   BEGIN
    UPDATE projects
    SET
        status = 'published',
        published_path = p_published_path,
        public_url = p_public_url,
        last_published_at = NOW()
    WHERE id_project = p_id_project
      AND id_user = p_id_user;

    INSERT INTO activity_logs (
        id_user,
        id_project,
        action,
        description
    ) VALUES (
        p_id_user,
        p_id_project,
        'publish_project',
        CONCAT('Project published to ', p_public_url)
    );
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_is_editable_extension` (`p_ext` VARCHAR(20)) RETURNS TINYINT DETERMINISTIC BEGIN
    RETURN CASE
        WHEN LOWER(p_ext) IN ('html','css','js') THEN 1
        ELSE 0
    END;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_project_status_label` (`p_status` VARCHAR(20)) RETURNS VARCHAR(50) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DETERMINISTIC BEGIN
    RETURN CASE p_status
        WHEN 'draft' THEN 'Draft'
        WHEN 'published' THEN 'Published'
        WHEN 'modified' THEN 'Modified After Publish'
        ELSE 'Unknown'
    END;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_public_url` (`p_username` VARCHAR(50), `p_slug` VARCHAR(140)) RETURNS VARCHAR(255) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DETERMINISTIC BEGIN
    RETURN CONCAT('/sites/', p_username, '/', p_slug);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id_log` bigint UNSIGNED NOT NULL,
  `id_user` bigint UNSIGNED NOT NULL,
  `id_project` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id_log`, `id_user`, `id_project`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, NULL, 'register', 'User baru terdaftar.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 14:09:56'),
(2, 1, NULL, 'login', 'User login.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 14:10:04'),
(3, 1, 1, 'create_project', 'Project dibuat: nes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 14:10:32'),
(4, 1, 1, 'publish_project', 'Project published to http://localhost/sites/pami/nes', NULL, NULL, '2026-05-26 15:04:20'),
(5, 1, 1, 'publish_project', 'Project dipublish ke http://localhost/sites/pami/nes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:04:20'),
(6, 1, 1, 'publish_project', 'Project published to http://localhost/sites/pami/nes', NULL, NULL, '2026-05-26 15:05:31'),
(7, 1, 1, 'publish_project', 'Project dipublish ke http://localhost/sites/pami/nes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:05:31'),
(8, 1, 1, 'publish_project', 'Project published to http://localhost/sites/pami/nes', NULL, NULL, '2026-05-26 15:05:43'),
(9, 1, 1, 'publish_project', 'Project dipublish ke http://localhost/sites/pami/nes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:05:43'),
(10, 1, 1, 'publish_project', 'Project published to http://localhost/sites/pami/nes', NULL, NULL, '2026-05-26 16:06:30'),
(11, 1, 1, 'publish_project', 'Project dipublish ke http://localhost/sites/pami/nes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 16:06:30'),
(12, 1, 1, 'publish_project', 'Project published to http://localhost/sites/pami/nes', NULL, NULL, '2026-05-26 16:11:02'),
(13, 1, 1, 'publish_project', 'Project dipublish ke http://localhost/sites/pami/nes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 16:11:02'),
(14, 1, NULL, 'login', 'User login.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 16:43:55');

-- --------------------------------------------------------

--
-- Table structure for table `allowed_file_types`
--

CREATE TABLE `allowed_file_types` (
  `id_type` int UNSIGNED NOT NULL,
  `extension` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('editable','asset') COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_size_mb` int UNSIGNED NOT NULL DEFAULT '2',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `allowed_file_types`
--

INSERT INTO `allowed_file_types` (`id_type`, `extension`, `category`, `max_size_mb`, `is_active`) VALUES
(1, 'html', 'editable', 1, 1),
(2, 'css', 'editable', 1, 1),
(3, 'js', 'editable', 1, 1),
(4, 'json', 'asset', 1, 1),
(5, 'txt', 'asset', 1, 1),
(6, 'png', 'asset', 3, 1),
(7, 'jpg', 'asset', 3, 1),
(8, 'jpeg', 'asset', 3, 1),
(9, 'gif', 'asset', 3, 1),
(10, 'svg', 'asset', 1, 1),
(11, 'webp', 'asset', 3, 1),
(12, 'ico', 'asset', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id_project` bigint UNSIGNED NOT NULL,
  `id_user` bigint UNSIGNED NOT NULL,
  `project_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workspace_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `public_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','modified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `last_published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id_project`, `id_user`, `project_name`, `slug`, `workspace_path`, `published_path`, `public_url`, `status`, `last_published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'nes', 'nes', 'D:\\Websites\\webdrop\\storage\\workspaces\\pami\\nes\\', 'D:\\Websites\\webdrop\\sites\\pami\\nes\\', 'http://localhost/sites/pami/nes', 'published', '2026-05-26 16:11:02', '2026-05-26 14:10:32', '2026-05-26 16:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `project_files`
--

CREATE TABLE `project_files` (
  `id_file` bigint UNSIGNED NOT NULL,
  `id_project` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `file_name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relative_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_extension` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint UNSIGNED NOT NULL DEFAULT '0',
  `is_folder` tinyint(1) NOT NULL DEFAULT '0',
  `is_editable` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_files`
--

INSERT INTO `project_files` (`id_file`, `id_project`, `parent_id`, `file_name`, `relative_path`, `file_extension`, `file_size`, `is_folder`, `is_editable`, `created_at`, `updated_at`) VALUES
(46, 1, NULL, 'index.html', 'index.html', 'html', 24051, 0, 1, '2026-05-26 16:10:58', NULL);

--
-- Triggers `project_files`
--
DELIMITER $$
CREATE TRIGGER `trg_project_files_after_delete` AFTER DELETE ON `project_files` FOR EACH ROW BEGIN
    UPDATE projects
    SET status = CASE
        WHEN status = 'published' THEN 'modified'
        ELSE status
    END
    WHERE id_project = OLD.id_project;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_project_files_after_insert` AFTER INSERT ON `project_files` FOR EACH ROW BEGIN
    UPDATE projects
    SET status = CASE
        WHEN status = 'published' THEN 'modified'
        ELSE status
    END
    WHERE id_project = NEW.id_project;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_project_files_after_update` AFTER UPDATE ON `project_files` FOR EACH ROW BEGIN
    UPDATE projects
    SET status = CASE
        WHEN status = 'published' THEN 'modified'
        ELSE status
    END
    WHERE id_project = NEW.id_project;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `publish_jobs`
--

CREATE TABLE `publish_jobs` (
  `id_publish_job` bigint UNSIGNED NOT NULL,
  `id_project` bigint UNSIGNED NOT NULL,
  `id_user` bigint UNSIGNED NOT NULL,
  `status` enum('queued','running','success','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `message` text COLLATE utf8mb4_unicode_ci,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `publish_jobs`
--

INSERT INTO `publish_jobs` (`id_publish_job`, `id_project`, `id_user`, `status`, `message`, `started_at`, `finished_at`, `created_at`) VALUES
(1, 1, 1, 'success', 'Publish berhasil.', '2026-05-26 15:04:20', '2026-05-26 15:04:20', '2026-05-26 15:04:20'),
(2, 1, 1, 'success', 'Publish berhasil.', '2026-05-26 15:05:31', '2026-05-26 15:05:31', '2026-05-26 15:05:31'),
(3, 1, 1, 'success', 'Publish berhasil.', '2026-05-26 15:05:43', '2026-05-26 15:05:43', '2026-05-26 15:05:43'),
(4, 1, 1, 'failed', 'Project belum dapat dipublish karena file index.html tidak ditemukan.', '2026-05-26 16:02:45', '2026-05-26 16:02:45', '2026-05-26 16:02:45'),
(5, 1, 1, 'success', 'Publish berhasil.', '2026-05-26 16:06:30', '2026-05-26 16:06:30', '2026-05-26 16:06:30'),
(6, 1, 1, 'success', 'Publish berhasil.', '2026-05-26 16:11:02', '2026-05-26 16:11:02', '2026-05-26 16:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` bigint UNSIGNED NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `email`, `username`, `password`, `profile_photo`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'mbahicbear@gmail.com', 'pami', '$2y$12$CCJoJKlw8RtFdl1B9MVvWetC/7MoQzNX8VXX02q9hyXyJlQzbyogG', NULL, 'user', 1, '2026-05-26 14:09:56', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_project_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `v_project_dashboard` (
`id_project` bigint unsigned
,`id_user` bigint unsigned
,`username` varchar(50)
,`project_name` varchar(120)
,`slug` varchar(140)
,`status` enum('draft','published','modified')
,`status_label` varchar(50)
,`workspace_path` varchar(255)
,`published_path` varchar(255)
,`public_url` varchar(255)
,`last_published_at` datetime
,`created_at` datetime
,`updated_at` datetime
,`total_items` bigint
,`total_files` decimal(23,0)
,`total_folders` decimal(23,0)
,`total_size_bytes` decimal(42,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_publish_history`
-- (See below for the actual view)
--
CREATE TABLE `v_publish_history` (
`id_publish_job` bigint unsigned
,`id_project` bigint unsigned
,`id_user` bigint unsigned
,`username` varchar(50)
,`project_name` varchar(120)
,`slug` varchar(140)
,`status` enum('queued','running','success','failed')
,`message` text
,`started_at` datetime
,`finished_at` datetime
,`duration_seconds` bigint
,`created_at` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_user_storage_usage`
-- (See below for the actual view)
--
CREATE TABLE `v_user_storage_usage` (
`id_user` bigint unsigned
,`username` varchar(50)
,`total_projects` bigint
,`total_items` bigint
,`total_storage_bytes` decimal(42,0)
,`total_storage_mb` decimal(45,2)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `fk_logs_project` (`id_project`),
  ADD KEY `idx_logs_user_created` (`id_user`,`created_at`),
  ADD KEY `idx_logs_action` (`action`);

--
-- Indexes for table `allowed_file_types`
--
ALTER TABLE `allowed_file_types`
  ADD PRIMARY KEY (`id_type`),
  ADD UNIQUE KEY `extension` (`extension`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id_project`),
  ADD UNIQUE KEY `uq_user_project_slug` (`id_user`,`slug`),
  ADD KEY `idx_projects_status` (`status`),
  ADD KEY `idx_projects_user_updated` (`id_user`,`updated_at`);

--
-- Indexes for table `project_files`
--
ALTER TABLE `project_files`
  ADD PRIMARY KEY (`id_file`),
  ADD UNIQUE KEY `uq_project_relative_path` (`id_project`,`relative_path`),
  ADD KEY `fk_files_parent` (`parent_id`),
  ADD KEY `idx_files_project_folder` (`id_project`,`is_folder`),
  ADD KEY `idx_files_extension` (`file_extension`);

--
-- Indexes for table `publish_jobs`
--
ALTER TABLE `publish_jobs`
  ADD PRIMARY KEY (`id_publish_job`),
  ADD KEY `fk_publish_project` (`id_project`),
  ADD KEY `fk_publish_user` (`id_user`),
  ADD KEY `idx_publish_status_created` (`status`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id_log` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `allowed_file_types`
--
ALTER TABLE `allowed_file_types`
  MODIFY `id_type` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id_project` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_files`
--
ALTER TABLE `project_files`
  MODIFY `id_file` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `publish_jobs`
--
ALTER TABLE `publish_jobs`
  MODIFY `id_publish_job` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------

--
-- Structure for view `v_project_dashboard`
--
DROP TABLE IF EXISTS `v_project_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_project_dashboard`  AS SELECT `p`.`id_project` AS `id_project`, `p`.`id_user` AS `id_user`, `u`.`username` AS `username`, `p`.`project_name` AS `project_name`, `p`.`slug` AS `slug`, `p`.`status` AS `status`, `fn_project_status_label`(`p`.`status`) AS `status_label`, `p`.`workspace_path` AS `workspace_path`, `p`.`published_path` AS `published_path`, `p`.`public_url` AS `public_url`, `p`.`last_published_at` AS `last_published_at`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, count(`f`.`id_file`) AS `total_items`, sum((case when (`f`.`is_folder` = 0) then 1 else 0 end)) AS `total_files`, sum((case when (`f`.`is_folder` = 1) then 1 else 0 end)) AS `total_folders`, coalesce(sum(`f`.`file_size`),0) AS `total_size_bytes` FROM ((`projects` `p` join `users` `u` on((`u`.`id_user` = `p`.`id_user`))) left join `project_files` `f` on((`f`.`id_project` = `p`.`id_project`))) GROUP BY `p`.`id_project`, `p`.`id_user`, `u`.`username`, `p`.`project_name`, `p`.`slug`, `p`.`status`, `p`.`workspace_path`, `p`.`published_path`, `p`.`public_url`, `p`.`last_published_at`, `p`.`created_at`, `p`.`updated_at` ;

-- --------------------------------------------------------

--
-- Structure for view `v_publish_history`
--
DROP TABLE IF EXISTS `v_publish_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_publish_history`  AS SELECT `pj`.`id_publish_job` AS `id_publish_job`, `pj`.`id_project` AS `id_project`, `pj`.`id_user` AS `id_user`, `u`.`username` AS `username`, `p`.`project_name` AS `project_name`, `p`.`slug` AS `slug`, `pj`.`status` AS `status`, `pj`.`message` AS `message`, `pj`.`started_at` AS `started_at`, `pj`.`finished_at` AS `finished_at`, timestampdiff(SECOND,`pj`.`started_at`,`pj`.`finished_at`) AS `duration_seconds`, `pj`.`created_at` AS `created_at` FROM ((`publish_jobs` `pj` join `users` `u` on((`u`.`id_user` = `pj`.`id_user`))) join `projects` `p` on((`p`.`id_project` = `pj`.`id_project`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_user_storage_usage`
--
DROP TABLE IF EXISTS `v_user_storage_usage`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_storage_usage`  AS SELECT `u`.`id_user` AS `id_user`, `u`.`username` AS `username`, count(distinct `p`.`id_project`) AS `total_projects`, count(`f`.`id_file`) AS `total_items`, coalesce(sum(`f`.`file_size`),0) AS `total_storage_bytes`, round(((coalesce(sum(`f`.`file_size`),0) / 1024) / 1024),2) AS `total_storage_mb` FROM ((`users` `u` left join `projects` `p` on((`p`.`id_user` = `u`.`id_user`))) left join `project_files` `f` on((`f`.`id_project` = `p`.`id_project`))) GROUP BY `u`.`id_user`, `u`.`username` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_logs_project` FOREIGN KEY (`id_project`) REFERENCES `projects` (`id_project`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `project_files`
--
ALTER TABLE `project_files`
  ADD CONSTRAINT `fk_files_parent` FOREIGN KEY (`parent_id`) REFERENCES `project_files` (`id_file`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_files_project` FOREIGN KEY (`id_project`) REFERENCES `projects` (`id_project`) ON DELETE CASCADE;

--
-- Constraints for table `publish_jobs`
--
ALTER TABLE `publish_jobs`
  ADD CONSTRAINT `fk_publish_project` FOREIGN KEY (`id_project`) REFERENCES `projects` (`id_project`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_publish_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_fail_stuck_publish_jobs` ON SCHEDULE EVERY 5 MINUTE STARTS '2026-05-26 05:31:22' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE publish_jobs
    SET
        status = 'failed',
        message = 'Auto failed by SQL worker: publish job timeout',
        finished_at = NOW()
    WHERE status IN ('queued','running')
      AND created_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
