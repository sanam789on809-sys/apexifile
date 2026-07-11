<?php
/**
 * Class that handles all actions for Tasks
 */

namespace ProjectSend\Classes;

use \PDO;

class Tasks
{
    private $dbh;
    private $logger;

    public $id;
    public $department_id;
    public $assigner_id;
    public $assignee_id;
    public $file_id;
    public $title;
    public $description;
    public $status;
    public $due_date;

    public function __construct($task_id = null)
    {
        global $dbh;
        $this->dbh = $dbh;
        $this->logger = new \ProjectSend\Classes\ActionsLog;

        if (!empty($task_id)) {
            $this->get($task_id);
        }
    }

    public function set($arguments = [])
    {
        $this->department_id = (!empty($arguments['department_id'])) ? (int)$arguments['department_id'] : null;
        $this->assigner_id = (!empty($arguments['assigner_id'])) ? (int)$arguments['assigner_id'] : null;
        $this->assignee_id = (!empty($arguments['assignee_id'])) ? (int)$arguments['assignee_id'] : null;
        $this->file_id = (!empty($arguments['file_id'])) ? (int)$arguments['file_id'] : null;
        $this->title = (!empty($arguments['title'])) ? encode_html($arguments['title']) : null;
        $this->description = (!empty($arguments['description'])) ? encode_html($arguments['description']) : null;
        $this->status = (!empty($arguments['status'])) ? $arguments['status'] : 'Pending';
        $this->due_date = (!empty($arguments['due_date'])) ? $arguments['due_date'] : null;
    }

    public function get($id)
    {
        $this->id = $id;

        $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_TASKS . " WHERE id=:id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($statement->rowCount() == 0) {
            return false;
        }

        while ($row = $statement->fetch()) {
            $this->department_id = $row['department_id'];
            $this->assigner_id = $row['assigner_id'];
            $this->assignee_id = $row['assignee_id'];
            $this->file_id = $row['file_id'];
            $this->title = html_output($row['title']);
            $this->description = htmlentities_allowed($row['description']);
            $this->status = $row['status'];
            $this->due_date = $row['due_date'];
        }

        return true;
    }

    public function recordCreate()
    {
        $query = "INSERT INTO " . TABLE_TASKS . " 
                  (department_id, assigner_id, assignee_id, file_id, title, description, status, due_date) 
                  VALUES (:department_id, :assigner_id, :assignee_id, :file_id, :title, :description, :status, :due_date)";
        
        $statement = $this->dbh->prepare($query);
        $statement->bindParam(':department_id', $this->department_id, PDO::PARAM_INT);
        $statement->bindParam(':assigner_id', $this->assigner_id, PDO::PARAM_INT);
        $statement->bindParam(':assignee_id', $this->assignee_id, PDO::PARAM_INT);
        $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
        $statement->bindParam(':title', $this->title);
        $statement->bindParam(':description', $this->description);
        $statement->bindParam(':status', $this->status);
        $statement->bindParam(':due_date', $this->due_date);
        
        if ($statement->execute()) {
            $this->id = $this->dbh->lastInsertId();
            
            $this->logger->addEntry([
                'action' => 50,
                'owner_id' => $this->assigner_id,
                'owner_user' => '', // Will be resolved by ActionsLog
                'affected_account' => $this->assignee_id,
                'affected_account_name' => ''
            ]);

            return [
                'status' => 'success',
                'message' => __('Task assigned correctly.', 'cftp_admin'),
                'new_id' => $this->id
            ];
        }

        return [
            'status' => 'error',
            'message' => __('Error assigning task.', 'cftp_admin')
        ];
    }

    public function updateStatus($new_status)
    {
        $statement = $this->dbh->prepare("UPDATE " . TABLE_TASKS . " SET status = :status WHERE id = :id");
        $statement->bindParam(':status', $new_status);
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        if ($statement->execute()) {
            $this->status = $new_status;
            
            $this->logger->addEntry([
                'action' => 51,
                'owner_id' => CURRENT_USER_ID,
                'owner_user' => '',
                'affected_account' => $this->assignee_id,
                'affected_account_name' => ''
            ]);
            
            return true;
        }

        return false;
    }
}
