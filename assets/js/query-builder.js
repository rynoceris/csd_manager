/**
 * Query Builder JavaScript
 */
(function($) {
	'use strict';

	// Initialize everything when the document is ready
	$(document).ready(function() {
		// Create a fallback for the AJAX object if needed
		if (typeof csd_ajax === 'undefined') {
			console.log('Creating fallback for csd_ajax');
			window.csd_ajax = {
				ajax_url: ajaxurl || '/wp-admin/admin-ajax.php',
				nonce: ''  // We'll need to get this from the page
			};
		}

		// Tab switching
		$('.csd-query-tab').on('click', function() {
			var panelId = $(this).data('panel');
			
			// Update active tab
			$('.csd-query-tab').removeClass('active');
			$(this).addClass('active');
			
			// Show the corresponding panel
			$('.csd-query-panel').removeClass('active');
			$('#csd-' + panelId + '-panel').addClass('active');
		});
		
		// Select all/none fields
		$('#csd-select-all-fields').on('click', function() {
			$('input[name="fields[]"]').prop('checked', true);
		});
		
		$('#csd-select-none-fields').on('click', function() {
			$('input[name="fields[]"]').prop('checked', false);
		});
		
		// Add condition
		$('.csd-add-condition').on('click', function() {
			var group = $(this).closest('.csd-condition-group');
			var groupIndex = group.data('group');
			var conditions = group.find('.csd-conditions');
			var newIndex = conditions.find('.csd-condition').length;
			
			// Clone the first condition as a template
			var newCondition = conditions.find('.csd-condition:first').clone();
			
			// Update names and clear values
			newCondition.attr('data-index', newIndex);
			newCondition.find('.csd-condition-field').attr('name', 'conditions[' + groupIndex + '][' + newIndex + '][field]').val('');
			newCondition.find('.csd-condition-operator').attr('name', 'conditions[' + groupIndex + '][' + newIndex + '][operator]').val('');
			newCondition.find('.csd-condition-value').attr('name', 'conditions[' + groupIndex + '][' + newIndex + '][value]').val('');
			newCondition.find('.csd-condition-value-2').attr('name', 'conditions[' + groupIndex + '][' + newIndex + '][value2]').val('');
			newCondition.find('.csd-condition-relation').attr('name', 'conditions[' + groupIndex + '][' + newIndex + '][relation]');
			
			// Show remove button
			newCondition.find('.csd-remove-condition').show();
			
			// Hide BETWEEN second value by default
			newCondition.find('.csd-between-values').hide();
			
			// Add the new condition
			conditions.append(newCondition);
			
			// Update the last condition's relation visibility (hide on the last one)
			updateRelationVisibility(group);
		});
		
		// Add condition group
		$('#csd-add-group').on('click', function() {
			var container = $('#csd-conditions-container');
			var newGroupIndex = container.find('.csd-condition-group').length;
			
			// Clone the first group as a template
			var newGroup = container.find('.csd-condition-group:first').clone();
			
			// Update group index and reset conditions
			newGroup.attr('data-group', newGroupIndex);
			newGroup.find('h4').text('Condition Group ' + (newGroupIndex + 1));
			
			// Clear all conditions except the first one
			newGroup.find('.csd-condition:not(:first)').remove();
			
			// Reset first condition
			var firstCondition = newGroup.find('.csd-condition:first');
			firstCondition.attr('data-index', 0);
			firstCondition.find('.csd-condition-field').attr('name', 'conditions[' + newGroupIndex + '][0][field]').val('');
			firstCondition.find('.csd-condition-operator').attr('name', 'conditions[' + newGroupIndex + '][0][operator]').val('');
			firstCondition.find('.csd-condition-value').attr('name', 'conditions[' + newGroupIndex + '][0][value]').val('');
			firstCondition.find('.csd-condition-value-2').attr('name', 'conditions[' + newGroupIndex + '][0][value2]').val('');
			firstCondition.find('.csd-condition-relation').attr('name', 'conditions[' + newGroupIndex + '][0][relation]');
			
			// Hide BETWEEN second value by default
			firstCondition.find('.csd-between-values').hide();
			
			// Show remove group button for all groups
			container.find('.csd-remove-group').show();
			
			// Add the new group
			container.append(newGroup);
		});
		
		// Remove condition
		$(document).on('click', '.csd-remove-condition', function() {
			var condition = $(this).closest('.csd-condition');
			var group = condition.closest('.csd-condition-group');
			
			// Only remove if there's more than one condition
			if (group.find('.csd-condition').length > 1) {
				condition.remove();
				
				// Update all remaining conditions in this group
				updateConditionIndices(group);
				
				// Update relation visibility
				updateRelationVisibility(group);
			}
		});
		
		// Remove condition group
		$(document).on('click', '.csd-remove-group', function() {
			var group = $(this).closest('.csd-condition-group');
			var container = $('#csd-conditions-container');
			
			// Only remove if there's more than one group
			if (container.find('.csd-condition-group').length > 1) {
				group.remove();
				
				// Update all remaining groups
				updateGroupIndices();
			}
		});
		
		// Handle operator change to show/hide appropriate value inputs
		$(document).on('change', '.csd-condition-operator', function() {
			var operator = $(this).val();
			var valueContainer = $(this).closest('.csd-condition').find('.csd-condition-value-container');
			var valueInput = valueContainer.find('.csd-condition-value');
			var betweenValues = valueContainer.find('.csd-between-values');
			
			// Reset
			valueInput.show();
			betweenValues.hide();
			
			// Handle different operator types
			if (operator === "= ''" || operator === "!= ''") {
				valueInput.hide();
			} else if (operator === 'BETWEEN' || operator === 'NOT BETWEEN') {
				betweenValues.show();
			} else if (operator === 'IN' || operator === 'NOT IN') {
				valueInput.attr('placeholder', 'Enter values separated by commas');
			} else {
				valueInput.attr('placeholder', 'Enter value');
			}
		});
		
		// Get possible values for a field
		$(document).on('click', '.csd-get-values', function() {
			var condition = $(this).closest('.csd-condition');
			var field = condition.find('.csd-condition-field').val();
			
			if (!field) {
				alert('Please select a field first.');
				return;
			}
			
			// Call AJAX to get values
			$.ajax({
				url: csd_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'csd_get_field_values',
					field: field,
					nonce: csd_ajax.nonce
				},
				beforeSend: function() {
					condition.find('.csd-get-values').prop('disabled', true).html('<span class="spinner" style="visibility:visible;float:none;margin:0;"></span>');
				},
				success: function(response) {
					condition.find('.csd-get-values').prop('disabled', false).html('<span class="dashicons dashicons-arrow-down"></span>');
					
					if (response.success) {
						// Create and show dropdown
						var values = response.data.values;
						var select = $('<select class="csd-values-dropdown"></select>');
						
						select.append('<option value="">-- Select Value --</option>');
						
						$.each(values, function(index, value) {
							select.append('<option value="' + value + '">' + value + '</option>');
						});
						
						// Add to DOM
						condition.find('.csd-values-dropdown').remove();
						condition.find('.csd-condition-value-container').append(select);
						
						// Handle selection
						select.on('change', function() {
							var selectedValue = $(this).val();
							if (selectedValue) {
								condition.find('.csd-condition-value').val(selectedValue);
							}
							$(this).remove();
						});
					} else {
						alert(response.data.message || 'Error fetching values.');
					}
				},
				error: function() {
					condition.find('.csd-get-values').prop('disabled', false).html('<span class="dashicons dashicons-arrow-down"></span>');
					alert('Error fetching values.');
				}
			});
		});
		
		// Run query
		$('#csd-query-builder-form').on('submit', function(e) {
			e.preventDefault();
			
			// Validate form
			var fields = $('input[name="fields[]"]:checked');
			if (fields.length === 0) {
				alert('Please select at least one field to display.');
				return;
			}
			
			var formData = $(this).serialize();
			
			// Call AJAX to run query
			$.ajax({
				url: csd_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'csd_run_custom_query',
					form_data: formData,
					nonce: csd_ajax.nonce
				},
				beforeSend: function() {
					$('#csd-run-query').prop('disabled', true).text('Running...');
					$('#csd-query-results').html('<div class="spinner is-active" style="float:none;margin:0 auto;display:block;width:100%;"></div>');
				},
				success: function(response) {
					$('#csd-run-query').prop('disabled', false).text('Run Query');
					
					if (response.success) {
						// Update record count
						$('#csd-record-count').text(response.data.count);
						
						// Show SQL query
						$('#csd-sql-query').val(response.data.sql);
						
						// Show results table
						$('#csd-query-results').html(response.data.html);
						
						// Scroll to results
						$('html, body').animate({
							scrollTop: $('.csd-query-results-container').offset().top - 50
						}, 500);
					} else {
						$('#csd-query-results').html('<div class="notice notice-error"><p>' + (response.data.message || 'Error running query.') + '</p></div>');
					}
				},
				error: function() {
					$('#csd-run-query').prop('disabled', false).text('Run Query');
					$('#csd-query-results').html('<div class="notice notice-error"><p>Error running query.</p></div>');
				}
			});
		});
		
		// Edit SQL query
		$('#csd-edit-sql').on('click', function() {
			var sqlTextarea = $('#csd-sql-query');
			
			// Toggle readonly attribute
			sqlTextarea.prop('readonly', false);
			
			// Show/hide buttons
			$(this).hide();
			$('#csd-run-sql, #csd-cancel-sql-edit').show();
			
			// Focus on textarea
			sqlTextarea.focus();
		});
		
		// Cancel SQL edit
		$('#csd-cancel-sql-edit').on('click', function() {
			var sqlTextarea = $('#csd-sql-query');
			
			// Toggle readonly attribute
			sqlTextarea.prop('readonly', true);
			
			// Show/hide buttons
			$(this).hide();
			$('#csd-run-sql').hide();
			$('#csd-edit-sql').show();
		});
		
		// Run custom SQL
		$('#csd-run-sql').on('click', function() {
			var sql = $('#csd-sql-query').val();
			
			if (!sql) {
				alert('Please enter an SQL query.');
				return;
			}
			
			// Call AJAX to run custom SQL
			$.ajax({
				url: csd_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'csd_run_custom_query',
					custom_sql: sql,
					nonce: csd_ajax.nonce
				},
				beforeSend: function() {
					$(this).prop('disabled', true).text('Running...');
					$('#csd-query-results').html('<div class="spinner is-active" style="float:none;margin:0 auto;display:block;width:100%;"></div>');
				},
				success: function(response) {
					$('#csd-run-sql').prop('disabled', false).text('Run SQL');
					
					if (response.success) {
						// Update record count
						$('#csd-record-count').text(response.data.count);
						
						// Show results table
						$('#csd-query-results').html(response.data.html);
						
						// Restore buttons
						$('#csd-run-sql, #csd-cancel-sql-edit').hide();
						$('#csd-edit-sql').show();
						
						// Make textarea readonly again
						$('#csd-sql-query').prop('readonly', true);
						
						// Scroll to results
						$('html, body').animate({
							scrollTop: $('.csd-query-results-container').offset().top - 50
						}, 500);
					} else {
						$('#csd-query-results').html('<div class="notice notice-error"><p>' + (response.data.message || 'Error running query.') + '</p></div>');
					}
				},
				error: function() {
					$('#csd-run-sql').prop('disabled', false).text('Run SQL');
					$('#csd-query-results').html('<div class="notice notice-error"><p>Error running query.</p></div>');
				}
			});
		});
		
		// Clear form
		$('#csd-clear-form').on('click', function() {
			if (confirm('Are you sure you want to clear the form?')) {
				// Reset checkboxes
				$('input[name="fields[]"]').prop('checked', false);
				
				// Reset conditions
				$('#csd-conditions-container').html('');
				
				// Clone the first group template
				var newGroup = $('.csd-condition-group:first').clone();
				
				// Reset the group
				newGroup.attr('data-group', 0);
				newGroup.find('h4').text('Condition Group 1');
				
				// Reset the first condition
				var firstCondition = newGroup.find('.csd-condition:first');
				firstCondition.attr('data-index', 0);
				firstCondition.find('.csd-condition-field').attr('name', 'conditions[0][0][field]').val('');
				firstCondition.find('.csd-condition-operator').attr('name', 'conditions[0][0][operator]').val('');
				firstCondition.find('.csd-condition-value').attr('name', 'conditions[0][0][value]').val('');
				firstCondition.find('.csd-condition-value-2').attr('name', 'conditions[0][0][value2]').val('');
				firstCondition.find('.csd-condition-relation').attr('name', 'conditions[0][0][relation]');
				
				// Remove any extra conditions
				newGroup.find('.csd-condition:not(:first)').remove();
				
				// Hide remove group button
				newGroup.find('.csd-remove-group').hide();
				
				// Hide remove condition button
				newGroup.find('.csd-remove-condition').hide();
				
				// Add the reset group
				$('#csd-conditions-container').append(newGroup);
				
				// Reset options
				$('#csd-limit').val('100');
				$('#csd-order-by').val('');
				$('#csd-order-dir').val('ASC');
				$('#csd-join-type').val('LEFT JOIN');
				$('#csd-query-name').val('');
				
				// Clear results
				$('#csd-record-count').text('0');
				$('#csd-sql-query').val('');
				$('#csd-query-results').html('');
			}
		});
		
		// Export to CSV
		$('#csd-export-csv').on('click', function() {
			var sql = $('#csd-sql-query').val();
			
			if (!sql) {
				alert('Please run a query first.');
				return;
			}
			
			// Create a form and submit it to initiate the download
			var form = $('<form>', {
				'method': 'POST',
				'action': csd_ajax.ajax_url
			});
			
			form.append($('<input>', {
				'type': 'hidden',
				'name': 'action',
				'value': 'csd_export_query_results'
			}));
			
			form.append($('<input>', {
				'type': 'hidden',
				'name': 'sql',
				'value': sql
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
		
		// Save query
		$('#csd-save-query-btn').on('click', function() {
			var queryName = $('#csd-query-name').val();
			
			if (!queryName) {
				alert('Please enter a name for this query.');
				return;
			}
			
			var formData = $('#csd-query-builder-form').serialize();
			
			// Call AJAX to save query
			$.ajax({
				url: csd_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'csd_save_query',
					query_name: queryName,
					form_data: formData,
					nonce: csd_ajax.nonce
				},
				beforeSend: function() {
					$('#csd-save-query-btn').prop('disabled', true).text('Saving...');
				},
				success: function(response) {
					$('#csd-save-query-btn').prop('disabled', false).text('Save Query');
					
					if (response.success) {
						alert('Query saved successfully.');
						
						// Add to dropdown if not already there
						var queryId = response.data.query_id;
						var existingOption = $('#csd-load-query option[value="' + queryId + '"]');
						
						if (existingOption.length) {
							existingOption.text(queryName);
						} else {
							$('#csd-load-query').append('<option value="' + queryId + '">' + queryName + '</option>');
						}
					} else {
						alert(response.data.message || 'Error saving query.');
					}
				},
				error: function() {
					$('#csd-save-query-btn').prop('disabled', false).text('Save Query');
					alert('Error saving query.');
				}
			});
		});
		
		// Load query
		$('#csd-load-query-btn').on('click', function() {
			var queryId = $('#csd-load-query').val();
			
			if (!queryId) {
				alert('Please select a query to load.');
				return;
			}
			
			// Call AJAX to load query
			$.ajax({
				url: csd_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'csd_load_query',
					query_id: queryId,
					nonce: csd_ajax.nonce
				},
				beforeSend: function() {
					$('#csd-load-query-btn').prop('disabled', true).text('Loading...');
				},
				success: function(response) {
					$('#csd-load-query-btn').prop('disabled', false).text('Load');
					
					if (response.success) {
						// Clear current form
						$('#csd-clear-form').trigger('click');
						
						// Load the query data
						var queryData = response.data.query_data;
						
						// Set query name
						$('#csd-query-name').val(queryData.query_name);
						
						// Check fields
						if (queryData.fields && queryData.fields.length) {
							$.each(queryData.fields, function(i, field) {
								$('input[name="fields[]"][value="' + field + '"]').prop('checked', true);
							});
						}
						
						// Load conditions
						// This would need the full code for loading conditions
						// ...
						
						// Load options
						if (queryData.limit) {
							$('#csd-limit').val(queryData.limit);
						}
						
						if (queryData.order_by) {
							$('#csd-order-by').val(queryData.order_by);
						}
						
						if (queryData.order_dir) {
							$('#csd-order-dir').val(queryData.order_dir);
						}
						
						if (queryData.join_type) {
							$('#csd-join-type').val(queryData.join_type);
						}
						
						// Automatically run the query
						$('#csd-query-builder-form').submit();
					} else {
						alert(response.data.message || 'Error loading query.');
					}
				},
				error: function() {
					$('#csd-load-query-btn').prop('disabled', false).text('Load');
					alert('Error loading query.');
				}
			});
		});
		
		// Delete query
		$('#csd-delete-query-btn').on('click', function() {
			var queryId = $('#csd-load-query').val();
			
			if (!queryId) {
				alert('Please select a query to delete.');
				return;
			}
			
			if (!confirm('Are you sure you want to delete this query?')) {
				return;
			}
			
			// Call AJAX to delete query
			$.ajax({
				url: csd_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'csd_delete_query',
					query_id: queryId,
					nonce: csd_ajax.nonce
				},
				beforeSend: function() {
					$('#csd-delete-query-btn').prop('disabled', true).text('Deleting...');
				},
				success: function(response) {
					$('#csd-delete-query-btn').prop('disabled', false).text('Delete');
					
					if (response.success) {
						alert('Query deleted successfully.');
						
						// Remove from dropdown
						$('#csd-load-query option[value="' + queryId + '"]').remove();
						$('#csd-load-query').val('');
					} else {
						alert(response.data.message || 'Error deleting query.');
					}
				},
				error: function() {
					$('#csd-delete-query-btn').prop('disabled', false).text('Delete');
					alert('Error deleting query.');
				}
			});
		});
		
		// Helper function to update condition indices
		function updateConditionIndices(group) {
			var groupIndex = group.data('group');
			
			group.find('.csd-condition').each(function(index) {
				$(this).attr('data-index', index);
				$(this).find('.csd-condition-field').attr('name', 'conditions[' + groupIndex + '][' + index + '][field]');
				$(this).find('.csd-condition-operator').attr('name', 'conditions[' + groupIndex + '][' + index + '][operator]');
				$(this).find('.csd-condition-value').attr('name', 'conditions[' + groupIndex + '][' + index + '][value]');
				$(this).find('.csd-condition-value-2').attr('name', 'conditions[' + groupIndex + '][' + index + '][value2]');
				$(this).find('.csd-condition-relation').attr('name', 'conditions[' + groupIndex + '][' + index + '][relation]');
			});
		}
		
		// Helper function to update group indices
		function updateGroupIndices() {
			$('.csd-condition-group').each(function(groupIndex) {
				$(this).attr('data-group', groupIndex);
				$(this).find('h4').text('Condition Group ' + (groupIndex + 1));
				
				$(this).find('.csd-condition').each(function(condIndex) {
					$(this).attr('data-index', condIndex);
					$(this).find('.csd-condition-field').attr('name', 'conditions[' + groupIndex + '][' + condIndex + '][field]');
					$(this).find('.csd-condition-operator').attr('name', 'conditions[' + groupIndex + '][' + condIndex + '][operator]');
					$(this).find('.csd-condition-value').attr('name', 'conditions[' + groupIndex + '][' + condIndex + '][value]');
					$(this).find('.csd-condition-value-2').attr('name', 'conditions[' + groupIndex + '][' + condIndex + '][value2]');
					$(this).find('.csd-condition-relation').attr('name', 'conditions[' + groupIndex + '][' + condIndex + '][relation]');
				});
			});
			
			// Hide remove group button if only one group
			if ($('.csd-condition-group').length === 1) {
				$('.csd-remove-group').hide();
			} else {
				$('.csd-remove-group').show();
			}
		}
		
		// Helper function to show/hide relation dropdowns
		function updateRelationVisibility(group) {
			var conditions = group.find('.csd-condition');
			
			conditions.each(function(index) {
				if (index === conditions.length - 1) {
					// Last condition in group, hide relation
					$(this).find('.csd-condition-relation').hide();
				} else {
					// Show relation for all other conditions
					$(this).find('.csd-condition-relation').show();
				}
			});
		}
		
		// Format SQL query with proper indentation
		function formatSqlQuery(sql) {
			if (!sql) return sql;
			
			// Replace multiple spaces with a single space
			sql = sql.replace(/\s+/g, ' ').trim();
			
			// Add line breaks after these SQL keywords
			var keywords = ['SELECT', 'FROM', 'WHERE', 'LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN', 'GROUP BY', 'ORDER BY', 'HAVING', 'LIMIT'];
			
			// Replace keywords with line breaks and proper indentation
			keywords.forEach(function(keyword) {
				var regex = new RegExp('\\b' + keyword + '\\b', 'gi');
				sql = sql.replace(regex, '\n' + keyword);
			});
			
			// Add indentation for JOIN statements
			sql = sql.replace(/\n(LEFT JOIN|INNER JOIN|RIGHT JOIN)/gi, '\n  $1');
			
			// Add indentation after WHERE for conditions
			sql = sql.replace(/\nWHERE\s+(.+?)(\n|$)/gi, '\nWHERE\n  $1$2');
			
			// Add line breaks for AND and OR
			sql = sql.replace(/\s+(AND|OR)\s+/gi, '\n  $1 ');
			
			return sql;
		}
		
		// Initialize SQL textarea click handling
		function initSqlTextareaHandling() {
			var sqlTextarea = $('#csd-sql-query');
			var editBtn = $('#csd-edit-sql');
			var runBtn = $('#csd-run-sql');
			var cancelBtn = $('#csd-cancel-sql-edit');
			
			// Format the SQL query initially
			sqlTextarea.val(formatSqlQuery(sqlTextarea.val()));
			
			// Enable direct editing when clicked
			sqlTextarea.on('click', function() {
				if (sqlTextarea.prop('readonly')) {
					sqlTextarea.prop('readonly', false);
					editBtn.hide();
					runBtn.show();
					cancelBtn.show();
				}
			});
			
			// Handle cancel button
			cancelBtn.on('click', function() {
				sqlTextarea.prop('readonly', true);
				runBtn.hide();
				cancelBtn.hide();
				editBtn.show();
			});
		}
		
		// Format SQL when displaying results
		$(document).on('ajaxSuccess', function(event, xhr, settings) {
			if (settings.data && settings.data.indexOf('csd_run_custom_query') > -1) {
				// Format the SQL in the textarea after query runs
				setTimeout(function() {
					var sqlTextarea = $('#csd-sql-query');
					sqlTextarea.val(formatSqlQuery(sqlTextarea.val()));
				}, 100);
			}
		});
		
		// Fix display text for operators with quotes
		$('.csd-condition-operator option').each(function() {
			var value = $(this).val();
			if (value === "= ''") {
				$(this).text("= '' (empty)");
			} else if (value === "!= ''") {
				$(this).text("!= '' (not empty)");
			}
		});
		
		initSqlTextareaHandling();
		
	});
})(jQuery);
