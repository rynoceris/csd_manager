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
		// Note: AJAX handlers are registered in the main plugin file using wrapper functions
		// Class methods are called from those wrappers
	}
	
	/**
	 * Render import/export page
	 */
	public function render_page() {
		// Ensure scripts and nonce are properly loaded
		wp_enqueue_script('jquery');
		
		// Manually localize the admin script
		wp_localize_script('jquery', 'csd_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('csd-ajax-nonce')
		));
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
						
						<!-- Status messages will be displayed here -->
						<div id="import-status" style="margin-bottom: 20px; padding: 10px; border-radius: 4px; display: none;"></div>
						
						<div class="csd-import-form-wrapper">
							<form id="csd-import-form" enctype="multipart/form-data">
								<div class="csd-import-options">
									<div class="csd-import-option">
										<label>
											<input type="radio" name="import_type" value="combined" checked>
											<?php _e('Import Combined Format', 'csd-manager'); ?>
										</label>
										<p class="description"><?php _e('CSV contains staff member in each row with associated school. Creates schools as needed.', 'csd-manager'); ?></p>
									</div>
									
									<div class="csd-import-option">
										<label>
											<input type="radio" name="import_type" value="schools">
											<?php _e('Import Schools Only', 'csd-manager'); ?>
										</label>
										<p class="description"><?php _e('CSV should contain school data.', 'csd-manager'); ?></p>
									</div>
									
									<div class="csd-import-option">
										<label>
											<input type="radio" name="import_type" value="staff">
											<?php _e('Import Staff Only', 'csd-manager'); ?>
										</label>
										<p class="description"><?php _e('CSV should contain staff data with optional school association.', 'csd-manager'); ?></p>
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
									<button type="submit" class="button button-primary"><?php _e('Upload and Preview', 'csd-manager'); ?></button>
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
				</div>
				
				<div class="csd-tab-content" id="export-tab">
					<div class="csd-export-section">
						<h2><?php _e('Export Data', 'csd-manager'); ?></h2>
						<p><?php _e('Export schools and staff data to a CSV file.', 'csd-manager'); ?></p>
						
						<form id="csd-export-form">
							<div class="csd-export-options">
								<div class="csd-export-option">
									<label>
										<input type="radio" name="export_type" value="combined" checked>
										<?php _e('Export Combined Format', 'csd-manager'); ?>
									</label>
								</div>
								
								<div class="csd-export-option">
									<label>
										<input type="radio" name="export_type" value="schools">
										<?php _e('Export All Schools', 'csd-manager'); ?>
									</label>
								</div>
								
								<div class="csd-export-option">
									<label>
										<input type="radio" name="export_type" value="staff">
										<?php _e('Export All Staff', 'csd-manager'); ?>
									</label>
								</div>
								
								<div class="csd-export-option school-specific-export">
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
				// Check if csd_ajax is defined and create a fallback if not
				if (typeof csd_ajax === 'undefined') {
					window.csd_ajax = {
						ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
						nonce: '<?php echo wp_create_nonce('csd-ajax-nonce'); ?>'
					};
					console.log('Created fallback csd_ajax object');
				}
				
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
				
				// Show/hide school-specific export option based on export type
				$('input[name="export_type"]').on('change', function() {
					if ($(this).val() === 'combined') {
						$('.school-specific-export').show();
					} else {
						$('.school-specific-export').hide();
						$('#export_school').val('');
					}
				});
				
				// Helper to show status messages
				function showStatus(message, isError) {
					var statusDiv = $('#import-status');
					statusDiv.html(message);
					
					if (isError) {
						statusDiv.css('background-color', '#f8d7da').css('border', '1px solid #f5c6cb').css('color', '#721c24');
					} else {
						statusDiv.css('background-color', '#d4edda').css('border', '1px solid #c3e6cb').css('color', '#155724');
					}
					
					statusDiv.show();
				}
				
				// Import form submission
				$('#csd-import-form').on('submit', function(e) {
					e.preventDefault();
					
					// Validate file input
					var fileInput = $('#csd_import_file')[0];
					if (fileInput.files.length === 0) {
						showStatus('Please select a CSV file to import.', true);
						return;
					}
					
					// Create FormData object for file upload
					var formData = new FormData(this);
					formData.append('action', 'csd_preview_import');
					formData.append('nonce', csd_ajax.nonce);
					
					// Show loading status
					showStatus('Uploading and processing file...', false);
					$('.csd-submit-button button').prop('disabled', true);
					
					// Log what we're sending
					console.log('Sending AJAX request to:', csd_ajax.ajax_url);
					
					// Send AJAX request
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: formData,
						processData: false,
						contentType: false,
						success: function(response) {
							console.log('Response:', response);
							$('.csd-submit-button button').prop('disabled', false);
							
							if (response.success) {
								// Hide status message
								$('#import-status').hide();
								
								// Hide import form and show preview
								$('.csd-import-form-wrapper').hide();
								$('#csd-import-preview').show();
								
								// Display preview data
								$('#csd-preview-content').html(response.data.preview_html);
								
								// Set up column mapping - UPDATED TO PASS THE EXPECTED COLUMNS
								setupColumnMapping(response.data.columns, response.data.import_type, response.data.expected_columns);
								
								// Store file data for processing
								$('#csd-process-import').data('file_id', response.data.file_id);
								$('#csd-process-import').data('import_type', response.data.import_type);
								$('#csd-process-import').data('update_existing', response.data.update_existing);
							} else {
								// Show error message
								showStatus('Error: ' + (response.data ? response.data.message : 'Unknown error'), true);
							}
						},
						error: function(xhr, status, error) {
							console.error('AJAX Error:', status, error);
							console.error('Response:', xhr.responseText);
							
							$('.csd-submit-button button').prop('disabled', false);
							showStatus('Error: ' + status + ' - ' + error, true);
						}
					});
				});
				
				// Process import button
				$('#csd-process-import').on('click', function() {
					var mappingData = {};
					$('.csd-column-map').each(function() {
						var dbField = $(this).data('field');
						var csvColumn = $(this).val();
						
						if (csvColumn) {
							mappingData[dbField] = csvColumn;
						}
					});
					
					// Show loading status
					showStatus('Processing import...', false);
					$('#csd-process-import').prop('disabled', true);
					$('#csd-cancel-import').prop('disabled', true);
					
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
						success: function(response) {
							console.log('Process Import Response:', response);
							
							$('#csd-process-import').prop('disabled', false);
							$('#csd-cancel-import').prop('disabled', false);
							
							if (response.success) {
								// Hide status message
								$('#import-status').hide();
								
								// Hide preview and show results
								$('#csd-import-preview').hide();
								$('#csd-import-results').show();
								
								// Display results
								$('#csd-results-content').html(response.data.results_html);
							} else {
								// Show error message
								showStatus('Error: ' + (response.data ? response.data.message : 'Unknown error'), true);
							}
						},
						error: function(xhr, status, error) {
							console.error('Process Import Error:', status, error);
							console.error('Response:', xhr.responseText);
							
							$('#csd-process-import').prop('disabled', false);
							$('#csd-cancel-import').prop('disabled', false);
							showStatus('Error processing import: ' + status + ' - ' + error, true);
						}
					});
				});
				
				// Cancel import button
				$('#csd-cancel-import').on('click', function() {
					$('#csd-import-preview').hide();
					$('.csd-import-form-wrapper').show();
					$('#csd-import-form')[0].reset();
					$('#import-status').hide();
				});
				
				// New import button
				$('#csd-new-import').on('click', function() {
					$('#csd-import-results').hide();
					$('.csd-import-form-wrapper').show();
					$('#csd-import-form')[0].reset();
					$('#import-status').hide();
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
				function setupColumnMapping(columns, importType, expectedColumns) {
					var $mappingFields = $('#csd-mapping-fields');
					$mappingFields.empty();
					
					// Use the passed expectedColumns parameter instead of trying to access response
					expectedColumns = expectedColumns || [];
					
					console.log('Original CSV columns:', columns);
					console.log('Expected DB columns:', expectedColumns);
					
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
					
					if (importType === 'schools') {
						$.extend(availableFields, schoolFields);
					} else if (importType === 'staff') {
						$.extend(availableFields, staffFields, relationFields);
					} else {
						// For combined or both type, all fields are available
						$.extend(availableFields, schoolFields, staffFields);
					}
					
					// Create mapping UI for each expected column from the import type
					$.each(expectedColumns, function(index, dbField) {
						var mappingField = $('<div class="csd-mapping-field"></div>');
						var label = availableFields[dbField] || dbField;
						
						mappingField.append('<label>' + label + ':</label>');
						
						var select = $('<select class="csd-column-map" data-field="' + dbField + '"></select>');
						select.append('<option value=""><?php _e('-- Not Mapped --', 'csd-manager'); ?></option>');
						
						// Add each CSV column as an option
						$.each(columns, function(i, csvColumn) {
							var selected = '';
							
							// Try to auto-match columns
							if (
								// Exact match
								(csvColumn && typeof csvColumn === 'string' && csvColumn.toLowerCase() === dbField.toLowerCase()) ||
								// sport___department special case
								(csvColumn === 'sport___department' && dbField === 'sport_department') ||
								// Similar names (contains)
								(csvColumn && typeof csvColumn === 'string' && 
								 (csvColumn.toLowerCase().indexOf(dbField.toLowerCase()) !== -1 ||
								  dbField.toLowerCase().indexOf(csvColumn.toLowerCase()) !== -1))
							) {
								selected = ' selected';
							}
							
							// Only add valid columns
							if (csvColumn && typeof csvColumn === 'string') {
								select.append('<option value="' + csvColumn + '"' + selected + '>' + csvColumn + '</option>');
							}
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
	 * AJAX handler for previewing import
	 */
	public function ajax_preview_import() {
		// Explicitly state content type for response
		header('Content-Type: application/json');
		
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => 'Security check failed.'));
			exit;
		}
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
			exit;
		}
		
		// Check if file was uploaded
		if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
			$error = isset($_FILES['import_file']) ? $_FILES['import_file']['error'] : 'No file uploaded';
			wp_send_json_error(array('message' => 'File upload error: ' . $error));
			exit;
		}
		
		// Get form data
		$import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : 'combined';
		$update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] === '1';
		$skip_first_row = isset($_POST['skip_first_row']) && $_POST['skip_first_row'] === '1';
		
		// Create temp directory if it doesn't exist
		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/csd-temp/';
		
		if (!file_exists($temp_dir)) {
			wp_mkdir_p($temp_dir);
		}
		
		// Generate a unique file ID
		$file_id = uniqid();
		$temp_file = $temp_dir . $file_id . '.csv';
		
		// Move uploaded file to temp directory
		if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $temp_file)) {
			wp_send_json_error(array('message' => 'Failed to move uploaded file.'));
			exit;
		}
		
		// Try to detect and fix encoding issues
		$file_content = file_get_contents($temp_file);
		
		// Remove UTF-8 BOM if present
		$bom = pack('H*', 'EFBBBF');
		$file_content = preg_replace("/^$bom/", '', $file_content);
		
		// Re-save the file with proper encoding
		file_put_contents($temp_file, $file_content);
		
		// Read CSV file for preview
		$file = fopen($temp_file, 'r');
		if (!$file) {
			wp_send_json_error(array('message' => 'Could not open CSV file for reading.'));
			exit;
		}
		
		// Get raw headers and rows for display
		$raw_headers = fgetcsv($file);
		if (!$raw_headers) {
			fclose($file);
			@unlink($temp_file);
			wp_send_json_error(array('message' => 'Could not read CSV headers.'));
			exit;
		}
		
		// Get first few rows for preview table display
		$preview_rows = array();
		$max_preview_rows = 5;
		$row_count = 0;
		
		while (($row = fgetcsv($file)) !== false && $row_count < $max_preview_rows) {
			$preview_rows[] = $row;
			$row_count++;
		}
		
		// Close file
		fclose($file);
		
		// COMPLETELY IGNORE CSV HEADERS AND USE PREDEFINED HEADERS ONLY
		// This is the key fix - we're not even attempting to use the headers from the file
		
		// Define known expected headers based on import type
		if ($import_type === 'schools') {
			$expected_headers = array(
				'school_name', 'street_address_line_1', 'street_address_line_2', 'street_address_line_3',
				'city', 'state', 'zipcode', 'country', 'county',
				'school_divisions', 'school_conferences', 'school_level', 'school_type',
				'school_enrollment', 'mascot', 'school_colors',
				'school_website', 'athletics_website', 'athletics_phone', 'football_division'
			);
		} elseif ($import_type === 'staff') {
			$expected_headers = array(
				'full_name', 'title', 'sport_department', 'email', 'phone', 'school_identifier'
			);
		} else {
			// Combined format
			$expected_headers = array(
				'full_name', 'title', 'phone', 'email', 'sport_department',
				'school_name', 'street_address_line_1', 'street_address_line_2', 'street_address_line_3',
				'city', 'state', 'county', 'zipcode', 'country', 'school_level',
				'school_website', 'athletics_website', 'athletics_phone',
				'mascot', 'school_type', 'school_enrollment', 'football_division',
				'school_colors', 'school_divisions', 'school_conferences'
			);
		}
		
		// Generate preview HTML
		$preview_html = '<div class="csd-preview-table-wrapper">';
		$preview_html .= '<h4>CSV File Preview</h4>';
		$preview_html .= '<table class="wp-list-table widefat fixed striped">';
		$preview_html .= '<thead><tr>';
		
		foreach ($raw_headers as $header) {
			$preview_html .= '<th>' . esc_html($header) . '</th>';
		}
		
		$preview_html .= '</tr></thead><tbody>';
		
		if (empty($preview_rows)) {
			$preview_html .= '<tr><td colspan="' . count($raw_headers) . '">No valid data rows found in CSV.</td></tr>';
		} else {
			foreach ($preview_rows as $row) {
				$preview_html .= '<tr>';
				
				foreach ($row as $cell) {
					$preview_html .= '<td>' . esc_html($cell) . '</td>';
				}
				
				$preview_html .= '</tr>';
			}
		}
		
		$preview_html .= '</tbody></table>';
		
		// Add a note about automatic column detection
		$preview_html .= '<div style="margin-top: 15px; padding: 10px; background-color: #f0f0f0; border-left: 4px solid #46b450;">';
		$preview_html .= '<p><strong>Note:</strong> Standard column headers have been automatically detected based on your import type. Please map the columns from your CSV to the appropriate database fields below.</p>';
		$preview_html .= '</div>';
		
		$preview_html .= '</div>';
		
		// Send success response with predefined headers only
		wp_send_json_success(array(
			'preview_html' => $preview_html,
			'columns' => $raw_headers,  // Send raw headers for display
			'expected_columns' => $expected_headers, // Send expected columns for mapping
			'import_type' => $import_type,
			'update_existing' => $update_existing,
			'file_id' => $file_id
		));
		exit;
	}
	
	/**
	 * AJAX handler for processing import
	 */
	public function ajax_process_import() {
		// Explicitly state content type for response
		header('Content-Type: application/json');
		
		// Start debugging
		error_log('=== STARTING IMPORT PROCESS ===');
		
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => 'Security check failed.'));
			exit;
		}
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
			exit;
		}
		
		$file_id = isset($_POST['file_id']) ? sanitize_text_field($_POST['file_id']) : '';
		$import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : '';
		$update_existing = isset($_POST['update_existing']) ? filter_var($_POST['update_existing'], FILTER_VALIDATE_BOOLEAN) : false;
		$mapping = isset($_POST['mapping']) ? $_POST['mapping'] : array();
		
		// Debug
		error_log('Import type: ' . $import_type);
		error_log('Update existing: ' . ($update_existing ? 'Yes' : 'No'));
		error_log('Import mapping: ' . print_r($mapping, true));
		
		if (empty($file_id) || empty($import_type) || empty($mapping)) {
			error_log('ERROR: Missing required import parameters');
			wp_send_json_error(array('message' => 'Missing required import parameters.'));
			exit;
		}
		
		// Get temporary file path
		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/csd-temp/';
		$file_path = $temp_dir . $file_id . '.csv';
		
		if (!file_exists($file_path)) {
			error_log('ERROR: Import file not found: ' . $file_path);
			wp_send_json_error(array('message' => 'Import file not found.'));
			exit;
		}
		
		// Open CSV file
		$file = fopen($file_path, 'r');
		
		if (!$file) {
			error_log('ERROR: Could not open import file');
			wp_send_json_error(array('message' => 'Could not open import file.'));
			exit;
		}
		
		// Get headers
		$headers = fgetcsv($file);
		
		if (!$headers) {
			fclose($file);
			error_log('ERROR: Could not read CSV headers');
			wp_send_json_error(array('message' => 'Could not read CSV headers.'));
			exit;
		}
		
		// Debug log
		error_log('CSV Headers: ' . print_r($headers, true));
		error_log('Header count: ' . count($headers));
		
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
			$processed_schools = array(); // Track processed schools to avoid duplicates
			
			while (($row = fgetcsv($file)) !== false) {
				$row_number++;
				
				if (count($row) !== count($headers)) {
					$results['errors'][] = "Row {$row_number}: Column count mismatch, skipping.";
					error_log("Row {$row_number}: Column count mismatch - headers: " . count($headers) . ", row: " . count($row));
					continue;
				}
				
				// Create associative array from row using our new mapping approach
				$row_data = array();
				
				// THE KEY CHANGE: Process the mapping correctly
				// The mapping is now in the format db_field => csv_column
				foreach ($mapping as $dbField => $csvColumn) {
					// Find the index of this CSV column in the headers array
					$index = array_search($csvColumn, $headers);
					
					// Only process if we found the column and have a value
					if ($index !== false && isset($row[$index])) {
						// Special handling for sport_department
						if ($csvColumn === 'sport___department' && $dbField === 'sport_department') {
							$row_data['sport_department'] = $row[$index];
						} else {
							$row_data[$dbField] = $row[$index];
						}
					}
				}
				
				// Debug the first row in detail
				if ($row_number == 2) {
					error_log('Raw CSV row: ' . print_r($row, true));
					error_log('Processed row data: ' . print_r($row_data, true));
					
					// Check for required fields
					if (empty($row_data['school_name'])) {
						error_log('WARNING: Missing school_name in the first row');
					}
					
					if (empty($row_data['full_name'])) {
						error_log('WARNING: Missing full_name in the first row');
					}
				}
				
				// Skip empty rows
				if (empty($row_data)) {
					$results['errors'][] = "Row {$row_number}: No valid data after mapping, skipping.";
					error_log("Row {$row_number}: No valid data after mapping, skipping.");
					continue;
				}
				
				// Process based on import type
				if ($import_type === 'schools') {
					$this->process_school_import($row_data, $update_existing, $results);
				} elseif ($import_type === 'staff') {
					$this->process_staff_import($row_data, $update_existing, $results);
				} elseif ($import_type === 'both' || $import_type === 'combined') {
					// For combined format, each row has both staff and school information
					// We need to first process the school, then the staff member
					
					// Extract school data from the row
					$school_data = array();
					foreach ($row_data as $key => $value) {
						// Check if this is a school field
						if (in_array($key, array(
							'school_name', 'street_address_line_1', 'street_address_line_2', 
							'street_address_line_3', 'city', 'state', 'zipcode', 'country', 
							'county', 'school_divisions', 'school_conferences', 'school_level', 
							'school_type', 'school_enrollment', 'mascot', 'school_colors', 
							'school_website', 'athletics_website', 'athletics_phone', 'football_division'
						))) {
							$school_data[$key] = $value;
						}
					}
					
					// Only process the school if it has a name
					if (!empty($school_data['school_name'])) {
						error_log("Processing school: " . $school_data['school_name']);
						
						// Check if we've already processed this school in this import
						if (!isset($processed_schools[$school_data['school_name']])) {
							$school_id = $this->process_school_import($school_data, $update_existing, $results);
							if ($school_id) {
								error_log("School processed, ID: " . $school_id);
								$processed_schools[$school_data['school_name']] = $school_id;
							} else {
								error_log("Failed to process school: " . $school_data['school_name']);
							}
						} else {
							$school_id = $processed_schools[$school_data['school_name']];
							error_log("Using already processed school, ID: " . $school_id);
						}
						
						// Extract staff data
						$staff_data = array();
						foreach ($row_data as $key => $value) {
							// Check if this is a staff field
							if (in_array($key, array(
								'full_name', 'title', 'sport_department', 'email', 'phone'
							))) {
								$staff_data[$key] = $value;
							}
						}
						
						// Only process staff if there's a full name
						if (!empty($staff_data['full_name']) && $school_id) {
							error_log("Processing staff member: " . $staff_data['full_name']);
							$staff_data['school_id'] = $school_id;
							$staff_result = $this->process_staff_import($staff_data, $update_existing, $results);
							if ($staff_result) {
								error_log("Staff processed, ID: " . $staff_result);
							} else {
								error_log("Failed to process staff: " . $staff_data['full_name']);
							}
						} else {
							if (empty($staff_data['full_name'])) {
								error_log("Missing full_name for staff member");
							}
							if (empty($school_id)) {
								error_log("Missing school_id for staff member");
							}
						}
					} else {
						error_log("Missing school_name, skipping staff member");
					}
				}
			}
			
			error_log("Import results: " . print_r($results, true));
			
			// Commit transaction
			$wpdb->query('COMMIT');
			error_log("Database transaction committed");
			
			// Close and delete file
			fclose($file);
			@unlink($file_path);
			
			// Generate results HTML
			$results_html = $this->generate_import_results_html($results);
			
			wp_send_json_success(array(
				'results_html' => $results_html
			));
			exit;
		} catch (Exception $e) {
			// Rollback transaction
			$wpdb->query('ROLLBACK');
			error_log("ERROR: Exception during import: " . $e->getMessage());
			
			// Close file
			fclose($file);
			
			wp_send_json_error(array('message' => $e->getMessage()));
			exit;
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
			$results['errors'][] = 'Missing required field: School Name';
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
			if (isset($row_data[$field]) && !empty($row_data[$field])) {
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
					$results['errors'][] = "Error updating school: {$school_data['school_name']}";
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
				$results['errors'][] = "Error adding school: {$school_data['school_name']}";
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
			$results['errors'][] = 'Missing required field: Full Name';
			return false;
		}
		
		$staff_data = array(
			'full_name' => sanitize_text_field($row_data['full_name']),
			'date_updated' => current_time('mysql')
		);
		
		// Map other fields
		$staff_fields = array('title', 'sport_department', 'email', 'phone');
		
		foreach ($staff_fields as $field) {
			if (isset($row_data[$field]) && !empty($row_data[$field])) {
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
		} elseif (isset($row_data['school_identifier']) || isset($row_data['school_name'])) {
			// Look up school by name
			$school_name = isset($row_data['school_identifier']) ? $row_data['school_identifier'] : $row_data['school_name'];
			$school = $wpdb->get_row($wpdb->prepare(
				"SELECT id FROM " . csd_table('schools') . " WHERE school_name = %s",
				$school_name
			));
			
			if ($school) {
				$school_id = $school->id;
			}
		}
		
		// Check if staff already exists
		$existing_staff = null;
		
		// First try to find by email if available
		if (!empty($staff_data['email'])) {
			$existing_staff = $wpdb->get_row($wpdb->prepare(
				"SELECT id FROM " . csd_table('staff') . " WHERE email = %s",
				$staff_data['email']
			));
		}
		
		// If not found by email, try to find by name
		if (!$existing_staff) {
			$existing_staff = $wpdb->get_row($wpdb->prepare(
				"SELECT id FROM " . csd_table('staff') . " WHERE full_name = %s",
				$staff_data['full_name']
			));
		}
		
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
					$results['errors'][] = "Error updating staff: {$staff_data['full_name']}";
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
				$results['errors'][] = "Error adding staff: {$staff_data['full_name']}";
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
		
		$html .= '<h4>Import Summary</h4>';
		
		$html .= '<ul>';
		$html .= '<li>Schools added: ' . $results['schools_added'] . '</li>';
		$html .= '<li>Schools updated: ' . $results['schools_updated'] . '</li>';
		$html .= '<li>Staff added: ' . $results['staff_added'] . '</li>';
		$html .= '<li>Staff updated: ' . $results['staff_updated'] . '</li>';
		$html .= '</ul>';
		
		if (!empty($results['errors'])) {
			$html .= '<h4>Errors</h4>';
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
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_die('Security check failed.');
		}
		
		if (!current_user_can('manage_options')) {
			wp_die('You do not have permission to perform this action.');
		}
		
		$export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'combined';
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
		} elseif ($export_type === 'combined') {
			$this->export_combined_format($output, $include_headers, $export_school);
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
			'school_website', 'athletics_website', 'athletics_phone', 'football_division'
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
			'school_identifier'
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
	 * Export data in the combined format
	 * 
	 * @param resource $output Output handle
	 * @param bool $include_headers Whether to include headers
	 * @param int $school_id Specific school ID to export
	 */
	private function export_combined_format($output, $include_headers, $school_id = 0) {
		$wpdb = csd_db_connection();
		
		// Define headers - match the expected format
		$headers = array(
			'full_name', 'title', 'phone', 'email', 'sport___department',
			'school_name', 'street_address_line_1', 'street_address_line_2', 'street_address_line_3',
			'city', 'state', 'county', 'zipcode', 'country', 'school_level',
			'school_website', 'athletics_website', 'athletics_phone',
			'mascot', 'school_type', 'school_enrollment', 'football_division',
			'school_colors', 'school_divisions', 'school_conferences'
		);
		
		if ($include_headers) {
			fputcsv($output, $headers);
		}
		
		// Build query
		$query = "SELECT 
				s.full_name, s.title, s.phone, s.email, s.sport_department as sport___department,
				sch.school_name, sch.street_address_line_1, sch.street_address_line_2, sch.street_address_line_3,
				sch.city, sch.state, sch.county, sch.zipcode, sch.country, sch.school_level,
				sch.school_website, sch.athletics_website, sch.athletics_phone,
				sch.mascot, sch.school_type, sch.school_enrollment, sch.football_division,
				sch.school_colors, sch.school_divisions, sch.school_conferences
			  FROM " . csd_table('staff') . " s
			  JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
			  JOIN " . csd_table('schools') . " sch ON ss.school_id = sch.id";
		
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
