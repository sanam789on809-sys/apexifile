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
$stmt_check = $dbh->prepare("SELECT COUNT(*) FROM " . TABLE_DEPARTMENT_MEMBERS . " WHERE user_id = :uid AND is_head = 1");
$stmt_check->bindParam(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
$stmt_check->execute();
if ($stmt_check->fetchColumn() > 0) {
    $is_dept_head = true;
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
            } else {
                $flash->error($result['message']);
            }
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
                                    <a href="#" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
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
