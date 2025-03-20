/**
 * Admin JavaScript (assets/js/admin.js)
 */
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
});
