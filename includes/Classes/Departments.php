<?php
/**
 * Class that handles all actions for Departments
 */

namespace ProjectSend\Classes;

use \PDO;

class Departments
{
    private $dbh;
    private $logger;

    public $id;
    public $name;
    public $description;
    public $created_at;

    public function __construct($department_id = null)
    {
        global $dbh;
        $this->dbh = $dbh;
        $this->logger = new \ProjectSend\Classes\ActionsLog;

        if (!empty($department_id)) {
            $this->get($department_id);
        }
    }

    public function set($arguments = [])
    {
        $this->name = (!empty($arguments['name'])) ? encode_html($arguments['name']) : null;
        $this->description = (!empty($arguments['description'])) ? encode_html($arguments['description']) : null;
    }

    public function get($id)
    {
        $this->id = $id;

        $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_DEPARTMENTS . " WHERE id=:id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($statement->rowCount() == 0) {
            return false;
        }

        while ($row = $statement->fetch()) {
            $this->name = html_output($row['name']);
            $this->description = htmlentities_allowed($row['description']);
            $this->created_at = $row['created_at'];
        }

        return true;
    }

    public function recordCreate()
    {
        $statement = $this->dbh->prepare("INSERT INTO " . TABLE_DEPARTMENTS . " (name, description) VALUES (:name, :description)");
        $statement->bindParam(':name', $this->name);
        $statement->bindParam(':description', $this->description);
        
        if ($statement->execute()) {
            $this->id = $this->dbh->lastInsertId();
            $this->logger->addEntry([
                'action' => 50, // Assuming 50 is a custom action log ID for 'Created Department'
                'owner_id' => CURRENT_USER_ID,
                'owner_user' => CURRENT_USER_USERNAME,
                'affected_account_name' => $this->name
            ]);
            return [
                'status' => 'success',
                'message' => __('Department created correctly.', 'cftp_admin'),
                'new_id' => $this->id
            ];
        }

        return [
            'status' => 'error',
            'message' => __('Error creating department.', 'cftp_admin')
        ];
    }

    public function recordEdit()
    {
        $statement = $this->dbh->prepare("UPDATE " . TABLE_DEPARTMENTS . " SET name = :name, description = :description WHERE id = :id");
        $statement->bindParam(':name', $this->name);
        $statement->bindParam(':description', $this->description);
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        if ($statement->execute()) {
            $this->logger->addEntry([
                'action' => 51, // Assuming 51 for 'Edited Department'
                'owner_id' => CURRENT_USER_ID,
                'owner_user' => CURRENT_USER_USERNAME,
                'affected_account_name' => $this->name
            ]);
            return [
                'status' => 'success',
                'message' => __('Department updated correctly.', 'cftp_admin')
            ];
        }

        return [
            'status' => 'error',
            'message' => __('Error updating department.', 'cftp_admin')
        ];
    }

    public function delete()
    {
        $statement = $this->dbh->prepare("DELETE FROM " . TABLE_DEPARTMENTS . " WHERE id = :id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        if ($statement->execute()) {
            return true;
        }

        return false;
    }

    public function addMember($user_id, $is_head = 0)
    {
        $statement = $this->dbh->prepare("INSERT INTO " . TABLE_DEPARTMENT_MEMBERS . " (department_id, user_id, is_head) VALUES (:department_id, :user_id, :is_head)");
        $statement->bindParam(':department_id', $this->id, PDO::PARAM_INT);
        $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $statement->bindParam(':is_head', $is_head, PDO::PARAM_INT);
        return $statement->execute();
    }

    public function getMembers()
    {
        $statement = $this->dbh->prepare("SELECT user_id, is_head FROM " . TABLE_DEPARTMENT_MEMBERS . " WHERE department_id = :id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function isDepartmentHead($department_id, $user_id)
    {
        global $dbh;
        $statement = $dbh->prepare("SELECT is_head FROM " . TABLE_DEPARTMENT_MEMBERS . " WHERE department_id = :department_id AND user_id = :user_id");
        $statement->bindParam(':department_id', $department_id, PDO::PARAM_INT);
        $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return ($result && $result['is_head'] == 1);
    }
}
