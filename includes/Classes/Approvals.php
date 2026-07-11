<?php
/**
 * Class that handles all actions for Approvals
 */

namespace ProjectSend\Classes;

use \PDO;

class Approvals
{
    private $dbh;
    private $logger;

    public $id;
    public $requester_id;
    public $file_id;
    public $target_department_id;
    public $reason;
    public $status;
    public $reviewer_id;
    public $reviewer_comments;

    public function __construct($approval_id = null)
    {
        global $dbh;
        $this->dbh = $dbh;
        $this->logger = new \ProjectSend\Classes\ActionsLog;

        if (!empty($approval_id)) {
            $this->get($approval_id);
        }
    }

    public function set($arguments = [])
    {
        $this->requester_id = (!empty($arguments['requester_id'])) ? (int)$arguments['requester_id'] : null;
        $this->file_id = (!empty($arguments['file_id'])) ? (int)$arguments['file_id'] : null;
        $this->target_department_id = (!empty($arguments['target_department_id'])) ? (int)$arguments['target_department_id'] : null;
        $this->reason = (!empty($arguments['reason'])) ? encode_html($arguments['reason']) : null;
        $this->status = (!empty($arguments['status'])) ? $arguments['status'] : 'Pending';
        $this->reviewer_id = (!empty($arguments['reviewer_id'])) ? (int)$arguments['reviewer_id'] : null;
        $this->reviewer_comments = (!empty($arguments['reviewer_comments'])) ? encode_html($arguments['reviewer_comments']) : null;
    }

    public function get($id)
    {
        $this->id = $id;

        $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_APPROVALS . " WHERE id=:id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($statement->rowCount() == 0) {
            return false;
        }

        while ($row = $statement->fetch()) {
            $this->requester_id = $row['requester_id'];
            $this->file_id = $row['file_id'];
            $this->target_department_id = $row['target_department_id'];
            $this->reason = html_output($row['reason']);
            $this->status = $row['status'];
            $this->reviewer_id = $row['reviewer_id'];
            $this->reviewer_comments = html_output($row['reviewer_comments']);
        }

        return true;
    }

    public function requestApproval()
    {
        $query = "INSERT INTO " . TABLE_APPROVALS . " 
                  (requester_id, file_id, target_department_id, reason, status) 
                  VALUES (:requester_id, :file_id, :target_department_id, :reason, :status)";
        
        $statement = $this->dbh->prepare($query);
        $statement->bindParam(':requester_id', $this->requester_id, PDO::PARAM_INT);
        $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
        $statement->bindParam(':target_department_id', $this->target_department_id, PDO::PARAM_INT);
        $statement->bindParam(':reason', $this->reason);
        $statement->bindParam(':status', $this->status);
        
        if ($statement->execute()) {
            $this->id = $this->dbh->lastInsertId();
            
            $this->logger->addEntry([
                'action' => 52,
                'owner_id' => $this->requester_id,
                'owner_user' => '',
                'affected_file' => $this->file_id,
                'affected_file_name' => ''
            ]);
            
            return [
                'status' => 'success',
                'message' => __('Approval requested.', 'cftp_admin'),
                'new_id' => $this->id
            ];
        }

        return [
            'status' => 'error',
            'message' => __('Error requesting approval.', 'cftp_admin')
        ];
    }

    public function process($new_status, $reviewer_id, $comments = '')
    {
        $statement = $this->dbh->prepare("UPDATE " . TABLE_APPROVALS . " SET status = :status, reviewer_id = :reviewer_id, reviewer_comments = :comments WHERE id = :id");
        $statement->bindParam(':status', $new_status);
        $statement->bindParam(':reviewer_id', $reviewer_id, PDO::PARAM_INT);
        $statement->bindParam(':comments', $comments);
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        if ($statement->execute()) {
            $this->status = $new_status;
            $this->reviewer_id = $reviewer_id;
            $this->reviewer_comments = $comments;
            
            $this->logger->addEntry([
                'action' => 53,
                'owner_id' => $reviewer_id,
                'owner_user' => '',
                'affected_file' => $this->file_id,
                'affected_file_name' => ''
            ]);
            
            // If approved, trigger logic to share the file with the target department
            if ($new_status == 'Approved') {
                $assign_stmt = $this->dbh->prepare("INSERT INTO " . TABLE_FILES_RELATIONS . " (file_id, department_id, hidden) VALUES (:file_id, :department_id, 0)");
                $assign_stmt->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
                $assign_stmt->bindParam(':department_id', $this->target_department_id, PDO::PARAM_INT);
                $assign_stmt->execute();
            }
            
            return true;
        }

        return false;
    }
}
