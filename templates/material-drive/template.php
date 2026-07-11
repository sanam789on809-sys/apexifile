<?php
/*
Template name: Default
URI: https://www.projectsend.org/templates/default
Author: CGT
Author URI: https://www.projectsend.org/
Author e-mail: contact@projectsend.org
Description: The default template uses the same style as the system backend, allowing for a seamless user experience
*/
$ld = 'cftp_template'; // specify the language domain for this template

define('TEMPLATE_RESULTS_PER_PAGE', get_option('pagination_results_per_page'));
define('TEMPLATE_THUMBNAILS_WIDTH', '50');
define('TEMPLATE_THUMBNAILS_HEIGHT', '50');

$filter_by_category = isset($_GET['category']) ? $_GET['category'] : null;

$current_url = get_form_action_with_existing_parameters('index.php');

include_once ROOT_DIR . '/templates/common.php'; // include the required functions for every template

$window_title = __('File downloads', 'cftp_template');

$page_id = 'default_template';

$body_class = array('template', 'default-template', 'hide_title');

// Flash errors
if (!$count) {
    if (isset($no_results_error)) {
        switch ($no_results_error) {
            case 'search':
                $flash->error(__('Your search keywords returned no results.', 'cftp_admin'));
                break;
            case 'filter':
                $flash->error(__('The filters you selected returned no results.', 'cftp_admin'));
                break;
        }
    } else {
        $flash->warning(__('There are no files available.', 'cftp_admin'));
    }
}

// Header buttons
if (current_user_can_upload()) {
    $header_action_buttons = [
        [
            'url' => BASE_URI.'upload.php',
            'label' => __('Upload file', 'cftp_admin'),
        ],
    ];
}

// Search + filters bar data
$search_form_action = 'index.php';
$filters_form = [
    'action' => '',
    'items' => [],
];

if (!empty($cat_ids)) {
    $selected_parent = (isset($_GET['category'])) ? [$_GET['category']] : [];
    $category_filter = [];
    $generate_categories_options = generate_categories_options($get_categories['arranged'], 0, $selected_parent, 'include', $cat_ids);
    $format_categories_options = format_categories_options($generate_categories_options);
    foreach ($format_categories_options as $key => $category) {
        $category_filter[$category['id']] = $category['label'];
    }
    $filters_form['items']['category'] = [
        'current' => (isset($_GET['category'])) ? $_GET['category'] : null,
        'placeholder' => [
            'value' => '0',
            'label' => __('All categories', 'cftp_admin')
        ],
        'options' => $category_filter,
    ];
}

// Results count and form actions 
$elements_found_count = (isset($count_for_pagination)) ? $count_for_pagination : 0;
$bulk_actions_items = [
    'none' => __('Select action', 'cftp_admin'),
    'zip' => __('Download zipped', 'cftp_admin'),
];

// Include layout files
include_once ADMIN_VIEWS_DIR . DS . 'header.php';

include_once LAYOUT_DIR . DS . 'search-filters-bar.php';

include_once LAYOUT_DIR . DS . 'breadcrumbs.php';

include_once LAYOUT_DIR . DS . 'folders-nav.php';

?>
    <div class="row">
        <div class="col-12">
            <?php include_once LAYOUT_DIR . DS . 'form-counts-actions.php'; ?>

            <?php if (isset($count) && $count > 0): ?>
                <div class="row g-4 mt-2">
                    <?php foreach ($available_files as $file_id): 
                        $file = new \ProjectSend\Classes\Files($file_id);
                        $is_image = $file->isImage();
                        $thumbnail_url = '';
                        if ($is_image && !$file->expired) {
                            $thumb_data = make_thumbnail($file->full_path, null, 250, 150);
                            $thumbnail_url = !empty($thumb_data['thumbnail']['url']) ? $thumb_data['thumbnail']['url'] : '';
                        }
                    ?>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="ps-card h-100 file-grid-card position-relative" style="transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; overflow: hidden; border: 1px solid var(--bs-border-color);">
                                
                                <!-- File Preview Area -->
                                <div class="file-preview-area bg-light d-flex align-items-center justify-content-center" style="height: 150px; border-bottom: 1px solid var(--bs-border-color);">
                                    <?php if ($thumbnail_url): ?>
                                        <img src="<?php echo $thumbnail_url; ?>" style="object-fit: cover; width: 100%; height: 100%;" alt="<?php echo html_output($file->title); ?>">
                                    <?php else: ?>
                                        <i class="fa fa-file-text-o fa-4x text-muted opacity-50"></i>
                                    <?php endif; ?>
                                    
                                    <!-- Hover actions overlay -->
                                    <div class="file-hover-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50" style="opacity: 0; transition: opacity 0.2s;">
                                        <?php if (!$file->expired): ?>
                                            <a href="<?php echo $file->download_link; ?>" target="_blank" class="btn btn-primary rounded-circle me-2" title="<?php _e('Download', 'cftp_admin'); ?>"><i class="fa fa-download"></i></a>
                                            <?php if ($is_image || $file->embeddable): ?>
                                                <button type="button" class="btn btn-light rounded-circle get-preview" data-url="<?php echo BASE_URI . 'process.php?do=get_preview&file_id=' . $file->id; ?>" title="<?php _e('Preview', 'cftp_admin'); ?>"><i class="fa fa-eye"></i></button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- File Details Area -->
                                <div class="ps-card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!$file->expired): ?>
                                            <input type="checkbox" name="files[]" value="<?php echo $file->id; ?>" class="batch_checkbox form-check-input me-2 mt-0" />
                                        <?php endif; ?>
                                        <h5 class="mb-0 text-truncate" style="font-size: 1rem;" title="<?php echo html_output($file->title); ?>">
                                            <?php echo html_output($file->title); ?>
                                        </h5>
                                    </div>
                                    <div class="text-muted small mb-2 text-truncate" title="<?php echo html_output($file->filename_original); ?>">
                                        <?php echo html_output($file->filename_original); ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="badge bg-secondary"><?php echo $file->extension; ?></span>
                                        <span class="text-muted small"><?php echo $file->size_formatted; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <style>
                .file-grid-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
                }
                .file-grid-card:hover .file-hover-overlay {
                    opacity: 1 !important;
                }
                </style>
            <?php endif; ?>
        </div>
    </div>
</form>
<?php
    if (!empty($table)) {
        // PAGINATION
        $pagination = new \ProjectSend\Classes\Layout\Pagination;
        echo $pagination->make([
            'link' => 'my_files/index.php',
            'current' => $pagination_page,
            'item_count' => $count_for_pagination,
            'items_per_page' => TEMPLATE_RESULTS_PER_PAGE,
        ]);
    }

render_footer_text();

render_json_variables();

render_assets('js', 'footer');
render_assets('css', 'footer');

render_custom_assets('body_bottom');
?>
</body>

</html>