<?php
/**
 * Class that handles all actions for Support Tickets
 */

namespace ProjectSend\Classes;

use \PDO;

class Tickets
{
    private $dbh;
    private $logger;

    public $id;
    public $user_id;
    public $subject;
    public $category;
    public $priority;
    public $status;
    public $created_at;

    public function __construct($ticket_id = null)
    {
        global $dbh;
        $this->dbh = $dbh;
        $this->logger = new \ProjectSend\Classes\ActionsLog;

        if (!empty($ticket_id)) {
            $this->get($ticket_id);
        }
    }

    public function set($arguments = [])
    {
        $this->user_id = (!empty($arguments['user_id'])) ? (int)$arguments['user_id'] : null;
        $this->subject = (!empty($arguments['subject'])) ? encode_html($arguments['subject']) : null;
        $this->category = (!empty($arguments['category'])) ? encode_html($arguments['category']) : null;
        $this->priority = (!empty($arguments['priority'])) ? $arguments['priority'] : 'Medium';
        $this->status = (!empty($arguments['status'])) ? $arguments['status'] : 'Open';
    }

    public function get($id)
    {
        $this->id = $id;

        $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_TICKETS . " WHERE id=:id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($statement->rowCount() == 0) {
            return false;
        }

        while ($row = $statement->fetch()) {
            $this->user_id = $row['user_id'];
            $this->subject = html_output($row['subject']);
            $this->category = html_output($row['category']);
            $this->priority = $row['priority'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
        }

        return true;
    }

    public function createTicket($message, $attachment_file_id = null)
    {
        $query = "INSERT INTO " . TABLE_TICKETS . " 
                  (user_id, subject, category, priority, status) 
                  VALUES (:user_id, :subject, :category, :priority, :status)";
        
        $statement = $this->dbh->prepare($query);
        $statement->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $statement->bindParam(':subject', $this->subject);
        $statement->bindParam(':category', $this->category);
        $statement->bindParam(':priority', $this->priority);
        $statement->bindParam(':status', $this->status);
        
        if ($statement->execute()) {
            $this->id = $this->dbh->lastInsertId();
            
            $this->logger->addEntry([
                'action' => 54,
                'owner_id' => $this->user_id,
                'owner_user' => '',
                'affected_account' => null,
                'affected_account_name' => ''
            ]);
            
            // Add initial message
            $this->addReply($this->user_id, $message, $attachment_file_id);
            
            return [
                'status' => 'success',
                'message' => __('Support ticket created.', 'cftp_admin'),
                'new_id' => $this->id
            ];
        }

        return [
            'status' => 'error',
            'message' => __('Error creating ticket.', 'cftp_admin')
        ];
    }

    public function addReply($user_id, $message, $attachment_file_id = null)
    {
        $query = "INSERT INTO " . TABLE_TICKET_REPLIES . " 
                  (ticket_id, user_id, message, attachment_file_id) 
                  VALUES (:ticket_id, :user_id, :message, :attachment_file_id)";
        
        $statement = $this->dbh->prepare($query);
        $statement->bindParam(':ticket_id', $this->id, PDO::PARAM_INT);
        $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $statement->bindParam(':message', $message);
        $statement->bindParam(':attachment_file_id', $attachment_file_id);
        
        $result = $statement->execute();
        if ($result) {
            $this->logger->addEntry([
                'action' => 55,
                'owner_id' => $user_id,
                'owner_user' => '',
                'affected_account' => null,
                'affected_account_name' => ''
            ]);
        }
        return $result;
    }
    
    public function updateStatus($new_status)
    {
        $statement = $this->dbh->prepare("UPDATE " . TABLE_TICKETS . " SET status = :status WHERE id = :id");
        $statement->bindParam(':status', $new_status);
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        if ($statement->execute()) {
            $this->status = $new_status;
            $this->logger->addEntry([
                'action' => 56,
                'owner_id' => CURRENT_USER_ID,
                'owner_user' => '',
                'affected_account' => $this->user_id,
                'affected_account_name' => ''
            ]);
            return true;
        }

        return false;
    }
}
