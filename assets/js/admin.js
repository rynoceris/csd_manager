/**
 * Admin JavaScript (assets/js/admin.js)
 */
 /**
  * This function initializes the simplified CSV import interface
  * It should replace the complex column mapping in your current code
  */
 function initSimplifiedCsvImport() {
	 // Check if we're on the import page
	 if (!$('#csd-import-form').length) {
		 return;
	 }
	 
	 // Hide unnecessary options
	 $('#csd-import-form .csd-import-option:first-child')
		 .nextAll('.csd-import-option')
		 .hide();
	 
	 // Change the first import option label
	 $('#csd-import-form .csd-import-option:first-child label').html(
		 '<input type="radio" name="import_type" value="combined" checked>' +
		 'Import School & Staff Data'
	 );
	 
	 $('#csd-import-form .csd-import-option:first-child p.description').html(
		 'Import a CSV with school data in row 2 and staff members in subsequent rows.'
	 );
	 
	 // Add a help notice
	 $('#csd-import-form').prepend(
		 '<div class="notice notice-info inline" style="margin-top: 15px; padding: 10px;">' +
		 '<p><strong>CSV Format Instructions:</strong></p>' +
		 '<ul style="list-style-type: disc; margin-left: 20px;">' +
		 '<li>Row 1: Column headers (full_name, title, phone, email, sport___department, school_name, etc.)</li>' +
		 '<li>Row 2: School data (leave staff columns empty)</li>' +
		 '<li>Rows 3+: Staff data (only fill full_name, title, phone, email, sport___department columns)</li>' +
		 '</ul>' +
		 '<p>This importer is specifically designed for this format and will automatically handle mapping.</p>' +
		 '</div>'
	 );
	 
	 // Hide the mapping section in the preview UI
	 $(document).on('show', '#csd-import-preview', function() {
		 $('.csd-column-mapping').hide();
		 $('#csd-preview-content').after(
			 '<div class="notice notice-success" style="margin-top: 20px; padding: 15px;">' +
			 '<p><strong>Ready to Import!</strong></p>' +
			 '<p>Your CSV file has been analyzed and is ready for import. The CSV format has been automatically detected.</p>' +
			 '<p>Click "Process Import" to create/update the school and staff records from this file.</p>' +
			 '</div>'
		 );
	 });
	 
	 // Modify the original form submission to skip mapping
	 $('#csd-import-form').off('submit').on('submit', function(e) {
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
					 
					 // Store file data for processing
					 $('#csd-process-import').data('file_id', response.data.file_id);
					 $('#csd-process-import').data('import_type', 'combined');
					 $('#csd-process-import').data('update_existing', response.data.update_existing);
					 
					 // Trigger the show event we defined above
					 $('#csd-import-preview').trigger('show');
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
	 
	 // Replace the process import click handler
	 $('#csd-process-import').off('click').on('click', function() {
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
				 import_type: 'combined',
				 update_existing: $('#csd-process-import').data('update_existing'),
				 // No mapping needed for our specialized importer
				 mapping: {},
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
	 
	 // Helper function to show status messages
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
 }
 
jQuery(document).ready(function($) {
	// Initialize Select2 for dropdowns if available
	if (typeof $.fn.select2 !== 'undefined') {
		$('.csd-select2').select2({
			width: '100%'
		});
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
	
	// Date picker initialization if available
	if (typeof $.fn.datepicker !== 'undefined') {
		$('.csd-datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	}
	
	// Handle confirm deletes
	$('.csd-confirm-delete').on('click', function(e) {
		if (!confirm($(this).data('confirm') || 'Are you sure you want to delete this item?')) {
			e.preventDefault();
		}
	});
	
	// Copy to clipboard functionality
	$('.csd-copy-to-clipboard').on('click', function(e) {
		e.preventDefault();
		
		var text = $(this).data('text');
		
		// Create a temporary textarea element to copy from
		var textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.style.position = 'fixed';
		document.body.appendChild(textarea);
		textarea.select();
		
		try {
			// Execute the copy command
			document.execCommand('copy');
			
			// Show success message
			var originalText = $(this).text();
			$(this).text('Copied!');
			
			// Reset text after timeout
			setTimeout(function() {
				$(this).text(originalText);
			}.bind(this), 2000);
		} catch (err) {
			console.error('Could not copy text', err);
		}
		
		// Remove the temporary element
		document.body.removeChild(textarea);
	});
	// Initialize the simplified CSV import functionality
	initSimplifiedCsvImport();
});
