<?php
/**
 * Edit an existing department.
 */
$allowed_levels = array(9);
require_once 'bootstrap.php';

$page_title = __('Edit Department', 'cftp_admin');
$page_id = 'departments_edit';

if (empty($_GET['id'])) {
    ps_redirect('departments.php');
}

$department = new \ProjectSend\Classes\Departments();
if (!$department->get($_GET['id'])) {
    ps_redirect('departments.php');
}

if ($_POST) {
    if (validateCsrfToken()) {
        $department->set([
            'name' => $_POST['name'],
            'description' => $_POST['description']
        ]);
        
        $result = $department->recordEdit();
        if ($result['status'] === 'success') {
            
            // Handle members
            $stmt = $dbh->prepare("DELETE FROM " . TABLE_DEPARTMENT_MEMBERS . " WHERE department_id = :id");
            $stmt->bindValue(':id', $department->id);
            $stmt->execute();
            
            if (!empty($_POST['members'])) {
                foreach ($_POST['members'] as $user_id) {
                    $is_head = (!empty($_POST['head_members']) && in_array($user_id, $_POST['head_members'])) ? 1 : 0;
                    $department->addMember($user_id, $is_head);
                }
            }
            
            $flash->success($result['message']);
            ps_redirect('departments.php');
        } else {
            $flash->error($result['message']);
        }
    }
}

$current_members = [];
$head_members = [];
foreach ($department->getMembers() as $member) {
    $current_members[] = $member['user_id'];
    if ($member['is_head'] == 1) {
        $head_members[] = $member['user_id'];
    }
}

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="ps-card">
            <div class="ps-card-header">
                <h2 class="ps-card-title"><?php echo $page_title; ?></h2>
            </div>
            <div class="ps-card-body">
                <form action="departments-edit.php?id=<?php echo $department->id; ?>" method="post">
                    <?php addCsrf(); ?>
                    <div class="mb-3">
                        <label for="name" class="form-label"><?php _e('Department Name', 'cftp_admin'); ?></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo html_output($department->name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label"><?php _e('Description', 'cftp_admin'); ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo html_output($department->description); ?></textarea>
                    </div>
                    
                    <h3 class="mt-4 mb-3"><?php _e('Department Members', 'cftp_admin'); ?></h3>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php _e('Select Members', 'cftp_admin'); ?></label>
                        <div class="list-group">
                            <?php
                            $users_stmt = $dbh->prepare("SELECT id, name FROM " . TABLE_USERS . " ORDER BY name ASC");
                            $users_stmt->execute();
                            while ($u = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
                                $checked = in_array($u['id'], $current_members) ? 'checked' : '';
                                $head_checked = in_array($u['id'], $head_members) ? 'checked' : '';
                                ?>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="members[]" value="<?php echo $u['id']; ?>" id="user_<?php echo $u['id']; ?>" <?php echo $checked; ?>>
                                                <label class="form-check-label" for="user_<?php echo $u['id']; ?>">
                                                    <?php echo html_output($u['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="head_members[]" value="<?php echo $u['id']; ?>" id="head_<?php echo $u['id']; ?>" <?php echo $head_checked; ?>>
                                                <label class="form-check-label" for="head_<?php echo $u['id']; ?>"><small><?php _e('Dept Head', 'cftp_admin'); ?></small></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="departments.php" class="btn btn-secondary"><?php _e('Cancel', 'cftp_admin'); ?></a>
                        <button type="submit" class="btn btn-primary"><?php _e('Save Changes', 'cftp_admin'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
