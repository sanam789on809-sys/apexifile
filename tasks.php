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
    $stmt_check->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
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
        $dept_stmt->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
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
        $stmt_update->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
        
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

<style>
.kanban-board {
    display: flex;
    overflow-x: auto;
    gap: 1.5rem;
    padding-bottom: 1rem;
    min-height: 60vh;
}
.kanban-column {
    flex: 1;
    min-width: 300px;
    background: rgba(var(--bs-body-bg-rgb), 0.5);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--bs-border-color);
}
.kanban-column-header {
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--bs-primary);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.kanban-column-header.col-pending { border-color: #6c757d; }
.kanban-column-header.col-in-progress { border-color: #0d6efd; }
.kanban-column-header.col-waiting-review { border-color: #fd7e14; }
.kanban-column-header.col-completed { border-color: #198754; }

.kanban-tasks {
    flex: 1;
    min-height: 150px;
}
.kanban-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    cursor: grab;
    transition: transform 0.2s, box-shadow 0.2s;
}
.kanban-card:active {
    cursor: grabbing;
}
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.kanban-card-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.kanban-card-meta {
    font-size: 0.85rem;
    color: var(--bs-secondary);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
}
</style>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><?php echo $page_title; ?></h2>
        <?php if ($is_dept_head || current_role_in(['System Administrator'])) : ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
            <i class="fa fa-plus"></i> <?php _e('Assign Task', 'cftp_admin'); ?>
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="kanban-board">
    <?php
    $columns = [
        'Pending' => ['title' => __('Pending', 'cftp_admin'), 'class' => 'col-pending'],
        'In Progress' => ['title' => __('In Progress', 'cftp_admin'), 'class' => 'col-in-progress'],
        'Waiting Review' => ['title' => __('Waiting Review', 'cftp_admin'), 'class' => 'col-waiting-review'],
        'Completed' => ['title' => __('Completed', 'cftp_admin'), 'class' => 'col-completed']
    ];

    // Fetch all tasks and group them
    $tasks_grouped = ['Pending' => [], 'In Progress' => [], 'Waiting Review' => [], 'Completed' => []];
    $stmt = $dbh->prepare("SELECT t.*, u.name as assignee_name FROM " . TABLE_TASKS . " t LEFT JOIN " . TABLE_USERS . " u ON t.assignee_id = u.id ORDER BY t.created_at DESC");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['status'] ?: 'Pending';
        if (isset($tasks_grouped[$status])) {
            $tasks_grouped[$status][] = $row;
        }
    }

    foreach ($columns as $status => $col) {
        echo '<div class="kanban-column">';
        echo '  <div class="kanban-column-header ' . $col['class'] . '">';
        echo '    <span>' . $col['title'] . '</span>';
        echo '    <span class="badge bg-secondary rounded-pill">' . count($tasks_grouped[$status]) . '</span>';
        echo '  </div>';
        echo '  <div class="kanban-tasks" data-status="' . $status . '">';
        
        foreach ($tasks_grouped[$status] as $task) {
            $due_date_class = '';
            if ($task['due_date']) {
                $due_timestamp = strtotime($task['due_date']);
                if ($status !== 'Completed' && $due_timestamp < time()) {
                    $due_date_class = 'text-danger fw-bold'; // Overdue
                }
            }
            
            echo '<div class="kanban-card" data-task-id="' . $task['id'] . '">';
            echo '  <div class="kanban-card-title">' . html_output($task['title']) . '</div>';
            if (!empty($task['description'])) {
                echo '  <div class="mb-2 text-muted small text-truncate">' . html_output($task['description']) . '</div>';
            }
            echo '  <div class="kanban-card-meta">';
            echo '    <span><i class="fa fa-user-circle-o"></i> ' . html_output($task['assignee_name']) . '</span>';
            if ($task['due_date']) {
                echo '    <span class="' . $due_date_class . '"><i class="fa fa-clock-o"></i> ' . date('M j', strtotime($task['due_date'])) . '</span>';
            }
            echo '  </div>';
            echo '</div>';
        }
        
        echo '  </div>'; // .kanban-tasks
        echo '</div>'; // .kanban-column
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var columns = document.querySelectorAll('.kanban-tasks');
    columns.forEach(function(column) {
        new Sortable(column, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                var itemEl = evt.item;
                var newStatus = evt.to.getAttribute('data-status');
                var taskId = itemEl.getAttribute('data-task-id');
                
                // If moved to a different column, update via AJAX
                if (evt.from !== evt.to) {
                    var formData = new FormData();
                    formData.append('update_task_status', '1');
                    formData.append('task_id', taskId);
                    formData.append('status', newStatus);
                    formData.append('csrf_token', '<?php echo getCsrfToken(); ?>');
                    
                    fetch('tasks-ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status !== 'success') {
                            alert('Error updating task: ' + data.message);
                            // Revert move on UI
                            evt.from.appendChild(itemEl);
                        } else {
                            // Update counts
                            var fromBadge = evt.from.previousElementSibling.querySelector('.badge');
                            var toBadge = evt.to.previousElementSibling.querySelector('.badge');
                            fromBadge.textContent = parseInt(fromBadge.textContent) - 1;
                            toBadge.textContent = parseInt(toBadge.textContent) + 1;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating task status. Please try again.');
                        evt.from.appendChild(itemEl);
                    });
                }
            },
        });
    });
});
</script>

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
