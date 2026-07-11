<?php
require 'bootstrap.php';
global $dbh;
try {
    $tables = [
        "CREATE TABLE IF NOT EXISTS `" . TABLES_PREFIX . "tasks` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `department_id` int(11) DEFAULT NULL,
            `assigner_id` int(11) NOT NULL,
            `assignee_id` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `description` text,
            `status` varchar(50) DEFAULT 'Pending',
            `due_date` date DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;",
        
        "CREATE TABLE IF NOT EXISTS `" . TABLES_PREFIX . "chat_messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `sender_id` int(11) NOT NULL,
            `message` text NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;",

        "CREATE TABLE IF NOT EXISTS `" . TABLES_PREFIX . "departments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `created_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;",

        "CREATE TABLE IF NOT EXISTS `" . TABLES_PREFIX . "department_members` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `department_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `is_head` tinyint(1) DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;"
    ];

    foreach ($tables as $sql) {
        $dbh->exec($sql);
    }
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
