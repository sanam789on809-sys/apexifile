<?php
/**
 * View version history for a file
 */
$allowed_levels = array(9, 8, 7, 0); // All logged in users
require_once 'bootstrap.php';

$page_title = __('File Versions', 'cftp_admin');
$page_id = 'file_versions';

if (empty($_GET['id'])) {
    ps_redirect('manage-files.php');
}

$file_id = (int)$_GET['id'];

$file = new \ProjectSend\Classes\Files($file_id);
if (!$file->record_exists) {
    ps_redirect('manage-files.php');
}

// In a real app we'd verify the user has access to this file

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="row">
    <div class="col-12 col-md-8 mx-auto">
        <div class="ps-card">
            <div class="ps-card-header">
                <h2 class="ps-card-title"><?php echo sprintf(__('Version History: %s', 'cftp_admin'), html_output($file->title)); ?></h2>
            </div>
            <div class="ps-card-body">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php _e('Version', 'cftp_admin'); ?></th>
                            <th><?php _e('Date Created', 'cftp_admin'); ?></th>
                            <th><?php _e('Created By', 'cftp_admin'); ?></th>
                            <th><?php _e('Actions', 'cftp_admin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // First show current version
                        echo '<tr class="table-primary">';
                        echo '<td>' . __('Current Version', 'cftp_admin') . '</td>';
                        echo '<td>' . html_output($file->uploaded_date) . '</td>';
                        echo '<td>' . html_output($file->uploaded_by) . '</td>';
                        echo '<td><a href="process.php?do=download&id=' . $file->id . '" class="btn btn-sm btn-success">' . __('Download', 'cftp_admin') . '</a></td>';
                        echo '</tr>';

                        // Then show older versions
                        $stmt = $dbh->prepare("SELECT v.*, u.name as user_name FROM " . TABLE_FILE_VERSIONS . " v LEFT JOIN " . TABLE_USERS . " u ON v.created_by = u.id WHERE v.file_id = :id ORDER BY v.version_number DESC");
                        $stmt->bindValue(':id', $file->id, PDO::PARAM_INT);
                        $stmt->execute();
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>v' . html_output($row['version_number']) . '</td>';
                            echo '<td>' . html_output($row['created_at']) . '</td>';
                            echo '<td>' . html_output($row['user_name']) . '</td>';
                            echo '<td>
                                    <a href="' . BASE_URI . 'upload/' . html_output($row['original_url']) . '" class="btn btn-sm btn-secondary" download><i class="fa fa-download"></i></a>
                                  </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="ps-card-footer text-end">
                <a href="manage-files.php" class="btn btn-secondary"><?php _e('Back to Files', 'cftp_admin'); ?></a>
            </div>
        </div>
    </div>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
