<?php
/**
 * Create internal notifications table
 */
$upgrades_update_version = '2026071103';

global $dbh;

try {
    if (!table_exists(TABLE_INTERNAL_NOTIFICATIONS)) {
        $statement = $dbh->query("
            CREATE TABLE IF NOT EXISTS `" . TABLE_INTERNAL_NOTIFICATIONS . "` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `message` text NOT NULL,
                `link_url` varchar(255) DEFAULT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_intnotif_user` FOREIGN KEY (`user_id`) REFERENCES `" . TABLE_USERS . "` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
} catch (\PDOException $e) {
    $updates_error_messages[] = __('Could not create internal notifications table.', 'cftp_admin');
}
