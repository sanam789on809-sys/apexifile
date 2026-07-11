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
<form action="" name="files_list" method="get" class="form-inline batch_actions">
    <div class="row">
        <div class="col-12">
            <?php include_once LAYOUT_DIR . DS . 'form-counts-actions.php'; ?>

            <?php
            if (isset($count) && $count > 0) {
                ?>
                <style>
                .google-drive-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                    gap: 1.5rem;
                    padding: 1rem 0;
                }
                .drive-card {
                    background: #ffffff;
                    border: 1px solid rgba(0,0,0,0.08);
                    border-radius: 12px;
                    padding: 1rem;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    position: relative;
                    transition: transform 0.2s, box-shadow 0.2s;
                    text-align: center;
                }
                .drive-card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
                    border-color: rgba(79, 70, 229, 0.4);
                }
                .drive-card .batch_checkbox {
                    position: absolute;
                    top: 10px;
                    left: 10px;
                    transform: scale(1.2);
                    cursor: pointer;
                }
                .drive-icon {
                    width: 64px;
                    height: 64px;
                    margin-bottom: 1rem;
                    color: #6366f1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 2.5rem;
                }
                .drive-thumbnail {
                    width: 100%;
                    height: 120px;
                    object-fit: cover;
                    border-radius: 8px;
                    margin-bottom: 1rem;
                }
                .drive-title {
                    font-family: var(--font-heading);
                    font-weight: 600;
                    font-size: 1rem;
                    color: #1e293b;
                    margin-bottom: 0.5rem;
                    word-break: break-word;
                    text-decoration: none;
                }
                .drive-meta {
                    font-size: 0.8rem;
                    color: #64748b;
                    margin-bottom: 1rem;
                }
                .drive-actions {
                    margin-top: auto;
                    width: 100%;
                    display: flex;
                    gap: 0.5rem;
                }
                .drive-actions .btn {
                    flex: 1;
                }
                </style>
                <div class="google-drive-grid">
                <?php

                foreach ($available_files as $file_id) {
                    $file = new \ProjectSend\Classes\Files($file_id);

                    // Checkbox
                    $checkbox = ($file->expired == false) ? '<input type="checkbox" name="files[]" value="' . $file->id . '" class="batch_checkbox" />' : '';

                    // Preview / Thumbnail
                    $preview_html = '<div class="drive-icon"><i class="fa fa-file-o"></i></div>';
                    if ($file->expired == false) {
                        if ($file->isImage()) {
                            $thumbnail = make_thumbnail($file->full_path, null, 300, 200);
                            if (!empty($thumbnail['thumbnail']['url'])) {
                                $preview_html = '<img src="' . $thumbnail['thumbnail']['url'] . '" class="drive-thumbnail" alt="' . html_output($file->title) . '" />';
                            }
                        } else if ($file->embeddable) {
                            $preview_html = '<div class="drive-icon get-preview" style="cursor:pointer;" data-url="' . BASE_URI . 'process.php?do=get_preview&file_id=' . $file->id . '"><i class="fa fa-eye"></i></div>';
                        }
                    }

                    // Download
                    if ($file->expired == true) {
                        $download_btn = '<a href="javascript:void(0);" class="btn btn-danger btn-sm disabled w-100">' . __('Expired', 'cftp_template') . '</a>';
                    } else {
                        $download_btn = '<a href="' . $file->download_link . '" class="btn btn-primary btn-sm w-100" target="_blank"><i class="fa fa-download"></i> ' . __('Download', 'cftp_template') . '</a>';
                    }

                    $date = format_date($file->uploaded_date);
                    $size = $file->size_formatted;

                    echo '<div class="drive-card">';
                    echo $checkbox;
                    echo $preview_html;
                    echo '<a href="' . $file->download_link . '" target="_blank" class="drive-title">' . html_output($file->title) . '</a>';
                    echo '<div class="drive-meta">' . $size . ' &bull; ' . $date . '</div>';
                    echo '<div class="drive-actions">' . $download_btn . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
            ?>
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