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
				
				// Replace this function in your import-export.php file
				function setupColumnMapping(columns, importType, expectedColumns) {
					var $mappingFields = $('#csd-mapping-fields');
					$mappingFields.empty();
					
					// Use the passed expectedColumns parameter
					expectedColumns = expectedColumns || [];
					
					console.log('Original CSV columns:', columns);
					console.log('Expected DB columns:', expectedColumns);
					
					// Create a preview panel to show how data will be mapped
					var $previewPanel = $('<div class="csd-mapping-preview"></div>');
					$previewPanel.append('<h4>Mapping Preview</h4>');
					$previewPanel.append('<p>This shows how your CSV data will be mapped to the database fields.</p>');
					$previewPanel.append('<div id="csd-mapping-preview-content" class="csd-mapping-preview-content"></div>');
					
					// Track the mapped columns to prevent duplicates
					var mappedColumns = {};
					
					// Array of fields that are required
					var requiredFields = ['school_name', 'full_name'];
					
					// Determine available fields based on import type
					var schoolFields = {
						'school_name': 'School Name',
						'street_address_line_1': 'Street Address Line 1',
						'street_address_line_2': 'Street Address Line 2',
						'street_address_line_3': 'Street Address Line 3',
						'city': 'City',
						'state': 'State',
						'zipcode': 'Zip Code',
						'country': 'Country',
						'county': 'County',
						'school_divisions': 'School Divisions',
						'school_conferences': 'School Conferences',
						'school_level': 'School Level',
						'school_type': 'School Type',
						'school_enrollment': 'School Enrollment',
						'mascot': 'Mascot',
						'school_colors': 'School Colors',
						'school_website': 'School Website',
						'athletics_website': 'Athletics Website',
						'athletics_phone': 'Athletics Phone',
						'football_division': 'Football Division'
					};
					
					var staffFields = {
						'full_name': 'Full Name',
						'title': 'Title',
						'sport_department': 'Sport/Department',
						'email': 'Email',
						'phone': 'Phone'
					};
					
					var relationFields = {
						'school_identifier': 'School Identifier'
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
					
					// Function to update the mapping preview
					function updateMappingPreview() {
						var $previewContent = $('#csd-mapping-preview-content');
						$previewContent.empty();
						
						var table = $('<table class="wp-list-table widefat fixed striped"></table>');
						var headerRow = $('<tr></tr>');
						var valueRow = $('<tr></tr>');
						
						// Add headers and sample values
						$('.csd-column-map').each(function() {
							var dbField = $(this).data('field');
							var csvColumn = $(this).val();
							
							if (csvColumn) {
								headerRow.append('<th>' + availableFields[dbField] + '</th>');
								
								// Get sample data from the preview table
								var columnIndex = -1;
								$('#csd-preview-content table thead th').each(function(index) {
									if ($(this).text() === csvColumn) {
										columnIndex = index;
										return false;
									}
								});
								
								var sampleValue = '';
								if (columnIndex >= 0) {
									sampleValue = $('#csd-preview-content table tbody tr:first-child td:eq(' + columnIndex + ')').text();
								}
								
								valueRow.append('<td>' + (sampleValue || '&mdash;') + '</td>');
							}
						});
						
						table.append(headerRow);
						table.append(valueRow);
						$previewContent.append(table);
					}
					
					// Create mapping UI for each expected column from the import type
					$.each(expectedColumns, function(index, dbField) {
						var mappingField = $('<div class="csd-mapping-field"></div>');
						var label = availableFields[dbField] || dbField;
						
						// Highlight required fields
						if (requiredFields.includes(dbField)) {
							label += ' <span class="required" style="color: red;">*</span>';
						}
						
						mappingField.append('<label>' + label + ':</label>');
						
						var select = $('<select class="csd-column-map" data-field="' + dbField + '"></select>');
						select.append('<option value="">' + (requiredFields.includes(dbField) ? 'Please select a column' : '-- Not Mapped --') + '</option>');
						
						// Variables to track best match
						var bestMatch = null;
						var bestMatchScore = 0;
						
						// Add each CSV column as an option
						$.each(columns, function(i, csvColumn) {
							// Only add valid columns
							if (csvColumn && typeof csvColumn === 'string') {
								var matchScore = 0;
								
								// Scoring algorithm to find the best match
								// Exact match gets highest score
								if (csvColumn.toLowerCase() === dbField.toLowerCase()) {
									matchScore = 100;
								} 
								// Special case for sport___department
								else if (csvColumn === 'sport___department' && dbField === 'sport_department') {
									matchScore = 90;
								}
								// Contains match
								else if (csvColumn.toLowerCase().indexOf(dbField.toLowerCase()) !== -1) {
									matchScore = 80;
								}
								// DB field contains CSV column
								else if (dbField.toLowerCase().indexOf(csvColumn.toLowerCase()) !== -1) {
									matchScore = 60;
								}
								// Partial word matches
								else {
									var dbWords = dbField.toLowerCase().split('_');
									var csvWords = csvColumn.toLowerCase().replace(/[^a-z0-9]/g, ' ').split(' ');
									
									$.each(dbWords, function(j, dbWord) {
										if (csvWords.includes(dbWord)) {
											matchScore += 20;
										}
										
										$.each(csvWords, function(k, csvWord) {
											if (dbWord.indexOf(csvWord) !== -1 || csvWord.indexOf(dbWord) !== -1) {
												matchScore += 10;
											}
										});
									});
								}
								
								// Keep track of the best match
								if (matchScore > bestMatchScore) {
									bestMatch = csvColumn;
									bestMatchScore = matchScore;
								}
								
								select.append('<option value="' + csvColumn + '">' + csvColumn + '</option>');
							}
						});
						
						// If we found a good match and no other field has claimed this column, select it
						if (bestMatch && bestMatchScore >= 60 && !mappedColumns[bestMatch]) {
							select.val(bestMatch);
							mappedColumns[bestMatch] = dbField;
						}
						
						// Add change event to prevent duplicate mappings
						select.on('change', function() {
							var selectedColumn = $(this).val();
							var thisField = $(this).data('field');
							
							// Remove this field from the mapped columns
							$.each(mappedColumns, function(col, field) {
								if (field === thisField) {
									delete mappedColumns[col];
								}
							});
							
							// Check if the column is already mapped to another field
							if (selectedColumn && mappedColumns[selectedColumn]) {
								// Find the dropdown that has this column selected
								var conflictField = mappedColumns[selectedColumn];
								
								alert('This column is already mapped to the "' + availableFields[conflictField] + '" field.\n\nPlease select a different column or change the other mapping first.');
								
								// Reset to previous value
								$(this).val('');
								return;
							}
							
							// Add this mapping
							if (selectedColumn) {
								mappedColumns[selectedColumn] = thisField;
							}
							
							// Update the preview
							updateMappingPreview();
						});
						
						mappingField.append(select);
						$mappingFields.append(mappingField);
					});
					
					// Add the preview panel after all fields
					$mappingFields.append($previewPanel);
					
					// Initialize preview
					setTimeout(updateMappingPreview, 100);
					
					// Add validation for the mapping before processing
					$('#csd-process-import').off('click').on('click', function(e) {
						// Check required fields
						var missingRequired = false;
						$.each(requiredFields, function(i, field) {
							var $select = $('.csd-column-map[data-field="' + field + '"]');
							if ($select.length && !$select.val()) {
								missingRequired = true;
								$select.parent().addClass('error');
								alert('The "' + availableFields[field] + '" field is required. Please select a column to map to this field.');
								return false;
							}
						});
						
						if (missingRequired) {
							e.preventDefault();
							return false;
						}
						
						// Continue with original click handler
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
				}
			});
		</script>
		<?php
	}
	
	/**
	 * AJAX handler for previewing import with Mac CR line ending support
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
		
		try {
			// Read content and handle Mac CR line endings
			$content = file_get_contents($temp_file);
			if ($content === false) {
				throw new Exception('Could not read CSV file');
			}
			
			// Log line ending detection
			$cr_count = substr_count($content, "\r") - substr_count($content, "\r\n");
			$lf_count = substr_count($content, "\n") - substr_count($content, "\r\n");
			$crlf_count = substr_count($content, "\r\n");
			
			error_log("CSV Preview - Line endings: CR: $cr_count, LF: $lf_count, CRLF: $crlf_count");
			
			// Split by CR if it's the dominant line ending
			if ($cr_count > $lf_count && $cr_count > $crlf_count) {
				$lines = explode("\r", $content);
				error_log("Using CR as line separator, found " . count($lines) . " lines");
			} else {
				// Otherwise normalize to LF for standard processing
				$content = str_replace("\r\n", "\n", $content);
				$content = str_replace("\r", "\n", $content);
				$lines = explode("\n", $content);
				error_log("Using normalized LF as line separator, found " . count($lines) . " lines");
			}
			
			// Filter empty lines
			$lines = array_values(array_filter($lines, function($line) {
				return trim($line) !== '';
			}));
			
			error_log("After filtering empty lines: " . count($lines) . " lines");
			
			if (count($lines) < 2) {
				wp_send_json_error(array('message' => 'Could not detect multiple lines in the CSV file.'));
				exit;
			}
			
			// Parse lines to rows
			$rows = [];
			foreach ($lines as $line) {
				$rows[] = str_getcsv($line);
			}
			
			$headers = $rows[0]; // First row is headers
			
			// Get first few rows for preview
			$preview_rows = array_slice($rows, 1, 5); // Skip header, take next 5 rows
			
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
			
			foreach ($headers as $header) {
				$preview_html .= '<th>' . esc_html($header) . '</th>';
			}
			
			$preview_html .= '</tr></thead><tbody>';
			
			if (empty($preview_rows)) {
				$preview_html .= '<tr><td colspan="' . count($headers) . '">No valid data rows found in CSV.</td></tr>';
			} else {
				foreach ($preview_rows as $row) {
					$preview_html .= '<tr>';
					
					// Make sure we don't try to access cells beyond what exists
					for ($i = 0; $i < count($headers); $i++) {
						$cell = isset($row[$i]) ? $row[$i] : '';
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
			
			// Send success response
			wp_send_json_success(array(
				'preview_html' => $preview_html,
				'columns' => $headers,
				'expected_columns' => $expected_headers,
				'import_type' => $import_type,
				'update_existing' => $update_existing,
				'file_id' => $file_id
			));
			exit;
			
		} catch (Exception $e) {
			wp_send_json_error(array('message' => $e->getMessage()));
			exit;
		}
	}
	
	/**
	 * AJAX handler for processing CSV with Mac-style CR line endings
	 */
	public function ajax_process_import() {
		// Set content type for response
		header('Content-Type: application/json');
		
		// Start debugging
		error_log('=== STARTING MAC CR LINE ENDING CSV IMPORT PROCESS ===');
		
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
		$update_existing = isset($_POST['update_existing']) ? filter_var($_POST['update_existing'], FILTER_VALIDATE_BOOLEAN) : false;
		
		error_log('Update existing: ' . ($update_existing ? 'Yes' : 'No'));
		
		// Get temporary file path
		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/csd-temp/';
		$file_path = $temp_dir . $file_id . '.csv';
		
		if (!file_exists($file_path)) {
			error_log('ERROR: Import file not found: ' . $file_path);
			wp_send_json_error(array('message' => 'Import file not found.'));
			exit;
		}
		
		try {
			// Read file content
			$content = file_get_contents($file_path);
			if ($content === false) {
				throw new Exception('Could not read CSV file');
			}
			
			// Log the content size
			error_log('CSV file size: ' . strlen($content) . ' bytes');
			
			// Split by CR (\r) which we know is the primary line ending from diagnostics
			$lines = explode("\r", $content);
			
			// Log the result
			error_log('Found ' . count($lines) . ' lines after splitting on CR');
			
			// Filter empty lines
			$lines = array_values(array_filter($lines, function($line) {
				return trim($line) !== '';
			}));
			
			error_log('After filtering empty lines: ' . count($lines));
			
			// If we don't have enough lines, try other approaches
			if (count($lines) < 2) {
				error_log('Not enough lines found with CR splitting, trying other approaches');
				
				// Try with standard line break normalization
				$normalized = str_replace(array("\r\n", "\r"), "\n", $content);
				$lines = explode("\n", $normalized);
				
				// Filter empty lines again
				$lines = array_values(array_filter($lines, function($line) {
					return trim($line) !== '';
				}));
				
				error_log('After normalization: ' . count($lines) . ' lines');
			}
			
			if (count($lines) < 2) {
				throw new Exception('Could not detect multiple lines in the CSV file. This CSV appears to be malformed.');
			}
			
			// Parse each line into fields
			$rows = array();
			foreach ($lines as $i => $line) {
				// Remove any residual \n from mixed line endings
				$line = str_replace("\n", "", $line);
				
				// Parse CSV line
				$row = str_getcsv($line);
				
				// Clean up fields - convert "NULL" to empty string
				foreach ($row as $j => $field) {
					if (trim($field) === 'NULL') {
						$row[$j] = '';
					}
				}
				
				$rows[] = $row;
				
				// Log the first few rows for debugging
				if ($i < 3) {
					error_log('Row ' . ($i+1) . ' has ' . count($row) . ' columns');
					if (!empty($row)) {
						error_log('First few columns: ' . implode(', ', array_slice($row, 0, min(5, count($row)))));
					}
				}
			}
			
			$headers = $rows[0]; // First row is headers
			$school_row = $rows[1]; // Second row is school data
			$staff_rows = array_slice($rows, 2); // Remaining rows are staff
			
			error_log('Headers count: ' . count($headers));
			error_log('School row columns: ' . count($school_row));
			error_log('Staff rows count: ' . count($staff_rows));
			
			// Map headers to indexes
			$header_map = array();
			foreach ($headers as $index => $header) {
				$header = strtolower(trim($header));
				if (!empty($header)) {
					$header_map[$header] = $index;
				}
			}
			
			error_log('Header map: ' . print_r($header_map, true));
			
			// Check for critical headers
			$critical_headers = array('school_name', 'full_name');
			$missing_headers = array();
			
			foreach ($critical_headers as $critical) {
				if (!isset($header_map[$critical])) {
					$missing_headers[] = $critical;
				}
			}
			
			if (!empty($missing_headers)) {
				error_log('Missing critical headers: ' . implode(', ', $missing_headers));
				throw new Exception('CSV is missing required headers: ' . implode(', ', $missing_headers));
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
				// 1. Process school data from row 2
				$school_data = array();
				
				// Map school fields from the school row
				$school_fields = array(
					'school_name', 'street_address_line_1', 'street_address_line_2', 
					'street_address_line_3', 'city', 'state', 'county', 'zipcode', 
					'country', 'school_level', 'school_website', 'athletics_website', 
					'athletics_phone', 'mascot', 'school_type', 'school_enrollment', 
					'football_division', 'school_colors', 'school_divisions', 'school_conferences'
				);
				
				foreach ($school_fields as $field) {
					$index = isset($header_map[strtolower($field)]) ? $header_map[strtolower($field)] : null;
					if ($index !== null && isset($school_row[$index]) && $school_row[$index] !== '') {
						$school_data[$field] = trim($school_row[$index]);
					}
				}
				
				error_log('Processed school data: ' . print_r($school_data, true));
				
				// Validate school data
				if (empty($school_data['school_name'])) {
					throw new Exception('School name is required in row 2.');
				}
				
				// Process the school (create or update)
				$school_id = $this->process_school_import($school_data, $update_existing, $results);
				
				if (!$school_id) {
					throw new Exception('Failed to process school record.');
				}
				
				error_log('Processed school: ' . $school_data['school_name'] . ', ID: ' . $school_id);
				
				// 2. Process staff data from rows 3+
				foreach ($staff_rows as $row_index => $staff_row) {
					$staff_data = array();
					
					// Map staff fields from the staff row
					$staff_fields = array(
						'full_name', 'title', 'phone', 'email'
					);
					
					// Special handling for sport___department
					$sport_dept_key = 'sport___department';
					$sport_dept_index = isset($header_map[strtolower($sport_dept_key)]) ? $header_map[strtolower($sport_dept_key)] : null;
					
					foreach ($staff_fields as $field) {
						$index = isset($header_map[strtolower($field)]) ? $header_map[strtolower($field)] : null;
						if ($index !== null && isset($staff_row[$index]) && $staff_row[$index] !== '') {
							$staff_data[$field] = trim($staff_row[$index]);
						}
					}
					
					// Handle sport_department
					if ($sport_dept_index !== null && isset($staff_row[$sport_dept_index]) && $staff_row[$sport_dept_index] !== '') {
						$staff_data['sport_department'] = trim($staff_row[$sport_dept_index]);
					}
					
					// Skip staff rows without a name
					if (empty($staff_data['full_name'])) {
						error_log('Skipping staff row ' . ($row_index + 3) . ' without a name');
						continue;
					}
					
					// Add school ID to staff data
					$staff_data['school_id'] = $school_id;
					
					error_log('Processing staff: ' . $staff_data['full_name']);
					error_log('Staff data: ' . print_r($staff_data, true));
					
					// Process the staff member (create or update)
					$staff_id = $this->process_staff_import($staff_data, $update_existing, $results);
					
					if (!$staff_id) {
						$results['errors'][] = 'Failed to process staff member: ' . $staff_data['full_name'];
						error_log('Failed to process staff: ' . $staff_data['full_name']);
					} else {
						error_log('Successfully processed staff: ' . $staff_data['full_name'] . ', ID: ' . $staff_id);
					}
				}
				
				// Commit transaction
				$wpdb->query('COMMIT');
				error_log('Database transaction committed - Import successful!');
				
				// Clean up
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
				error_log('ERROR: Import failed: ' . $e->getMessage());
				wp_send_json_error(array('message' => $e->getMessage()));
				exit;
			}
			
		} catch (Exception $e) {
			error_log('ERROR: Exception during import: ' . $e->getMessage());
			wp_send_json_error(array('message' => $e->getMessage()));
			exit;
		}
	}
	
	/**
	 * Read CSV file with support for various line endings including Mac CR endings
	 * 
	 * @param string $file_path Path to CSV file
	 * @return array Array of CSV rows
	 */
	private function read_csv_file($file_path) {
		$rows = array();
		
		// Check if file exists
		if (!file_exists($file_path)) {
			error_log("CSV file not found: " . $file_path);
			throw new Exception('CSV file not found');
		}
		
		// Get file content
		$content = file_get_contents($file_path);
		if ($content === false) {
			error_log("Could not read CSV file: " . $file_path);
			throw new Exception('Could not read CSV file');
		}
		
		// Remove UTF-8 BOM if present
		$bom = pack('H*', 'EFBBBF');
		$content = preg_replace("/^$bom/", '', $content);
		
		// Log file size
		error_log("CSV file size: " . strlen($content) . " bytes");
		
		// Detect dominant line ending type
		$cr_count = substr_count($content, "\r") - substr_count($content, "\r\n");
		$lf_count = substr_count($content, "\n") - substr_count($content, "\r\n");
		$crlf_count = substr_count($content, "\r\n");
		
		error_log("Line ending detection - CR: {$cr_count}, LF: {$lf_count}, CRLF: {$crlf_count}");
		
		// Split by appropriate line ending
		$lines = array();
		
		// If Mac-style CR line endings dominate
		if ($cr_count > $lf_count && $cr_count > $crlf_count) {
			$lines = explode("\r", $content);
			error_log("Using CR as line separator, found " . count($lines) . " lines");
		} 
		// If Windows-style CRLF line endings dominate
		else if ($crlf_count > 0) {
			$content = str_replace("\r\n", "\n", $content); // Normalize to LF
			$content = str_replace("\r", "\n", $content);   // Convert any remaining CR to LF
			$lines = explode("\n", $content);
			error_log("Using CRLF normalized to LF as line separator, found " . count($lines) . " lines");
		}
		// Otherwise use Unix-style LF line endings
		else {
			$content = str_replace("\r", "\n", $content); // Convert any CR to LF for consistency
			$lines = explode("\n", $content);
			error_log("Using LF as line separator, found " . count($lines) . " lines");
		}
		
		// Filter empty lines
		$lines = array_values(array_filter($lines, function($line) {
			return trim($line) !== '';
		}));
		
		error_log("After filtering empty lines: " . count($lines) . " lines");
		
		// Check if we have enough lines
		if (count($lines) < 2) {
			error_log("Not enough lines in CSV file (must have at least header + data row)");
			throw new Exception('CSV file must contain at least 2 rows (header + data row)');
		}
		
		// Parse each line into fields
		foreach ($lines as $i => $line) {
			// Parse the line as CSV
			$row = str_getcsv($line);
			
			// Clean up each field
			foreach ($row as $j => $field) {
				// Convert "NULL" to empty string
				if (trim($field) === 'NULL') {
					$row[$j] = '';
				}
			}
			
			$rows[] = $row;
			
			// Log the first few rows for debugging
			if ($i < 3) {
				error_log("Row " . ($i+1) . " has " . count($row) . " columns");
			}
		}
		
		return $rows;
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
					// Remove commas from numbers like "3,236"
					$school_data[$field] = intval(str_replace(',', '', $row_data[$field]));
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
				// Skip update but return the ID
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
		
		// Get school ID from data
		$school_id = isset($row_data['school_id']) ? intval($row_data['school_id']) : 0;
		
		if (!$school_id) {
			$results['errors'][] = "No school ID provided for staff: {$staff_data['full_name']}";
			return false;
		}
		
		// Check if staff already exists at this school
		$existing_staff = null;
		
		// First check by name + school to find exact matches
		$existing_staff = $wpdb->get_row($wpdb->prepare(
			"SELECT s.id FROM " . csd_table('staff') . " s
			JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
			WHERE s.full_name = %s AND ss.school_id = %d",
			$staff_data['full_name'], $school_id
		));
		
		// If not found, try by email if available
		if (!$existing_staff && !empty($staff_data['email'])) {
			$existing_staff = $wpdb->get_row($wpdb->prepare(
				"SELECT s.id FROM " . csd_table('staff') . " s
				JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
				WHERE s.email = %s AND ss.school_id = %d",
				$staff_data['email'], $school_id
			));
		}
		
		// If still not found, check by just name without school
		if (!$existing_staff) {
			$existing_staff = $wpdb->get_row($wpdb->prepare(
				"SELECT id FROM " . csd_table('staff') . " WHERE full_name = %s",
				$staff_data['full_name']
			));
		}
		
		if ($existing_staff) {
			$staff_id = $existing_staff->id;
			
			if ($update_existing) {
				// Update existing staff
				$result = $wpdb->update(
					csd_table('staff'),
					$staff_data,
					array('id' => $staff_id)
				);
				
				if ($result !== false) {
					$results['staff_updated']++;
				} else {
					$results['errors'][] = "Error updating staff: {$staff_data['full_name']}";
					return false;
				}
			}
		} else {
			// Add new staff
			$staff_data['date_created'] = current_time('mysql');
			
			$result = $wpdb->insert(
				csd_table('staff'),
				$staff_data
			);
			
			if ($result) {
				$staff_id = $wpdb->insert_id;
				$results['staff_added']++;
			} else {
				$results['errors'][] = "Error adding staff: {$staff_data['full_name']}";
				return false;
			}
		}
		
		// Handle school association
		if ($staff_id && $school_id) {
			// Check if association already exists
			$existing_association = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM " . csd_table('school_staff') . " 
				 WHERE school_id = %d AND staff_id = %d",
				$school_id, $staff_id
			));
			
			if (!$existing_association) {
				// Create new association
				$wpdb->insert(
					csd_table('school_staff'),
					array(
						'school_id' => $school_id,
						'staff_id' => $staff_id,
						'date_created' => current_time('mysql')
					)
				);
			}
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
