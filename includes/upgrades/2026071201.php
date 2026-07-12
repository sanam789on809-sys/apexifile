<?php
function upgrade_2026071201()
{
    global $dbh;
    global $updates_error_messages;

    if (table_exists(TABLE_FILES)) {
        // Add workflow_status column
        $statement = $dbh->prepare("
            ALTER TABLE `" . TABLE_FILES . "` 
            ADD COLUMN `workflow_status` VARCHAR(50) NOT NULL DEFAULT 'Pending'
        ");
        try {
            $statement->execute();
        } catch (\PDOException $e) {
            // Error is caught, but it might just be that the column already exists
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                $updates_error_messages[] = $e->getMessage();
            }
        }
    }
}
