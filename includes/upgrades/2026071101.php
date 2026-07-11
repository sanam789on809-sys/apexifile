<?php
function upgrade_2026071101()
{
    global $dbh;
    global $updates_error_messages;

    // Create Departments Table
    if (!table_exists(TABLE_DEPARTMENTS)) {
        $query = "
        CREATE TABLE IF NOT EXISTS `" . TABLE_DEPARTMENTS . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $statement = $dbh->prepare($query);
        try { $statement->execute(); } catch (\PDOException $e) { $updates_error_messages[] = $e->getMessage(); }
    }

    // Create Department Members Table
    if (!table_exists(TABLE_DEPARTMENT_MEMBERS)) {
        $query = "
        CREATE TABLE IF NOT EXISTS `" . TABLE_DEPARTMENT_MEMBERS . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `department_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `is_head` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`department_id`) REFERENCES " . TABLE_DEPARTMENTS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES " . TABLE_USERS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $statement = $dbh->prepare($query);
        try { $statement->execute(); } catch (\PDOException $e) { $updates_error_messages[] = $e->getMessage(); }
    }

    // Create Tasks Table
    if (!table_exists(TABLE_TASKS)) {
        $query = "
        CREATE TABLE IF NOT EXISTS `" . TABLE_TASKS . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `department_id` int(11) NOT NULL,
            `assigner_id` int(11) NOT NULL,
            `assignee_id` int(11) NOT NULL,
            `file_id` int(11) DEFAULT NULL,
            `title` varchar(255) NOT NULL,
            `description` text,
            `status` ENUM('Pending', 'In Progress', 'Waiting Review', 'Completed') DEFAULT 'Pending',
            `due_date` DATETIME DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`department_id`) REFERENCES " . TABLE_DEPARTMENTS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`assigner_id`) REFERENCES " . TABLE_USERS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`assignee_id`) REFERENCES " . TABLE_USERS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`file_id`) REFERENCES " . TABLE_FILES . "(`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $statement = $dbh->prepare($query);
        try { $statement->execute(); } catch (\PDOException $e) { $updates_error_messages[] = $e->getMessage(); }
    }

    // Create Tickets Table
    if (!table_exists(TABLE_TICKETS)) {
        $query = "
        CREATE TABLE IF NOT EXISTS `" . TABLE_TICKETS . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `subject` varchar(255) NOT NULL,
            `category` varchar(255) DEFAULT NULL,
            `priority` ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
            `status` ENUM('Open', 'In Progress', 'Waiting Client', 'Resolved', 'Closed') DEFAULT 'Open',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES " . TABLE_USERS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $statement = $dbh->prepare($query);
        try { $statement->execute(); } catch (\PDOException $e) { $updates_error_messages[] = $e->getMessage(); }
    }

    // Create Ticket Replies Table
    if (!table_exists(TABLE_TICKET_REPLIES)) {
        $query = "
        CREATE TABLE IF NOT EXISTS `" . TABLE_TICKET_REPLIES . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ticket_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `message` text NOT NULL,
            `attachment_file_id` int(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`ticket_id`) REFERENCES " . TABLE_TICKETS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES " . TABLE_USERS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`attachment_file_id`) REFERENCES " . TABLE_FILES . "(`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $statement = $dbh->prepare($query);
        try { $statement->execute(); } catch (\PDOException $e) { $updates_error_messages[] = $e->getMessage(); }
    }

    // Create Approvals Table
    if (!table_exists(TABLE_APPROVALS)) {
        $query = "
        CREATE TABLE IF NOT EXISTS `" . TABLE_APPROVALS . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `requester_id` int(11) NOT NULL,
            `file_id` int(11) NOT NULL,
            `target_department_id` int(11) NOT NULL,
            `reason` text,
            `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            `reviewer_id` int(11) DEFAULT NULL,
            `reviewer_comments` text,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`requester_id`) REFERENCES " . TABLE_USERS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`file_id`) REFERENCES " . TABLE_FILES . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`target_department_id`) REFERENCES " . TABLE_DEPARTMENTS . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`reviewer_id`) REFERENCES " . TABLE_USERS . "(`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $statement = $dbh->prepare($query);
        try { $statement->execute(); } catch (\PDOException $e) { $updates_error_messages[] = $e->getMessage(); }
    }

    // Create File Versions Table
    if (!table_exists(TABLE_FILE_VERSIONS)) {
        $query = "
        CREATE TABLE IF NOT EXISTS `" . TABLE_FILE_VERSIONS . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `file_id` int(11) NOT NULL,
            `version_number` int(11) NOT NULL,
            `url` varchar(2048) NOT NULL,
            `original_url` varchar(2048) NOT NULL,
            `created_by` varchar(255) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`file_id`) REFERENCES " . TABLE_FILES . "(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $statement = $dbh->prepare($query);
        try { $statement->execute(); } catch (\PDOException $e) { $updates_error_messages[] = $e->getMessage(); }
    }
}

