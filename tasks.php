<?php
/**
 * Tasks Management
 */

$allowed_levels = array(9, 8, 7); // Admin, Department Head, Employee
require_once 'bootstrap.php';

$page_title = __('Task Management', 'cftp_admin');
$page_id = 'tasks';

// Check if user is a department head for ANY department
$is_dept_head = false;
try {
    $stmt_check = $dbh->prepare("SELECT COUNT(*) FROM " . TABLE_DEPARTMENT_MEMBERS . " WHERE user_id = :uid AND is_head = 1");
    $stmt_check->bindParam(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
    $stmt_check->execute();
    if ($stmt_check->fetchColumn() > 0) {
        $is_dept_head = true;
    }
} catch (PDOException $e) {
    // Table might not exist yet, will be created when header.php is included
}

if ($_POST && isset($_POST['create_task'])) {
    if (!$is_dept_head && current_role_in(['System Administrator']) == false) {
        $flash->error(__('Only Department Heads can assign tasks.', 'cftp_admin'));
    } else {
        // Assume department_id is known or selected, for now just use the first department they head
        $dept_stmt = $dbh->prepare("SELECT department_id FROM " . TABLE_DEPARTMENT_MEMBERS . " WHERE user_id = :uid AND is_head = 1 LIMIT 1");
        $dept_stmt->bindParam(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
        $dept_stmt->execute();
        $dept_id = $dept_stmt->fetchColumn();

        if ($dept_id) {
            $task = new \ProjectSend\Classes\Tasks();
            $task->set([
                'department_id' => $dept_id,
                'assigner_id' => CURRENT_USER_ID,
                'assignee_id' => $_POST['assignee'],
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'due_date' => $_POST['due_date']
            ]);
            $result = $task->create();
            if ($result['status'] === 'success') {
                $flash->success($result['message']);
                
                // Notify assignee
                $notifier = new \ProjectSend\Classes\InternalNotifications();
                $notifier->addNotification($_POST['assignee'], __('You have been assigned a new task: ', 'cftp_admin') . $_POST['title'], 'tasks.php');
            } else {
                $flash->error($result['message']);
            }
        }
    }
    ps_redirect('tasks.php');
}

if ($_POST && isset($_POST['update_task'])) {
    if (validateCsrfToken()) {
        $task_id = (int)$_POST['task_id'];
        $new_status = $_POST['status'];
        
        $stmt_update = $dbh->prepare("UPDATE " . TABLE_TASKS . " SET status = :status WHERE id = :id AND assignee_id = :uid");
        $stmt_update->bindParam(':status', $new_status);
        $stmt_update->bindParam(':id', $task_id, PDO::PARAM_INT);
        $stmt_update->bindParam(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
        
        if ($stmt_update->execute()) {
            $flash->success(__('Task status updated successfully.', 'cftp_admin'));
            
            // Notify assigner
            $stmt_get = $dbh->prepare("SELECT assigner_id, title FROM " . TABLE_TASKS . " WHERE id = :id");
            $stmt_get->bindParam(':id', $task_id, PDO::PARAM_INT);
            $stmt_get->execute();
            if ($t = $stmt_get->fetch(PDO::FETCH_ASSOC)) {
                $notifier = new \ProjectSend\Classes\InternalNotifications();
                $notifier->addNotification($t['assigner_id'], __('Task status updated to ', 'cftp_admin') . $new_status . ': ' . $t['title'], 'tasks.php');
            }
        } else {
            $flash->error(__('Error updating task status.', 'cftp_admin'));
        }
    }
    ps_redirect('tasks.php');
}

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="ps-card">
            <div class="ps-card-header">
                <h2 class="ps-card-title"><?php echo $page_title; ?></h2>
                <div class="ps-card-actions">
                    <?php if ($is_dept_head || current_role_in(['System Administrator'])) : ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                        <i class="fa fa-plus"></i> <?php _e('Assign Task', 'cftp_admin'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ps-card-body">
                <table id="tasks_table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'cftp_admin'); ?></th>
                            <th><?php _e('Assignee', 'cftp_admin'); ?></th>
                            <th><?php _e('Due Date', 'cftp_admin'); ?></th>
                            <th><?php _e('Status', 'cftp_admin'); ?></th>
                            <th><?php _e('Actions', 'cftp_admin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch tasks logic here
                        $stmt = $dbh->prepare("SELECT t.*, u.name as assignee_name FROM " . TABLE_TASKS . " t LEFT JOIN " . TABLE_USERS . " u ON t.assignee_id = u.id ORDER BY t.created_at DESC");
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . html_output($row['title']) . '</td>';
                            echo '<td>' . html_output($row['assignee_name']) . '</td>';
                            echo '<td>' . html_output($row['due_date']) . '</td>';
                            echo '<td><span class="badge bg-info">' . html_output($row['status']) . '</span></td>';
                            echo '<td>
                                    <form action="tasks.php" method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">
                                        <input type="hidden" name="update_task" value="1">
                                        <input type="hidden" name="task_id" value="' . $row['id'] . '">
                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="Pending" ' . ($row['status'] == 'Pending' ? 'selected' : '') . '>' . __('Pending', 'cftp_admin') . '</option>
                                            <option value="In Progress" ' . ($row['status'] == 'In Progress' ? 'selected' : '') . '>' . __('In Progress', 'cftp_admin') . '</option>
                                            <option value="Waiting Review" ' . ($row['status'] == 'Waiting Review' ? 'selected' : '') . '>' . __('Waiting Review', 'cftp_admin') . '</option>
                                        </select>
                                    </form>
                                  </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- New Task Modal (Placeholder) -->
<div class="modal fade" id="newTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php _e('Assign New Task', 'cftp_admin'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="tasks.php" method="post">
            <div class="mb-3">
                <label for="title" class="form-label"><?php _e('Task Title', 'cftp_admin'); ?></label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label"><?php _e('Description', 'cftp_admin'); ?></label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="assignee" class="form-label"><?php _e('Assignee', 'cftp_admin'); ?></label>
                <select class="form-select" id="assignee" name="assignee" required>
                    <option value=""><?php _e('Select User...', 'cftp_admin'); ?></option>
                    <?php
                    // Fetch users (staff) for assignment
                    $users_stmt = $dbh->prepare("SELECT id, name FROM " . TABLE_USERS . " WHERE role IN ('System Administrator', 'Account Manager')");
                    $users_stmt->execute();
                    while ($u = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $u['id'] . '">' . html_output($u['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="due_date" class="form-label"><?php _e('Due Date', 'cftp_admin'); ?></label>
                <input type="date" class="form-control" id="due_date" name="due_date">
            </div>
            <div class="modal-footer px-0 pb-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'cftp_admin'); ?></button>
                <button type="submit" class="btn btn-primary" name="create_task"><?php _e('Assign Task', 'cftp_admin'); ?></button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
