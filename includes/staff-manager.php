<?php
/**
 * Staff Manager
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Staff Manager Class
 */
class CSD_Staff_Manager {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('wp_ajax_csd_get_staff', array($this, 'ajax_get_staff'));
		add_action('wp_ajax_csd_save_staff', array($this, 'ajax_save_staff'));
		add_action('wp_ajax_csd_delete_staff', array($this, 'ajax_delete_staff'));
		add_action('wp_ajax_csd_get_schools_dropdown', array($this, 'ajax_get_schools_dropdown'));
	}
	
	/**
	 * Render staff page
	 */
	public function render_page() {
		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
		
		switch ($action) {
			case 'add':
				$this->render_add_edit_page();
				break;
				
			case 'edit':
				$this->render_add_edit_page();
				break;
				
			case 'view':
				$this->render_view_page();
				break;
				
			default:
				$this->render_list_page();
				break;
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
	 * Render staff list page
	 */
	private function render_list_page() {
		$this->enqueue_page_scripts();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Staff', 'csd-manager'); ?></h1>
			<a href="<?php echo admin_url('admin.php?page=csd-staff&action=add'); ?>" class="page-title-action"><?php _e('Add New', 'csd-manager'); ?></a>
			<hr class="wp-header-end">
			
			<div class="csd-filters">
				<div class="csd-search-box">
					<input type="text" id="csd-staff-search" placeholder="<?php _e('Search staff...', 'csd-manager'); ?>">
					<button type="button" id="csd-staff-search-btn" class="button"><?php _e('Search', 'csd-manager'); ?></button>
				</div>
				
				<div class="csd-filter-controls">
					<select id="csd-staff-filter-school">
						<option value=""><?php _e('All Schools', 'csd-manager'); ?></option>
						<?php
						$wpdb = csd_db_connection();
						$schools = $wpdb->get_results("SELECT id, school_name FROM " . csd_table('schools') . " ORDER BY school_name");
						
						foreach ($schools as $school) {
							echo '<option value="' . esc_attr($school->id) . '">' . esc_html($school->school_name) . '</option>';
						}
						?>
					</select>
					
					<select id="csd-staff-filter-sport">
						<option value=""><?php _e('All Sports/Departments', 'csd-manager'); ?></option>
						<?php
						$departments = $wpdb->get_col("SELECT DISTINCT sport_department FROM " . csd_table('staff') . " WHERE sport_department != '' ORDER BY sport_department");
						
						foreach ($departments as $department) {
							echo '<option value="' . esc_attr($department) . '">' . esc_html($department) . '</option>';
						}
						?>
					</select>
					
					<button type="button" id="csd-staff-filter-btn" class="button"><?php _e('Apply Filters', 'csd-manager'); ?></button>
					<button type="button" id="csd-staff-filter-reset" class="button"><?php _e('Reset', 'csd-manager'); ?></button>
				</div>
			</div>
			
			<div class="csd-table-container">
				<table class="wp-list-table widefat fixed striped csd-staff-table">
					<thead>
						<tr>
							<th class="sortable" data-sort="full_name"><?php _e('Name', 'csd-manager'); ?></th>
							<th class="sortable" data-sort="title"><?php _e('Title', 'csd-manager'); ?></th>
							<th class="sortable" data-sort="sport_department"><?php _e('Sport/Department', 'csd-manager'); ?></th>
							<th class="sortable" data-sort="school_name"><?php _e('School', 'csd-manager'); ?></th>
							<th><?php _e('Email', 'csd-manager'); ?></th>
							<th><?php _e('Phone', 'csd-manager'); ?></th>
							<th><?php _e('Actions', 'csd-manager'); ?></th>
						</tr>
					</thead>
					<tbody id="csd-staff-list">
						<tr>
							<td colspan="7"><?php _e('Loading staff...', 'csd-manager'); ?></td>
						</tr>
					</tbody>
				</table>
				
				<div class="csd-pagination">
					<div class="csd-pagination-counts">
						<span id="csd-showing-staff"></span>
					</div>
					<div class="csd-pagination-links" id="csd-staff-pagination">
						<!-- Pagination will be inserted here via JS -->
					</div>
				</div>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Check if csd_ajax is defined and add a fallback if not
				if (typeof csd_ajax === 'undefined') {
					window.csd_ajax = {
						ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
						nonce: '<?php echo wp_create_nonce('csd-ajax-nonce'); ?>'
					};
				}
				
				var currentPage = 1;
				var perPage = 20;
				var sortBy = 'full_name';
				var sortOrder = 'ASC';
				var searchTerm = '';
				var schoolFilter = '';
				var sportFilter = '';
				
				// Load staff on page load
				loadStaff();
				
				// Handle sorting
				$('.sortable').on('click', function() {
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
					$('.sortable').removeClass('sorted-asc sorted-desc');
					$(this).addClass(sortOrder === 'ASC' ? 'sorted-asc' : 'sorted-desc');
					
					loadStaff();
				});
				
				// Handle search
				$('#csd-staff-search-btn').on('click', function() {
					searchTerm = $('#csd-staff-search').val();
					currentPage = 1;
					loadStaff();
				});
				
				// Handle enter key in search field
				$('#csd-staff-search').on('keypress', function(e) {
					if (e.which === 13) {
						searchTerm = $(this).val();
						currentPage = 1;
						loadStaff();
					}
				});
				
				// Handle filters
				$('#csd-staff-filter-btn').on('click', function() {
					schoolFilter = $('#csd-staff-filter-school').val();
					sportFilter = $('#csd-staff-filter-sport').val();
					currentPage = 1;
					loadStaff();
				});
				
				// Handle filter reset
				$('#csd-staff-filter-reset').on('click', function() {
					$('#csd-staff-filter-school').val('');
					$('#csd-staff-filter-sport').val('');
					$('#csd-staff-search').val('');
					schoolFilter = '';
					sportFilter = '';
					searchTerm = '';
					currentPage = 1;
					loadStaff();
				});
				
				// Handle pagination clicks
				$(document).on('click', '.csd-page-number', function(e) {
					e.preventDefault();
					currentPage = parseInt($(this).data('page'));
					loadStaff();
				});
				
				// Handle delete staff
				$(document).on('click', '.csd-delete-staff', function(e) {
					e.preventDefault();
					
					if (confirm('<?php _e('Are you sure you want to delete this staff member?', 'csd-manager'); ?>')) {
						var staffId = $(this).data('id');
						
						$.ajax({
							url: csd_ajax.ajax_url,
							type: 'POST',
							data: {
								action: 'csd_delete_staff',
								staff_id: staffId,
								nonce: csd_ajax.nonce
							},
							success: function(response) {
								if (response.success) {
									// Reload the staff list
									loadStaff();
								} else {
									alert(response.data.message);
								}
							}
						});
					}
				});
				
				// Load staff function
				function loadStaff() {
					$('#csd-staff-list').html('<tr><td colspan="7"><?php _e('Loading staff...', 'csd-manager'); ?></td></tr>');
					
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'csd_get_staff',
							page: currentPage,
							per_page: perPage,
							sort_by: sortBy,
							sort_order: sortOrder,
							search: searchTerm,
							school: schoolFilter,
							sport: sportFilter,
							nonce: csd_ajax.nonce
						},
						success: function(response) {
							if (response.success) {
								var data = response.data;
								var staffMembers = data.staff;
								var totalStaff = data.total;
								var totalPages = Math.ceil(totalStaff / perPage);
								
								// Clear the table
								$('#csd-staff-list').empty();
								
								if (staffMembers.length === 0) {
									$('#csd-staff-list').html('<tr><td colspan="7"><?php _e('No staff members found.', 'csd-manager'); ?></td></tr>');
								} else {
									// Add each staff member to the table
									$.each(staffMembers, function(index, staff) {
										var row = '<tr>' +
											'<td><a href="?page=csd-staff&action=edit&id=' + staff.id + '">' + staff.full_name + '</a></td>' +
											'<td>' + (staff.title || '') + '</td>' +
											'<td>' + (staff.sport_department || '') + '</td>' +
											'<td>' + (staff.school_name || '<em><?php _e('None', 'csd-manager'); ?></em>') + '</td>' +
											'<td>' + (staff.email ? '<a href="mailto:' + staff.email + '">' + staff.email + '</a>' : '') + '</td>' +
											'<td>' + (staff.phone || '') + '</td>' +
											'<td>' +
												'<a href="?page=csd-staff&action=view&id=' + staff.id + '" class="button button-small"><?php _e('View', 'csd-manager'); ?></a> ' +
												'<a href="?page=csd-staff&action=edit&id=' + staff.id + '" class="button button-small"><?php _e('Edit', 'csd-manager'); ?></a> ' +
												'<a href="#" class="button button-small csd-delete-staff" data-id="' + staff.id + '"><?php _e('Delete', 'csd-manager'); ?></a>' +
											'</td>' +
										'</tr>';
										
										$('#csd-staff-list').append(row);
									});
									
									// Update showing count
									var start = ((currentPage - 1) * perPage) + 1;
									var end = Math.min(start + staffMembers.length - 1, totalStaff);
									$('#csd-showing-staff').text('<?php _e('Showing', 'csd-manager'); ?> ' + start + ' <?php _e('to', 'csd-manager'); ?> ' + end + ' <?php _e('of', 'csd-manager'); ?> ' + totalStaff + ' <?php _e('staff members', 'csd-manager'); ?>');
									
									// Update pagination
									updatePagination(totalPages);
								}
							} else {
								$('#csd-staff-list').html('<tr><td colspan="7"><?php _e('Error loading staff.', 'csd-manager'); ?></td></tr>');
								console.error(response.data.message);
							}
						},
						error: function() {
							$('#csd-staff-list').html('<tr><td colspan="7"><?php _e('Error loading staff.', 'csd-manager'); ?></td></tr>');
						}
					});
				}
				
				// Update pagination links
				function updatePagination(totalPages) {
					var paginationHtml = '';
					
					if (totalPages > 1) {
						// Previous button
						if (currentPage > 1) {
							paginationHtml += '<a href="#" class="csd-page-number button" data-page="' + (currentPage - 1) + '">&laquo; <?php _e('Previous', 'csd-manager'); ?></a> ';
						}
						
						// Page numbers
						var startPage = Math.max(1, currentPage - 2);
						var endPage = Math.min(totalPages, startPage + 4);
						
						if (startPage > 1) {
							paginationHtml += '<a href="#" class="csd-page-number button" data-page="1">1</a> ';
							if (startPage > 2) {
								paginationHtml += '<span class="csd-pagination-dots">...</span> ';
							}
						}
						
						for (var i = startPage; i <= endPage; i++) {
							if (i === currentPage) {
								paginationHtml += '<span class="csd-page-number button button-primary">' + i + '</span> ';
							} else {
								paginationHtml += '<a href="#" class="csd-page-number button" data-page="' + i + '">' + i + '</a> ';
							}
						}
						
						if (endPage < totalPages) {
							if (endPage < totalPages - 1) {
								paginationHtml += '<span class="csd-pagination-dots">...</span> ';
							}
							paginationHtml += '<a href="#" class="csd-page-number button" data-page="' + totalPages + '">' + totalPages + '</a> ';
						}
						
						// Next button
						if (currentPage < totalPages) {
							paginationHtml += '<a href="#" class="csd-page-number button" data-page="' + (currentPage + 1) + '"><?php _e('Next', 'csd-manager'); ?> &raquo;</a>';
						}
					}
					
					$('#csd-staff-pagination').html(paginationHtml);
				}
			});
		</script>
		<?php
	}
	
	/**
	 * Render add/edit staff page
	 */
	private function render_add_edit_page() {
		$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;
		$is_edit = $staff_id > 0;
		$staff = null;
		$staff_school_id = 0;
		
		if ($is_edit) {
			$wpdb = csd_db_connection();
			$staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . csd_table('staff') . " WHERE id = %d", $staff_id));
			
			if (!$staff) {
				wp_die(__('Staff member not found.', 'csd-manager'));
			}
			
			// Get associated school
			$staff_school = $wpdb->get_row($wpdb->prepare("
				SELECT ss.school_id, s.school_name
				FROM " . csd_table('school_staff') . " ss
				JOIN " . csd_table('schools') . " s ON ss.school_id = s.id
				WHERE ss.staff_id = %d
			", $staff_id));
			
			if ($staff_school) {
				$staff_school_id = $staff_school->school_id;
			}
		}
		
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo $is_edit ? __('Edit Staff Member', 'csd-manager') : __('Add New Staff Member', 'csd-manager'); ?>
			</h1>
			<a href="<?php echo admin_url('admin.php?page=csd-staff'); ?>" class="page-title-action"><?php _e('Back to Staff', 'csd-manager'); ?></a>
			<hr class="wp-header-end">
			
			<form id="csd-staff-form" class="csd-form">
				<?php wp_nonce_field('csd_save_staff', 'csd_staff_nonce'); ?>
				<input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
				
				<div class="csd-form-section">
					<h2><?php _e('Staff Information', 'csd-manager'); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="full_name"><?php _e('Full Name', 'csd-manager'); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="full_name" name="full_name" value="<?php echo $is_edit ? esc_attr($staff->full_name) : ''; ?>" class="regular-text" required>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="title"><?php _e('Title', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="title" name="title" value="<?php echo $is_edit ? esc_attr($staff->title) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="sport_department"><?php _e('Sport/Department', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="sport_department" name="sport_department" value="<?php echo $is_edit ? esc_attr($staff->sport_department) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="email"><?php _e('Email', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="email" id="email" name="email" value="<?php echo $is_edit ? esc_attr($staff->email) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="phone"><?php _e('Phone', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="phone" name="phone" value="<?php echo $is_edit ? esc_attr($staff->phone) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="school_id"><?php _e('Associated School', 'csd-manager'); ?></label>
							</th>
							<td>
								<select id="school_id" name="school_id" class="regular-text">
									<option value=""><?php _e('Select a school', 'csd-manager'); ?></option>
									<?php
									$wpdb = csd_db_connection();
									
									// If we have a pre-selected school (from URL)
									if ($school_id > 0) {
										$selected_school = $wpdb->get_row($wpdb->prepare("
											SELECT id, school_name FROM " . csd_table('schools') . " WHERE id = %d
										", $school_id));
										
										if ($selected_school) {
											echo '<option value="' . esc_attr($selected_school->id) . '" selected>' . 
												esc_html($selected_school->school_name) . 
											'</option>';
										}
									} 
									// If this is an edit and we have an associated school
									elseif ($is_edit && $staff_school_id > 0) {
										$selected_school = $wpdb->get_row($wpdb->prepare("
											SELECT id, school_name FROM " . csd_table('schools') . " WHERE id = %d
										", $staff_school_id));
										
										if ($selected_school) {
											echo '<option value="' . esc_attr($selected_school->id) . '" selected>' . 
												esc_html($selected_school->school_name) . 
											'</option>';
										}
									} else {
										// Load top 20 schools for initial dropdown, rest will load via AJAX search
										$schools = $wpdb->get_results("
											SELECT id, school_name FROM " . csd_table('schools') . " 
											ORDER BY school_name ASC LIMIT 20
										");
										
										foreach ($schools as $school) {
											echo '<option value="' . esc_attr($school->id) . '">' . 
												esc_html($school->school_name) . 
											'</option>';
										}
									}
									?>
								</select>
								<p class="description"><?php _e('Start typing to search for a school', 'csd-manager'); ?></p>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="csd-form-submit">
					<button type="submit" class="button button-primary"><?php echo $is_edit ? __('Update Staff Member', 'csd-manager') : __('Add Staff Member', 'csd-manager'); ?></button>
					<a href="<?php echo admin_url('admin.php?page=csd-staff'); ?>" class="button"><?php _e('Cancel', 'csd-manager'); ?></a>
					
					<?php if ($is_edit): ?>
					<span class="csd-last-updated">
						<?php 
						echo sprintf(
							__('Last updated: %s', 'csd-manager'), 
							date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($staff->date_updated))
						); 
						?>
					</span>
					<?php endif; ?>
				</div>
			</form>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Initialize school search dropdown
				$('#school_id').select2({
					placeholder: '<?php _e('Select a school', 'csd-manager'); ?>',
					allowClear: true,
					minimumInputLength: 2,
					ajax: {
						url: csd_ajax.ajax_url,
						dataType: 'json',
						delay: 250,
						data: function(params) {
							return {
								action: 'csd_get_schools_dropdown',
								search: params.term,
								nonce: csd_ajax.nonce
							};
						},
						processResults: function(data) {
							return {
								results: data.data
							};
						},
						cache: true
					}
				});
				
				// Form submission
				$('#csd-staff-form').on('submit', function(e) {
					e.preventDefault();
					
					var formData = $(this).serialize();
					
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'csd_save_staff',
							form_data: formData,
							nonce: csd_ajax.nonce
						},
						beforeSend: function() {
							$('.csd-form-submit button').prop('disabled', true).text('<?php _e('Saving...', 'csd-manager'); ?>');
						},
						success: function(response) {
							if (response.success) {
								// Redirect to staff list or edit page
								if (response.data.redirect) {
									window.location.href = response.data.redirect;
								} else {
									window.location.href = '<?php echo admin_url('admin.php?page=csd-staff'); ?>';
								}
							} else {
								alert(response.data.message);
								$('.csd-form-submit button').prop('disabled', false).text('<?php echo $is_edit ? __('Update Staff Member', 'csd-manager') : __('Add Staff Member', 'csd-manager'); ?>');
							}
						},
						error: function() {
							alert('<?php _e('An error occurred. Please try again.', 'csd-manager'); ?>');
							$('.csd-form-submit button').prop('disabled', false).text('<?php echo $is_edit ? __('Update Staff Member', 'csd-manager') : __('Add Staff Member', 'csd-manager'); ?>');
						}
					});
				});
			});
		</script>
		<?php
	}
	
	/**
	 * Render view staff page
	 */
	private function render_view_page() {
		$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		
		if (!$staff_id) {
			wp_die(__('Invalid staff ID.', 'csd-manager'));
		}
		
		$wpdb = csd_db_connection();
		$staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . csd_table('staff') . " WHERE id = %d", $staff_id));
		
		if (!$staff) {
			wp_die(__('Staff member not found.', 'csd-manager'));
		}
		
		// Get associated school
		$school = $wpdb->get_row($wpdb->prepare("
			SELECT s.*
			FROM " . csd_table('schools') . " s
			JOIN " . csd_table('school_staff') . " ss ON s.id = ss.school_id
			WHERE ss.staff_id = %d
		", $staff_id));
		
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html($staff->full_name); ?></h1>
			<a href="<?php echo admin_url('admin.php?page=csd-staff'); ?>" class="page-title-action"><?php _e('Back to Staff', 'csd-manager'); ?></a>
			<a href="<?php echo admin_url('admin.php?page=csd-staff&action=edit&id=' . $staff_id); ?>" class="page-title-action"><?php _e('Edit Staff Member', 'csd-manager'); ?></a>
			<hr class="wp-header-end">
			
			<div class="csd-staff-details">
				<div class="csd-detail-section">
					<h2><?php _e('Staff Information', 'csd-manager'); ?></h2>
					
					<table class="widefat fixed striped">
						<tr>
							<th><?php _e('Name', 'csd-manager'); ?></th>
							<td><?php echo esc_html($staff->full_name); ?></td>
						</tr>
						<tr>
							<th><?php _e('Title', 'csd-manager'); ?></th>
							<td><?php echo !empty($staff->title) ? esc_html($staff->title) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('Sport/Department', 'csd-manager'); ?></th>
							<td><?php echo !empty($staff->sport_department) ? esc_html($staff->sport_department) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('Email', 'csd-manager'); ?></th>
							<td>
								<?php if (!empty($staff->email)): ?>
									<a href="mailto:<?php echo esc_attr($staff->email); ?>"><?php echo esc_html($staff->email); ?></a>
								<?php else: ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><?php _e('Phone', 'csd-manager'); ?></th>
							<td><?php echo !empty($staff->phone) ? esc_html($staff->phone) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('Added On', 'csd-manager'); ?></th>
							<td><?php echo date('M j, Y', strtotime($staff->date_created)); ?></td>
						</tr>
						<tr>
							<th><?php _e('Last Updated', 'csd-manager'); ?></th>
							<td><?php echo date('M j, Y', strtotime($staff->date_updated)); ?></td>
						</tr>
					</table>
				</div>
				
				<?php if ($school): ?>
				<div class="csd-detail-section">
					<h2><?php _e('Associated School', 'csd-manager'); ?></h2>
					
					<table class="widefat fixed striped">
						<tr>
							<th><?php _e('School Name', 'csd-manager'); ?></th>
							<td>
								<a href="<?php echo admin_url('admin.php?page=csd-schools&action=view&id=' . $school->id); ?>">
									<?php echo esc_html($school->school_name); ?>
								</a>
							</td>
						</tr>
						<tr>
							<th><?php _e('Location', 'csd-manager'); ?></th>
							<td>
								<?php
								$location_parts = array();
								
								if (!empty($school->city)) {
									$location_parts[] = esc_html($school->city);
								}
								
								if (!empty($school->state)) {
									$location_parts[] = esc_html($school->state);
								}
								
								echo implode(', ', $location_parts);
								?>
							</td>
						</tr>
						<tr>
							<th><?php _e('School Division', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->school_divisions) ? esc_html($school->school_divisions) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('School Website', 'csd-manager'); ?></th>
							<td>
								<?php if (!empty($school->school_website)): ?>
									<a href="<?php echo esc_url($school->school_website); ?>" target="_blank"><?php echo esc_html($school->school_website); ?></a>
								<?php else: ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><?php _e('Athletics Website', 'csd-manager'); ?></th>
							<td>
								<?php if (!empty($school->athletics_website)): ?>
									<a href="<?php echo esc_url($school->athletics_website); ?>" target="_blank"><?php echo esc_html($school->athletics_website); ?></a>
								<?php else: ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>
				<?php else: ?>
				<div class="csd-detail-section">
					<h2><?php _e('Associated School', 'csd-manager'); ?></h2>
					<p><?php _e('No school associated with this staff member.', 'csd-manager'); ?></p>
					
					<p>
						<a href="<?php echo admin_url('admin.php?page=csd-staff&action=edit&id=' . $staff_id); ?>" class="button">
							<?php _e('Assign a School', 'csd-manager'); ?>
						</a>
					</p>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * AJAX handler for getting staff
	 */
	public function ajax_get_staff() {
		// Add these debugging lines
		error_log('AJAX get_staff called: ' . print_r($_POST, true));
		
		// If the nonce check fails, provide more details
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			error_log('Nonce check failed: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'not set'));
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
			return;
		}
		
		// Get parameters
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
		$sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'full_name';
		$sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'ASC';
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		$school = isset($_POST['school']) ? intval($_POST['school']) : 0;
		$sport = isset($_POST['sport']) ? sanitize_text_field($_POST['sport']) : '';
		
		// Validate sort by column
		$allowed_sort_columns = array(
			'full_name', 'title', 'sport_department', 'email', 'phone', 'date_created', 'school_name'
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
				  LEFT JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
				  LEFT JOIN " . csd_table('schools') . " sch ON ss.school_id = sch.id";
		
		$where_clauses = array();
		$query_args = array();
		
		// Add search condition
		if (!empty($search)) {
			$where_clauses[] = "(s.full_name LIKE %s OR s.title LIKE %s OR s.email LIKE %s OR s.sport_department LIKE %s OR sch.school_name LIKE %s)";
			$search_term = '%' . $wpdb->esc_like($search) . '%';
			$query_args[] = $search_term;
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
		
		// Add sport filter
		if (!empty($sport)) {
			$where_clauses[] = "s.sport_department = %s";
			$query_args[] = $sport;
		}
		
		// Combine where clauses
		if (!empty($where_clauses)) {
			$query .= " WHERE " . implode(" AND ", $where_clauses);
		}
		
		// Add order by
		$query .= " ORDER BY {$sort_by} {$sort_order}";
		
		// Count total staff (before pagination)
		$count_query = "SELECT COUNT(DISTINCT s.id) 
						FROM " . csd_table('staff') . " s
						LEFT JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
						LEFT JOIN " . csd_table('schools') . " sch ON ss.school_id = sch.id";
		
		if (!empty($where_clauses)) {
			$count_query .= " WHERE " . implode(" AND ", $where_clauses);
		}
		
		$total_staff = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
		
		// Add pagination
		$offset = ($page - 1) * $per_page;
		$query .= " LIMIT %d OFFSET %d";
		$query_args[] = $per_page;
		$query_args[] = $offset;
		
		// Get staff
		$staff = $wpdb->get_results($wpdb->prepare($query, $query_args));
		
		// Send response
		wp_send_json_success(array(
			'staff' => $staff,
			'total' => intval($total_staff)
		));
	}
	
	/**
	 * AJAX handler for saving a staff member
	 */
	public function ajax_save_staff() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
		}
		
		// Parse form data
		parse_str($_POST['form_data'], $form_data);
		
		// Verify form nonce
		if (!isset($form_data['csd_staff_nonce']) || !wp_verify_nonce($form_data['csd_staff_nonce'], 'csd_save_staff')) {
			wp_send_json_error(array('message' => __('Form security check failed.', 'csd-manager')));
		}
		
		// Validate required fields
		if (empty($form_data['full_name'])) {
			wp_send_json_error(array('message' => __('Full name is required.', 'csd-manager')));
		}
		
		$wpdb = csd_db_connection();
		
		// Prepare data
		$staff_id = isset($form_data['staff_id']) ? intval($form_data['staff_id']) : 0;
		$school_id = isset($form_data['school_id']) ? intval($form_data['school_id']) : 0;
		$current_time = current_time('mysql');
		
		$staff_data = array(
			'full_name' => sanitize_text_field($form_data['full_name']),
			'title' => sanitize_text_field($form_data['title']),
			'sport_department' => sanitize_text_field($form_data['sport_department']),
			'email' => sanitize_email($form_data['email']),
			'phone' => sanitize_text_field($form_data['phone']),
			'date_updated' => $current_time
		);
		
		// Start transaction
		$wpdb->query('START TRANSACTION');
		
		try {
			// Insert or update staff
			if ($staff_id > 0) {
				// Update existing staff
				$result = $wpdb->update(
					csd_table('staff'),
					$staff_data,
					array('id' => $staff_id)
				);
				
				if ($result === false) {
					throw new Exception(__('Error updating staff member.', 'csd-manager'));
				}
			} else {
				// Add new staff
				$staff_data['date_created'] = $current_time;
				
				$result = $wpdb->insert(
					csd_table('staff'),
					$staff_data
				);
				
				if (!$result) {
					throw new Exception(__('Error adding staff member.', 'csd-manager'));
				}
				
				$staff_id = $wpdb->insert_id;
			}
			
			// Handle school association
			if ($staff_id > 0) {
				// First, remove existing association
				$wpdb->delete(
					csd_table('school_staff'),
					array('staff_id' => $staff_id)
				);
				
				// Then, add new association if school is selected
				if ($school_id > 0) {
					$result = $wpdb->insert(
						csd_table('school_staff'),
						array(
							'school_id' => $school_id,
							'staff_id' => $staff_id,
							'date_created' => $current_time
						)
					);
					
					if (!$result) {
						throw new Exception(__('Error associating staff with school.', 'csd-manager'));
					}
				}
			}
			
			// Commit transaction
			$wpdb->query('COMMIT');
			
			wp_send_json_success(array(
				'message' => __('Staff member saved successfully.', 'csd-manager'),
				'staff_id' => $staff_id,
				'redirect' => admin_url('admin.php?page=csd-staff&action=view&id=' . $staff_id)
			));
		} catch (Exception $e) {
			// Rollback transaction
			$wpdb->query('ROLLBACK');
			
			wp_send_json_error(array(
				'message' => $e->getMessage()
			));
		}
	}
	
	/**
	 * AJAX handler for deleting a staff member
	 */
	public function ajax_delete_staff() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
		}
		
		$staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
		
		if ($staff_id <= 0) {
			wp_send_json_error(array('message' => __('Invalid staff ID.', 'csd-manager')));
		}
		
		$wpdb = csd_db_connection();
		
		// Start transaction
		$wpdb->query('START TRANSACTION');
		
		try {
			// Delete school_staff relationships
			$wpdb->delete(
				csd_table('school_staff'),
				array('staff_id' => $staff_id)
			);
			
			// Delete staff
			$result = $wpdb->delete(
				csd_table('staff'),
				array('id' => $staff_id)
			);
			
			if ($result === false) {
				throw new Exception(__('Error deleting staff member.', 'csd-manager'));
			}
			
			// Commit transaction
			$wpdb->query('COMMIT');
			
			wp_send_json_success(array(
				'message' => __('Staff member deleted successfully.', 'csd-manager')
			));
		} catch (Exception $e) {
			// Rollback transaction
			$wpdb->query('ROLLBACK');
			
			wp_send_json_error(array(
				'message' => $e->getMessage()
			));
		}
	}
	
	/**
	 * AJAX handler for getting schools dropdown
	 */
	public function ajax_get_schools_dropdown() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
		}
		
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		
		$wpdb = csd_db_connection();
		
		$query = "SELECT id, school_name as text FROM " . csd_table('schools');
		$query_args = array();
		
		if (!empty($search)) {
			$query .= " WHERE school_name LIKE %s";
			$query_args[] = '%' . $wpdb->esc_like($search) . '%';
		}
		
		$query .= " ORDER BY school_name ASC LIMIT 20";
		
		$schools = $wpdb->get_results($wpdb->prepare($query, $query_args));
		
		wp_send_json_success($schools);
	}
}