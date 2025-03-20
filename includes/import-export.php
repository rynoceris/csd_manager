<?php
/**
 * Import/Export Functionality
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Import/Export Class
 */
class CSD_Import_Export {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('wp_ajax_csd_process_import', array($this, 'ajax_process_import'));
		add_action('wp_ajax_csd_export_data', array($this, 'ajax_export_data'));
	}
	
	/**
	 * Render import/export page
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php _e('Import/Export', 'csd-manager'); ?></h1>
			
			<div class="csd-tabs-wrapper">
				<div class="csd-tabs">
					<a href="#" class="csd-tab active" data-tab="import"><?php _e('Import', 'csd-manager'); ?></a>
					<a href="#" class="csd-tab" data-tab="export"><?php _e('Export', 'csd-manager'); ?></a>
				</div>
				
				<div class="csd-tab-content active" id="import-tab">
					<div class="csd-import-section">
						<h2><?php _e('Import Schools and Staff', 'csd-manager'); ?></h2>
						<p><?php _e('Import schools and staff members from a CSV file. The first row should contain column headers.', 'csd-manager'); ?></p>
						
						<div class="csd-import-form-wrapper">
							<form id="csd-import-form" enctype="multipart/form-data">
								<div class="csd-import-options">
									<div class="csd-import-option">
										<label>
											<input type="radio" name="import_type" value="schools" checked>
											<?php _e('Import Schools', 'csd-manager'); ?>
										</label>
										<p class="description"><?php _e('CSV should contain school data.', 'csd-manager'); ?></p>
									</div>
									
									<div class="csd-import-option">
										<label>
											<input type="radio" name="import_type" value="staff">
											<?php _e('Import Staff', 'csd-manager'); ?>
										</label>
										<p class="description"><?php _e('CSV should contain staff data with optional school association.', 'csd-manager'); ?></p>
									</div>
									
									<div class="csd-import-option">
										<label>
											<input type="radio" name="import_type" value="both">
											<?php _e('Import Both (School with Staff)', 'csd-manager'); ?>
										</label>
										<p class="description"><?php _e('CSV should contain both school and staff data.', 'csd-manager'); ?></p>
									</div>
								</div>
								
								<div class="csd-file-upload">
									<label for="csd_import_file"><?php _e('Select CSV File', 'csd-manager'); ?></label>
									<input type="file" name="import_file" id="csd_import_file" accept=".csv">
								</div>
								
								<div class="csd-import-options">
									<h3><?php _e('Import Options', 'csd-manager'); ?></h3>
									
									<div class="csd-import-option">
										<label>
											<input type="checkbox" name="update_existing" value="1" checked>
											<?php _e('Update existing records', 'csd-manager'); ?>
										</label>
										<p class="description"><?php _e('If checked, existing records will be updated. Otherwise, only new records will be added.', 'csd-manager'); ?></p>
									</div>
									
									<div class="csd-import-option">
										<label>
											<input type="checkbox" name="skip_first_row" value="1" checked>
											<?php _e('Skip first row (headers)', 'csd-manager'); ?>
										</label>
									</div>
								</div>
								
								<div class="csd-submit-button">
									<button type="submit" class="button button-primary"><?php _e('Upload and Import', 'csd-manager'); ?></button>
								</div>
							</form>
						</div>
						
						<div id="csd-import-preview" style="display: none;">
							<h3><?php _e('Import Preview', 'csd-manager'); ?></h3>
							<div id="csd-preview-content"></div>
							
							<div class="csd-column-mapping">
								<h3><?php _e('Column Mapping', 'csd-manager'); ?></h3>
								<p><?php _e('Match CSV columns to database fields', 'csd-manager'); ?></p>
								
								<div id="csd-mapping-fields"></div>
								
								<div class="csd-submit-button">
									<button type="button" id="csd-process-import" class="button button-primary"><?php _e('Process Import', 'csd-manager'); ?></button>
									<button type="button" id="csd-cancel-import" class="button"><?php _e('Cancel', 'csd-manager'); ?></button>
								</div>
							</div>
						</div>
						
						<div id="csd-import-results" style="display: none;">
							<h3><?php _e('Import Results', 'csd-manager'); ?></h3>
							<div id="csd-results-content"></div>
							
							<div class="csd-submit-button">
								<button type="button" id="csd-new-import" class="button button-primary"><?php _e('Start New Import', 'csd-manager'); ?></button>
							</div>
						</div>
					</div>
					
					<div class="csd-import-templates">
						<h3><?php _e('CSV Templates', 'csd-manager'); ?></h3>
						<p><?php _e('Download CSV templates to get started:', 'csd-manager'); ?></p>
						
						<a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../templates/schools-template.csv'); ?>" class="button"><?php _e('Schools Template', 'csd-manager'); ?></a>
						<a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../templates/staff-template.csv'); ?>" class="button"><?php _e('Staff Template', 'csd-manager'); ?></a>
						<a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../templates/school-with-staff-template.csv'); ?>" class="button"><?php _e('School with Staff Template', 'csd-manager'); ?></a>
					</div>
				</div>
				
				<div class="csd-tab-content" id="export-tab">
					<div class="csd-export-section">
						<h2><?php _e('Export Data', 'csd-manager'); ?></h2>
						<p><?php _e('Export schools and staff data to a CSV file.', 'csd-manager'); ?></p>
						
						<form id="csd-export-form">
							<div class="csd-export-options">
								<div class="csd-export-option">
									<label>
										<input type="radio" name="export_type" value="schools" checked>
										<?php _e('Export All Schools', 'csd-manager'); ?>
									</label>
								</div>
								
								<div class="csd-export-option">
									<label>
										<input type="radio" name="export_type" value="staff">
										<?php _e('Export All Staff', 'csd-manager'); ?>
									</label>
								</div>
								
								<div class="csd-export-option">
									<label>
										<input type="radio" name="export_type" value="both">
										<?php _e('Export Schools with Staff', 'csd-manager'); ?>
									</label>
								</div>
								
								<div class="csd-export-option school-specific-export" style="display: none;">
									<label for="export_school"><?php _e('Select a specific school:', 'csd-manager'); ?></label>
									<select name="export_school" id="export_school">
										<option value=""><?php _e('All Schools', 'csd-manager'); ?></option>
										<?php
										$wpdb = csd_db_connection();
										$schools = $wpdb->get_results("SELECT id, school_name FROM " . csd_table('schools') . " ORDER BY school_name");
										
										foreach ($schools as $school) {
											echo '<option value="' . esc_attr($school->id) . '">' . esc_html($school->school_name) . '</option>';
										}
										?>
									</select>
								</div>
								
								<div class="csd-export-option">
									<label>
										<input type="checkbox" name="include_headers" value="1" checked>
										<?php _e('Include column headers', 'csd-manager'); ?>
									</label>
								</div>
							</div>
							
							<div class="csd-submit-button">
								<button type="submit" class="button button-primary"><?php _e('Generate Export', 'csd-manager'); ?></button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Tab switching
				$('.csd-tab').on('click', function(e) {
					e.preventDefault();
					
					var tab = $(this).data('tab');
					
					// Update active tab
					$('.csd-tab').removeClass('active');
					$(this).addClass('active');
					
					// Show active content
					$('.csd-tab-content').removeClass('active');
					$('#' + tab + '-tab').addClass('active');
				});
				
				// Show/hide school-specific export option
				$('input[name="export_type"]').on('change', function() {
					if ($(this).val() === 'both') {
						$('.school-specific-export').show();
					} else {
						$('.school-specific-export').hide();
						$('#export_school').val('');
					}
				});
				
				// Import form submission
				$('#csd-import-form').on('submit', function(e) {
					e.preventDefault();
					
					var formData = new FormData(this);
					formData.append('action', 'csd_preview_import');
					formData.append('nonce', csd_ajax.nonce);
					
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: formData,
						processData: false,
						contentType: false,
						beforeSend: function() {
							// Show loading indicator
							$('.csd-submit-button button').prop('disabled', true).text('<?php _e('Processing...', 'csd-manager'); ?>');
						},
						success: function(response) {
							$('.csd-submit-button button').prop('disabled', false).text('<?php _e('Upload and Import', 'csd-manager'); ?>');
							
							if (response.success) {
								// Hide import form and show preview
								$('.csd-import-form-wrapper').hide();
								$('#csd-import-preview').show();
								
								// Display preview data
								$('#csd-preview-content').html(response.data.preview_html);
								
								// Set up column mapping
								setupColumnMapping(response.data.columns, response.data.import_type);
								
								// Store file data for processing
								$('#csd-process-import').data('file_id', response.data.file_id);
								$('#csd-process-import').data('import_type', response.data.import_type);
								$('#csd-process-import').data('update_existing', response.data.update_existing);
							} else {
								alert(response.data.message);
							}
						},
						error: function() {
							$('.csd-submit-button button').prop('disabled', false).text('<?php _e('Upload and Import', 'csd-manager'); ?>');
							alert('<?php _e('An error occurred. Please try again.', 'csd-manager'); ?>');
						}
					});
				});
				
				// Process import button
				$('#csd-process-import').on('click', function() {
					var mappingData = {};
					$('.csd-column-map').each(function() {
						var csvColumn = $(this).data('column');
						var dbField = $(this).val();
						
						if (dbField) {
							mappingData[csvColumn] = dbField;
						}
					});
					
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'csd_process_import',
							file_id: $('#csd-process-import').data('file_id'),
							import_type: $('#csd-process-import').data('import_type'),
							update_existing: $('#csd-process-import').data('update_existing'),
							mapping: mappingData,
							nonce: csd_ajax.nonce
						},
						beforeSend: function() {
							$('#csd-process-import').prop('disabled', true).text('<?php _e('Processing...', 'csd-manager'); ?>');
							$('#csd-cancel-import').prop('disabled', true);
						},
						success: function(response) {
							$('#csd-process-import').prop('disabled', false).text('<?php _e('Process Import', 'csd-manager'); ?>');
							$('#csd-cancel-import').prop('disabled', false);
							
							if (response.success) {
								// Hide preview and show results
								$('#csd-import-preview').hide();
								$('#csd-import-results').show();
								
								// Display results
								$('#csd-results-content').html(response.data.results_html);
							} else {
								alert(response.data.message);
							}
						},
						error: function() {
							$('#csd-process-import').prop('disabled', false).text('<?php _e('Process Import', 'csd-manager'); ?>');
							$('#csd-cancel-import').prop('disabled', false);
							alert('<?php _e('An error occurred. Please try again.', 'csd-manager'); ?>');
						}
					});
				});
				
				// Cancel import button
				$('#csd-cancel-import').on('click', function() {
					$('#csd-import-preview').hide();
					$('.csd-import-form-wrapper').show();
					$('#csd-import-form')[0].reset();
				});
				
				// New import button
				$('#csd-new-import').on('click', function() {
					$('#csd-import-results').hide();
					$('.csd-import-form-wrapper').show();
					$('#csd-import-form')[0].reset();
				});
				
				// Export form submission
				$('#csd-export-form').on('submit', function(e) {
					e.preventDefault();
					
					var exportType = $('input[name="export_type"]:checked').val();
					var exportSchool = $('#export_school').val();
					var includeHeaders = $('input[name="include_headers"]').is(':checked') ? 1 : 0;
					
					// Create a form and submit it to initiate the download
					var form = $('<form>', {
						'method': 'POST',
						'action': csd_ajax.ajax_url
					});
					
					form.append($('<input>', {
						'type': 'hidden',
						'name': 'action',
						'value': 'csd_export_data'
					}));
					
					form.append($('<input>', {
						'type': 'hidden',
						'name': 'export_type',
						'value': exportType
					}));
					
					form.append($('<input>', {
						'type': 'hidden',
						'name': 'export_school',
						'value': exportSchool
					}));
					
					form.append($('<input>', {
						'type': 'hidden',
						'name': 'include_headers',
						'value': includeHeaders
					}));
					
					form.append($('<input>', {
						'type': 'hidden',
						'name': 'nonce',
						'value': csd_ajax.nonce
					}));
					
					$('body').append(form);
					form.submit();
					form.remove();
				});
				
				// Function to set up column mapping UI
				function setupColumnMapping(columns, importType) {
					var $mappingFields = $('#csd-mapping-fields');
					$mappingFields.empty();
					
					// Determine available fields based on import type
					var schoolFields = {
						'school_name': '<?php _e('School Name', 'csd-manager'); ?>',
						'street_address_line_1': '<?php _e('Street Address Line 1', 'csd-manager'); ?>',
						'street_address_line_2': '<?php _e('Street Address Line 2', 'csd-manager'); ?>',
						'street_address_line_3': '<?php _e('Street Address Line 3', 'csd-manager'); ?>',
						'city': '<?php _e('City', 'csd-manager'); ?>',
						'state': '<?php _e('State', 'csd-manager'); ?>',
						'zipcode': '<?php _e('Zip Code', 'csd-manager'); ?>',
						'country': '<?php _e('Country', 'csd-manager'); ?>',
						'county': '<?php _e('County', 'csd-manager'); ?>',
						'school_divisions': '<?php _e('School Divisions', 'csd-manager'); ?>',
						'school_conferences': '<?php _e('School Conferences', 'csd-manager'); ?>',
						'school_level': '<?php _e('School Level', 'csd-manager'); ?>',
						'school_type': '<?php _e('School Type', 'csd-manager'); ?>',
						'school_enrollment': '<?php _e('School Enrollment', 'csd-manager'); ?>',
						'mascot': '<?php _e('Mascot', 'csd-manager'); ?>',
						'school_colors': '<?php _e('School Colors', 'csd-manager'); ?>',
						'school_website': '<?php _e('School Website', 'csd-manager'); ?>',
						'athletics_website': '<?php _e('Athletics Website', 'csd-manager'); ?>',
						'athletics_phone': '<?php _e('Athletics Phone', 'csd-manager'); ?>',
						'football_division': '<?php _e('Football Division', 'csd-manager'); ?>'
					};
					
					var staffFields = {
						'full_name': '<?php _e('Full Name', 'csd-manager'); ?>',
						'title': '<?php _e('Title', 'csd-manager'); ?>',
						'sport_department': '<?php _e('Sport/Department', 'csd-manager'); ?>',
						'email': '<?php _e('Email', 'csd-manager'); ?>',
						'phone': '<?php _e('Phone', 'csd-manager'); ?>'
					};
					
					var relationFields = {
						'school_identifier': '<?php _e('School Identifier', 'csd-manager'); ?>'
					};
					
					var availableFields = {};
					
					if (importType === 'schools' || importType === 'both') {
						$.extend(availableFields, schoolFields);
					}
					
					if (importType === 'staff' || importType === 'both') {
						$.extend(availableFields, staffFields);
						
						if (importType === 'staff') {
							$.extend(availableFields, relationFields);
						}
					}
					
					// Create mapping UI for each CSV column
					$.each(columns, function(index, column) {
						var fieldName = column.replace(/[^a-z0-9_]/gi, '_').toLowerCase();
						var mappingField = $('<div class="csd-mapping-field"></div>');
						
						// Attempt to automatically map fields by name similarity
						var matchedField = '';
						
						$.each(availableFields, function(dbField, label) {
							// Check for exact match
							if (fieldName === dbField) {
								matchedField = dbField;
								return false; // Break the loop
							}
							
							// Check for name contained in field
							if (fieldName.indexOf(dbField) !== -1 || dbField.indexOf(fieldName) !== -1) {
								matchedField = dbField;
							}
						});
						
						mappingField.append('<label>' + column + ':</label>');
						
						var select = $('<select class="csd-column-map" data-column="' + column + '"></select>');
						select.append('<option value=""><?php _e('-- Ignore this column --', 'csd-manager'); ?></option>');
						
						$.each(availableFields, function(dbField, label) {
							var selected = dbField === matchedField ? ' selected' : '';
							select.append('<option value="' + dbField + '"' + selected + '>' + label + '</option>');
						});
						
						mappingField.append(select);
						$mappingFields.append(mappingField);
					});
				}
			});
		</script>
		<?php
	}
	
	/**
	 * AJAX handler for processing import
	 */
	public function ajax_process_import() {
		check_admin_referer('csd-ajax-nonce', 'nonce');
		
		if (!current_user_can('manage_csd')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
		}
		
		$file_id = isset($_POST['file_id']) ? sanitize_text_field($_POST['file_id']) : '';
		$import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : '';
		$update_existing = isset($_POST['update_existing']) ? filter_var($_POST['update_existing'], FILTER_VALIDATE_BOOLEAN) : false;
		$mapping = isset($_POST['mapping']) ? $_POST['mapping'] : array();
		
		if (empty($file_id) || empty($import_type) || empty($mapping)) {
			wp_send_json_error(array('message' => __('Missing required import parameters.', 'csd-manager')));
		}
		
		// Get temporary file path
		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/csd-temp/';
		$file_path = $temp_dir . $file_id . '.csv';
		
		if (!file_exists($file_path)) {
			wp_send_json_error(array('message' => __('Import file not found.', 'csd-manager')));
		}
		
		// Open CSV file
		$file = fopen($file_path, 'r');
		
		if (!$file) {
			wp_send_json_error(array('message' => __('Could not open import file.', 'csd-manager')));
		}
		
		// Get headers
		$headers = fgetcsv($file);
		
		if (!$headers) {
			fclose($file);
			wp_send_json_error(array('message' => __('Could not read CSV headers.', 'csd-manager')));
		}
		
		$wpdb = csd_db_connection();
		
		// Start transaction
		$wpdb->query('START TRANSACTION');
		
		$results = array(
			'schools_added' => 0,
			'schools_updated' => 0,
			'staff_added' => 0,
			'staff_updated' => 0,
			'errors' => array()
		);
		
		try {
			// Process each row
			$row_number = 1;
			
			while (($row = fgetcsv($file)) !== false) {
				$row_number++;
				
				if (count($row) !== count($headers)) {
					$results['errors'][] = sprintf(__('Row %d: Column count mismatch, skipping.', 'csd-manager'), $row_number);
					continue;
				}
				
				// Create associative array from row
				$row_data = array();
				foreach ($headers as $index => $header) {
					if (isset($mapping[$header])) {
						$row_data[$mapping[$header]] = $row[$index];
					}
				}
				
				// Process based on import type
				if ($import_type === 'schools') {
					$this->process_school_import($row_data, $update_existing, $results);
				} elseif ($import_type === 'staff') {
					$this->process_staff_import($row_data, $update_existing, $results);
				} elseif ($import_type === 'both') {
					$school_id = $this->process_school_import($row_data, $update_existing, $results);
					
					if ($school_id) {
						$row_data['school_id'] = $school_id;
						$this->process_staff_import($row_data, $update_existing, $results);
					}
				}
			}
			
			// Commit transaction
			$wpdb->query('COMMIT');
			
			// Close and delete file
			fclose($file);
			@unlink($file_path);
			
			// Generate results HTML
			$results_html = $this->generate_import_results_html($results);
			
			wp_send_json_success(array(
				'results_html' => $results_html
			));
		} catch (Exception $e) {
			// Rollback transaction
			$wpdb->query('ROLLBACK');
			
			// Close file
			fclose($file);
			
			wp_send_json_error(array('message' => $e->getMessage()));
		}
	}
	
	/**
	 * Process school import
	 * 
	 * @param array $row_data Row data
	 * @param bool $update_existing Whether to update existing records
	 * @param array $results Results array
	 * @return int|false School ID or false on failure
	 */
	private function process_school_import($row_data, $update_existing, &$results) {
		$wpdb = csd_db_connection();
		
		// Check for required fields
		if (empty($row_data['school_name'])) {
			$results['errors'][] = __('Missing required field: School Name', 'csd-manager');
			return false;
		}
		
		$school_data = array(
			'school_name' => sanitize_text_field($row_data['school_name']),
			'date_updated' => current_time('mysql')
		);
		
		// Map other fields
		$school_fields = array(
			'street_address_line_1', 'street_address_line_2', 'street_address_line_3',
			'city', 'state', 'zipcode', 'country', 'county',
			'school_divisions', 'school_conferences', 'school_level', 'school_type',
			'school_enrollment', 'mascot', 'school_colors',
			'school_website', 'athletics_website', 'athletics_phone', 'football_division'
		);
		
		foreach ($school_fields as $field) {
			if (isset($row_data[$field])) {
				if ($field === 'school_website' || $field === 'athletics_website') {
					$school_data[$field] = esc_url_raw($row_data[$field]);
				} elseif ($field === 'school_enrollment') {
					$school_data[$field] = intval($row_data[$field]);
				} else {
					$school_data[$field] = sanitize_text_field($row_data[$field]);
				}
			}
		}
		
		// Check if school already exists
		$existing_school = $wpdb->get_row($wpdb->prepare(
			"SELECT id FROM " . csd_table('schools') . " WHERE school_name = %s",
			$school_data['school_name']
		));
		
		if ($existing_school) {
			if ($update_existing) {
				// Update existing school
				$result = $wpdb->update(
					csd_table('schools'),
					$school_data,
					array('id' => $existing_school->id)
				);
				
				if ($result !== false) {
					$results['schools_updated']++;
					return $existing_school->id;
				} else {
					$results['errors'][] = sprintf(__('Error updating school: %s', 'csd-manager'), $school_data['school_name']);
					return false;
				}
			} else {
				// Skip update
				return $existing_school->id;
			}
		} else {
			// Add new school
			$school_data['date_created'] = current_time('mysql');
			
			$result = $wpdb->insert(
				csd_table('schools'),
				$school_data
			);
			
			if ($result) {
				$results['schools_added']++;
				return $wpdb->insert_id;
			} else {
				$results['errors'][] = sprintf(__('Error adding school: %s', 'csd-manager'), $school_data['school_name']);
				return false;
			}
		}
	}
	
	/**
	 * Process staff import
	 * 
	 * @param array $row_data Row data
	 * @param bool $update_existing Whether to update existing records
	 * @param array $results Results array
	 * @return int|false Staff ID or false on failure
	 */
	private function process_staff_import($row_data, $update_existing, &$results) {
		$wpdb = csd_db_connection();
		
		// Check for required fields
		if (empty($row_data['full_name'])) {
			$results['errors'][] = __('Missing required field: Full Name', 'csd-manager');
			return false;
		}
		
		$staff_data = array(
			'full_name' => sanitize_text_field($row_data['full_name']),
			'date_updated' => current_time('mysql')
		);
		
		// Map other fields
		$staff_fields = array('title', 'sport_department', 'email', 'phone');
		
		foreach ($staff_fields as $field) {
			if (isset($row_data[$field])) {
				if ($field === 'email') {
					$staff_data[$field] = sanitize_email($row_data[$field]);
				} else {
					$staff_data[$field] = sanitize_text_field($row_data[$field]);
				}
			}
		}
		
		// Get school ID
		$school_id = 0;
		
		if (isset($row_data['school_id'])) {
			$school_id = intval($row_data['school_id']);
		} elseif (isset($row_data['school_identifier'])) {
			// Look up school by name
			$school = $wpdb->get_row($wpdb->prepare(
				"SELECT id FROM " . csd_table('schools') . " WHERE school_name = %s",
				$row_data['school_identifier']
			));
			
			if ($school) {
				$school_id = $school->id;
			}
		}
		
		// Check if staff already exists
		$existing_staff = $wpdb->get_row($wpdb->prepare(
			"SELECT id FROM " . csd_table('staff') . " WHERE full_name = %s",
			$staff_data['full_name']
		));
		
		if ($existing_staff) {
			if ($update_existing) {
				// Update existing staff
				$result = $wpdb->update(
					csd_table('staff'),
					$staff_data,
					array('id' => $existing_staff->id)
				);
				
				if ($result !== false) {
					$results['staff_updated']++;
					$staff_id = $existing_staff->id;
				} else {
					$results['errors'][] = sprintf(__('Error updating staff: %s', 'csd-manager'), $staff_data['full_name']);
					return false;
				}
			} else {
				// Skip update
				$staff_id = $existing_staff->id;
			}
		} else {
			// Add new staff
			$staff_data['date_created'] = current_time('mysql');
			
			$result = $wpdb->insert(
				csd_table('staff'),
				$staff_data
			);
			
			if ($result) {
				$results['staff_added']++;
				$staff_id = $wpdb->insert_id;
			} else {
				$results['errors'][] = sprintf(__('Error adding staff: %s', 'csd-manager'), $staff_data['full_name']);
				return false;
			}
		}
		
		// Handle school association
		if ($staff_id && $school_id) {
			// Remove existing association
			$wpdb->delete(
				csd_table('school_staff'),
				array('staff_id' => $staff_id)
			);
			
			// Add new association
			$wpdb->insert(
				csd_table('school_staff'),
				array(
					'school_id' => $school_id,
					'staff_id' => $staff_id,
					'date_created' => current_time('mysql')
				)
			);
		}
		
		return $staff_id;
	}
	
	/**
	 * Generate import results HTML
	 * 
	 * @param array $results Import results
	 * @return string HTML
	 */
	private function generate_import_results_html($results) {
		$html = '<div class="csd-import-summary">';
		
		$html .= '<h4>' . __('Import Summary', 'csd-manager') . '</h4>';
		
		$html .= '<ul>';
		$html .= '<li>' . sprintf(__('Schools added: %d', 'csd-manager'), $results['schools_added']) . '</li>';
		$html .= '<li>' . sprintf(__('Schools updated: %d', 'csd-manager'), $results['schools_updated']) . '</li>';
		$html .= '<li>' . sprintf(__('Staff added: %d', 'csd-manager'), $results['staff_added']) . '</li>';
		$html .= '<li>' . sprintf(__('Staff updated: %d', 'csd-manager'), $results['staff_updated']) . '</li>';
		$html .= '</ul>';
		
		if (!empty($results['errors'])) {
			$html .= '<h4>' . __('Errors', 'csd-manager') . '</h4>';
			$html .= '<ul class="csd-import-errors">';
			
			foreach ($results['errors'] as $error) {
				$html .= '<li>' . esc_html($error) . '</li>';
			}
			
			$html .= '</ul>';
		}
		
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * AJAX handler for exporting data
	 */
	public function ajax_export_data() {
		check_admin_referer('csd-ajax-nonce', 'nonce');
		
		if (!current_user_can('manage_csd')) {
			wp_die(__('You do not have permission to perform this action.', 'csd-manager'));
		}
		
		$export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'schools';
		$export_school = isset($_POST['export_school']) ? intval($_POST['export_school']) : 0;
		$include_headers = isset($_POST['include_headers']) ? filter_var($_POST['include_headers'], FILTER_VALIDATE_BOOLEAN) : true;
		
		// Set filename based on export type
		$filename = 'csd-export-' . $export_type . '-' . date('Y-m-d') . '.csv';
		
		// Set headers for CSV download
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);
		
		// Create output handle
		$output = fopen('php://output', 'w');
		
		$wpdb = csd_db_connection();
		
		// Export based on type
		if ($export_type === 'schools') {
			$this->export_schools($output, $include_headers, $export_school);
		} elseif ($export_type === 'staff') {
			$this->export_staff($output, $include_headers, $export_school);
		} elseif ($export_type === 'both') {
			$this->export_schools_with_staff($output, $include_headers, $export_school);
		}
		
		fclose($output);
		exit;
	}
	
	/**
	 * Export schools data
	 * 
	 * @param resource $output Output handle
	 * @param bool $include_headers Whether to include headers
	 * @param int $school_id Specific school ID to export
	 */
	private function export_schools($output, $include_headers, $school_id = 0) {
		$wpdb = csd_db_connection();
		
		// Define headers
		$headers = array(
			'school_name', 'street_address_line_1', 'street_address_line_2', 'street_address_line_3',
			'city', 'state', 'zipcode', 'country', 'county',
			'school_divisions', 'school_conferences', 'school_level', 'school_type',
			'school_enrollment', 'mascot', 'school_colors',
			'school_website', 'athletics_website', 'athletics_phone', 'football_division',
			'date_created', 'date_updated'
		);
		
		if ($include_headers) {
			fputcsv($output, $headers);
		}
		
		// Build query
		$query = "SELECT * FROM " . csd_table('schools');
		$query_args = array();
		
		if ($school_id > 0) {
			$query .= " WHERE id = %d";
			$query_args[] = $school_id;
		}
		
		$query .= " ORDER BY school_name ASC";
		
		// Get schools
		if (!empty($query_args)) {
			$schools = $wpdb->get_results($wpdb->prepare($query, $query_args), ARRAY_A);
		} else {
			$schools = $wpdb->get_results($query, ARRAY_A);
		}
		
		// Output school data
		foreach ($schools as $school) {
			$row = array();
			
			foreach ($headers as $header) {
				$row[] = isset($school[$header]) ? $school[$header] : '';
			}
			
			fputcsv($output, $row);
		}
	}
	
	/**
	 * Export staff data
	 * 
	 * @param resource $output Output handle
	 * @param bool $include_headers Whether to include headers
	 * @param int $school_id Specific school ID to export
	 */
	private function export_staff($output, $include_headers, $school_id = 0) {
		$wpdb = csd_db_connection();
		
		// Define headers
		$headers = array(
			'full_name', 'title', 'sport_department', 'email', 'phone',
			'school_identifier', 'date_created', 'date_updated'
		);
		
		if ($include_headers) {
			fputcsv($output, $headers);
		}
		
		// Build query
		$query = "SELECT s.*, sch.school_name as school_identifier
				  FROM " . csd_table('staff') . " s
				  LEFT JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
				  LEFT JOIN " . csd_table('schools') . " sch ON ss.school_id = sch.id";
		
		$query_args = array();
		
		if ($school_id > 0) {
			$query .= " WHERE ss.school_id = %d";
			$query_args[] = $school_id;
		}
		
		$query .= " ORDER BY s.full_name ASC";
		
		// Get staff
		if (!empty($query_args)) {
			$staff = $wpdb->get_results($wpdb->prepare($query, $query_args), ARRAY_A);
		} else {
			$staff = $wpdb->get_results($query, ARRAY_A);
		}
		
		// Output staff data
		foreach ($staff as $member) {
			$row = array();
			
			foreach ($headers as $header) {
				$row[] = isset($member[$header]) ? $member[$header] : '';
			}
			
			fputcsv($output, $row);
		}
	}
	
	/**
	 * Export schools with staff data
	 * 
	 * @param resource $output Output handle
	 * @param bool $include_headers Whether to include headers
	 * @param int $school_id Specific school ID to export
	 */
	private function export_schools_with_staff($output, $include_headers, $school_id = 0) {
		$wpdb = csd_db_connection();
		
		// Define headers
		$headers = array(
			// School fields
			'school_name', 'street_address_line_1', 'street_address_line_2', 'street_address_line_3',
			'city', 'state', 'zipcode', 'country', 'county',
			'school_divisions', 'school_conferences', 'school_level', 'school_type',
			'school_enrollment', 'mascot', 'school_colors',
			'school_website', 'athletics_website', 'athletics_phone', 'football_division',
			
			// Staff fields
			'full_name', 'title', 'sport_department', 'email', 'phone'
		);
		
		if ($include_headers) {
			fputcsv($output, $headers);
		}
		
		// Build query
		$query = "SELECT sch.*, s.full_name, s.title, s.sport_department, s.email, s.phone
				  FROM " . csd_table('schools') . " sch
				  LEFT JOIN " . csd_table('school_staff') . " ss ON sch.id = ss.school_id
				  LEFT JOIN " . csd_table('staff') . " s ON ss.staff_id = s.id";
		 
		$query_args = array();
		
		if ($school_id > 0) {
			$query .= " WHERE sch.id = %d";
			$query_args[] = $school_id;
		}
		
		$query .= " ORDER BY sch.school_name ASC, s.full_name ASC";
		
		// Get data
		if (!empty($query_args)) {
			$data = $wpdb->get_results($wpdb->prepare($query, $query_args), ARRAY_A);
		} else {
			$data = $wpdb->get_results($query, ARRAY_A);
		}
		
		// Output data
		foreach ($data as $row_data) {
			$row = array();
			
			foreach ($headers as $header) {
				$row[] = isset($row_data[$header]) ? $row_data[$header] : '';
			}
			
			fputcsv($output, $row);
		}
	}
}