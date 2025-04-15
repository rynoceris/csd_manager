/**
 * Query Builder JavaScript
 */
(function($) {
	'use strict';

	// Initialize everything when the document is ready
	$(document).ready(function() {
		var currentPage = 1;
		var resultsPerPage = 25;
		
		// CodeMirror instance variable
		let sqlEditor = null;

		// Create a fallback for the AJAX object if needed
		if (typeof csd_ajax === 'undefined') {
			console.log('Creating fallback for csd_ajax');
			window.csd_ajax = {
				ajax_url: ajaxurl || '/wp-admin/admin-ajax.php',
				nonce: ''  // We'll need to get this from the page
			};
		}

		// Initialize CodeMirror with both auto and manual resizing capabilities
		function initCodeMirror() {
		  const sqlTextarea = document.getElementById('csd-sql-query');
		  if (sqlTextarea && !sqlEditor && typeof CodeMirror !== 'undefined') {
			// Clear any existing content first
			sqlTextarea.value = '';
			
			sqlEditor = CodeMirror.fromTextArea(sqlTextarea, {
			  mode: 'text/x-mysql',
			  theme: 'monokai',
			  lineNumbers: true,
			  indentWithTabs: true,
			  readOnly: true,
			  lineWrapping: true,
			  autoRefresh: true
			});
			
			// Make wrapper resizable
			const wrapper = sqlEditor.getWrapperElement();
			$(wrapper).addClass('resizable-cm');
			
			// Add click handler for edit mode
			$(wrapper).on('click', function() {
			  if (sqlEditor.getOption('readOnly')) {
				$('#csd-edit-sql').click();
			  }
			});
			
			// Set initial height
			autoAdjustEditorHeight();
			
			// Make sure changes in content trigger height adjustment
			sqlEditor.on('change', function() {
			  autoAdjustEditorHeight();
			});
		  }
		}
		
		// Function to adjust editor height based on content
		function autoAdjustEditorHeight() {
		  if (!sqlEditor) return;
		  
		  // Get the editor wrapper
		  const wrapper = sqlEditor.getWrapperElement();
		  
		  // Get the content's height (accounting for line wrapping)
		  sqlEditor.refresh(); // Ensure measurements are accurate
		  const contentHeight = sqlEditor.getScrollInfo().height;
		  
		  // Set a minimum height and add some padding
		  const newHeight = Math.max(100, contentHeight + 20);
		  
		  // Only adjust if not manually resized by user
		  if (!$(wrapper).data('manually-resized')) {
			$(wrapper).height(newHeight + 'px');
			sqlEditor.refresh();
		  }
		}
		
		// Handler for when user manually resizes the editor
		function setupManualResize() {
		  const wrapper = sqlEditor?.getWrapperElement();
		  if (!wrapper) return;
		  
		  // Track original height before manual resize
		  $(wrapper).data('original-height', $(wrapper).height());
		  
		  // Use ResizeObserver to detect manual resizes
		  const resizeObserver = new ResizeObserver((entries) => {
			for (const entry of entries) {
			  const height = entry.contentRect.height;
			  const originalHeight = $(wrapper).data('original-height');
			  
			  // If height has changed significantly from original, mark as manually resized
			  if (Math.abs(height - originalHeight) > 10) {
				$(wrapper).data('manually-resized', true);
			  }
			}
		  });
		  
		  resizeObserver.observe(wrapper);
		}
		
		// Add this function after initCodeMirror
		function adjustEditorHeight(sql) {
		  if (!sqlEditor) return;
		  
		  // Count the number of lines in the SQL query
		  const lines = (sql.match(/\n/g) || []).length + 1;
		  
		  // Set height based on line count (approximately 20px per line)
		  const height = Math.max(150, Math.min(400, lines * 20)); 
		  
		  // Set the height
		  $(sqlEditor.getWrapperElement()).height(height);
		  
		  // Refresh the editor
		  sqlEditor.refresh();
		}
		
		// Function to auto-adjust editor height based on content
		function autoAdjustEditorHeight() {
		  if (!sqlEditor) return;
		  
		  // Get the editor wrapper
		  const wrapper = sqlEditor.getWrapperElement();
		  
		  // Set a reasonable min-height
		  $(wrapper).css('min-height', '100px');
		  
		  // Get the content's height
		  const contentHeight = sqlEditor.getScrollInfo().height;
		  
		  // Calculate new height with some padding
		  const newHeight = Math.max(100, contentHeight + 20);
		  
		  // Apply the new height
		  $(wrapper).css('height', newHeight + 'px');
		  
		  // Refresh to update the layout
		  sqlEditor.refresh();
		}

		// Call this after page load with a slight delay to ensure DOM is ready
		setTimeout(function() {
		  if (typeof CodeMirror !== 'undefined') {
			initCodeMirror();
			setupManualResize();
		  }
		}, 100);

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
		
		// Modify the form submit handler to include pagination
		$('#csd-query-builder-form').on('submit', function(e) {
			e.preventDefault();
			
			// Validate form
			var fields = $('input[name="fields[]"]:checked');
			if (fields.length === 0) {
				alert('Please select at least one field to display.');
				return;
			}
			
			var formData = $(this).serialize();
			
			// Reset to first page when submitting a new query
			currentPage = 1;
			resultsPerPage = parseInt($('#csd-per-page').val() || 25);
			
			// Run the query with pagination
			runQuery(formData, currentPage, resultsPerPage);
		});
		
		// Add a function to run the query with pagination
		function runQuery(formData, page, perPage) {
			// Call AJAX to run query
			$.ajax({
				url: csd_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'csd_run_custom_query',
					form_data: formData,
					page: page,
					per_page: perPage,
					nonce: csd_ajax.nonce
				},
				beforeSend: function() {
					$('#csd-run-query').prop('disabled', true).text('Running...');
					$('#csd-query-results').html('<div class="spinner is-active" style="float:none;margin:0 auto;display:block;width:100%;"></div>');
				},
				success: function(response) {
					$('#csd-run-query').prop('disabled', false).text('Run Query');
					
					if (response.success) {
						// Store the form data for pagination use
						$('#csd-query-results').data('form-data', formData);
						
						// Update currentPage and resultsPerPage from response
						currentPage = response.data.current_page;
						resultsPerPage = response.data.per_page;
						
						// Update record count
						$('#csd-record-count').text(response.data.count);
						
						// Show SQL query
						var cleanSql = formatSqlQuery(response.data.sql);
						// Ensure there are no leading newlines
						cleanSql = cleanSql.replace(/^\n+/, '');
						
						if (sqlEditor) {
							// Reset manual resize flag when setting new content
							const wrapper = sqlEditor.getWrapperElement();
							$(wrapper).data('manually-resized', false);
							$(wrapper).data('original-height', null);
							
							// Clear editor completely before setting new content
							sqlEditor.setValue("");
							sqlEditor.clearHistory();
							
							// Now set the cleaned SQL
							sqlEditor.setValue(cleanSql);
							sqlEditor.refresh();
							
							// Ensure cursor is at start
							sqlEditor.setCursor(0, 0);
							
							// Auto-adjust height based on new content
							autoAdjustEditorHeight();
							
							// Setup manual resize tracking again
							setupManualResize();
						} else {
							$('#csd-sql-query').val(cleanSql);
						}
						
						// Show results table with pagination
						$('#csd-query-results').html(response.data.html);
						
						// Initialize resizable columns
						initializeResizableColumns();
						
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
		}
		
		// Handle pagination clicks
		$(document).on('click', '.csd-page-number', function(e) {
			e.preventDefault();
			
			var page = parseInt($(this).data('page'));
			var formData = $('#csd-query-results').data('form-data');
			
			if (formData) {
				runQuery(formData, page, resultsPerPage);
			}
		});
		
		// Handle per-page changes
		$(document).on('change', '#csd-per-page', function(e) {
			var perPage = parseInt($(this).val());
			var formData = $('#csd-query-results').data('form-data');
			
			if (formData) {
				// Reset to first page when changing results per page
				runQuery(formData, 1, perPage);
			}
		});
		
		// Update your edit SQL button handler
		$('#csd-edit-sql').on('click', function() {
		  if (sqlEditor) {
			sqlEditor.setOption('readOnly', false);
			
			// Force focus after a slight delay
			setTimeout(function() {
			  sqlEditor.focus();
			}, 50);
		  } else {
			$('#csd-sql-query').prop('readonly', false).focus();
		  }
		  
		  // Show/hide buttons
		  $(this).hide();
		  $('#csd-run-sql, #csd-cancel-sql-edit').show();
		});
		
		// Cancel SQL edit
		$('#csd-cancel-sql-edit').on('click', function() {
			if (sqlEditor) {
				sqlEditor.setOption('readOnly', true);
			} else {
				$('#csd-sql-query').prop('readonly', true);
			}
			
			// Show/hide buttons
			$(this).hide();
			$('#csd-run-sql').hide();
			$('#csd-edit-sql').show();
		});
		
		// Run custom SQL
		$('#csd-run-sql').on('click', function() {
			var sql = sqlEditor ? sqlEditor.getValue() : $('#csd-sql-query').val();
			
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
						
						// Initialize resizable columns
						initializeResizableColumns();
						
						// Restore buttons
						$('#csd-run-sql, #csd-cancel-sql-edit').hide();
						$('#csd-edit-sql').show();
						
						// Make textarea readonly again
						if (sqlEditor) {
							sqlEditor.setOption('readOnly', true);
						} else {
							$('#csd-sql-query').prop('readonly', true);
						}
						
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
				
				// In the clear form handler where you reset the SQL editor
				if (sqlEditor) {
				  // Reset manual resize flag
				  const wrapper = sqlEditor.getWrapperElement();
				  $(wrapper).data('manually-resized', false);
				  
				  sqlEditor.setValue('');
				  autoAdjustEditorHeight();
				} else {
				  $('#csd-sql-query').val('');
				}
				
				$('#csd-query-results').html('');
			}
		});
		
		// Export to CSV
		$('#csd-export-csv').on('click', function() {
			var sql = sqlEditor ? sqlEditor.getValue() : $('#csd-sql-query').val();
			
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
						if (queryData.conditions && Array.isArray(queryData.conditions)) {
							// Get container
							var $conditionsContainer = $('#csd-conditions-container');
							
							// Clear container
							$conditionsContainer.empty();
							
							// First, cache the original options to ensure they're available
							var fieldOptionsHtml = '';
							var operatorOptionsHtml = '';
							var relationOptionsHtml = '';
							
							// Get option HTML from page elements before building new conditions
							if ($('.csd-condition-field:first').length) {
								fieldOptionsHtml = $('.csd-condition-field:first').html();
							} else {
								// Fallback - recreate field options if none found
								fieldOptionsHtml = '<option value="">-- Select Field --</option>';
								
								// Dynamically generate field options based on the tables configuration
								var tables = ['schools', 'staff', 'school_staff']; // Match your backend tables
								tables.forEach(function(table) {
									var tableName = table === 'schools' ? 'Schools' : 
													table === 'staff' ? 'Staff' : 
													'School Staff Relations';
									
									fieldOptionsHtml += '<optgroup label="' + tableName + '">';
									
									// These fields should match exactly what's in your backend configuration
									var fields = {
										'schools': [
											'id', 'school_name', 'city', 'state', 'street_address_line_1', 
											'street_address_line_2', 'zipcode', 'country', 'school_divisions', 
											'school_conferences', 'school_type', 'mascot'
										],
										'staff': [
											'id', 'full_name', 'title', 'sport_department', 
											'email', 'phone'
										],
										'school_staff': [
											'id', 'school_id', 'staff_id', 'date_created'
										]
									};
									
									fields[table].forEach(function(field) {
										// Create an option with the fully qualified field name
										fieldOptionsHtml += '<option value="' + table + '.' + field + '">' + 
															 field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + 
															 '</option>';
									});
									
									fieldOptionsHtml += '</optgroup>';
								});
							}
							
							if ($('.csd-condition-operator:first').length) {
								operatorOptionsHtml = $('.csd-condition-operator:first').html();
							} else {
								// Fallback operator options
								operatorOptionsHtml = 
									'<option value="">-- Select Operator --</option>' +
									'<option value="=">=</option>' +
									'<option value="!=">!=</option>' +
									'<option value="LIKE">LIKE</option>' +
									'<option value="LIKE %...%">LIKE %...% (contains)</option>' +
									'<option value="NOT LIKE">NOT LIKE</option>' +
									'<option value="NOT LIKE %...%">NOT LIKE %...% (not contains)</option>' +
									'<option value="REGEXP">REGEXP</option>' +
									'<option value="REGEXP ^...$">REGEXP ^...$ (exact match)</option>' +
									'<option value="NOT REGEXP">NOT REGEXP</option>' +
									'<option value="= \'\'">= \'\' (empty)</option>' +
									'<option value="!= \'\'">!= \'\' (not empty)</option>' +
									'<option value="IN">IN (...)</option>' +
									'<option value="NOT IN">NOT IN (...)</option>' +
									'<option value="BETWEEN">BETWEEN</option>' +
									'<option value="NOT BETWEEN">NOT BETWEEN</option>' +
									'<option value=">">></option>' +
									'<option value=">=">>=</option>' +
									'<option value="<"><</option>' +
									'<option value="<="><=</option>';
							}
							
							if ($('.csd-condition-relation:first').length) {
								relationOptionsHtml = $('.csd-condition-relation:first').html();
							} else {
								// Fallback relation options
								relationOptionsHtml = 
									'<option value="AND">AND</option>' +
									'<option value="OR">OR</option>';
							}
							
							// Process each condition group from the saved data
							$.each(queryData.conditions, function(groupIndex, group) {
								// Create a new condition group
								var groupHtml = '<div class="csd-condition-group" data-group="' + groupIndex + '">' +
									'<div class="csd-condition-group-header">' +
									'<h4>Condition Group ' + (groupIndex + 1) + '</h4>' +
									'<button type="button" class="csd-remove-group button" style="' + 
									(queryData.conditions.length > 1 ? '' : 'display:none') + 
									'">Remove Group</button>' +
									'</div>' +
									'<div class="csd-conditions"></div>' +
									'<div class="csd-condition-actions">' +
									'<button type="button" class="csd-add-condition button">Add Condition</button>' +
									'</div>' +
									'</div>';
								
								var $group = $(groupHtml);
								$conditionsContainer.append($group);
								
								// Add each condition to this group
								$.each(group, function(condIndex, cond) {
									// Build condition HTML with the cached options
									var condHtml = '<div class="csd-condition" data-index="' + condIndex + '">' +
										'<select class="csd-condition-field" name="conditions[' + groupIndex + '][' + condIndex + '][field]">' +
										fieldOptionsHtml +
										'</select>' +
										
										'<select class="csd-condition-operator" name="conditions[' + groupIndex + '][' + condIndex + '][operator]">' +
										operatorOptionsHtml +
										'</select>' +
										
										'<div class="csd-condition-value-container">' +
										'<input type="text" class="csd-condition-value" name="conditions[' + groupIndex + '][' + condIndex + '][value]" placeholder="Enter value">' +
										
										'<div class="csd-between-values" style="' + 
										((cond.operator === 'BETWEEN' || cond.operator === 'NOT BETWEEN') ? '' : 'display:none;') + 
										'">' +
										'<input type="text" class="csd-condition-value-2" name="conditions[' + groupIndex + '][' + condIndex + '][value2]" placeholder="End value">' +
										'</div>' +
										
										'<button type="button" class="csd-get-values button" title="Get possible values">' +
										'<span class="dashicons dashicons-arrow-down"></span>' +
										'</button>' +
										'</div>' +
										
										'<select class="csd-condition-relation" name="conditions[' + groupIndex + '][' + condIndex + '][relation]" style="' + 
										(condIndex === group.length - 1 ? 'display:none;' : '') + 
										'">' +
										relationOptionsHtml +
										'</select>' +
										
										'<button type="button" class="csd-remove-condition button" style="' + 
										(condIndex > 0 || group.length > 1 ? '' : 'display:none;') + 
										'">' +
										'<span class="dashicons dashicons-no"></span>' +
										'</button>' +
										'</div>';
									
									var $condition = $(condHtml);
									$group.find('.csd-conditions').append($condition);
									
									// Set values for select elements
									$condition.find('.csd-condition-field').val(cond.field);
									$condition.find('.csd-condition-operator').val(cond.operator).trigger('change');
									$condition.find('.csd-condition-value').val(cond.value);
									
									if (cond.value2) {
										$condition.find('.csd-condition-value-2').val(cond.value2);
									}
									
									if (cond.relation) {
										$condition.find('.csd-condition-relation').val(cond.relation);
									}
							
									// Handle visibility based on operator
									var operator = cond.operator;
									if (operator === 'BETWEEN' || operator === 'NOT BETWEEN') {
										$condition.find('.csd-between-values').show();
									} else if (operator === "= ''" || operator === "!= ''") {
										$condition.find('.csd-condition-value').hide();
									} else {
										$condition.find('.csd-between-values').hide();
										$condition.find('.csd-condition-value').show();
									}
								});
							});
							
							// Reattach event handlers to all new elements
							$('.csd-add-condition').off('click').on('click', function() {
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
							
							$('.csd-remove-condition').off('click').on('click', function() {
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
							
							$('.csd-remove-group').off('click').on('click', function() {
								var group = $(this).closest('.csd-condition-group');
								var container = $('#csd-conditions-container');
								
								// Only remove if there's more than one group
								if (container.find('.csd-condition-group').length > 1) {
									group.remove();
									
									// Update all remaining groups
									updateGroupIndices();
								}
							});
							
							$('.csd-condition-operator').off('change').on('change', function() {
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
						}
						
						// Make sure to properly handle field selections *before* submitting the form
						if (queryData.fields && queryData.fields.length) {
							// First uncheck all fields
							$('input[name="fields[]"]').prop('checked', false);
							
							// Then check the ones in the query data
							$.each(queryData.fields, function(i, field) {
								$('input[name="fields[]"][value="' + field + '"]').prop('checked', true);
							});
						}
						
						// Fix the order-dir selector
						if (queryData.order_dir) {
							$('#csd-order-dir').val(queryData.order_dir);
						}
						
						// DON'T automatically submit the form
						// This gives the user a chance to review before running
						// $('#csd-query-builder-form').submit();
						
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
		
		// Initialize resizable columns
		function initializeResizableColumns() {
			$(document).on('mousedown', '.csd-resizable-table .resize-handle', function(e) {
				e.preventDefault();
				
				const th = $(this).closest('th');
				const table = th.closest('table');
				const columnIndex = th.index();
				const startX = e.pageX;
				const startWidth = th.width();
				
				$(this).addClass('resizing');
				
				const mouseMoveHandler = function(e) {
					// Calculate width change
					const widthChange = e.pageX - startX;
					const newWidth = Math.max(50, startWidth + widthChange);
					
					// Apply new width to header and all cells in this column
					th.width(newWidth);
					table.find('tr').each(function() {
						$(this).find('td').eq(columnIndex).width(newWidth);
					});
				};
				
				const mouseUpHandler = function() {
					$(document).off('mousemove', mouseMoveHandler);
					$(document).off('mouseup', mouseUpHandler);
					$('.resize-handle').removeClass('resizing');
				};
				
				$(document).on('mousemove', mouseMoveHandler);
				$(document).on('mouseup', mouseUpHandler);
			});
		}
		
		// Call resizable columns initialization after page load
		initializeResizableColumns();
		
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
		
		// Format SQL query with proper indentation and cleanup
		function formatSqlQuery(sql) {
			if (!sql) return '';
			
			// Trim the SQL to remove any whitespace at beginning and end
			sql = sql.trim();
			
			// Replace multiple spaces with a single space
			sql = sql.replace(/\s+/g, ' ');
			
			// Add line breaks after these SQL keywords
			var keywords = ['SELECT', 'FROM', 'WHERE', 'LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN', 'GROUP BY', 'ORDER BY', 'HAVING', 'LIMIT'];
			
			// Start with SELECT (first keyword) and don't add a newline before it
			var formattedSql = '';
			var parts = sql.split(/\b(SELECT|FROM|WHERE|LEFT JOIN|INNER JOIN|RIGHT JOIN|GROUP BY|ORDER BY|HAVING|LIMIT)\b/i);
			
			for (var i = 0; i < parts.length; i++) {
				if (keywords.some(kw => parts[i].toUpperCase() === kw)) {
					// It's a keyword
					if (i > 0 || parts[i].toUpperCase() !== 'SELECT') {
						formattedSql += '\n';
					}
					formattedSql += parts[i];
				} else {
					formattedSql += parts[i];
				}
			}
			
			// Add indentation for JOIN statements
			formattedSql = formattedSql.replace(/\n(LEFT JOIN|INNER JOIN|RIGHT JOIN)/gi, '\n  $1');
			
			// Add indentation after WHERE for conditions
			formattedSql = formattedSql.replace(/\nWHERE\s+(.+?)(\n|$)/gi, '\nWHERE\n  $1$2');
			
			// Add line breaks for AND and OR
			formattedSql = formattedSql.replace(/\s+(AND|OR)\s+/gi, '\n  $1 ');
			
			return formattedSql;
		}

		// Fix display text for operators with quotes
		$('.csd-condition-operator option').each(function() {
			var value = $(this).val();
			if (value === "= ''") {
				$(this).text("= '' (empty)");
			} else if (value === "!= ''") {
				$(this).text("!= '' (not empty)");
			}
		});
		
		// Add CSS for making CodeMirror resizable
		$('<style>')
			.prop('type', 'text/css')
			.html(`
				.resizable-cm {
					resize: vertical;
					overflow: auto !important;
					min-height: 150px;
				}
				.CodeMirror {
					border: 1px solid #ddd;
					height: auto;
				}
			`)
			.appendTo('head');
	});
})(jQuery);