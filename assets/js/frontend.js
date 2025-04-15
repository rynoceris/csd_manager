/**
 * Frontend JavaScript (assets/js/frontend.js)
 */
jQuery(document).ready(function($) {
	// Schools filtering and pagination functionality is handled inline in the shortcode output
	
	// Staff filtering and pagination functionality is handled inline in the shortcode output
	
	// Basic responsive table handling
	$('.csd-schools-table, .csd-staff-table').each(function() {
		var headerText = [];
		
		// Get header text for each column
		$(this).find('thead th').each(function(index) {
			headerText.push($(this).text());
		});
		
		// Add data attributes to each cell in the tbody
		$(this).find('tbody tr').each(function() {
			$(this).find('td').each(function(index) {
				$(this).attr('data-title', headerText[index]);
			});
		});
	});
	
	// Handle click on table rows for mobile if they have a link
	$('.csd-schools-table tbody tr, .csd-staff-table tbody tr').on('click', function(e) {
		// Only trigger on mobile and if clicked directly on the row (not a link inside)
		if (window.innerWidth < 768 && e.target.tagName !== 'A' && $(this).find('a').length) {
			window.location = $(this).find('a:first').attr('href');
		}
	});
});