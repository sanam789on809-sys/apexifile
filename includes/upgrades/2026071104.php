<?php
function upgrade_2026071104()
{
    global $dbh;
    global $updates_error_messages;

    // Create Chat Messages Table
    if (!table_exists(TABLES_PREFIX . 'chat_messages')) {
        $statement = $dbh->prepare("
            CREATE TABLE IF NOT EXISTS `" . TABLES_PREFIX . "chat_messages` (
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
}
