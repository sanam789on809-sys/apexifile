<?php
/**
 * Add a new department.
 */
$allowed_levels = array(9);
require_once 'bootstrap.php';

$page_title = __('Add Department', 'cftp_admin');
$page_id = 'departments_add';

if ($_POST) {
    if (check_csrf_token()) {
        $department = new \ProjectSend\Classes\Departments();
        $department->set([
            'name' => $_POST['name'],
            'description' => $_POST['description']
        ]);
        
        $result = $department->recordCreate();
        if ($result['status'] === 'success') {
            $flash->success($result['message']);
            ps_redirect('departments.php');
        } else {
            $flash->error($result['message']);
        }
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
                <form action="departments-add.php" method="post">
                    <?php echo generate_csrf_input(); ?>
                    <div class="mb-3">
                        <label for="name" class="form-label"><?php _e('Department Name', 'cftp_admin'); ?></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label"><?php _e('Description', 'cftp_admin'); ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="text-end">
                        <a href="departments.php" class="btn btn-secondary"><?php _e('Cancel', 'cftp_admin'); ?></a>
                        <button type="submit" class="btn btn-primary"><?php _e('Create Department', 'cftp_admin'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
