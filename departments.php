<?php
/**
 * Show the list of current departments.
 */
$allowed_levels = array(9); // Only Admins can manage departments
require_once 'bootstrap.php';

$page_title = __('Departments', 'cftp_admin');
$page_id = 'departments';

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="ps-card">
            <div class="ps-card-header">
                <h2 class="ps-card-title"><?php echo $page_title; ?></h2>
                <div class="ps-card-actions">
                    <a href="departments-add.php" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> <?php _e('Add Department', 'cftp_admin'); ?>
                    </a>
                </div>
            </div>
            <div class="ps-card-body">
                <table id="departments_table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'cftp_admin'); ?></th>
                            <th><?php _e('Description', 'cftp_admin'); ?></th>
                            <th><?php _e('Members', 'cftp_admin'); ?></th>
                            <th><?php _e('Created At', 'cftp_admin'); ?></th>
                            <th><?php _e('Actions', 'cftp_admin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $dbh->prepare("SELECT d.*, (SELECT COUNT(*) FROM " . TABLE_DEPARTMENT_MEMBERS . " dm WHERE dm.department_id = d.id) as members_count FROM " . TABLE_DEPARTMENTS . " d ORDER BY d.name ASC");
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . html_output($row['name']) . '</td>';
                            echo '<td>' . html_output($row['description']) . '</td>';
                            echo '<td><span class="badge bg-secondary">' . html_output($row['members_count']) . '</span></td>';
                            echo '<td>' . html_output($row['created_at']) . '</td>';
                            echo '<td>
                                    <a href="departments-edit.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
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

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
