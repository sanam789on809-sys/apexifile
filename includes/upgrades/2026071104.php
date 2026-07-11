<?php
$this->current_version = '2026071104';

// Create Chat Messages Table
if (!table_exists(TABLE_PREFIX . 'chat_messages')) {
    $statement = $this->dbh->prepare("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "chat_messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `sender_id` int(11) NOT NULL,
            `message` text NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`sender_id`) REFERENCES `" . TABLE_USERS . "`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
    ");
    try {
        $statement->execute();
    } catch (\PDOException $e) {
        $updates_error_messages[] = $e->getMessage();
    }
}
