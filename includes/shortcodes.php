<?php
/**
 * Shortcodes Functionality
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shortcodes Class
 */
class CSD_Shortcodes {
	/**
	 * Constructor
	 */
	public function __construct() {
		// Register shortcodes
		add_shortcode('csd_schools', array($this, 'schools_shortcode'));
		add_shortcode('csd_staff', array($this, 'staff_shortcode'));
		add_shortcode('csd_school', array($this, 'single_school_shortcode'));
		add_shortcode('csd_saved_view', array($this, 'saved_view_shortcode'));
		
		// AJAX handlers
		add_action('wp_ajax_csd_save_shortcode_view', array($this, 'ajax_save_shortcode_view'));
		add_action('wp_ajax_csd_delete_shortcode_view', array($this, 'ajax_delete_shortcode_view'));
		add_action('wp_ajax_csd_get_shortcode_views', array($this, 'ajax_get_shortcode_views'));
		
		// Frontend AJAX handlers
		add_action('wp_ajax_nopriv_csd_filter_schools', array($this, 'ajax_filter_schools'));
		add_action('wp_ajax_csd_filter_schools', array($this, 'ajax_filter_schools'));
		add_action('wp_ajax_nopriv_csd_filter_staff', array($this, 'ajax_filter_staff'));
		add_action('wp_ajax_csd_filter_staff', array($this, 'ajax_filter_staff'));
	}
	
	/**
	 * Render admin page for shortcodes
	 */
	public function render_admin_page() {
		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
		
		if ($action === 'add' || $action === 'edit') {
			$this->render_shortcode_editor();
		} else {
			$this->render_shortcodes_list();
		}
	}
	
	/**
	 * Enqueue required scripts on this specific page
	 */
	private function enqueue_page_scripts() {
		// Enqueue jQuery
		wp_enqueue_script('jquery');
		
		// Enqueue the admin script again to ensure it's loaded
		wp_enqueue_script('csd-admin-scripts', CSD_MANAGER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CSD_MANAGER_VERSION, true);
		
		// Localize the script with fresh data
		wp_localize_script('csd-admin-scripts', 'csd_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('csd-ajax-nonce')
		));
	}
	
	/**
	 * Render shortcodes list page
	 */
	private function render_shortcodes_list() {
		$this->enqueue_page_scripts();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Saved Views & Shortcodes', 'csd-manager'); ?></h1>
			<a href="<?php echo admin_url('admin.php?page=csd-shortcodes&action=add'); ?>" class="page-title-action"><?php _e('Add New', 'csd-manager'); ?></a>
			<hr class="wp-header-end">
			
			<div class="csd-table-container">
				<table class="wp-list-table widefat fixed striped csd-shortcodes-table">
					<thead>
						<tr>
							<th><?php _e('View Name', 'csd-manager'); ?></th>
							<th><?php _e('View Type', 'csd-manager'); ?></th>
							<th><?php _e('Shortcode', 'csd-manager'); ?></th>
							<th><?php _e('Date Created', 'csd-manager'); ?></th>
							<th><?php _e('Actions', 'csd-manager'); ?></th>
						</tr>
					</thead>
					<tbody id="csd-shortcodes-list">
						<tr>
							<td colspan="5"><?php _e('Loading saved views...', 'csd-manager'); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Load shortcode views
				loadShortcodeViews();
				
				// Handle delete shortcode view
				$(document).on('click', '.csd-delete-shortcode', function(e) {
					e.preventDefault();
					
					if (confirm('<?php _e('Are you sure you want to delete this saved view?', 'csd-manager'); ?>')) {
						var viewId = $(this).data('id');
						
						$.ajax({
							url: csd_ajax.ajax_url,
							type: 'POST',
							data: {
								action: 'csd_delete_shortcode_view',
								view_id: viewId,
								nonce: csd_ajax.nonce
							},
							success: function(response) {
								if (response.success) {
									// Reload the shortcode views list
									loadShortcodeViews();
								} else {
									alert(response.data.message);
								}
							}
						});
					}
				});
				
				// Copy shortcode to clipboard
				$(document).on('click', '.csd-copy-shortcode', function(e) {
					e.preventDefault();
					
					var shortcode = $(this).data('shortcode');
					navigator.clipboard.writeText(shortcode).then(function() {
						alert('<?php _e('Shortcode copied to clipboard!', 'csd-manager'); ?>');
					}, function() {
						// Fallback for older browsers
						var textarea = document.createElement('textarea');
						textarea.value = shortcode;
						textarea.style.position = 'fixed';
						document.body.appendChild(textarea);
						textarea.select();
						
						try {
							document.execCommand('copy');
							alert('<?php _e('Shortcode copied to clipboard!', 'csd-manager'); ?>');
						} catch (err) {
							alert('<?php _e('Could not copy shortcode. Please select and copy manually.', 'csd-manager'); ?>');
						}
						
						document.body.removeChild(textarea);
					});
				});
				
				// Load shortcode views
				function loadShortcodeViews() {
					$('#csd-shortcodes-list').html('<tr><td colspan="5"><?php _e('Loading saved views...', 'csd-manager'); ?></td></tr>');
					
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'csd_get_shortcode_views',
							nonce: csd_ajax.nonce
						},
						success: function(response) {
							if (response.success) {
								var views = response.data;
								
								// Clear the table
								$('#csd-shortcodes-list').empty();
								
								if (views.length === 0) {
									$('#csd-shortcodes-list').html('<tr><td colspan="5"><?php _e('No saved views found.', 'csd-manager'); ?></td></tr>');
								} else {
									// Add each view to the table
									$.each(views, function(index, view) {
										var row = '<tr>' +
											'<td>' + view.view_name + '</td>' +
											'<td>' + formatViewType(view.view_type) + '</td>' +
											'<td><code>' + view.shortcode + '</code> <a href="#" class="csd-copy-shortcode" data-shortcode="' + view.shortcode + '"><span class="dashicons dashicons-clipboard"></span></a></td>' +
											'<td>' + formatDate(view.date_created) + '</td>' +
											'<td>' +
												'<a href="?page=csd-shortcodes&action=edit&id=' + view.id + '" class="button button-small"><?php _e('Edit', 'csd-manager'); ?></a> ' +
												'<a href="#" class="button button-small csd-delete-shortcode" data-id="' + view.id + '"><?php _e('Delete', 'csd-manager'); ?></a>' +
											'</td>' +
										'</tr>';
										
										$('#csd-shortcodes-list').append(row);
									});
								}
							} else {
								$('#csd-shortcodes-list').html('<tr><td colspan="5"><?php _e('Error loading saved views.', 'csd-manager'); ?></td></tr>');
								console.error(response.data.message);
							}
						},
						error: function() {
							$('#csd-shortcodes-list').html('<tr><td colspan="5"><?php _e('Error loading saved views.', 'csd-manager'); ?></td></tr>');
						}
					});
				}
				
				// Format view type
				function formatViewType(type) {
					if (type === 'schools') {
						return '<?php _e('Schools List', 'csd-manager'); ?>';
					} else if (type === 'staff') {
						return '<?php _e('Staff List', 'csd-manager'); ?>';
					} else if (type === 'school') {
						return '<?php _e('Single School', 'csd-manager'); ?>';
					}
					
					return type;
				}
				
				// Format date
				function formatDate(dateString) {
					var date = new Date(dateString);
					return date.toLocaleDateString();
				}
			});
		</script>
		<?php
	}
	
	/**
	 * Render shortcode editor page
	 */
	private function render_shortcode_editor() {
		$this->enqueue_page_scripts();
		
		$view_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$is_edit = $view_id > 0;
		$view = null;
		$view_settings = array();
		
		if ($is_edit) {
			$wpdb = csd_db_connection();
			$view = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM " . csd_table('shortcode_views') . " WHERE id = %d",
				$view_id
			));
			
			if (!$view) {
				wp_die(__('Saved view not found.', 'csd-manager'));
			}
			
			$view_settings = json_decode($view->view_settings, true);
		}
		
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo $is_edit ? __('Edit Saved View', 'csd-manager') : __('Create New Saved View', 'csd-manager'); ?>
			</h1>
			<a href="<?php echo admin_url('admin.php?page=csd-shortcodes'); ?>" class="page-title-action"><?php _e('Back to Saved Views', 'csd-manager'); ?></a>
			<hr class="wp-header-end">
			
			<form id="csd-shortcode-form" class="csd-form">
				<input type="hidden" name="view_id" value="<?php echo $view_id; ?>">
				
				<div class="csd-form-section">
					<h2><?php _e('View Settings', 'csd-manager'); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="view_name"><?php _e('View Name', 'csd-manager'); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="view_name" name="view_name" value="<?php echo $is_edit ? esc_attr($view->view_name) : ''; ?>" class="regular-text" required>
								<p class="description"><?php _e('Name for your saved view (internal use only).', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="view_type"><?php _e('View Type', 'csd-manager'); ?> <span class="required">*</span></label>
							</th>
							<td>
								<select id="view_type" name="view_type" required>
									<option value=""><?php _e('Select view type', 'csd-manager'); ?></option>
									<option value="schools" <?php echo ($is_edit && $view->view_type === 'schools') ? 'selected' : ''; ?>><?php _e('Schools List', 'csd-manager'); ?></option>
									<option value="staff" <?php echo ($is_edit && $view->view_type === 'staff') ? 'selected' : ''; ?>><?php _e('Staff List', 'csd-manager'); ?></option>
									<option value="school" <?php echo ($is_edit && $view->view_type === 'school') ? 'selected' : ''; ?>><?php _e('Single School', 'csd-manager'); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
				
				<div id="schools-settings" class="csd-form-section view-type-settings" style="display: none;">
					<h2><?php _e('Schools List Settings', 'csd-manager'); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="schools_per_page"><?php _e('Schools Per Page', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="number" id="schools_per_page" name="schools_per_page" value="<?php echo $is_edit && isset($view_settings['schools_per_page']) ? esc_attr($view_settings['schools_per_page']) : '10'; ?>" min="1" max="100" class="small-text">
								<p class="description"><?php _e('Number of schools to display per page.', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="schools_sort_by"><?php _e('Sort By', 'csd-manager'); ?></label>
							</th>
							<td>
								<select id="schools_sort_by" name="schools_sort_by">
									<option value="school_name" <?php echo $is_edit && isset($view_settings['schools_sort_by']) && $view_settings['schools_sort_by'] === 'school_name' ? 'selected' : ''; ?>><?php _e('School Name', 'csd-manager'); ?></option>
									<option value="city" <?php echo $is_edit && isset($view_settings['schools_sort_by']) && $view_settings['schools_sort_by'] === 'city' ? 'selected' : ''; ?>><?php _e('City', 'csd-manager'); ?></option>
									<option value="state" <?php echo $is_edit && isset($view_settings['schools_sort_by']) && $view_settings['schools_sort_by'] === 'state' ? 'selected' : ''; ?>><?php _e('State', 'csd-manager'); ?></option>
									<option value="school_divisions" <?php echo $is_edit && isset($view_settings['schools_sort_by']) && $view_settings['schools_sort_by'] === 'school_divisions' ? 'selected' : ''; ?>><?php _e('Division', 'csd-manager'); ?></option>
								</select>
								
								<select id="schools_sort_order" name="schools_sort_order">
									<option value="ASC" <?php echo $is_edit && isset($view_settings['schools_sort_order']) && $view_settings['schools_sort_order'] === 'ASC' ? 'selected' : ''; ?>><?php _e('Ascending', 'csd-manager'); ?></option>
									<option value="DESC" <?php echo $is_edit && isset($view_settings['schools_sort_order']) && $view_settings['schools_sort_order'] === 'DESC' ? 'selected' : ''; ?>><?php _e('Descending', 'csd-manager'); ?></option>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label><?php _e('Display Filters', 'csd-manager'); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" name="schools_show_search" value="1" <?php echo $is_edit && isset($view_settings['schools_show_search']) && $view_settings['schools_show_search'] ? 'checked' : ''; ?>>
									<?php _e('Show search box', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="schools_show_state_filter" value="1" <?php echo $is_edit && isset($view_settings['schools_show_state_filter']) && $view_settings['schools_show_state_filter'] ? 'checked' : ''; ?>>
									<?php _e('Show state filter', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="schools_show_division_filter" value="1" <?php echo $is_edit && isset($view_settings['schools_show_division_filter']) && $view_settings['schools_show_division_filter'] ? 'checked' : ''; ?>>
									<?php _e('Show division filter', 'csd-manager'); ?>
								</label>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label><?php _e('Fixed Filters', 'csd-manager'); ?></label>
							</th>
							<td>
								<label for="schools_filter_state"><?php _e('State:', 'csd-manager'); ?></label>
								<select id="schools_filter_state" name="schools_filter_state">
									<option value=""><?php _e('All States', 'csd-manager'); ?></option>
									<?php
									$wpdb = csd_db_connection();
									$states = $wpdb->get_col("SELECT DISTINCT state FROM " . csd_table('schools') . " ORDER BY state");
									
									foreach ($states as $state) {
										echo '<option value="' . esc_attr($state) . '" ' . ($is_edit && isset($view_settings['schools_filter_state']) && $view_settings['schools_filter_state'] === $state ? 'selected' : '') . '>' . esc_html($state) . '</option>';
									}
									?>
								</select>
								<br><br>
								
								<label for="schools_filter_division"><?php _e('Division:', 'csd-manager'); ?></label>
								<select id="schools_filter_division" name="schools_filter_division">
									<option value=""><?php _e('All Divisions', 'csd-manager'); ?></option>
									<option value="NCAA D1" <?php echo $is_edit && isset($view_settings['schools_filter_division']) && $view_settings['schools_filter_division'] === 'NCAA D1' ? 'selected' : ''; ?>><?php _e('NCAA D1', 'csd-manager'); ?></option>
									<option value="NCAA D2" <?php echo $is_edit && isset($view_settings['schools_filter_division']) && $view_settings['schools_filter_division'] === 'NCAA D2' ? 'selected' : ''; ?>><?php _e('NCAA D2', 'csd-manager'); ?></option>
									<option value="NCAA D3" <?php echo $is_edit && isset($view_settings['schools_filter_division']) && $view_settings['schools_filter_division'] === 'NCAA D3' ? 'selected' : ''; ?>><?php _e('NCAA D3', 'csd-manager'); ?></option>
									<option value="NAIA" <?php echo $is_edit && isset($view_settings['schools_filter_division']) && $view_settings['schools_filter_division'] === 'NAIA' ? 'selected' : ''; ?>><?php _e('NAIA', 'csd-manager'); ?></option>
									<option value="NJCAA" <?php echo $is_edit && isset($view_settings['schools_filter_division']) && $view_settings['schools_filter_division'] === 'NJCAA' ? 'selected' : ''; ?>><?php _e('NJCAA', 'csd-manager'); ?></option>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label><?php _e('Display Columns', 'csd-manager'); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" name="schools_show_city" value="1" <?php echo $is_edit && isset($view_settings['schools_show_city']) && $view_settings['schools_show_city'] ? 'checked' : ''; ?>>
									<?php _e('City', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="schools_show_state" value="1" <?php echo $is_edit && isset($view_settings['schools_show_state']) && $view_settings['schools_show_state'] ? 'checked' : ''; ?>>
									<?php _e('State', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="schools_show_division" value="1" <?php echo $is_edit && isset($view_settings['schools_show_division']) && $view_settings['schools_show_division'] ? 'checked' : ''; ?>>
									<?php _e('Division', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="schools_show_website" value="1" <?php echo $is_edit && isset($view_settings['schools_show_website']) && $view_settings['schools_show_website'] ? 'checked' : ''; ?>>
									<?php _e('Website', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="schools_show_mascot" value="1" <?php echo $is_edit && isset($view_settings['schools_show_mascot']) && $view_settings['schools_show_mascot'] ? 'checked' : ''; ?>>
									<?php _e('Mascot', 'csd-manager'); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
				
				<div id="staff-settings" class="csd-form-section view-type-settings" style="display: none;">
					<h2><?php _e('Staff List Settings', 'csd-manager'); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="staff_per_page"><?php _e('Staff Per Page', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="number" id="staff_per_page" name="staff_per_page" value="<?php echo $is_edit && isset($view_settings['staff_per_page']) ? esc_attr($view_settings['staff_per_page']) : '10'; ?>" min="1" max="100" class="small-text">
								<p class="description"><?php _e('Number of staff members to display per page.', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="staff_sort_by"><?php _e('Sort By', 'csd-manager'); ?></label>
							</th>
							<td>
								<select id="staff_sort_by" name="staff_sort_by">
									<option value="full_name" <?php echo $is_edit && isset($view_settings['staff_sort_by']) && $view_settings['staff_sort_by'] === 'full_name' ? 'selected' : ''; ?>><?php _e('Name', 'csd-manager'); ?></option>
									<option value="title" <?php echo $is_edit && isset($view_settings['staff_sort_by']) && $view_settings['staff_sort_by'] === 'title' ? 'selected' : ''; ?>><?php _e('Title', 'csd-manager'); ?></option>
									<option value="sport_department" <?php echo $is_edit && isset($view_settings['staff_sort_by']) && $view_settings['staff_sort_by'] === 'sport_department' ? 'selected' : ''; ?>><?php _e('Sport/Department', 'csd-manager'); ?></option>
									<option value="school_name" <?php echo $is_edit && isset($view_settings['staff_sort_by']) && $view_settings['staff_sort_by'] === 'school_name' ? 'selected' : ''; ?>><?php _e('School', 'csd-manager'); ?></option>
								</select>
								
								<select id="staff_sort_order" name="staff_sort_order">
									<option value="ASC" <?php echo $is_edit && isset($view_settings['staff_sort_order']) && $view_settings['staff_sort_order'] === 'ASC' ? 'selected' : ''; ?>><?php _e('Ascending', 'csd-manager'); ?></option>
									<option value="DESC" <?php echo $is_edit && isset($view_settings['staff_sort_order']) && $view_settings['staff_sort_order'] === 'DESC' ? 'selected' : ''; ?>><?php _e('Descending', 'csd-manager'); ?></option>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label><?php _e('Display Filters', 'csd-manager'); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" name="staff_show_search" value="1" <?php echo $is_edit && isset($view_settings['staff_show_search']) && $view_settings['staff_show_search'] ? 'checked' : ''; ?>>
									<?php _e('Show search box', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="staff_show_school_filter" value="1" <?php echo $is_edit && isset($view_settings['staff_show_school_filter']) && $view_settings['staff_show_school_filter'] ? 'checked' : ''; ?>>
									<?php _e('Show school filter', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="staff_show_department_filter" value="1" <?php echo $is_edit && isset($view_settings['staff_show_department_filter']) && $view_settings['staff_show_department_filter'] ? 'checked' : ''; ?>>
									<?php _e('Show department filter', 'csd-manager'); ?>
								</label>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label><?php _e('Fixed Filters', 'csd-manager'); ?></label>
							</th>
							<td>
								<label for="staff_filter_school"><?php _e('School:', 'csd-manager'); ?></label>
								<select id="staff_filter_school" name="staff_filter_school">
									<option value=""><?php _e('All Schools', 'csd-manager'); ?></option>
									<?php
									$wpdb = csd_db_connection();
									$schools = $wpdb->get_results("SELECT id, school_name FROM " . csd_table('schools') . " ORDER BY school_name");
									
									foreach ($schools as $school) {
										echo '<option value="' . esc_attr($school->id) . '" ' . ($is_edit && isset($view_settings['staff_filter_school']) && $view_settings['staff_filter_school'] == $school->id ? 'selected' : '') . '>' . esc_html($school->school_name) . '</option>';
									}
									?>
								</select>
								<br><br>
								
								<label for="staff_filter_department"><?php _e('Department:', 'csd-manager'); ?></label>
								<select id="staff_filter_department" name="staff_filter_department">
									<option value=""><?php _e('All Departments', 'csd-manager'); ?></option>
									<?php
									$departments = $wpdb->get_col("SELECT DISTINCT sport_department FROM " . csd_table('staff') . " WHERE sport_department != '' ORDER BY sport_department");
									
									foreach ($departments as $department) {
										echo '<option value="' . esc_attr($department) . '" ' . ($is_edit && isset($view_settings['staff_filter_department']) && $view_settings['staff_filter_department'] === $department ? 'selected' : '') . '>' . esc_html($department) . '</option>';
									}
									?>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label><?php _e('Display Columns', 'csd-manager'); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" name="staff_show_title" value="1" <?php echo $is_edit && isset($view_settings['staff_show_title']) && $view_settings['staff_show_title'] ? 'checked' : ''; ?>>
									<?php _e('Title', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="staff_show_department" value="1" <?php echo $is_edit && isset($view_settings['staff_show_department']) && $view_settings['staff_show_department'] ? 'checked' : ''; ?>>
									<?php _e('Sport/Department', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="staff_show_school" value="1" <?php echo $is_edit && isset($view_settings['staff_show_school']) && $view_settings['staff_show_school'] ? 'checked' : ''; ?>>
									<?php _e('School', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="staff_show_email" value="1" <?php echo $is_edit && isset($view_settings['staff_show_email']) && $view_settings['staff_show_email'] ? 'checked' : ''; ?>>
									<?php _e('Email', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="staff_show_phone" value="1" <?php echo $is_edit && isset($view_settings['staff_show_phone']) && $view_settings['staff_show_phone'] ? 'checked' : ''; ?>>
									<?php _e('Phone', 'csd-manager'); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
				
				<div id="school-settings" class="csd-form-section view-type-settings" style="display: none;">
					<h2><?php _e('Single School Settings', 'csd-manager'); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="single_school_id"><?php _e('School', 'csd-manager'); ?> <span class="required">*</span></label>
							</th>
							<td>
								<select id="single_school_id" name="single_school_id" class="regular-text">
									<option value=""><?php _e('Select a school', 'csd-manager'); ?></option>
									<?php
									$wpdb = csd_db_connection();
									$schools = $wpdb->get_results("SELECT id, school_name FROM " . csd_table('schools') . " ORDER BY school_name");
									
									foreach ($schools as $school) {
										echo '<option value="' . esc_attr($school->id) . '" ' . ($is_edit && isset($view_settings['single_school_id']) && $view_settings['single_school_id'] == $school->id ? 'selected' : '') . '>' . esc_html($school->school_name) . '</option>';
									}
									?>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label><?php _e('Display Sections', 'csd-manager'); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" name="school_show_info" value="1" <?php echo $is_edit && isset($view_settings['school_show_info']) && $view_settings['school_show_info'] ? 'checked' : ''; ?>>
									<?php _e('School Information', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="school_show_contact" value="1" <?php echo $is_edit && isset($view_settings['school_show_contact']) && $view_settings['school_show_contact'] ? 'checked' : ''; ?>>
									<?php _e('Contact Information', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="school_show_staff" value="1" <?php echo $is_edit && isset($view_settings['school_show_staff']) && $view_settings['school_show_staff'] ? 'checked' : ''; ?>>
									<?php _e('Staff List', 'csd-manager'); ?>
								</label>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="school_staff_sort_by"><?php _e('Staff Sort By', 'csd-manager'); ?></label>
							</th>
							<td>
								<select id="school_staff_sort_by" name="school_staff_sort_by">
									<option value="full_name" <?php echo $is_edit && isset($view_settings['school_staff_sort_by']) && $view_settings['school_staff_sort_by'] === 'full_name' ? 'selected' : ''; ?>><?php _e('Name', 'csd-manager'); ?></option>
									<option value="title" <?php echo $is_edit && isset($view_settings['school_staff_sort_by']) && $view_settings['school_staff_sort_by'] === 'title' ? 'selected' : ''; ?>><?php _e('Title', 'csd-manager'); ?></option>
									<option value="sport_department" <?php echo $is_edit && isset($view_settings['school_staff_sort_by']) && $view_settings['school_staff_sort_by'] === 'sport_department' ? 'selected' : ''; ?>><?php _e('Sport/Department', 'csd-manager'); ?></option>
								</select>
								
								<select id="school_staff_sort_order" name="school_staff_sort_order">
									<option value="ASC" <?php echo $is_edit && isset($view_settings['school_staff_sort_order']) && $view_settings['school_staff_sort_order'] === 'ASC' ? 'selected' : ''; ?>><?php _e('Ascending', 'csd-manager'); ?></option>
									<option value="DESC" <?php echo $is_edit && isset($view_settings['school_staff_sort_order']) && $view_settings['school_staff_sort_order'] === 'DESC' ? 'selected' : ''; ?>><?php _e('Descending', 'csd-manager'); ?></option>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label><?php _e('Staff Display Columns', 'csd-manager'); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" name="school_staff_show_title" value="1" <?php echo $is_edit && isset($view_settings['school_staff_show_title']) && $view_settings['school_staff_show_title'] ? 'checked' : ''; ?>>
									<?php _e('Title', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="school_staff_show_department" value="1" <?php echo $is_edit && isset($view_settings['school_staff_show_department']) && $view_settings['school_staff_show_department'] ? 'checked' : ''; ?>>
									<?php _e('Sport/Department', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="school_staff_show_email" value="1" <?php echo $is_edit && isset($view_settings['school_staff_show_email']) && $view_settings['school_staff_show_email'] ? 'checked' : ''; ?>>
									<?php _e('Email', 'csd-manager'); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="school_staff_show_phone" value="1" <?php echo $is_edit && isset($view_settings['school_staff_show_phone']) && $view_settings['school_staff_show_phone'] ? 'checked' : ''; ?>>
									<?php _e('Phone', 'csd-manager'); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="csd-shortcode-preview-wrapper" style="display: none;">
					<h2><?php _e('Shortcode Preview', 'csd-manager'); ?></h2>
					<div class="csd-shortcode-preview">
						<code id="shortcode-preview"></code>
						<button type="button" id="copy-shortcode" class="button button-small"><span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'csd-manager'); ?></button>
					</div>
				</div>
				
				<div class="csd-form-submit">
					<button type="submit" class="button button-primary"><?php echo $is_edit ? __('Update Saved View', 'csd-manager') : __('Save View', 'csd-manager'); ?></button>
					<a href="<?php echo admin_url('admin.php?page=csd-shortcodes'); ?>" class="button"><?php _e('Cancel', 'csd-manager'); ?></a>
				</div>
			</form>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Show/hide settings based on view type
				$('#view_type').on('change', function() {
					var viewType = $(this).val();
					
					// Hide all settings
					$('.view-type-settings').hide();
					
					// Show settings for selected type
					if (viewType) {
						$('#' + viewType + '-settings').show();
						
						// Update shortcode preview
						updateShortcodePreview();
						$('.csd-shortcode-preview-wrapper').show();
					} else {
						$('.csd-shortcode-preview-wrapper').hide();
					}
				});
				
				// Trigger change to initialize view
				$('#view_type').trigger('change');
				
				// Update shortcode preview on form change
				$('#csd-shortcode-form input, #csd-shortcode-form select').on('change', function() {
					updateShortcodePreview();
				});
				
				// Copy shortcode button
				$('#copy-shortcode').on('click', function() {
					var shortcode = $('#shortcode-preview').text();
					
					navigator.clipboard.writeText(shortcode).then(function() {
						$('#copy-shortcode').html('<span class="dashicons dashicons-yes"></span> <?php _e('Copied!', 'csd-manager'); ?>');
						
						setTimeout(function() {
							$('#copy-shortcode').html('<span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'csd-manager'); ?>');
						}, 2000);
					}, function() {
						// Fallback for older browsers
						var textarea = document.createElement('textarea');
						textarea.value = shortcode;
						textarea.style.position = 'fixed';
						document.body.appendChild(textarea);
						textarea.select();
						
						try {
							document.execCommand('copy');
							$('#copy-shortcode').html('<span class="dashicons dashicons-yes"></span> <?php _e('Copied!', 'csd-manager'); ?>');
							
							setTimeout(function() {
								$('#copy-shortcode').html('<span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'csd-manager'); ?>');
							}, 2000);
						} catch (err) {
							alert('<?php _e('Could not copy shortcode. Please select and copy manually.', 'csd-manager'); ?>');
						}
						
						document.body.removeChild(textarea);
					});
				});
				
				// Form submission
				$('#csd-shortcode-form').on('submit', function(e) {
					e.preventDefault();
					
					var formData = $(this).serialize();
					var viewType = $('#view_type').val();
					
					// Validate form
					if (!$('#view_name').val()) {
						alert('<?php _e('Please enter a view name.', 'csd-manager'); ?>');
						$('#view_name').focus();
						return;
					}
					
					if (!viewType) {
						alert('<?php _e('Please select a view type.', 'csd-manager'); ?>');
						$('#view_type').focus();
						return;
					}
					
					if (viewType === 'school' && !$('#single_school_id').val()) {
						alert('<?php _e('Please select a school for the single school view.', 'csd-manager'); ?>');
						$('#single_school_id').focus();
						return;
					}
					
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'csd_save_shortcode_view',
							form_data: formData,
							nonce: csd_ajax.nonce
						},
						beforeSend: function() {
							$('.csd-form-submit button').prop('disabled', true).text('<?php _e('Saving...', 'csd-manager'); ?>');
						},
						success: function(response) {
							if (response.success) {
								window.location.href = '<?php echo admin_url('admin.php?page=csd-shortcodes'); ?>';
							} else {
								alert(response.data.message);
								$('.csd-form-submit button').prop('disabled', false).text('<?php echo $is_edit ? __('Update Saved View', 'csd-manager') : __('Save View', 'csd-manager'); ?>');
							}
						},
						error: function() {
							alert('<?php _e('An error occurred. Please try again.', 'csd-manager'); ?>');
							$('.csd-form-submit button').prop('disabled', false).text('<?php echo $is_edit ? __('Update Saved View', 'csd-manager') : __('Save View', 'csd-manager'); ?>');
						}
					});
				});
				
				// Update shortcode preview
				function updateShortcodePreview() {
					var viewType = $('#view_type').val();
					var viewId = $('input[name="view_id"]').val();
					var shortcode = '';
					
					if (!viewType) {
						return;
					}
					
					if (viewId) {
						// For existing views, use the saved view shortcode
						shortcode = '[csd_saved_view id="' + viewId + '"]';
					} else {
						// For new views, generate a preview based on type
						if (viewType === 'schools') {
							shortcode = '[csd_schools';
							
							// Add attributes
							var perPage = $('#schools_per_page').val();
							if (perPage && perPage !== '10') {
								shortcode += ' per_page="' + perPage + '"';
							}
							
							var sortBy = $('#schools_sort_by').val();
							if (sortBy && sortBy !== 'school_name') {
								shortcode += ' sort_by="' + sortBy + '"';
							}
							
							var sortOrder = $('#schools_sort_order').val();
							if (sortOrder && sortOrder !== 'ASC') {
								shortcode += ' sort_order="' + sortOrder + '"';
							}
							
							var filterState = $('#schools_filter_state').val();
							if (filterState) {
								shortcode += ' state="' + filterState + '"';
							}
							
							var filterDivision = $('#schools_filter_division').val();
							if (filterDivision) {
								shortcode += ' division="' + filterDivision + '"';
							}
							
							shortcode += ']';
						} else if (viewType === 'staff') {
							shortcode = '[csd_staff';
							
							// Add attributes
							var perPage = $('#staff_per_page').val();
							if (perPage && perPage !== '10') {
								shortcode += ' per_page="' + perPage + '"';
							}
							
							var sortBy = $('#staff_sort_by').val();
							if (sortBy && sortBy !== 'full_name') {
								shortcode += ' sort_by="' + sortBy + '"';
							}
							
							var sortOrder = $('#staff_sort_order').val();
							if (sortOrder && sortOrder !== 'ASC') {
								shortcode += ' sort_order="' + sortOrder + '"';
							}
							
							var filterSchool = $('#staff_filter_school').val();
							if (filterSchool) {
								shortcode += ' school_id="' + filterSchool + '"';
							}
							
							var filterDepartment = $('#staff_filter_department').val();
							if (filterDepartment) {
								shortcode += ' department="' + filterDepartment + '"';
							}
							
							shortcode += ']';
						} else if (viewType === 'school') {
							var schoolId = $('#single_school_id').val();
							shortcode = '[csd_school id="' + (schoolId ? schoolId : '0') + '"';
							
							// Add attributes
							var showInfo = $('input[name="school_show_info"]').is(':checked');
							if (!showInfo) {
								shortcode += ' show_info="0"';
							}
							
							var showContact = $('input[name="school_show_contact"]').is(':checked');
							if (!showContact) {
								shortcode += ' show_contact="0"';
							}
							
							var showStaff = $('input[name="school_show_staff"]').is(':checked');
							if (!showStaff) {
								shortcode += ' show_staff="0"';
							}
							
							shortcode += ']';
						}
					}
					
					$('#shortcode-preview').text(shortcode);
				}
			});
		</script>
		<?php
	}
	
	/**
	 * Schools shortcode
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function schools_shortcode($atts) {
		$atts = shortcode_atts(array(
			'per_page' => 10,
			'sort_by' => 'school_name',
			'sort_order' => 'ASC',
			'state' => '',
			'division' => '',
			'show_search' => 1,
			'show_state_filter' => 1,
			'show_division_filter' => 1,
			'show_city' => 1,
			'show_state' => 1,
			'show_division' => 1,
			'show_website' => 1,
			'show_mascot' => 0
		), $atts);
		
		// Enqueue scripts and styles
		wp_enqueue_style('csd-frontend-styles');
		wp_enqueue_script('csd-frontend-scripts');
		
		// Localize script with AJAX URL
		wp_localize_script('csd-frontend-scripts', 'csd_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php')
		));
		
		// Generate unique ID for this instance
		$instance_id = 'csd-schools-' . uniqid();
		
		// Start output buffer
		ob_start();
		
		// Get states for filter
		$wpdb = csd_db_connection();
		$states = $wpdb->get_col("SELECT DISTINCT state FROM " . csd_table('schools') . " ORDER BY state");
		
		?>
		<div class="csd-schools-container" id="<?php echo esc_attr($instance_id); ?>">
			<?php if ($atts['show_search'] || $atts['show_state_filter'] || $atts['show_division_filter']): ?>
			<div class="csd-filters">
				<?php if ($atts['show_search']): ?>
				<div class="csd-search-box">
					<input type="text" class="csd-school-search" placeholder="<?php _e('Search schools...', 'csd-manager'); ?>">
					<button type="button" class="csd-school-search-btn"><?php _e('Search', 'csd-manager'); ?></button>
				</div>
				<?php endif; ?>
				
				<div class="csd-filter-controls">
					<?php if ($atts['show_state_filter']): ?>
					<div class="csd-filter-field">
						<label for="<?php echo esc_attr($instance_id); ?>-state"><?php _e('State:', 'csd-manager'); ?></label>
						<select class="csd-school-filter-state" id="<?php echo esc_attr($instance_id); ?>-state">
							<option value=""><?php _e('All States', 'csd-manager'); ?></option>
							<?php foreach ($states as $state): ?>
								<option value="<?php echo esc_attr($state); ?>" <?php selected($atts['state'], $state); ?>><?php echo esc_html($state); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>
					
					<?php if ($atts['show_division_filter']): ?>
					<div class="csd-filter-field">
						<label for="<?php echo esc_attr($instance_id); ?>-division"><?php _e('Division:', 'csd-manager'); ?></label>
						<select class="csd-school-filter-division" id="<?php echo esc_attr($instance_id); ?>-division">
							<option value=""><?php _e('All Divisions', 'csd-manager'); ?></option>
							<option value="NCAA D1" <?php selected($atts['division'], 'NCAA D1'); ?>><?php _e('NCAA D1', 'csd-manager'); ?></option>
							<option value="NCAA D2" <?php selected($atts['division'], 'NCAA D2'); ?>><?php _e('NCAA D2', 'csd-manager'); ?></option>
							<option value="NCAA D3" <?php selected($atts['division'], 'NCAA D3'); ?>><?php _e('NCAA D3', 'csd-manager'); ?></option>
							<option value="NAIA" <?php selected($atts['division'], 'NAIA'); ?>><?php _e('NAIA', 'csd-manager'); ?></option>
							<option value="NJCAA" <?php selected($atts['division'], 'NJCAA'); ?>><?php _e('NJCAA', 'csd-manager'); ?></option>
						</select>
					</div>
					<?php endif; ?>
					
					<?php if ($atts['show_state_filter'] || $atts['show_division_filter']): ?>
					<div class="csd-filter-buttons">
						<button type="button" class="csd-school-filter-btn"><?php _e('Filter', 'csd-manager'); ?></button>
						<button type="button" class="csd-school-filter-reset"><?php _e('Reset', 'csd-manager'); ?></button>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
			
			<div class="csd-schools-list-container">
				<table class="csd-schools-table">
					<thead>
						<tr>
							<th class="csd-sortable" data-sort="school_name"><?php _e('School Name', 'csd-manager'); ?></th>
							<?php if ($atts['show_city']): ?>
							<th class="csd-sortable" data-sort="city"><?php _e('City', 'csd-manager'); ?></th>
							<?php endif; ?>
							<?php if ($atts['show_state']): ?>
							<th class="csd-sortable" data-sort="state"><?php _e('State', 'csd-manager'); ?></th>
							<?php endif; ?>
							<?php if ($atts['show_division']): ?>
							<th class="csd-sortable" data-sort="school_divisions"><?php _e('Division', 'csd-manager'); ?></th>
							<?php endif; ?>
							<?php if ($atts['show_mascot']): ?>
							<th><?php _e('Mascot', 'csd-manager'); ?></th>
							<?php endif; ?>
							<?php if ($atts['show_website']): ?>
							<th><?php _e('Website', 'csd-manager'); ?></th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody class="csd-schools-list">
						<tr>
							<td colspan="6"><?php _e('Loading schools...', 'csd-manager'); ?></td>
						</tr>
					</tbody>
				</table>
				
				<div class="csd-pagination">
					<div class="csd-pagination-counts">
						<span class="csd-showing-schools"></span>
					</div>
					<div class="csd-pagination-links csd-school-pagination">
						<!-- Pagination will be inserted here via JS -->
					</div>
				</div>
			</div>
		</div>
		
		<script type="text/javascript">
			(function($) {
				$(document).ready(function() {
					var instanceId = '<?php echo esc_js($instance_id); ?>';
					var container = $('#' + instanceId);
					var perPage = <?php echo intval($atts['per_page']); ?>;
					var sortBy = '<?php echo esc_js($atts['sort_by']); ?>';
					var sortOrder = '<?php echo esc_js($atts['sort_order']); ?>';
					var showCity = <?php echo $atts['show_city'] ? 'true' : 'false'; ?>;
					var showState = <?php echo $atts['show_state'] ? 'true' : 'false'; ?>;
					var showDivision = <?php echo $atts['show_division'] ? 'true' : 'false'; ?>;
					var showMascot = <?php echo $atts['show_mascot'] ? 'true' : 'false'; ?>;
					var showWebsite = <?php echo $atts['show_website'] ? 'true' : 'false'; ?>;
					var currentPage = 1;
					var searchTerm = '';
					var stateFilter = '<?php echo esc_js($atts['state']); ?>';
					var divisionFilter = '<?php echo esc_js($atts['division']); ?>';
					
					// Load schools on page load
					loadSchools();
					
					// Handle sorting
					container.find('.csd-sortable').on('click', function() {
						var newSortBy = $(this).data('sort');
						
						if (sortBy === newSortBy) {
							sortOrder = (sortOrder === 'ASC') ? 'DESC' : 'ASC';
						} else {
							sortBy = newSortBy;
							sortOrder = 'ASC';
						}
						
						// Reset to first page
						currentPage = 1;
						
						// Update UI to show sort direction
						container.find('.csd-sortable').removeClass('sorted-asc sorted-desc');
						$(this).addClass(sortOrder === 'ASC' ? 'sorted-asc' : 'sorted-desc');
						
						loadSchools();
					});
					
					// Handle search
					container.find('.csd-school-search-btn').on('click', function() {
						searchTerm = container.find('.csd-school-search').val();
						currentPage = 1;
						loadSchools();
					});
					
					// Handle enter key in search field
					container.find('.csd-school-search').on('keypress', function(e) {
						if (e.which === 13) {
							searchTerm = $(this).val();
							currentPage = 1;
							loadSchools();
						}
					});
					
					// Handle filters
					container.find('.csd-school-filter-btn').on('click', function() {
						stateFilter = container.find('.csd-school-filter-state').val();
						divisionFilter = container.find('.csd-school-filter-division').val();
						currentPage = 1;
						loadSchools();
					});
					
					// Handle filter reset
					container.find('.csd-school-filter-reset').on('click', function() {
						container.find('.csd-school-filter-state').val('<?php echo esc_js($atts['state']); ?>');
						container.find('.csd-school-filter-division').val('<?php echo esc_js($atts['division']); ?>');
						container.find('.csd-school-search').val('');
						stateFilter = '<?php echo esc_js($atts['state']); ?>';
						divisionFilter = '<?php echo esc_js($atts['division']); ?>';
						searchTerm = '';
						currentPage = 1;
						loadSchools();
					});
					
					// Handle pagination clicks
					container.on('click', '.csd-page-number', function(e) {
						e.preventDefault();
						currentPage = parseInt($(this).data('page'));
						loadSchools();
					});
					
					// Load schools function
					function loadSchools() {
						container.find('.csd-schools-list').html('<tr><td colspan="6"><?php _e('Loading schools...', 'csd-manager'); ?></td></tr>');
						
						$.ajax({
							url: csd_ajax.ajax_url,
							type: 'POST',
							data: {
								action: 'csd_filter_schools',
								page: currentPage,
								per_page: perPage,
								sort_by: sortBy,
								sort_order: sortOrder,
								search: searchTerm,
								state: stateFilter,
								division: divisionFilter
							},
							success: function(response) {
								if (response.success) {
									var data = response.data;
									var schools = data.schools;
									var totalSchools = data.total;
									var totalPages = Math.ceil(totalSchools / perPage);
									
									// Clear the table
									container.find('.csd-schools-list').empty();
									
									if (schools.length === 0) {
										var colSpan = 1;
										if (showCity) colSpan++;
										if (showState) colSpan++;
										if (showDivision) colSpan++;
										if (showMascot) colSpan++;
										if (showWebsite) colSpan++;
										
										container.find('.csd-schools-list').html('<tr><td colspan="' + colSpan + '"><?php _e('No schools found.', 'csd-manager'); ?></td></tr>');
									} else {
										// Add each school to the table
										$.each(schools, function(index, school) {
											var row = '<tr>' +
												'<td><a href="<?php echo home_url(); ?>?csd_school=' + school.id + '">' + school.school_name + '</a></td>';
												
											if (showCity) {
												row += '<td>' + (school.city || '') + '</td>';
											}
											
											if (showState) {
												row += '<td>' + (school.state || '') + '</td>';
											}
											
											if (showDivision) {
												row += '<td>' + (school.school_divisions || '') + '</td>';
											}
											
											if (showMascot) {
												row += '<td>' + (school.mascot || '') + '</td>';
											}
											
											if (showWebsite) {
												row += '<td>' + (school.school_website ? '<a href="' + school.school_website + '" target="_blank">Website</a>' : '') + '</td>';
											}
											
											row += '</tr>';
											
											container.find('.csd-schools-list').append(row);
										});
										
										// Update showing count
										var start = ((currentPage - 1) * perPage) + 1;
										var end = Math.min(start + schools.length - 1, totalSchools);
										container.find('.csd-showing-schools').text('<?php _e('Showing', 'csd-manager'); ?> ' + start + ' <?php _e('to', 'csd-manager'); ?> ' + end + ' <?php _e('of', 'csd-manager'); ?> ' + totalSchools + ' <?php _e('schools', 'csd-manager'); ?>');
										
										// Update pagination
										updatePagination(totalPages);
									}
								} else {
									container.find('.csd-schools-list').html('<tr><td colspan="6"><?php _e('Error loading schools.', 'csd-manager'); ?></td></tr>');
									console.error(response.data.message);
								}
							},
							error: function() {
								container.find('.csd-schools-list').html('<tr><td colspan="6"><?php _e('Error loading schools.', 'csd-manager'); ?></td></tr>');
							}
						});
					}
					
					// Update pagination links
					function updatePagination(totalPages) {
						var paginationHtml = '';
						
						if (totalPages > 1) {
							// Previous button
							if (currentPage > 1) {
								paginationHtml += '<a href="#" class="csd-page-number" data-page="' + (currentPage - 1) + '">&laquo; <?php _e('Previous', 'csd-manager'); ?></a> ';
							}
							
							// Page numbers
							var startPage = Math.max(1, currentPage - 2);
							var endPage = Math.min(totalPages, startPage + 4);
							
							if (startPage > 1) {
								paginationHtml += '<a href="#" class="csd-page-number" data-page="1">1</a> ';
								if (startPage > 2) {
									paginationHtml += '<span class="csd-pagination-dots">...</span> ';
								}
							}
							
							for (var i = startPage; i <= endPage; i++) {
								if (i === currentPage) {
									paginationHtml += '<span class="csd-page-number current">' + i + '</span> ';
								} else {
									paginationHtml += '<a href="#" class="csd-page-number" data-page="' + i + '">' + i + '</a> ';
								}
							}
							
							if (endPage < totalPages) {
								if (endPage < totalPages - 1) {
									paginationHtml += '<span class="csd-pagination-dots">...</span> ';
								}
								paginationHtml += '<a href="#" class="csd-page-number" data-page="' + totalPages + '">' + totalPages + '</a> ';
							}
							
							// Next button
							if (currentPage < totalPages) {
								paginationHtml += '<a href="#" class="csd-page-number" data-page="' + (currentPage + 1) + '"><?php _e('Next', 'csd-manager'); ?> &raquo;</a>';
							}
						}
						
						container.find('.csd-school-pagination').html(paginationHtml);
					}
				});
			})(jQuery);
		</script>
		<?php
		
		// Return output buffer content
		return ob_get_clean();
	}
	
	/**
	 * Staff shortcode
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function staff_shortcode($atts) {
		$atts = shortcode_atts(array(
			'per_page' => 10,
			'sort_by' => 'full_name',
			'sort_order' => 'ASC',
			'school_id' => 0,
			'department' => '',
			'show_search' => 1,
			'show_school_filter' => 1,
			'show_department_filter' => 1,
			'show_title' => 1,
			'show_department' => 1,
			'show_school' => 1,
			'show_email' => 1,
			'show_phone' => 1
		), $atts);
		
		// Enqueue scripts and styles
		wp_enqueue_style('csd-frontend-styles');
		wp_enqueue_script('csd-frontend-scripts');
		
		// Localize script with AJAX URL
		wp_localize_script('csd-frontend-scripts', 'csd_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php')
		));
		
		// Generate unique ID for this instance
		$instance_id = 'csd-staff-' . uniqid();
		
		// Start output buffer
		ob_start();
		
		// Get schools for filter
		$wpdb = csd_db_connection();
		$schools = $wpdb->get_results("SELECT id, school_name FROM " . csd_table('schools') . " ORDER BY school_name");
		
		// Get departments for filter
		$departments = $wpdb->get_col("SELECT DISTINCT sport_department FROM " . csd_table('staff') . " WHERE sport_department != '' ORDER BY sport_department");
		
		?>
		<div class="csd-staff-container" id="<?php echo esc_attr($instance_id); ?>">
			<?php if ($atts['show_search'] || $atts['show_school_filter'] || $atts['show_department_filter']): ?>
			<div class="csd-filters">
				<?php if ($atts['show_search']): ?>
				<div class="csd-search-box">
					<input type="text" class="csd-staff-search" placeholder="<?php _e('Search staff...', 'csd-manager'); ?>">
					<button type="button" class="csd-staff-search-btn"><?php _e('Search', 'csd-manager'); ?></button>
				</div>
				<?php endif; ?>
				
				<div class="csd-filter-controls">
					<?php if ($atts['show_school_filter']): ?>
					<div class="csd-filter-field">
						<label for="<?php echo esc_attr($instance_id); ?>-school"><?php _e('School:', 'csd-manager'); ?></label>
						<select class="csd-staff-filter-school" id="<?php echo esc_attr($instance_id); ?>-school">
							<option value=""><?php _e('All Schools', 'csd-manager'); ?></option>
							<?php foreach ($schools as $school): ?>
								<option value="<?php echo esc_attr($school->id); ?>" <?php selected($atts['school_id'], $school->id); ?>><?php echo esc_html($school->school_name); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>
					
					<?php if ($atts['show_department_filter']): ?>
					<div class="csd-filter-field">
						<label for="<?php echo esc_attr($instance_id); ?>-department"><?php _e('Department:', 'csd-manager'); ?></label>
						<select class="csd-staff-filter-department" id="<?php echo esc_attr($instance_id); ?>-department">
							<option value=""><?php _e('All Departments', 'csd-manager'); ?></option>
							<?php foreach ($departments as $department): ?>
								<option value="<?php echo esc_attr($department); ?>" <?php selected($atts['department'], $department); ?>><?php echo esc_html($department); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>
					
					<?php if ($atts['show_school_filter'] || $atts['show_department_filter']): ?>
					<div class="csd-filter-buttons">
						<button type="button" class="csd-staff-filter-btn"><?php _e('Filter', 'csd-manager'); ?></button>
						<button type="button" class="csd-staff-filter-reset"><?php _e('Reset', 'csd-manager'); ?></button>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
			
			<div class="csd-staff-list-container">
				<table class="csd-staff-table">
					<thead>
						<tr>
							<th class="csd-sortable" data-sort="full_name"><?php _e('Name', 'csd-manager'); ?></th>
							<?php if ($atts['show_title']): ?>
							<th class="csd-sortable" data-sort="title"><?php _e('Title', 'csd-manager'); ?></th>
							<?php endif; ?>
							<?php if ($atts['show_department']): ?>
							<th class="csd-sortable" data-sort="sport_department"><?php _e('Department', 'csd-manager'); ?></th>
							<?php endif; ?>
							<?php if ($atts['show_school']): ?>
							<th class="csd-sortable" data-sort="school_name"><?php _e('School', 'csd-manager'); ?></th>
							<?php endif; ?>
							<?php if ($atts['show_email']): ?>
							<th><?php _e('Email', 'csd-manager'); ?></th>
							<?php endif; ?>
							<?php if ($atts['show_phone']): ?>
							<th><?php _e('Phone', 'csd-manager'); ?></th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody class="csd-staff-list">
						<tr>
							<td colspan="6"><?php _e('Loading staff...', 'csd-manager'); ?></td>
						</tr>
					</tbody>
				</table>
				
				<div class="csd-pagination">
					<div class="csd-pagination-counts">
						<span class="csd-showing-staff"></span>
					</div>
					<div class="csd-pagination-links csd-staff-pagination">
						<!-- Pagination will be inserted here via JS -->
					</div>
				</div>
			</div>
		</div>
		
		<script type="text/javascript">
			(function($) {
				$(document).ready(function() {
					var instanceId = '<?php echo esc_js($instance_id); ?>';
					var container = $('#' + instanceId);
					var perPage = <?php echo intval($atts['per_page']); ?>;
					var sortBy = '<?php echo esc_js($atts['sort_by']); ?>';
					var sortOrder = '<?php echo esc_js($atts['sort_order']); ?>';
					var showTitle = <?php echo $atts['show_title'] ? 'true' : 'false'; ?>;
					var showDepartment = <?php echo $atts['show_department'] ? 'true' : 'false'; ?>;
					var showSchool = <?php echo $atts['show_school'] ? 'true' : 'false'; ?>;
					var showEmail = <?php echo $atts['show_email'] ? 'true' : 'false'; ?>;
					var showPhone = <?php echo $atts['show_phone'] ? 'true' : 'false'; ?>;
					var currentPage = 1;
					var searchTerm = '';
					var schoolFilter = '<?php echo intval($atts['school_id']); ?>';
					var departmentFilter = '<?php echo esc_js($atts['department']); ?>';
					
					// Load staff on page load
					loadStaff();
					
					// Handle sorting
					container.find('.csd-sortable').on('click', function() {
						var newSortBy = $(this).data('sort');
						
						if (sortBy === newSortBy) {
							sortOrder = (sortOrder === 'ASC') ? 'DESC' : 'ASC';
						} else {
							sortBy = newSortBy;
							sortOrder = 'ASC';
						}
						
						// Reset to first page
						currentPage = 1;
						
						// Update UI to show sort direction
						container.find('.csd-sortable').removeClass('sorted-asc sorted-desc');
						$(this).addClass(sortOrder === 'ASC' ? 'sorted-asc' : 'sorted-desc');
						
						loadStaff();
					});
					
					// Handle search
					container.find('.csd-staff-search-btn').on('click', function() {
						searchTerm = container.find('.csd-staff-search').val();
						currentPage = 1;
						loadStaff();
					});
					
					// Handle enter key in search field
					container.find('.csd-staff-search').on('keypress', function(e) {
						if (e.which === 13) {
							searchTerm = $(this).val();
							currentPage = 1;
							loadStaff();
						}
					});
					
					// Handle filters
					container.find('.csd-staff-filter-btn').on('click', function() {
						schoolFilter = container.find('.csd-staff-filter-school').val();
						departmentFilter = container.find('.csd-staff-filter-department').val();
						currentPage = 1;
						loadStaff();
					});
					
					// Handle filter reset
					container.find('.csd-staff-filter-reset').on('click', function() {
						container.find('.csd-staff-filter-school').val('<?php echo intval($atts['school_id']); ?>');
						container.find('.csd-staff-filter-department').val('<?php echo esc_js($atts['department']); ?>');
						container.find('.csd-staff-search').val('');
						schoolFilter = '<?php echo intval($atts['school_id']); ?>';
						departmentFilter = '<?php echo esc_js($atts['department']); ?>';
						searchTerm = '';
						currentPage = 1;
						loadStaff();
					});
					
					// Handle pagination clicks
					container.on('click', '.csd-page-number', function(e) {
						e.preventDefault();
						currentPage = parseInt($(this).data('page'));
						loadStaff();
					});
					
					// Load staff function
					function loadStaff() {
						container.find('.csd-staff-list').html('<tr><td colspan="6"><?php _e('Loading staff...', 'csd-manager'); ?></td></tr>');
						
						$.ajax({
							url: csd_ajax.ajax_url,
							type: 'POST',
							data: {
								action: 'csd_filter_staff',
								page: currentPage,
								per_page: perPage,
								sort_by: sortBy,
								sort_order: sortOrder,
								search: searchTerm,
								school: schoolFilter,
								department: departmentFilter
							},
							success: function(response) {
								if (response.success) {
									var data = response.data;
									var staffMembers = data.staff;
									var totalStaff = data.total;
									var totalPages = Math.ceil(totalStaff / perPage);
									
									// Clear the table
									container.find('.csd-staff-list').empty();
									
									if (staffMembers.length === 0) {
										var colSpan = 1;
										if (showTitle) colSpan++;
										if (showDepartment) colSpan++;
										if (showSchool) colSpan++;
										if (showEmail) colSpan++;
										if (showPhone) colSpan++;
										
										container.find('.csd-staff-list').html('<tr><td colspan="' + colSpan + '"><?php _e('No staff members found.', 'csd-manager'); ?></td></tr>');
									} else {
										// Add each staff member to the table
										$.each(staffMembers, function(index, staff) {
											var row = '<tr>' +
												'<td>' + staff.full_name + '</td>';
												
											if (showTitle) {
												row += '<td>' + (staff.title || '') + '</td>';
											}
											
											if (showDepartment) {
												row += '<td>' + (staff.sport_department || '') + '</td>';
											}
											
											if (showSchool) {
												row += '<td>' + (staff.school_name || '') + '</td>';
											}
											
											if (showEmail) {
												row += '<td>' + (staff.email ? '<a href="mailto:' + staff.email + '">' + staff.email + '</a>' : '') + '</td>';
											}
											
											if (showPhone) {
												row += '<td>' + (staff.phone || '') + '</td>';
											}
											
											row += '</tr>';
											
											container.find('.csd-staff-list').append(row);
										});
										
										// Update showing count
										var start = ((currentPage - 1) * perPage) + 1;
										var end = Math.min(start + staffMembers.length - 1, totalStaff);
										container.find('.csd-showing-staff').text('<?php _e('Showing', 'csd-manager'); ?> ' + start + ' <?php _e('to', 'csd-manager'); ?> ' + end + ' <?php _e('of', 'csd-manager'); ?> ' + totalStaff + ' <?php _e('staff members', 'csd-manager'); ?>');
										
										// Update pagination
										updatePagination(totalPages);
									}
								} else {
									container.find('.csd-staff-list').html('<tr><td colspan="6"><?php _e('Error loading staff.', 'csd-manager'); ?></td></tr>');
									console.error(response.data.message);
								}
							},
							error: function() {
								container.find('.csd-staff-list').html('<tr><td colspan="6"><?php _e('Error loading staff.', 'csd-manager'); ?></td></tr>');
							}
						});
					}
					
					// Update pagination links
					function updatePagination(totalPages) {
						var paginationHtml = '';
						
						if (totalPages > 1) {
							// Previous button
							if (currentPage > 1) {
								paginationHtml += '<a href="#" class="csd-page-number" data-page="' + (currentPage - 1) + '">&laquo; <?php _e('Previous', 'csd-manager'); ?></a> ';
							}
							
							// Page numbers
							var startPage = Math.max(1, currentPage - 2);
							var endPage = Math.min(totalPages, startPage + 4);
							
							if (startPage > 1) {
								paginationHtml += '<a href="#" class="csd-page-number" data-page="1">1</a> ';
								if (startPage > 2) {
									paginationHtml += '<span class="csd-pagination-dots">...</span> ';
								}
							}
							
							for (var i = startPage; i <= endPage; i++) {
								if (i === currentPage) {
									paginationHtml += '<span class="csd-page-number current">' + i + '</span> ';
								} else {
									paginationHtml += '<a href="#" class="csd-page-number" data-page="' + i + '">' + i + '</a> ';
								}
							}
							
							if (endPage < totalPages) {
								if (endPage < totalPages - 1) {
									paginationHtml += '<span class="csd-pagination-dots">...</span> ';
								}
								paginationHtml += '<a href="#" class="csd-page-number" data-page="' + totalPages + '">' + totalPages + '</a> ';
							}
							
							// Next button
							if (currentPage < totalPages) {
								paginationHtml += '<a href="#" class="csd-page-number" data-page="' + (currentPage + 1) + '"><?php _e('Next', 'csd-manager'); ?> &raquo;</a>';
							}
						}
						
						container.find('.csd-staff-pagination').html(paginationHtml);
					}
				});
			})(jQuery);
		</script>
		<?php
		
		// Return output buffer content
		return ob_get_clean();
	}
	
	/**
	 * Single school shortcode
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function single_school_shortcode($atts) {
		$atts = shortcode_atts(array(
			'id' => 0,
			'show_info' => 1,
			'show_contact' => 1,
			'show_staff' => 1,
			'staff_sort_by' => 'full_name',
			'staff_sort_order' => 'ASC',
			'staff_show_title' => 1,
			'staff_show_department' => 1,
			'staff_show_email' => 1,
			'staff_show_phone' => 1
		), $atts);
		
		// Get school ID from URL if not specified
		$school_id = $atts['id'];
		
		if (!$school_id && isset($_GET['csd_school'])) {
			$school_id = intval($_GET['csd_school']);
		}
		
		if (!$school_id) {
			return '<p>' . __('School ID is required.', 'csd-manager') . '</p>';
		}
		
		// Get school data
		$wpdb = csd_db_connection();
		
		$school = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . csd_table('schools') . " WHERE id = %d",
			$school_id
		));
		
		if (!$school) {
			return '<p>' . __('School not found.', 'csd-manager') . '</p>';
		}
		
		// Enqueue scripts and styles
		wp_enqueue_style('csd-frontend-styles');
		
		// Start output buffer
		ob_start();
		
		?>
		<div class="csd-single-school">
			<div class="csd-school-header">
				<h2 class="csd-school-name"><?php echo esc_html($school->school_name); ?></h2>
				<?php if (!empty($school->mascot)): ?>
				<div class="csd-school-mascot"><?php echo esc_html($school->mascot); ?></div>
				<?php endif; ?>
			</div>
			
			<?php if ($atts['show_info']): ?>
			<div class="csd-school-section csd-school-info">
				<h3><?php _e('School Information', 'csd-manager'); ?></h3>
				
				<table class="csd-info-table">
					<?php if (!empty($school->city) || !empty($school->state)): ?>
					<tr>
						<th><?php _e('Location:', 'csd-manager'); ?></th>
						<td>
							<?php
							$location = array();
							
							if (!empty($school->city)) {
								$location[] = esc_html($school->city);
							}
							
							if (!empty($school->state)) {
								$location[] = esc_html($school->state);
							}
							
							echo implode(', ', $location);
							?>
						</td>
					</tr>
					<?php endif; ?>
					
					<?php if (!empty($school->street_address_line_1)): ?>
					<tr>
						<th><?php _e('Address:', 'csd-manager'); ?></th>
						<td>
							<?php
							$address = array();
							
							if (!empty($school->street_address_line_1)) {
								$address[] = esc_html($school->street_address_line_1);
							}
							
							if (!empty($school->street_address_line_2)) {
								$address[] = esc_html($school->street_address_line_2);
							}
							
							if (!empty($school->street_address_line_3)) {
								$address[] = esc_html($school->street_address_line_3);
							}
							
							$city_state_zip = array();
							
							if (!empty($school->city)) {
								$city_state_zip[] = esc_html($school->city);
							}
							
							if (!empty($school->state)) {
								$city_state_zip[] = esc_html($school->state);
							}
							
							if (!empty($school->zipcode)) {
								$city_state_zip[] = esc_html($school->zipcode);
							}
							
							if (!empty($city_state_zip)) {
								$address[] = implode(', ', $city_state_zip);
							}
							
							if (!empty($school->country) && $school->country !== 'USA') {
								$address[] = esc_html($school->country);
							}
							
							echo implode('<br>', $address);
							?>
						</td>
					</tr>
					<?php endif; ?>
					
					<?php if (!empty($school->school_divisions)): ?>
					<tr>
						<th><?php _e('Division:', 'csd-manager'); ?></th>
						<td><?php echo esc_html($school->school_divisions); ?></td>
					</tr>
					<?php endif; ?>
					
					<?php if (!empty($school->school_conferences)): ?>
					<tr>
						<th><?php _e('Conference:', 'csd-manager'); ?></th>
						<td><?php echo esc_html($school->school_conferences); ?></td>
					</tr>
					<?php endif; ?>
					
					<?php if (!empty($school->school_type)): ?>
					<tr>
						<th><?php _e('Type:', 'csd-manager'); ?></th>
						<td><?php echo esc_html($school->school_type); ?></td>
					</tr>
					<?php endif; ?>
					
					<?php if (!empty($school->school_enrollment)): ?>
					<tr>
						<th><?php _e('Enrollment:', 'csd-manager'); ?></th>
						<td><?php echo number_format($school->school_enrollment); ?></td>
					</tr>
					<?php endif; ?>
					
					<?php if (!empty($school->school_colors)): ?>
					<tr>
						<th><?php _e('Colors:', 'csd-manager'); ?></th>
						<td><?php echo esc_html($school->school_colors); ?></td>
					</tr>
					<?php endif; ?>
				</table>
			</div>
			<?php endif; ?>
			
			<?php if ($atts['show_contact']): ?>
			<div class="csd-school-section csd-school-contact">
				<h3><?php _e('Contact Information', 'csd-manager'); ?></h3>
				
				<table class="csd-info-table">
					<?php if (!empty($school->school_website)): ?>
					<tr>
						<th><?php _e('School Website:', 'csd-manager'); ?></th>
						<td><a href="<?php echo esc_url($school->school_website); ?>" target="_blank"><?php echo esc_html($school->school_website); ?></a></td>
					</tr>
					<?php endif; ?>
					
					<?php if (!empty($school->athletics_website)): ?>
					<tr>
						<th><?php _e('Athletics Website:', 'csd-manager'); ?></th>
						<td><a href="<?php echo esc_url($school->athletics_website); ?>" target="_blank"><?php echo esc_html($school->athletics_website); ?></a></td>
					</tr>
					<?php endif; ?>
					
					<?php if (!empty($school->athletics_phone)): ?>
					<tr>
						<th><?php _e('Athletics Phone:', 'csd-manager'); ?></th>
						<td><?php echo esc_html($school->athletics_phone); ?></td>
					</tr>
					<?php endif; ?>
				</table>
			</div>
			<?php endif; ?>
			
			<?php if ($atts['show_staff']): ?>
			<div class="csd-school-section csd-school-staff">
				<h3><?php _e('Staff Directory', 'csd-manager'); ?></h3>
				
				<?php
				// Get staff members
				$staff = $wpdb->get_results($wpdb->prepare("
					SELECT *
					FROM " . csd_table('staff') . " s
					JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
					WHERE ss.school_id = %d
					ORDER BY {$atts['staff_sort_by']} {$atts['staff_sort_order']}
				", $school_id));
				
				if (empty($staff)) {
					echo '<p>' . __('No staff members found for this school.', 'csd-manager') . '</p>';
				} else {
					?>
					<table class="csd-staff-table">
						<thead>
							<tr>
								<th><?php _e('Name', 'csd-manager'); ?></th>
								<?php if ($atts['staff_show_title']): ?>
								<th><?php _e('Title', 'csd-manager'); ?></th>
								<?php endif; ?>
								<?php if ($atts['staff_show_department']): ?>
								<th><?php _e('Department', 'csd-manager'); ?></th>
								<?php endif; ?>
								<?php if ($atts['staff_show_email']): ?>
								<th><?php _e('Email', 'csd-manager'); ?></th>
								<?php endif; ?>
								<?php if ($atts['staff_show_phone']): ?>
								<th><?php _e('Phone', 'csd-manager'); ?></th>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($staff as $staff_member): ?>
							<tr>
								<td><?php echo esc_html($staff_member->full_name); ?></td>
								<?php if ($atts['staff_show_title']): ?>
								<td><?php echo esc_html($staff_member->title); ?></td>
								<?php endif; ?>
								<?php if ($atts['staff_show_department']): ?>
								<td><?php echo esc_html($staff_member->sport_department); ?></td>
								<?php endif; ?>
								<?php if ($atts['staff_show_email']): ?>
								<td>
									<?php if (!empty($staff_member->email)): ?>
									<a href="mailto:<?php echo esc_attr($staff_member->email); ?>"><?php echo esc_html($staff_member->email); ?></a>
									<?php endif; ?>
								</td>
								<?php endif; ?>
								<?php if ($atts['staff_show_phone']): ?>
								<td><?php echo esc_html($staff_member->phone); ?></td>
								<?php endif; ?>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php
				}
				?>
			</div>
			<?php endif; ?>
		</div>
		<?php
		
		// Return output buffer content
		return ob_get_clean();
	}
	
	/**
	 * Saved view shortcode
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function saved_view_shortcode($atts) {
		$atts = shortcode_atts(array(
			'id' => 0
		), $atts);
		
		if (!$atts['id']) {
			return '<p>' . __('View ID is required.', 'csd-manager') . '</p>';
		}
		
		$wpdb = csd_db_connection();
		
		// Get view data
		$view = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . csd_table('shortcode_views') . " WHERE id = %d",
			$atts['id']
		));
		
		if (!$view) {
			return '<p>' . __('Saved view not found.', 'csd-manager') . '</p>';
		}
		
		// Parse view settings
		$view_settings = json_decode($view->view_settings, true);
		
		// Call appropriate shortcode based on view type
		if ($view->view_type === 'schools') {
			return $this->schools_shortcode($view_settings);
		} elseif ($view->view_type === 'staff') {
			return $this->staff_shortcode($view_settings);
		} elseif ($view->view_type === 'school') {
			return $this->single_school_shortcode($view_settings);
		}
		
		return '';
	}
	
	/**
	 * Helper function to log data safely
	 */
	private function safe_log($message, $data = null) {
		if ($data !== null) {
			if (is_array($data) || is_object($data)) {
				// Convert to string but limit size to avoid huge logs
				$str_data = substr(print_r($data, true), 0, 1000);
				if (strlen($str_data) >= 1000) {
					$str_data .= '... [truncated]';
				}
				error_log($message . ' ' . $str_data);
			} else {
				error_log($message . ' ' . $data);
			}
		} else {
			error_log($message);
		}
	}
	
	/**
	 * AJAX handler for saving a shortcode view
	 */
	public function ajax_save_shortcode_view() {
		try {
			// Check nonce
			if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
				wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
				return;
			}
			
			// Process form data
			if (!isset($_POST['form_data'])) {
				wp_send_json_error(array('message' => __('No form data received.', 'csd-manager')));
				return;
			}
			
			$form_data_raw = $_POST['form_data'];
			
			// Parse form data
			$form_data = array();
			parse_str($form_data_raw, $form_data);
			
			// Validate required fields
			if (empty($form_data['view_name'])) {
				wp_send_json_error(array('message' => __('View name is required.', 'csd-manager')));
				return;
			}
			
			if (empty($form_data['view_type'])) {
				wp_send_json_error(array('message' => __('View type is required.', 'csd-manager')));
				return;
			}
			
			// Set view type-specific settings
			$view_settings = array();
			
			if ($form_data['view_type'] === 'schools') {
				$view_settings = array(
					'per_page' => isset($form_data['schools_per_page']) ? intval($form_data['schools_per_page']) : 10,
					'sort_by' => isset($form_data['schools_sort_by']) ? sanitize_text_field($form_data['schools_sort_by']) : 'school_name',
					'sort_order' => isset($form_data['schools_sort_order']) ? sanitize_text_field($form_data['schools_sort_order']) : 'ASC',
					'show_search' => isset($form_data['schools_show_search']) ? 1 : 0,
					'show_state_filter' => isset($form_data['schools_show_state_filter']) ? 1 : 0,
					'show_division_filter' => isset($form_data['schools_show_division_filter']) ? 1 : 0,
					'show_city' => isset($form_data['schools_show_city']) ? 1 : 0,
					'show_state' => isset($form_data['schools_show_state']) ? 1 : 0,
					'show_division' => isset($form_data['schools_show_division']) ? 1 : 0,
					'show_website' => isset($form_data['schools_show_website']) ? 1 : 0,
					'show_mascot' => isset($form_data['schools_show_mascot']) ? 1 : 0,
					'state' => isset($form_data['schools_filter_state']) ? sanitize_text_field($form_data['schools_filter_state']) : '',
					'division' => isset($form_data['schools_filter_division']) ? sanitize_text_field($form_data['schools_filter_division']) : ''
				);
			} elseif ($form_data['view_type'] === 'staff') {
				$view_settings = array(
					'per_page' => isset($form_data['staff_per_page']) ? intval($form_data['staff_per_page']) : 10,
					'sort_by' => isset($form_data['staff_sort_by']) ? sanitize_text_field($form_data['staff_sort_by']) : 'full_name',
					'sort_order' => isset($form_data['staff_sort_order']) ? sanitize_text_field($form_data['staff_sort_order']) : 'ASC',
					'show_search' => isset($form_data['staff_show_search']) ? 1 : 0,
					'show_school_filter' => isset($form_data['staff_show_school_filter']) ? 1 : 0,
					'show_department_filter' => isset($form_data['staff_show_department_filter']) ? 1 : 0,
					'show_title' => isset($form_data['staff_show_title']) ? 1 : 0,
					'show_department' => isset($form_data['staff_show_department']) ? 1 : 0,
					'show_school' => isset($form_data['staff_show_school']) ? 1 : 0,
					'show_email' => isset($form_data['staff_show_email']) ? 1 : 0,
					'show_phone' => isset($form_data['staff_show_phone']) ? 1 : 0,
					'school_id' => isset($form_data['staff_filter_school']) ? intval($form_data['staff_filter_school']) : 0,
					'department' => isset($form_data['staff_filter_department']) ? sanitize_text_field($form_data['staff_filter_department']) : ''
				);
			} elseif ($form_data['view_type'] === 'school') {
				if (empty($form_data['single_school_id'])) {
					wp_send_json_error(array('message' => __('Please select a school for the single school view.', 'csd-manager')));
					return;
				}
				
				$view_settings = array(
					'id' => isset($form_data['single_school_id']) ? intval($form_data['single_school_id']) : 0,
					'show_info' => isset($form_data['school_show_info']) ? 1 : 0,
					'show_contact' => isset($form_data['school_show_contact']) ? 1 : 0,
					'show_staff' => isset($form_data['school_show_staff']) ? 1 : 0,
					'staff_sort_by' => isset($form_data['school_staff_sort_by']) ? sanitize_text_field($form_data['school_staff_sort_by']) : 'full_name',
					'staff_sort_order' => isset($form_data['school_staff_sort_order']) ? sanitize_text_field($form_data['school_staff_sort_order']) : 'ASC',
					'staff_show_title' => isset($form_data['school_staff_show_title']) ? 1 : 0,
					'staff_show_department' => isset($form_data['school_staff_show_department']) ? 1 : 0,
					'staff_show_email' => isset($form_data['school_staff_show_email']) ? 1 : 0,
					'staff_show_phone' => isset($form_data['school_staff_show_phone']) ? 1 : 0
				);
			}
			
			$wpdb = csd_db_connection();
			
			// Prepare data
			$view_id = isset($form_data['view_id']) ? intval($form_data['view_id']) : 0;
			$current_time = current_time('mysql');
			
			$view_data = array(
				'view_name' => sanitize_text_field($form_data['view_name']),
				'view_type' => sanitize_text_field($form_data['view_type']),
				'view_settings' => json_encode($view_settings),
				'shortcode' => '[csd_saved_view id="' . ($view_id ? $view_id : 'NEW_ID') . '"]',
				'date_created' => $current_time
			);
			
			// Insert or update
			if ($view_id > 0) {
				// Update existing view
				$result = $wpdb->update(
					csd_table('shortcode_views'),
					$view_data,
					array('id' => $view_id)
				);
				
				if ($result === false) {
					wp_send_json_error(array('message' => __('Error updating saved view.', 'csd-manager')));
					return;
				}
			} else {
				// Add new view
				$result = $wpdb->insert(
					csd_table('shortcode_views'),
					$view_data
				);
				
				if (!$result) {
					wp_send_json_error(array('message' => __('Error adding saved view.', 'csd-manager')));
					return;
				}
				
				$view_id = $wpdb->insert_id;
				
				// Update shortcode with the actual ID
				$wpdb->update(
					csd_table('shortcode_views'),
					array('shortcode' => '[csd_saved_view id="' . $view_id . '"]'),
					array('id' => $view_id)
				);
			}
			
			wp_send_json_success(array(
				'message' => __('Saved view saved successfully.', 'csd-manager'),
				'view_id' => $view_id
			));
		} catch (Exception $e) {
			wp_send_json_error(array('message' => __('Error: ', 'csd-manager') . $e->getMessage()));
		}
	}
	
	/**
	 * Create shortcode views table
	 */
	private function create_shortcode_views_table() {
		$wpdb = csd_db_connection();
		
		$table_name = csd_table('shortcode_views');
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			view_name varchar(255) NOT NULL,
			view_type varchar(50) NOT NULL,
			view_settings longtext NOT NULL,
			shortcode varchar(255) NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	/**
	 * AJAX handler for deleting a shortcode view
	 */
	public function ajax_delete_shortcode_view() {
		check_admin_referer('csd-ajax-nonce', 'nonce');
		
		if (!current_user_can('manage_csd')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
		}
		
		$view_id = isset($_POST['view_id']) ? intval($_POST['view_id']) : 0;
		
		if ($view_id <= 0) {
			wp_send_json_error(array('message' => __('Invalid view ID.', 'csd-manager')));
		}
		
		$wpdb = csd_db_connection();
		
		$result = $wpdb->delete(
			csd_table('shortcode_views'),
			array('id' => $view_id)
		);
		
		if (!$result) {
			wp_send_json_error(array('message' => __('Error deleting saved view.', 'csd-manager')));
		}
		
		wp_send_json_success(array(
			'message' => __('Saved view deleted successfully.', 'csd-manager')
		));
	}
	
	/**
	 * AJAX handler for getting shortcode views
	 */
	public function ajax_get_shortcode_views() {
		check_admin_referer('csd-ajax-nonce', 'nonce');
		
		if (!current_user_can('manage_csd')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
		}
		
		$wpdb = csd_db_connection();
		
		$views = $wpdb->get_results("
			SELECT *
			FROM " . csd_table('shortcode_views') . "
			ORDER BY view_name ASC
		");
		
		wp_send_json_success($views);
	}
	
	/**
	 * AJAX handler for filtering schools
	 */
	public function ajax_filter_schools() {
		// Get parameters
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
		$sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'school_name';
		$sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'ASC';
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		$state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
		$division = isset($_POST['division']) ? sanitize_text_field($_POST['division']) : '';
		
		// Validate sort by column
		$allowed_sort_columns = array(
			'school_name', 'city', 'state', 'school_divisions'
		);
		
		if (!in_array($sort_by, $allowed_sort_columns)) {
			$sort_by = 'school_name';
		}
		
		// Validate sort order
		if ($sort_order !== 'ASC' && $sort_order !== 'DESC') {
			$sort_order = 'ASC';
		}
		
		$wpdb = csd_db_connection();
		
		// Build the query
		$query = "SELECT * FROM " . csd_table('schools');
		
		$where_clauses = array();
		$query_args = array();
		
		// Add search condition
		if (!empty($search)) {
			$where_clauses[] = "(school_name LIKE %s OR city LIKE %s OR state LIKE %s)";
			$search_term = '%' . $wpdb->esc_like($search) . '%';
			$query_args[] = $search_term;
			$query_args[] = $search_term;
			$query_args[] = $search_term;
		}
		
		// Add state filter
		if (!empty($state)) {
			$where_clauses[] = "state = %s";
			$query_args[] = $state;
		}
		
		// Add division filter
		if (!empty($division)) {
			$where_clauses[] = "school_divisions LIKE %s";
			$query_args[] = '%' . $wpdb->esc_like($division) . '%';
		}
		
		// Combine where clauses
		if (!empty($where_clauses)) {
			$query .= " WHERE " . implode(" AND ", $where_clauses);
		}
		
		// Count total schools (before pagination)
		$count_query = "SELECT COUNT(*) FROM " . csd_table('schools');
		
		if (!empty($where_clauses)) {
			$count_query .= " WHERE " . implode(" AND ", $where_clauses);
		}
		
		$total_schools = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
		
		// Add order and pagination
		$query .= " ORDER BY {$sort_by} {$sort_order}";
		$query .= " LIMIT %d OFFSET %d";
		$query_args[] = $per_page;
		$query_args[] = ($page - 1) * $per_page;
		
		// Get schools
		$schools = $wpdb->get_results($wpdb->prepare($query, $query_args));
		
		// Send response
		wp_send_json_success(array(
			'schools' => $schools,
			'total' => intval($total_schools)
		));
	}
	
	/**
	 * AJAX handler for filtering staff
	 */
	public function ajax_filter_staff() {
		// Get parameters
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
		$sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'full_name';
		$sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'ASC';
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		$school = isset($_POST['school']) ? intval($_POST['school']) : 0;
		$department = isset($_POST['department']) ? sanitize_text_field($_POST['department']) : '';
		
		// Validate sort by column
		$allowed_sort_columns = array(
			'full_name', 'title', 'sport_department', 'school_name'
		);
		
		if (!in_array($sort_by, $allowed_sort_columns)) {
			$sort_by = 'full_name';
		}
		
		// Special handling for school_name sorting
		if ($sort_by === 'school_name') {
			$sort_by = 'sch.school_name';
		} else {
			$sort_by = 's.' . $sort_by;
		}
		
		// Validate sort order
		if ($sort_order !== 'ASC' && $sort_order !== 'DESC') {
			$sort_order = 'ASC';
		}
		
		$wpdb = csd_db_connection();
		
		// Build the query
		$query = "SELECT s.*, sch.school_name, sch.id as school_id
				  FROM " . csd_table('staff') . " s
				  LEFT JOIN " . csd_table('school.staff') . " ss ON s.id = ss.staff_id
				  LEFT JOIN " . csd_table('schools') . " sch ON ss.school_id = sch.id";
		
		$where_clauses = array();
		$query_args = array();
		
		// Add search condition
		if (!empty($search)) {
			$where_clauses[] = "(s.full_name LIKE %s OR s.title LIKE %s OR s.sport_department LIKE %s OR sch.school_name LIKE %s)";
			$search_term = '%' . $wpdb->esc_like($search) . '%';
			$query_args[] = $search_term;
			$query_args[] = $search_term;
			$query_args[] = $search_term;
			$query_args[] = $search_term;
		}
		
		// Add school filter
		if (!empty($school)) {
			$where_clauses[] = "sch.id = %d";
			$query_args[] = $school;
		}
		
		// Add department filter
		if (!empty($department)) {
			$where_clauses[] = "s.sport_department = %s";
			$query_args[] = $department;
		}
		
		// Combine where clauses
		if (!empty($where_clauses)) {
			$query .= " WHERE " . implode(" AND ", $where_clauses);
		}
		
		// Count total staff (before pagination)
		$count_query = "SELECT COUNT(DISTINCT s.id) 
						FROM " . csd_table('staff') . " s
						LEFT JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
						LEFT JOIN " . csd_table('schools') . " sch ON ss.school_id = sch.id";
		
		if (!empty($where_clauses)) {
			$count_query .= " WHERE " . implode(" AND ", $where_clauses);
		}
		
		$total_staff = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
		
		// Add order and pagination
		$query .= " ORDER BY {$sort_by} {$sort_order}";
		$query .= " LIMIT %d OFFSET %d";
		$query_args[] = $per_page;
		$query_args[] = ($page - 1) * $per_page;
		
		// Get staff
		$staff = $wpdb->get_results($wpdb->prepare($query, $query_args));
		
		// Send response
		wp_send_json_success(array(
			'staff' => $staff,
			'total' => intval($total_staff)
		));
	}
}