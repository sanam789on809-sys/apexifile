<?php
function upgrade_2026071102()
{
    global $dbh;
    global $updates_error_messages;

    // Add department_id to TABLE_FILES_RELATIONS
    $query = "SHOW COLUMNS FROM `" . TABLE_FILES_RELATIONS . "` LIKE 'department_id'";
    $statement = $dbh->prepare($query);
    $statement->execute();
    
    if ($statement->rowCount() == 0) {
        $alter = "ALTER TABLE `" . TABLE_FILES_RELATIONS . "` 
                  ADD COLUMN `department_id` int(11) DEFAULT NULL AFTER `group_id`,
                  ADD CONSTRAINT `fk_file_rel_department` FOREIGN KEY (`department_id`) REFERENCES `" . TABLE_DEPARTMENTS . "`(`id`) ON DELETE CASCADE ON UPDATE CASCADE";
        $statement = $dbh->prepare($alter);
        try {
            $statement->execute();
        } catch (\PDOException $e) {
            $updates_error_messages[] = __('Could not alter TABLE_FILES_RELATIONS to add department_id.', 'cftp_admin');
        }
    }
}
