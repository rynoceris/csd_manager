<?php
/**
 * Schools Manager
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Schools Manager Class
 */
class CSD_Schools_Manager {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('wp_ajax_csd_get_schools', array($this, 'ajax_get_schools'));
		add_action('wp_ajax_csd_save_school', array($this, 'ajax_save_school'));
		add_action('wp_ajax_csd_delete_school', array($this, 'ajax_delete_school'));
	}
	
	/**
	 * Render schools page
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
	 * Render schools list page
	 */
	private function render_list_page() {
		$this->enqueue_page_scripts();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Schools', 'csd-manager'); ?></h1>
			<a href="<?php echo admin_url('admin.php?page=csd-schools&action=add'); ?>" class="page-title-action"><?php _e('Add New', 'csd-manager'); ?></a>
			<hr class="wp-header-end">
			
			<div class="csd-filters">
				<div class="csd-search-box">
					<input type="text" id="csd-school-search" placeholder="<?php _e('Search schools...', 'csd-manager'); ?>">
					<button type="button" id="csd-school-search-btn" class="button"><?php _e('Search', 'csd-manager'); ?></button>
				</div>
				
				<div class="csd-filter-controls">
					<select id="csd-school-filter-state">
						<option value=""><?php _e('All States', 'csd-manager'); ?></option>
						<?php
						$wpdb = csd_db_connection();
						$states = $wpdb->get_col("SELECT DISTINCT state FROM " . csd_table('schools') . " ORDER BY state");
						
						foreach ($states as $state) {
							echo '<option value="' . esc_attr($state) . '">' . esc_html($state) . '</option>';
						}
						?>
					</select>
					
					<select id="csd-school-filter-division">
						<option value=""><?php _e('All Divisions', 'csd-manager'); ?></option>
						<option value="NCAA D1"><?php _e('NCAA D1', 'csd-manager'); ?></option>
						<option value="NCAA D2"><?php _e('NCAA D2', 'csd-manager'); ?></option>
						<option value="NCAA D3"><?php _e('NCAA D3', 'csd-manager'); ?></option>
						<option value="NAIA"><?php _e('NAIA', 'csd-manager'); ?></option>
						<option value="NJCAA"><?php _e('NJCAA', 'csd-manager'); ?></option>
					</select>
					
					<button type="button" id="csd-school-filter-btn" class="button"><?php _e('Apply Filters', 'csd-manager'); ?></button>
					<button type="button" id="csd-school-filter-reset" class="button"><?php _e('Reset', 'csd-manager'); ?></button>
				</div>
			</div>
			
			<div class="csd-table-container">
				<table class="wp-list-table widefat fixed striped csd-schools-table">
					<thead>
						<tr>
							<th class="sortable" data-sort="school_name"><?php _e('School Name', 'csd-manager'); ?></th>
							<th class="sortable" data-sort="city"><?php _e('City', 'csd-manager'); ?></th>
							<th class="sortable" data-sort="state"><?php _e('State', 'csd-manager'); ?></th>
							<th><?php _e('School Division', 'csd-manager'); ?></th>
							<th><?php _e('Website', 'csd-manager'); ?></th>
							<th><?php _e('Staff Count', 'csd-manager'); ?></th>
							<th><?php _e('Actions', 'csd-manager'); ?></th>
						</tr>
					</thead>
					<tbody id="csd-schools-list">
						<tr>
							<td colspan="7"><?php _e('Loading schools...', 'csd-manager'); ?></td>
						</tr>
					</tbody>
				</table>
				
				<div class="csd-pagination">
					<div class="csd-pagination-counts">
						<span id="csd-showing-schools"></span>
					</div>
					<div class="csd-pagination-links" id="csd-school-pagination">
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
				var sortBy = 'school_name';
				var sortOrder = 'ASC';
				var searchTerm = '';
				var stateFilter = '';
				var divisionFilter = '';
				
				// Load schools on page load
				loadSchools();
				
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
					
					loadSchools();
				});
				
				// Handle search
				$('#csd-school-search-btn').on('click', function() {
					searchTerm = $('#csd-school-search').val();
					currentPage = 1;
					loadSchools();
				});
				
				// Handle enter key in search field
				$('#csd-school-search').on('keypress', function(e) {
					if (e.which === 13) {
						searchTerm = $(this).val();
						currentPage = 1;
						loadSchools();
					}
				});
				
				// Handle filters
				$('#csd-school-filter-btn').on('click', function() {
					stateFilter = $('#csd-school-filter-state').val();
					divisionFilter = $('#csd-school-filter-division').val();
					currentPage = 1;
					loadSchools();
				});
				
				// Handle filter reset
				$('#csd-school-filter-reset').on('click', function() {
					$('#csd-school-filter-state').val('');
					$('#csd-school-filter-division').val('');
					$('#csd-school-search').val('');
					stateFilter = '';
					divisionFilter = '';
					searchTerm = '';
					currentPage = 1;
					loadSchools();
				});
				
				// Handle pagination clicks
				$(document).on('click', '.csd-page-number', function(e) {
					e.preventDefault();
					currentPage = parseInt($(this).data('page'));
					loadSchools();
				});
				
				// Handle delete school
				$(document).on('click', '.csd-delete-school', function(e) {
					e.preventDefault();
					
					if (confirm('<?php _e('Are you sure you want to delete this school? This will also remove all staff associations.', 'csd-manager'); ?>')) {
						var schoolId = $(this).data('id');
						
						$.ajax({
							url: csd_ajax.ajax_url,
							type: 'POST',
							data: {
								action: 'csd_delete_school',
								school_id: schoolId,
								nonce: csd_ajax.nonce
							},
							success: function(response) {
								if (response.success) {
									// Reload the schools list
									loadSchools();
								} else {
									alert(response.data.message);
								}
							}
						});
					}
				});
				
				// Load schools function
				function loadSchools() {
					$('#csd-schools-list').html('<tr><td colspan="7"><?php _e('Loading schools...', 'csd-manager'); ?></td></tr>');
					
					$.ajax({
						url: window.csd_ajax ? window.csd_ajax.ajax_url : '<?php echo admin_url('admin-ajax.php'); ?>',
						type: 'POST',
						data: {
							action: 'csd_get_schools',
							page: currentPage,
							per_page: perPage,
							sort_by: sortBy,
							sort_order: sortOrder,
							search: searchTerm,
							state: stateFilter,
							division: divisionFilter,
							nonce: window.csd_ajax ? window.csd_ajax.nonce : '<?php echo wp_create_nonce('csd-ajax-nonce'); ?>'
						},
						success: function(response) {
							console.log('AJAX response:', response);
							
							// Debug the response structure
							console.log('Response has success property:', response.hasOwnProperty('success'));
							console.log('Response success value:', response.success);
							console.log('Response has data property:', response.hasOwnProperty('data'));
							if (response.hasOwnProperty('data')) {
								console.log('Response data has schools property:', response.data.hasOwnProperty('schools'));
								console.log('Response data schools length:', response.data.schools ? response.data.schools.length : 'N/A');
								console.log('Response data has total property:', response.data.hasOwnProperty('total'));
							}
							
							if (response.success) {
								var data = response.data;
								var schools = data.schools;
								var totalSchools = data.total;
								var totalPages = Math.ceil(totalSchools / perPage);
								
								// Clear the table
								$('#csd-schools-list').empty();
								
								// Debug DOM update
								console.log('Found schools list element:', $('#csd-schools-list').length);
								console.log('Found schools table:', $('.csd-schools-table').length);
								
								if (schools.length === 0) {
									$('#csd-schools-list').html('<tr><td colspan="7"><?php _e('No schools found.', 'csd-manager'); ?></td></tr>');
									console.log('No schools found, message set');
								} else {
									console.log('About to add schools to table');
									
									// Add each school to the table
									$.each(schools, function(index, school) {
										console.log('Adding school:', school.school_name);
										
										var row = '<tr>' +
											'<td><a href="?page=csd-schools&action=edit&id=' + school.id + '">' + school.school_name + '</a></td>' +
											'<td>' + (school.city || '') + '</td>' +
											'<td>' + (school.state || '') + '</td>' +
											'<td>' + (school.school_divisions || '') + '</td>' +
											'<td>' + (school.school_website ? '<a href="' + school.school_website + '" target="_blank">' + school.school_website + '</a>' : '') + '</td>' +
											'<td>' + school.staff_count + '</td>' +
											'<td>' +
												'<a href="?page=csd-schools&action=view&id=' + school.id + '" class="button button-small"><?php _e('View', 'csd-manager'); ?></a> ' +
												'<a href="?page=csd-schools&action=edit&id=' + school.id + '" class="button button-small"><?php _e('Edit', 'csd-manager'); ?></a> ' +
												'<a href="#" class="button button-small csd-delete-school" data-id="' + school.id + '"><?php _e('Delete', 'csd-manager'); ?></a>' +
											'</td>' +
										'</tr>';
										
										$('#csd-schools-list').append(row);
									});
									
									console.log('Added all schools to table');
									
									// Update showing count
									var start = ((currentPage - 1) * perPage) + 1;
									var end = Math.min(start + schools.length - 1, totalSchools);
									$('#csd-showing-schools').text('<?php _e('Showing', 'csd-manager'); ?> ' + start + ' <?php _e('to', 'csd-manager'); ?> ' + end + ' <?php _e('of', 'csd-manager'); ?> ' + totalSchools + ' <?php _e('schools', 'csd-manager'); ?>');
									
									// Update pagination
									updatePagination(totalPages);
								}
							} else {
								$('#csd-schools-list').html('<tr><td colspan="7"><?php _e('Error loading schools.', 'csd-manager'); ?></td></tr>');
								console.error('Response indicates error:', response.data ? response.data.message : 'Unknown error');
							}
						},
						error: function(xhr, status, error) {
							$('#csd-schools-list').html('<tr><td colspan="7"><?php _e('Error loading schools.', 'csd-manager'); ?></td></tr>');
							console.error('AJAX error:', status, error, xhr.responseText);
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
					
					$('#csd-school-pagination').html(paginationHtml);
				}
			});
		</script>
		<?php
	}
	
	/**
	 * Render add/edit school page
	 */
	private function render_add_edit_page() {
		$school_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$is_edit = $school_id > 0;
		$school = null;
		
		if ($is_edit) {
			$wpdb = csd_db_connection();
			$school = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . csd_table('schools') . " WHERE id = %d", $school_id));
			
			if (!$school) {
				wp_die(__('School not found.', 'csd-manager'));
			}
		}
		
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo $is_edit ? __('Edit School', 'csd-manager') : __('Add New School', 'csd-manager'); ?>
			</h1>
			<a href="<?php echo admin_url('admin.php?page=csd-schools'); ?>" class="page-title-action"><?php _e('Back to Schools', 'csd-manager'); ?></a>
			<hr class="wp-header-end">
			
			<form id="csd-school-form" class="csd-form">
				<?php wp_nonce_field('csd_save_school', 'csd_school_nonce'); ?>
				<input type="hidden" name="school_id" value="<?php echo $school_id; ?>">
				
				<div class="csd-form-section">
					<h2><?php _e('School Information', 'csd-manager'); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="school_name"><?php _e('School Name', 'csd-manager'); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="school_name" name="school_name" value="<?php echo $is_edit ? esc_attr($school->school_name) : ''; ?>" class="regular-text" required>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="street_address_line_1"><?php _e('Street Address', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="street_address_line_1" name="street_address_line_1" value="<?php echo $is_edit ? esc_attr($school->street_address_line_1) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('Line 1', 'csd-manager'); ?></p>
								
								<input type="text" id="street_address_line_2" name="street_address_line_2" value="<?php echo $is_edit ? esc_attr($school->street_address_line_2) : ''; ?>" class="regular-text" style="margin-top: 5px;">
								<p class="description"><?php _e('Line 2', 'csd-manager'); ?></p>
								
								<input type="text" id="street_address_line_3" name="street_address_line_3" value="<?php echo $is_edit ? esc_attr($school->street_address_line_3) : ''; ?>" class="regular-text" style="margin-top: 5px;">
								<p class="description"><?php _e('Line 3', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="city"><?php _e('City', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="city" name="city" value="<?php echo $is_edit ? esc_attr($school->city) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="state"><?php _e('State', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="state" name="state" value="<?php echo $is_edit ? esc_attr($school->state) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="zipcode"><?php _e('Zip Code', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="zipcode" name="zipcode" value="<?php echo $is_edit ? esc_attr($school->zipcode) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="country"><?php _e('Country', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="country" name="country" value="<?php echo $is_edit ? esc_attr($school->country) : 'USA'; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="county"><?php _e('County', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="county" name="county" value="<?php echo $is_edit ? esc_attr($school->county) : ''; ?>" class="regular-text">
							</td>
						</tr>
					</table>
				</div>
				
				<div class="csd-form-section">
					<h2><?php _e('School Details', 'csd-manager'); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="school_divisions"><?php _e('School Divisions', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="school_divisions" name="school_divisions" value="<?php echo $is_edit ? esc_attr($school->school_divisions) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('E.g., NCAA D1, NCAA D2, NAIA, etc.', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="school_conferences"><?php _e('School Conferences', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="school_conferences" name="school_conferences" value="<?php echo $is_edit ? esc_attr($school->school_conferences) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="school_level"><?php _e('School Level', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="school_level" name="school_level" value="<?php echo $is_edit ? esc_attr($school->school_level) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('E.g., 4-Year, 2-Year, etc.', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="school_type"><?php _e('School Type', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="school_type" name="school_type" value="<?php echo $is_edit ? esc_attr($school->school_type) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('E.g., Public, Private, etc.', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="school_enrollment"><?php _e('School Enrollment', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="number" id="school_enrollment" name="school_enrollment" value="<?php echo $is_edit ? esc_attr($school->school_enrollment) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="mascot"><?php _e('Mascot', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="mascot" name="mascot" value="<?php echo $is_edit ? esc_attr($school->mascot) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="school_colors"><?php _e('School Colors', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="school_colors" name="school_colors" value="<?php echo $is_edit ? esc_attr($school->school_colors) : ''; ?>" class="regular-text">
							</td>
						</tr>
					</table>
				</div>
				
				<div class="csd-form-section">
					<h2><?php _e('Contact Information', 'csd-manager'); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="school_website"><?php _e('School Website', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="url" id="school_website" name="school_website" value="<?php echo $is_edit ? esc_attr($school->school_website) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="athletics_website"><?php _e('Athletics Website', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="url" id="athletics_website" name="athletics_website" value="<?php echo $is_edit ? esc_attr($school->athletics_website) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="athletics_phone"><?php _e('Athletics Phone', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="athletics_phone" name="athletics_phone" value="<?php echo $is_edit ? esc_attr($school->athletics_phone) : ''; ?>" class="regular-text">
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="football_division"><?php _e('Football Division', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="football_division" name="football_division" value="<?php echo $is_edit ? esc_attr($school->football_division) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('May be different from main school division.', 'csd-manager'); ?></p>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="csd-form-submit">
					<button type="submit" class="button button-primary"><?php echo $is_edit ? __('Update School', 'csd-manager') : __('Add School', 'csd-manager'); ?></button>
					<a href="<?php echo admin_url('admin.php?page=csd-schools'); ?>" class="button"><?php _e('Cancel', 'csd-manager'); ?></a>
					
					<?php if ($is_edit): ?>
					<span class="csd-last-updated">
						<?php 
						echo sprintf(
							__('Last updated: %s', 'csd-manager'), 
							date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($school->date_updated))
						); 
						?>
					</span>
					<?php endif; ?>
				</div>
			</form>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#csd-school-form').on('submit', function(e) {
					e.preventDefault();
					
					var formData = $(this).serialize();
					
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'csd_save_school',
							form_data: formData,
							nonce: csd_ajax.nonce
						},
						beforeSend: function() {
							$('.csd-form-submit button').prop('disabled', true).text('<?php _e('Saving...', 'csd-manager'); ?>');
						},
						success: function(response) {
							if (response.success) {
								// Redirect to schools list or edit page
								if (response.data.redirect) {
									window.location.href = response.data.redirect;
								} else {
									window.location.href = '<?php echo admin_url('admin.php?page=csd-schools'); ?>';
								}
							} else {
								alert(response.data.message);
								$('.csd-form-submit button').prop('disabled', false).text('<?php echo $is_edit ? __('Update School', 'csd-manager') : __('Add School', 'csd-manager'); ?>');
							}
						},
						error: function() {
							alert('<?php _e('An error occurred. Please try again.', 'csd-manager'); ?>');
							$('.csd-form-submit button').prop('disabled', false).text('<?php echo $is_edit ? __('Update School', 'csd-manager') : __('Add School', 'csd-manager'); ?>');
						}
					});
				});
			});
		</script>
		<?php
	}
	
	/**
	 * Render view school page
	 */
	private function render_view_page() {
		$school_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		
		if (!$school_id) {
			wp_die(__('Invalid school ID.', 'csd-manager'));
		}
		
		$wpdb = csd_db_connection();
		$school = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . csd_table('schools') . " WHERE id = %d", $school_id));
		
		if (!$school) {
			wp_die(__('School not found.', 'csd-manager'));
		}
		
		// Get staff members for this school
		$staff = $wpdb->get_results($wpdb->prepare("
			SELECT s.*
			FROM " . csd_table('staff') . " s
			JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
			WHERE ss.school_id = %d
			ORDER BY s.full_name
		", $school_id));
		
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html($school->school_name); ?></h1>
			<a href="<?php echo admin_url('admin.php?page=csd-schools'); ?>" class="page-title-action"><?php _e('Back to Schools', 'csd-manager'); ?></a>
			<a href="<?php echo admin_url('admin.php?page=csd-schools&action=edit&id=' . $school_id); ?>" class="page-title-action"><?php _e('Edit School', 'csd-manager'); ?></a>
			<hr class="wp-header-end">
			
			<div class="csd-school-details">
				<div class="csd-detail-section">
					<h2><?php _e('School Information', 'csd-manager'); ?></h2>
					
					<table class="widefat fixed striped">
						<tr>
							<th><?php _e('School Name', 'csd-manager'); ?></th>
							<td><?php echo esc_html($school->school_name); ?></td>
						</tr>
						<tr>
							<th><?php _e('Address', 'csd-manager'); ?></th>
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
						<tr>
							<th><?php _e('County', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->county) ? esc_html($school->county) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('School Division', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->school_divisions) ? esc_html($school->school_divisions) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('School Conference', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->school_conferences) ? esc_html($school->school_conferences) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('School Level', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->school_level) ? esc_html($school->school_level) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('School Type', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->school_type) ? esc_html($school->school_type) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('Enrollment', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->school_enrollment) ? number_format($school->school_enrollment) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('Mascot', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->mascot) ? esc_html($school->mascot) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('School Colors', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->school_colors) ? esc_html($school->school_colors) : '&mdash;'; ?></td>
						</tr>
					</table>
				</div>
				
				<div class="csd-detail-section">
					<h2><?php _e('Contact Information', 'csd-manager'); ?></h2>
					
					<table class="widefat fixed striped">
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
						<tr>
							<th><?php _e('Athletics Phone', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->athletics_phone) ? esc_html($school->athletics_phone) : '&mdash;'; ?></td>
						</tr>
						<tr>
							<th><?php _e('Football Division', 'csd-manager'); ?></th>
							<td><?php echo !empty($school->football_division) ? esc_html($school->football_division) : '&mdash;'; ?></td>
						</tr>
					</table>
				</div>
				
				<div class="csd-detail-section">
					<h2><?php _e('Staff Members', 'csd-manager'); ?></h2>
					
					<div class="csd-staff-actions">
						<a href="<?php echo admin_url('admin.php?page=csd-staff&action=add&school_id=' . $school_id); ?>" class="button button-primary"><?php _e('Add Staff Member', 'csd-manager'); ?></a>
					</div>
					
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php _e('Name', 'csd-manager'); ?></th>
								<th><?php _e('Title', 'csd-manager'); ?></th>
								<th><?php _e('Sport/Department', 'csd-manager'); ?></th>
								<th><?php _e('Email', 'csd-manager'); ?></th>
								<th><?php _e('Phone', 'csd-manager'); ?></th>
								<th><?php _e('Actions', 'csd-manager'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($staff)): ?>
								<tr>
									<td colspan="6"><?php _e('No staff members found for this school.', 'csd-manager'); ?></td>
								</tr>
							<?php else: ?>
								<?php foreach ($staff as $staff_member): ?>
									<tr>
										<td><?php echo esc_html($staff_member->full_name); ?></td>
										<td><?php echo esc_html($staff_member->title); ?></td>
										<td><?php echo esc_html($staff_member->sport_department); ?></td>
										<td>
											<?php if (!empty($staff_member->email)): ?>
												<a href="mailto:<?php echo esc_attr($staff_member->email); ?>"><?php echo esc_html($staff_member->email); ?></a>
											<?php else: ?>
												&mdash;
											<?php endif; ?>
										</td>
										<td><?php echo !empty($staff_member->phone) ? esc_html($staff_member->phone) : '&mdash;'; ?></td>
										<td>
											<a href="<?php echo admin_url('admin.php?page=csd-staff&action=view&id=' . $staff_member->id); ?>" class="button button-small"><?php _e('View', 'csd-manager'); ?></a>
											<a href="<?php echo admin_url('admin.php?page=csd-staff&action=edit&id=' . $staff_member->id); ?>" class="button button-small"><?php _e('Edit', 'csd-manager'); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
	
	/**
	 * AJAX handler for getting schools
	 */
	public function ajax_get_schools() {
		try {
			// Add these debugging lines
			error_log('AJAX get_schools called: ' . print_r($_POST, true));
			
			// If the nonce check fails, provide more details
			if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
				error_log('Nonce check failed: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'not set'));
				wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
				return;
			}
			
			// Get parameters
			$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
			$per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
			$sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'school_name';
			$sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'ASC';
			$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
			$state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
			$division = isset($_POST['division']) ? sanitize_text_field($_POST['division']) : '';
			
			// Validate sort by column
			$allowed_sort_columns = array(
				'school_name', 'city', 'state', 'school_divisions', 'school_website', 'date_created'
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
			$query = "SELECT s.*, COUNT(ss.staff_id) as staff_count
					FROM " . csd_table('schools') . " s
					LEFT JOIN " . csd_table('school_staff') . " ss ON s.id = ss.school_id";
			
			$where_clauses = array();
			$query_args = array();
			
			// Add search condition
			if (!empty($search)) {
				$where_clauses[] = "(s.school_name LIKE %s OR s.city LIKE %s OR s.state LIKE %s OR s.zipcode LIKE %s)";
				$search_term = '%' . $wpdb->esc_like($search) . '%';
				$query_args[] = $search_term;
				$query_args[] = $search_term;
				$query_args[] = $search_term;
				$query_args[] = $search_term;
			}
			
			// Add state filter
			if (!empty($state)) {
				$where_clauses[] = "s.state = %s";
				$query_args[] = $state;
			}
			
			// Add division filter
			if (!empty($division)) {
				$where_clauses[] = "s.school_divisions LIKE %s";
				$query_args[] = '%' . $wpdb->esc_like($division) . '%';
			}
			
			// Combine where clauses
			if (!empty($where_clauses)) {
				$query .= " WHERE " . implode(" AND ", $where_clauses);
			}
			
			// Add group by
			$query .= " GROUP BY s.id";
			
			// Add order by
			$query .= " ORDER BY {$sort_by} {$sort_order}";
			
			// Count total schools (before pagination)
			$count_query = "SELECT COUNT(DISTINCT s.id) FROM " . csd_table('schools') . " s";
			
			if (!empty($where_clauses)) {
				$count_query .= " WHERE " . implode(" AND ", $where_clauses);
			}
			
			$total_schools = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
			
			// Add pagination
			$offset = ($page - 1) * $per_page;
			$query .= " LIMIT %d OFFSET %d";
			$query_args[] = $per_page;
			$query_args[] = $offset;
			
			try {
				// Get schools
				$schools = $wpdb->get_results($wpdb->prepare($query, $query_args));
			} catch (Exception $e) {
				error_log('Database error: ' . $e->getMessage());
				wp_send_json_error(array('message' => 'Database error: ' . $e->getMessage()));
				return;
			}
			
			// Send response
			wp_send_json_success(array(
				'schools' => $schools,
				'total' => intval($total_schools)
			));
		} catch (Exception $e) {
			error_log('AJAX error: ' . $e->getMessage());
			wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
		}
	}
	
	/**
	 * AJAX handler for saving a school
	 */
	public function ajax_save_school() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
		}
		
		// Parse form data
		parse_str($_POST['form_data'], $form_data);
		
		// Verify form nonce
		if (!isset($form_data['csd_school_nonce']) || !wp_verify_nonce($form_data['csd_school_nonce'], 'csd_save_school')) {
			wp_send_json_error(array('message' => __('Form security check failed.', 'csd-manager')));
		}
		
		// Validate required fields
		if (empty($form_data['school_name'])) {
			wp_send_json_error(array('message' => __('School name is required.', 'csd-manager')));
		}
		
		$wpdb = csd_db_connection();
		
		// Prepare data
		$school_id = isset($form_data['school_id']) ? intval($form_data['school_id']) : 0;
		$current_time = current_time('mysql');
		
		$school_data = array(
			'school_name' => sanitize_text_field($form_data['school_name']),
			'street_address_line_1' => sanitize_text_field($form_data['street_address_line_1']),
			'street_address_line_2' => sanitize_text_field($form_data['street_address_line_2']),
			'street_address_line_3' => sanitize_text_field($form_data['street_address_line_3']),
			'city' => sanitize_text_field($form_data['city']),
			'state' => sanitize_text_field($form_data['state']),
			'zipcode' => sanitize_text_field($form_data['zipcode']),
			'country' => sanitize_text_field($form_data['country']),
			'county' => sanitize_text_field($form_data['county']),
			'school_divisions' => sanitize_text_field($form_data['school_divisions']),
			'school_conferences' => sanitize_text_field($form_data['school_conferences']),
			'school_level' => sanitize_text_field($form_data['school_level']),
			'school_type' => sanitize_text_field($form_data['school_type']),
			'school_enrollment' => intval($form_data['school_enrollment']),
			'mascot' => sanitize_text_field($form_data['mascot']),
			'school_colors' => sanitize_text_field($form_data['school_colors']),
			'school_website' => esc_url_raw($form_data['school_website']),
			'athletics_website' => esc_url_raw($form_data['athletics_website']),
			'athletics_phone' => sanitize_text_field($form_data['athletics_phone']),
			'football_division' => sanitize_text_field($form_data['football_division']),
			'date_updated' => $current_time
		);
		
		// Insert or update
		if ($school_id > 0) {
			// Update existing school
			$result = $wpdb->update(
				csd_table('schools'),
				$school_data,
				array('id' => $school_id)
			);
			
			if ($result === false) {
				wp_send_json_error(array('message' => __('Error updating school.', 'csd-manager')));
			}
			
			$redirect_url = admin_url('admin.php?page=csd-schools&action=view&id=' . $school_id);
		} else {
			// Add new school
			$school_data['date_created'] = $current_time;
			
			$result = $wpdb->insert(
				csd_table('schools'),
				$school_data
			);
			
			if (!$result) {
				wp_send_json_error(array('message' => __('Error adding school.', 'csd-manager')));
			}
			
			$school_id = $wpdb->insert_id;
			$redirect_url = admin_url('admin.php?page=csd-schools&action=view&id=' . $school_id);
		}
		
		wp_send_json_success(array(
			'message' => __('School saved successfully.', 'csd-manager'),
			'school_id' => $school_id,
			'redirect' => $redirect_url
		));
	}
	
	/**
	 * AJAX handler for deleting a school
	 */
	public function ajax_delete_school() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
		}
		
		$school_id = isset($_POST['school_id']) ? intval($_POST['school_id']) : 0;
		
		if ($school_id <= 0) {
			wp_send_json_error(array('message' => __('Invalid school ID.', 'csd-manager')));
		}
		
		$wpdb = csd_db_connection();
		
		// Start transaction
		$wpdb->query('START TRANSACTION');
		
		try {
			// Delete school_staff relationships
			$wpdb->delete(
				csd_table('school_staff'),
				array('school_id' => $school_id)
			);
			
			// Delete school
			$result = $wpdb->delete(
				csd_table('schools'),
				array('id' => $school_id)
			);
			
			if ($result === false) {
				throw new Exception(__('Error deleting school.', 'csd-manager'));
			}
			
			// Commit transaction
			$wpdb->query('COMMIT');
			
			wp_send_json_success(array(
				'message' => __('School deleted successfully.', 'csd-manager')
			));
		} catch (Exception $e) {
			// Rollback transaction
			$wpdb->query('ROLLBACK');
			
			wp_send_json_error(array(
				'message' => $e->getMessage()
			));
		}
	}
}