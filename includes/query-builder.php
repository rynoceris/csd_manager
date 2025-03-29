<?php
/**
 * Query Builder
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Query Builder Class
 */
class CSD_Query_Builder {
	/**
	 * Check if the saved_queries table exists and create it if it doesn't
	 */
	private function check_table_exists() {
		$wpdb = csd_db_connection();
		
		$table_name = csd_table('saved_queries');
		
		// Check if the table exists
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
		
		if (!$table_exists) {
			// Create the table
			$this->create_saved_queries_table();
		}
	}
	
	/**
	 * Tables and fields configuration
	 */
	private $tables_config = array();
	
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('wp_ajax_csd_run_custom_query', array($this, 'ajax_run_custom_query'));
		add_action('wp_ajax_csd_get_field_values', array($this, 'ajax_get_field_values'));
		add_action('wp_ajax_csd_save_query', array($this, 'ajax_save_query'));
		add_action('wp_ajax_csd_load_query', array($this, 'ajax_load_query'));
		
		// Initialize tables configuration
		$this->init_tables_config();
	}
	
	/**
	 * Initialize tables configuration
	 */
	private function init_tables_config() {
		// Schools table
		$this->tables_config['schools'] = array(
			'label' => 'Schools',
			'table' => csd_table('schools'),
			'fields' => array(
				'id' => array('label' => 'ID', 'type' => 'number'),
				'school_name' => array('label' => 'School Name', 'type' => 'text'),
				'street_address_line_1' => array('label' => 'Address Line 1', 'type' => 'text'),
				'street_address_line_2' => array('label' => 'Address Line 2', 'type' => 'text'),
				'street_address_line_3' => array('label' => 'Address Line 3', 'type' => 'text'),
				'city' => array('label' => 'City', 'type' => 'text'),
				'state' => array('label' => 'State', 'type' => 'text'),
				'zipcode' => array('label' => 'Zip Code', 'type' => 'text'),
				'country' => array('label' => 'Country', 'type' => 'text'),
				'county' => array('label' => 'County', 'type' => 'text'),
				'school_divisions' => array('label' => 'School Divisions', 'type' => 'text'),
				'school_conferences' => array('label' => 'School Conferences', 'type' => 'text'),
				'school_level' => array('label' => 'School Level', 'type' => 'text'),
				'school_type' => array('label' => 'School Type', 'type' => 'text'),
				'school_enrollment' => array('label' => 'School Enrollment', 'type' => 'number'),
				'mascot' => array('label' => 'Mascot', 'type' => 'text'),
				'school_colors' => array('label' => 'School Colors', 'type' => 'text'),
				'school_website' => array('label' => 'School Website', 'type' => 'text'),
				'athletics_website' => array('label' => 'Athletics Website', 'type' => 'text'),
				'athletics_phone' => array('label' => 'Athletics Phone', 'type' => 'text'),
				'football_division' => array('label' => 'Football Division', 'type' => 'text'),
				'date_created' => array('label' => 'Date Created', 'type' => 'date'),
				'date_updated' => array('label' => 'Date Updated', 'type' => 'date')
			)
		);
		
		// Staff table
		$this->tables_config['staff'] = array(
			'label' => 'Staff',
			'table' => csd_table('staff'),
			'fields' => array(
				'id' => array('label' => 'ID', 'type' => 'number'),
				'full_name' => array('label' => 'Full Name', 'type' => 'text'),
				'title' => array('label' => 'Title', 'type' => 'text'),
				'sport_department' => array('label' => 'Sport/Department', 'type' => 'text'),
				'email' => array('label' => 'Email', 'type' => 'text'),
				'phone' => array('label' => 'Phone', 'type' => 'text'),
				'date_created' => array('label' => 'Date Created', 'type' => 'date'),
				'date_updated' => array('label' => 'Date Updated', 'type' => 'date')
			)
		);
		
		// School Staff relation table
		$this->tables_config['school_staff'] = array(
			'label' => 'School Staff Relations',
			'table' => csd_table('school_staff'),
			'fields' => array(
				'id' => array('label' => 'ID', 'type' => 'number'),
				'school_id' => array('label' => 'School ID', 'type' => 'number'),
				'staff_id' => array('label' => 'Staff ID', 'type' => 'number'),
				'date_created' => array('label' => 'Date Created', 'type' => 'date')
			)
		);
	}
	
	/**
	 * Render query builder page
	 */
	public function render_page() {
		// Check if table exists and create it if it doesn't
		$this->check_table_exists();
		
		// Dequeue the problematic script
		wp_dequeue_script('csd-admin-scripts');
		
		// Enqueue only jQuery and UI components
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker');
		
		// Enqueue styles
		wp_enqueue_style('csd-admin-styles');
		
		// Register and enqueue our custom script for the query builder
		// Register and enqueue the custom query builder script
		wp_register_script(
			'csd-query-builder-js', 
			CSD_MANAGER_PLUGIN_URL . 'assets/js/query-builder.js',
			array('jquery'),  // Specify jQuery as a dependency
			CSD_MANAGER_VERSION, 
			true  // Load in footer
		);
		
		wp_enqueue_script('csd-query-builder-js');
		
		// Localize script with AJAX data
		wp_localize_script('csd-query-builder-js', 'csd_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('csd-ajax-nonce')
		));
		
		// Add this code right after wp_enqueue_script('csd-query-builder-js')
		wp_enqueue_style('codemirror', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css');
		wp_enqueue_style('codemirror-theme', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css');
		wp_enqueue_script('codemirror', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js', array('jquery'), null, true);
		wp_enqueue_script('codemirror-sql', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js', array('codemirror'), null, true);
		
		// Get saved queries for dropdown
		$wpdb = csd_db_connection();
		$saved_queries = $wpdb->get_results("SELECT id, query_name FROM " . csd_table('saved_queries') . " ORDER BY query_name");
		?>
		<div class="wrap">
			<h1><?php _e('Custom Query Builder', 'csd-manager'); ?></h1>
			
			<div class="csd-query-builder-container">
				<!-- Saved queries section -->
				<div class="csd-saved-queries">
					<h2><?php _e('Saved Queries', 'csd-manager'); ?></h2>
					<select id="csd-load-query">
						<option value=""><?php _e('-- Select a saved query --', 'csd-manager'); ?></option>
						<?php foreach ($saved_queries as $query): ?>
							<option value="<?php echo esc_attr($query->id); ?>"><?php echo esc_html($query->query_name); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="button" id="csd-load-query-btn" class="button"><?php _e('Load', 'csd-manager'); ?></button>
					<button type="button" id="csd-delete-query-btn" class="button"><?php _e('Delete', 'csd-manager'); ?></button>
				</div>
				
				<!-- Query builder form -->
				<form id="csd-query-builder-form">
					<div class="csd-query-main-panel">
						<div class="csd-query-tabs">
							<button type="button" class="csd-query-tab active" data-panel="fields"><?php _e('Fields', 'csd-manager'); ?></button>
							<button type="button" class="csd-query-tab" data-panel="conditions"><?php _e('Conditions', 'csd-manager'); ?></button>
							<button type="button" class="csd-query-tab" data-panel="options"><?php _e('Options', 'csd-manager'); ?></button>
						</div>
						
						<!-- Fields selection panel -->
						<div class="csd-query-panel active" id="csd-fields-panel">
							<h3><?php _e('Select Fields to Display', 'csd-manager'); ?></h3>
							
							<?php foreach ($this->tables_config as $table_key => $table_config): ?>
								<div class="csd-table-fields">
									<h4><?php echo esc_html($table_config['label']); ?></h4>
									
									<div class="csd-field-list">
										<?php foreach ($table_config['fields'] as $field_key => $field_config): ?>
											<div class="csd-field-checkbox">
												<label>
													<input type="checkbox" name="fields[]" value="<?php echo esc_attr($table_key . '.' . $field_key); ?>">
													<?php echo esc_html($field_config['label']); ?>
												</label>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
							
							<div class="csd-field-actions">
								<button type="button" id="csd-select-all-fields" class="button"><?php _e('Select All', 'csd-manager'); ?></button>
								<button type="button" id="csd-select-none-fields" class="button"><?php _e('Select None', 'csd-manager'); ?></button>
							</div>
						</div>
						
						<!-- Conditions panel -->
						<div class="csd-query-panel" id="csd-conditions-panel">
							<h3><?php _e('Set Query Conditions', 'csd-manager'); ?></h3>
							
							<div id="csd-conditions-container">
								<div class="csd-condition-group" data-group="0">
									<div class="csd-condition-group-header">
										<h4><?php _e('Condition Group', 'csd-manager'); ?> 1</h4>
										<button type="button" class="csd-remove-group button" <?php echo 'style="display:none"'; ?>><?php _e('Remove Group', 'csd-manager'); ?></button>
									</div>
									
									<div class="csd-conditions">
										<div class="csd-condition" data-index="0">
											<select class="csd-condition-field" name="conditions[0][0][field]">
												<option value=""><?php _e('-- Select Field --', 'csd-manager'); ?></option>
												<?php foreach ($this->tables_config as $table_key => $table_config): ?>
													<optgroup label="<?php echo esc_attr($table_config['label']); ?>">
														<?php foreach ($table_config['fields'] as $field_key => $field_config): ?>
															<option value="<?php echo esc_attr($table_key . '.' . $field_key); ?>" data-type="<?php echo esc_attr($field_config['type']); ?>">
																<?php echo esc_html($field_config['label']); ?>
															</option>
														<?php endforeach; ?>
													</optgroup>
												<?php endforeach; ?>
											</select>
											
											<select class="csd-condition-operator" name="conditions[0][0][operator]">
												<option value=""><?php _e('-- Select Operator --', 'csd-manager'); ?></option>
												<option value="="><?php _e('= (equals)', 'csd-manager'); ?></option>
												<option value="!="><?php _e('!= (not equals)', 'csd-manager'); ?></option>
												<option value="LIKE"><?php _e('LIKE', 'csd-manager'); ?></option>
												<option value="LIKE %...%"><?php _e('LIKE %...% (contains)', 'csd-manager'); ?></option>
												<option value="NOT LIKE"><?php _e('NOT LIKE', 'csd-manager'); ?></option>
												<option value="NOT LIKE %...%"><?php _e('NOT LIKE %...% (not contains)', 'csd-manager'); ?></option>
												<option value="REGEXP"><?php _e('REGEXP', 'csd-manager'); ?></option>
												<option value="REGEXP ^...$"><?php _e('REGEXP ^...$ (exact match)', 'csd-manager'); ?></option>
												<option value="NOT REGEXP"><?php _e('NOT REGEXP', 'csd-manager'); ?></option>
												<option value="= ''"><?php _e("= '' (empty)", 'csd-manager'); ?></option>
												<option value="!= ''"><?php _e("!= '' (not empty)", 'csd-manager'); ?></option>
												<option value="IN"><?php _e('IN (...)', 'csd-manager'); ?></option>
												<option value="NOT IN"><?php _e('NOT IN (...)', 'csd-manager'); ?></option>
												<option value="BETWEEN"><?php _e('BETWEEN', 'csd-manager'); ?></option>
												<option value="NOT BETWEEN"><?php _e('NOT BETWEEN', 'csd-manager'); ?></option>
												<option value=">"><?php _e('> (greater than)', 'csd-manager'); ?></option>
												<option value=">="><?php _e('>= (greater than or equal)', 'csd-manager'); ?></option>
												<option value="<"><?php _e('< (less than)', 'csd-manager'); ?></option>
												<option value="<="><?php _e('<= (less than or equal)', 'csd-manager'); ?></option>
											</select>
											
											<div class="csd-condition-value-container">
												<input type="text" class="csd-condition-value" name="conditions[0][0][value]" placeholder="<?php _e('Enter value', 'csd-manager'); ?>">
												
												<div class="csd-between-values" style="display:none;">
													<input type="text" class="csd-condition-value-2" name="conditions[0][0][value2]" placeholder="<?php _e('End value', 'csd-manager'); ?>">
												</div>
												
												<button type="button" class="csd-get-values button" title="<?php _e('Get possible values', 'csd-manager'); ?>">
													<span class="dashicons dashicons-arrow-down"></span>
												</button>
											</div>
											
											<select class="csd-condition-relation" name="conditions[0][0][relation]">
												<option value="AND"><?php _e('AND', 'csd-manager'); ?></option>
												<option value="OR"><?php _e('OR', 'csd-manager'); ?></option>
											</select>
											
											<button type="button" class="csd-remove-condition button" <?php echo 'style="display:none"'; ?>>
												<span class="dashicons dashicons-no"></span>
											</button>
										</div>
									</div>
									
									<div class="csd-condition-actions">
										<button type="button" class="csd-add-condition button"><?php _e('Add Condition', 'csd-manager'); ?></button>
									</div>
								</div>
							</div>
							
							<div class="csd-group-actions">
								<button type="button" id="csd-add-group" class="button"><?php _e('Add Condition Group (OR)', 'csd-manager'); ?></button>
							</div>
						</div>
						
						<!-- Options panel -->
						<div class="csd-query-panel" id="csd-options-panel">
							<h3><?php _e('Query Options', 'csd-manager'); ?></h3>
							
							<div class="csd-options-form">
								<div class="csd-option">
									<label for="csd-limit"><?php _e('Limit Results:', 'csd-manager'); ?></label>
									<input type="number" id="csd-limit" name="limit" value="100" min="1">
								</div>
								
								<div class="csd-option">
									<label for="csd-order-by"><?php _e('Order By:', 'csd-manager'); ?></label>
									<select id="csd-order-by" name="order_by">
										<option value=""><?php _e('-- Select Field --', 'csd-manager'); ?></option>
										<?php foreach ($this->tables_config as $table_key => $table_config): ?>
											<optgroup label="<?php echo esc_attr($table_config['label']); ?>">
												<?php foreach ($table_config['fields'] as $field_key => $field_config): ?>
													<option value="<?php echo esc_attr($table_key . '.' . $field_key); ?>">
														<?php echo esc_html($field_config['label']); ?>
													</option>
												<?php endforeach; ?>
											</optgroup>
										<?php endforeach; ?>
									</select>
									
									<select id="csd-order-dir" name="order_dir">
										<option value="ASC"><?php _e('Ascending', 'csd-manager'); ?></option>
										<option value="DESC"><?php _e('Descending', 'csd-manager'); ?></option>
									</select>
								</div>
								
								<div class="csd-option">
									<label for="csd-join-type"><?php _e('Join Type:', 'csd-manager'); ?></label>
									<select id="csd-join-type" name="join_type">
										<option value="LEFT JOIN"><?php _e('LEFT JOIN (include all records, even if no match)', 'csd-manager'); ?></option>
										<option value="INNER JOIN"><?php _e('INNER JOIN (only include records with matches)', 'csd-manager'); ?></option>
									</select>
								</div>
								
								<div class="csd-option">
									<label for="csd-query-name"><?php _e('Save Query As:', 'csd-manager'); ?></label>
									<input type="text" id="csd-query-name" name="query_name" placeholder="<?php _e('Enter name to save this query', 'csd-manager'); ?>">
									<button type="button" id="csd-save-query-btn" class="button"><?php _e('Save Query', 'csd-manager'); ?></button>
								</div>
							</div>
						</div>
					</div>
					
					<div class="csd-query-actions">
						<button type="submit" id="csd-run-query" class="button button-primary button-large"><?php _e('Run Query', 'csd-manager'); ?></button>
						<button type="button" id="csd-clear-form" class="button button-large"><?php _e('Clear Form', 'csd-manager'); ?></button>
					</div>
				</form>
				
				<div class="csd-query-results-container">
					<div class="csd-query-count-container">
						<h3><?php _e('Query Results', 'csd-manager'); ?></h3>
						<div class="csd-query-count">
							<span class="csd-count-label"><?php _e('Records found:', 'csd-manager'); ?></span>
							<span id="csd-record-count">0</span>
						</div>
					</div>
					
					<!-- Replace the existing SQL query textarea with this -->
					<div class="csd-query-sql-container">
						<h4><?php _e('SQL Query', 'csd-manager'); ?></h4>
						<textarea id="csd-sql-query" rows="8" readonly 
							placeholder="<?php _e('SQL query will appear here after running', 'csd-manager'); ?>"></textarea>
						<div class="csd-sql-actions">
							<button type="button" id="csd-edit-sql" class="button"><?php _e('Edit SQL', 'csd-manager'); ?></button>
							<button type="button" id="csd-run-sql" class="button" style="display:none;"><?php _e('Run SQL', 'csd-manager'); ?></button>
							<button type="button" id="csd-cancel-sql-edit" class="button" style="display:none;"><?php _e('Cancel Edit', 'csd-manager'); ?></button>
						</div>
					</div>
					
					<div class="csd-query-table-container">
						<div id="csd-query-results"></div>
						
						<div class="csd-export-actions">
							<button type="button" id="csd-export-csv" class="button"><?php _e('Export to CSV', 'csd-manager'); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<style type="text/css">
			/* Query Builder Styles */
			.csd-query-builder-container {
				margin-top: 20px;
			}
			
			.csd-saved-queries {
				margin-bottom: 20px;
				padding: 15px;
				background: #f8f8f8;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			
			.csd-query-main-panel {
				background: #fff;
				border: 1px solid #ddd;
				margin-bottom: 20px;
			}
			
			.csd-query-tabs {
				display: flex;
				border-bottom: 1px solid #ddd;
				background: #f5f5f5;
			}
			
			.csd-query-tab {
				padding: 10px 15px;
				border: none;
				background: none;
				border-right: 1px solid #ddd;
				cursor: pointer;
				font-weight: 600;
			}
			
			.csd-query-tab.active {
				background: #fff;
				border-bottom: 2px solid #2271b1;
			}
			
			.csd-query-panel {
				display: none;
				padding: 20px;
			}
			
			.csd-query-panel.active {
				display: block;
			}
			
			.csd-table-fields {
				margin-bottom: 20px;
			}
			
			.csd-field-list {
				display: flex;
				flex-wrap: wrap;
				margin: 0 -10px;
			}
			
			.csd-field-checkbox {
				flex: 0 0 25%;
				padding: 5px 10px;
				box-sizing: border-box;
			}
			
			@media (max-width: 1200px) {
				.csd-field-checkbox {
					flex: 0 0 33.33%;
				}
			}
			
			@media (max-width: 782px) {
				.csd-field-checkbox {
					flex: 0 0 50%;
				}
			}
			
			.csd-field-actions {
				margin-top: 15px;
				text-align: right;
			}
			
			.csd-condition-group {
				margin-bottom: 20px;
				padding: 15px;
				background: #f8f8f8;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			
			.csd-condition-group-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}
			
			.csd-condition-group-header h4 {
				margin: 0;
			}
			
			.csd-condition {
				display: flex;
				margin-bottom: 10px;
				align-items: center;
			}
			
			.csd-condition-field,
			.csd-condition-operator {
				width: 200px;
				margin-right: 10px;
			}
			
			.csd-condition-value-container {
				flex: 1;
				display: flex;
				align-items: center;
				margin-right: 10px;
			}
			
			.csd-condition-value {
				flex: 1;
				margin-right: 5px;
			}
			
			.csd-between-values {
				margin-left: 5px;
				margin-right: 5px;
			}
			
			.csd-condition-value-2 {
				width: 150px;
			}
			
			.csd-values-dropdown {
				position: absolute;
				margin-top: 30px;
				z-index: 100;
				background: white;
				border: 1px solid #ddd;
				max-height: 200px;
				overflow-y: auto;
				width: 200px;
			}
			
			.csd-condition-relation {
				width: 80px;
				margin-right: 10px;
			}
			
			.csd-condition-actions {
				margin-top: 15px;
			}
			
			.csd-group-actions {
				margin-top: 20px;
			}
			
			.csd-options-form {
				max-width: 600px;
			}
			
			.csd-option {
				margin-bottom: 15px;
			}
			
			.csd-option label {
				display: block;
				margin-bottom: 5px;
				font-weight: 600;
			}
			
			.csd-query-actions {
				margin: 20px 0;
			}
			
			.csd-query-results-container {
				margin-top: 30px;
			}
			
			.csd-query-count-container {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 15px;
			}
			
			.csd-query-count {
				font-size: 16px;
				font-weight: 600;
			}
			
			#csd-record-count {
				color: #2271b1;
				font-size: 18px;
				font-weight: 700;
			}
			
			.csd-query-sql-container {
				margin-bottom: 20px;
			}
			
			#csd-sql-query {
				width: 100%;
				font-family: monospace;
				margin-bottom: 10px;
			}
			
			.csd-query-table-container {
				overflow-x: auto;
				margin-bottom: 20px;
			}
			
			.csd-query-table-container table {
				width: 100%;
				border-collapse: collapse;
				margin-bottom: 20px;
			}
			
			.csd-query-table-container th,
			.csd-query-table-container td {
				padding: 8px;
				text-align: left;
				border: 1px solid #ddd;
			}
			
			.csd-query-table-container th {
				background-color: #f5f5f5;
				font-weight: 600;
			}
			
			.csd-query-table-container tr:nth-child(even) {
				background-color: #f9f9f9;
			}
			
			.csd-export-actions {
				margin-top: 15px;
				text-align: right;
			}
			
			/* Make room for the value dropdown */
			.csd-condition-value-container {
				position: relative;
			}
			
			/* Add this to your existing CSS for the query builder */
			.csd-results-table-wrapper {
				overflow-x: auto;
				width: 100%;
				margin-bottom: 20px;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			
			.csd-results-table-wrapper table {
				width: 100%;
				border-collapse: collapse;
				margin: 0;
			}
			
			.csd-results-table-wrapper th,
			.csd-results-table-wrapper td {
				padding: 10px;
				border: 1px solid #eee;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				max-width: 250px; /* Maximum column width */
			}
			
			/* Allow cell content to wrap when hovered */
			.csd-results-table-wrapper td:hover {
				white-space: normal;
				overflow: visible;
				max-width: none;
				background-color: #f9f9f9;
				position: relative;
				z-index: 1;
			}
			
			.csd-results-table-wrapper th {
				background-color: #f5f5f5;
				font-weight: 600;
				position: sticky;
				top: 0;
			}
			
			/* Alternating row colors */
			.csd-results-table-wrapper tr:nth-child(even) {
				background-color: #f9f9f9;
			}
			
			/* Hover effect for rows */
			.csd-results-table-wrapper tr:hover {
				background-color: #f0f0f0;
			}
			
			/* Responsive adjustments */
			@media screen and (max-width: 782px) {
				.csd-results-table-wrapper td, 
				.csd-results-table-wrapper th {
					padding: 8px 5px;
				}
			}
			
			/* Improved SQL textarea styling */
			#csd-sql-query {
				width: 100%;
				font-family: monospace;
				padding: 12px;
				line-height: 1.5;
				border: 1px solid #ddd;
				border-radius: 4px;
				background-color: #f9f9f9;
				resize: vertical;
				min-height: 150px;
				white-space: pre;
				overflow-x: auto;
			}
			
			#csd-sql-query:focus {
				background-color: #fff;
				border-color: #2271b1;
				box-shadow: 0 0 0 1px #2271b1;
				outline: none;
			}
			
			#csd-sql-query[readonly]:hover {
				cursor: pointer;
				background-color: #f0f0f0;
			}
			
			.csd-sql-actions {
				margin-top: 10px;
				margin-bottom: 20px;
			}
			
			/* Add this to your CSS (either in the style tag in query-builder.php or in admin.css) */
			.csd-results-table-wrapper {
				overflow-x: auto;
				max-width: 100%;
				margin-bottom: 20px;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			
			.csd-results-table-wrapper table {
				table-layout: auto;  /* This enables auto-width columns */
				border-collapse: collapse;
				margin: 0;
				min-width: 100%;
			}
			
			.csd-results-table-wrapper th {
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				background-color: #f5f5f5;
				font-weight: 600;
				position: sticky;
				top: 0;
				z-index: 10;
			}
			
			.csd-results-table-wrapper td {
				max-width: 300px;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
			
			/* Add tooltip-like behavior on hover */
			.csd-results-table-wrapper td:hover {
				white-space: normal;
				overflow: visible;
				position: relative;
				background-color: #f9f9f9;
				z-index: 1;
				box-shadow: 0 0 5px rgba(0,0,0,0.2);
			}
			
			/* Add this to your CSS */
			.csd-resizable-table th {
				position: relative;
				padding-right: 20px;
			}
			
			.csd-resizable-table th .resize-handle {
				position: absolute;
				top: 0;
				right: 0;
				width: 8px;
				height: 100%;
				cursor: col-resize;
				user-select: none;
			}
			
			.csd-resizable-table th .resize-handle:hover,
			.csd-resizable-table th .resize-handle.resizing {
				background-color: #0073aa;
			}
			
			.csd-resizable-table th .th-content {
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
			
			/* Add this to your existing CSS in query-builder.php */
			.CodeMirror {
			  border: 1px solid #ddd;
			  height: auto;
			  min-height: 100px;
			  transition: height 0.2s ease;
			}
			
			/* Add this to restore resizable editor */
			.CodeMirror {
			  resize: vertical;
			  overflow: auto !important;
			  min-height: 150px;
			  border: 1px solid #ddd;
			}
			
			/* Make sure the content doesn't overflow */
			.CodeMirror-scroll {
			  overflow-y: hidden;
			  overflow-x: auto;
			}
			
			.resizable-cm {
			  resize: vertical;
			  overflow: auto !important;
			  min-height: 100px;
			  transition: height 0.1s ease;
			}
			.CodeMirror {
			  border: 1px solid #ddd;
			  height: auto;
			  transition: height 0.1s ease;
			}
			.CodeMirror-focused {
			  border-color: #2271b1;
			  box-shadow: 0 0 0 1px #2271b1;
			}
			.CodeMirror-scroll {
			  min-height: 100px;
			}
			/* Dragging handle style */
			.CodeMirror .CodeMirror-scrollbar-filler {
			  background-color: #f0f0f0;
			  cursor: ns-resize;
			}
			.CodeMirror:hover .CodeMirror-scrollbar-filler {
			  background-color: #ddd;
			}
		</style>
		<?php
	}
	
	/**
	 * AJAX handler for running a custom query
	 * This should replace the existing ajax_run_custom_query method
	 */
	public function ajax_run_custom_query() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
			return;
		}
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
			return;
		}
		
		// Check if we're running a custom SQL query directly
		if (isset($_POST['custom_sql'])) {
			$this->run_custom_sql_query();
			return;
		}
		
		// Parse form data
		parse_str($_POST['form_data'], $form_data);
		
		// Validate fields
		if (!isset($form_data['fields']) || !is_array($form_data['fields']) || empty($form_data['fields'])) {
			wp_send_json_error(array('message' => __('Please select at least one field to display.', 'csd-manager')));
			return;
		}
		
		try {
			// Build SQL query
			$sql = $this->build_sql_query($form_data);
			
			// Clean the SQL query - fix any potential issues
			$sql = $this->clean_sql_query($sql);
			
			// Run the query
			$results = $this->execute_query($sql);
			
			// Generate results HTML
			$html = $this->generate_results_html($results);
			
			wp_send_json_success(array(
				'count' => count($results),
				'sql' => $sql,
				'html' => $html
			));
		} catch (Exception $e) {
			wp_send_json_error(array('message' => $e->getMessage()));
		}
	}
	
	/**
	 * Run a custom SQL query
	 * This should replace the existing run_custom_sql_query method
	 */
	private function run_custom_sql_query() {
		$sql = trim($_POST['custom_sql']);
		
		// Basic validation
		if (empty($sql)) {
			wp_send_json_error(array('message' => __('SQL query is empty.', 'csd-manager')));
			return;
		}
		
		// Only allow SELECT queries
		if (!preg_match('/^\s*SELECT\s/i', $sql)) {
			wp_send_json_error(array('message' => __('Only SELECT queries are allowed.', 'csd-manager')));
			return;
		}
		
		try {
			// Clean the SQL query
			$sql = $this->clean_sql_query($sql);
			
			// Run the query
			$results = $this->execute_query($sql);
			
			// Generate results HTML
			$html = $this->generate_results_html($results);
			
			wp_send_json_success(array(
				'count' => count($results),
				'sql' => $sql,
				'html' => $html
			));
		} catch (Exception $e) {
			wp_send_json_error(array('message' => $e->getMessage()));
		}
	}
	
	/**
	 * Clean an SQL query by removing unwanted characters
	 * This is a new method to add to the class
	 * 
	 * @param string $sql The SQL query to clean
	 * @return string Cleaned SQL query
	 */
	private function clean_sql_query($sql) {
		// Make sure we have a string
		if (!is_string($sql)) {
			return '';
		}
		
		// Trim whitespace
		$sql = trim($sql);
		
		// Remove hexadecimal artifacts that might appear in LIKE clauses
		$sql = preg_replace('/{[0-9a-f]+}/', '%', $sql);
		
		// Remove any double %% that might cause issues
		$sql = str_replace('%%', '%', $sql);
		
		return $sql;
	}
	
	/**
	 * Build SQL query from form data
	 * 
	 * @param array $form_data Form data
	 * @return string SQL query
	 */
	private function build_sql_query($form_data) {
		// Extract selected fields
		$fields = $form_data['fields'];
		
		// Determine which tables are needed
		$tables_needed = array();
		foreach ($fields as $field) {
			list($table, $field_name) = explode('.', $field);
			$tables_needed[$table] = true;
		}
		
		// Add tables needed for conditions
		if (isset($form_data['conditions']) && is_array($form_data['conditions'])) {
			foreach ($form_data['conditions'] as $group) {
				if (is_array($group)) {
					foreach ($group as $condition) {
						if (!empty($condition['field'])) {
							list($table, $field_name) = explode('.', $condition['field']);
							$tables_needed[$table] = true;
						}
					}
				}
			}
		}
		
		// Create SELECT clause
		$select_fields = array();
		foreach ($fields as $field) {
			list($table, $field_name) = explode('.', $field);
			$select_fields[] = $this->tables_config[$table]['table'] . '.' . $field_name . ' AS `' . $table . '_' . $field_name . '`';
		}
		
		$select_clause = 'SELECT ' . implode(', ', $select_fields);
		
		// Create FROM and JOIN clauses
		$join_type = isset($form_data['join_type']) ? $form_data['join_type'] : 'LEFT JOIN';
		$tables_list = array_keys($tables_needed);
		
		// Start with the first table
		$from_clause = 'FROM ' . $this->tables_config[$tables_list[0]]['table'];
		
		// Add JOINs for additional tables
		$join_clauses = array();
		if (count($tables_list) > 1) {
			// If we have both schools and staff tables, use school_staff to join them
			if (isset($tables_needed['schools']) && isset($tables_needed['staff'])) {
				$tables_needed['school_staff'] = true;
				
				if ($tables_list[0] === 'schools') {
					$join_clauses[] = $join_type . ' ' . $this->tables_config['school_staff']['table'] . ' ON ' . 
									  $this->tables_config['schools']['table'] . '.id = ' . 
									  $this->tables_config['school_staff']['table'] . '.school_id';
					
					$join_clauses[] = $join_type . ' ' . $this->tables_config['staff']['table'] . ' ON ' . 
									  $this->tables_config['school_staff']['table'] . '.staff_id = ' . 
									  $this->tables_config['staff']['table'] . '.id';
				} else {
					$join_clauses[] = $join_type . ' ' . $this->tables_config['school_staff']['table'] . ' ON ' . 
									  $this->tables_config['staff']['table'] . '.id = ' . 
									  $this->tables_config['school_staff']['table'] . '.staff_id';
					
					$join_clauses[] = $join_type . ' ' . $this->tables_config['schools']['table'] . ' ON ' . 
									  $this->tables_config['school_staff']['table'] . '.school_id = ' . 
									  $this->tables_config['schools']['table'] . '.id';
				}
			} 
			// Handle school_staff table if it's explicitly selected
			else if (isset($tables_needed['school_staff'])) {
				if (isset($tables_needed['schools'])) {
					$join_clauses[] = $join_type . ' ' . $this->tables_config['school_staff']['table'] . ' ON ' . 
									  $this->tables_config['schools']['table'] . '.id = ' . 
									  $this->tables_config['school_staff']['table'] . '.school_id';
				} else if (isset($tables_needed['staff'])) {
					$join_clauses[] = $join_type . ' ' . $this->tables_config['school_staff']['table'] . ' ON ' . 
									  $this->tables_config['staff']['table'] . '.id = ' . 
									  $this->tables_config['school_staff']['table'] . '.staff_id';
				}
			}
		}
		
		// Combine JOIN clauses
		$join_clause = implode(' ', $join_clauses);
		
		// Create WHERE clause
		$where_clause = '';
		if (isset($form_data['conditions']) && is_array($form_data['conditions'])) {
			$condition_groups = array();
			
			foreach ($form_data['conditions'] as $group) {
				if (is_array($group)) {
					$conditions = array();
					
					foreach ($group as $index => $condition) {
						// Skip empty conditions
						if (empty($condition['field']) || empty($condition['operator'])) {
							continue;
						}
						
						// Parse field
						list($table, $field_name) = explode('.', $condition['field']);
						$field = $this->tables_config[$table]['table'] . '.' . $field_name;
						
						// Handle different operators
						$where_condition = '';
						
						switch ($condition['operator']) {
							case '=':
							case '!=':
							case '>':
							case '>=':
							case '<':
							case '<=':
								$where_condition = $field . ' ' . $condition['operator'] . ' ' . $this->prepare_value($condition['value']);
								break;
								
							case 'LIKE':
								$where_condition = $field . ' LIKE ' . $this->prepare_value($condition['value']);
								break;
								
							case 'LIKE %...%':
								$where_condition = $field . ' LIKE ' . $this->prepare_value('%' . $condition['value'] . '%');
								break;
								
							case 'NOT LIKE':
								$where_condition = $field . ' NOT LIKE ' . $this->prepare_value($condition['value']);
								break;
								
							case 'NOT LIKE %...%':
								$where_condition = $field . ' NOT LIKE ' . $this->prepare_value('%' . $condition['value'] . '%');
								break;
								
							case 'REGEXP':
								$where_condition = $field . ' REGEXP ' . $this->prepare_value($condition['value']);
								break;
								
							case 'REGEXP ^...$':
								$where_condition = $field . ' REGEXP ' . $this->prepare_value('^' . $condition['value'] . '$');
								break;
								
							case 'NOT REGEXP':
								$where_condition = $field . ' NOT REGEXP ' . $this->prepare_value($condition['value']);
								break;
								
							case "= ''":
								$where_condition = '(' . $field . ' = \'\' OR ' . $field . ' IS NULL)';
								break;
								
							case "!= ''":
								$where_condition = '(' . $field . ' != \'\' AND ' . $field . ' IS NOT NULL)';
								break;
								
							case 'IN':
								$values = array_map('trim', explode(',', $condition['value']));
								$prepared_values = array();
								
								foreach ($values as $value) {
									$prepared_values[] = $this->prepare_value($value);
								}
								
								$where_condition = $field . ' IN (' . implode(', ', $prepared_values) . ')';
								break;
								
							case 'NOT IN':
								$values = array_map('trim', explode(',', $condition['value']));
								$prepared_values = array();
								
								foreach ($values as $value) {
									$prepared_values[] = $this->prepare_value($value);
								}
								
								$where_condition = $field . ' NOT IN (' . implode(', ', $prepared_values) . ')';
								break;
								
							case 'BETWEEN':
								$where_condition = $field . ' BETWEEN ' . $this->prepare_value($condition['value']) . ' AND ' . $this->prepare_value($condition['value2']);
								break;
								
							case 'NOT BETWEEN':
								$where_condition = $field . ' NOT BETWEEN ' . $this->prepare_value($condition['value']) . ' AND ' . $this->prepare_value($condition['value2']);
								break;
						}
						
						if (!empty($where_condition)) {
							if ($index < count($group) - 1 && isset($condition['relation'])) {
								$where_condition .= ' ' . $condition['relation'] . ' ';
							}
							
							$conditions[] = $where_condition;
						}
					}
					
					if (!empty($conditions)) {
						$condition_groups[] = '(' . implode('', $conditions) . ')';
					}
				}
			}
			
			if (!empty($condition_groups)) {
				$where_clause = 'WHERE ' . implode(' OR ', $condition_groups);
			}
		}
		
		// Create ORDER BY clause
		$order_clause = '';
		if (!empty($form_data['order_by'])) {
			list($table, $field_name) = explode('.', $form_data['order_by']);
			$order_field = $this->tables_config[$table]['table'] . '.' . $field_name;
			$order_direction = !empty($form_data['order_dir']) ? $form_data['order_dir'] : 'ASC';
			
			$order_clause = 'ORDER BY ' . $order_field . ' ' . $order_direction;
		}
		
		// Create LIMIT clause
		$limit_clause = '';
		if (!empty($form_data['limit'])) {
			$limit = intval($form_data['limit']);
			if ($limit > 0) {
				$limit_clause = 'LIMIT ' . $limit;
			}
		}
		
		// Combine all clauses
		$sql = $select_clause . ' ' . $from_clause . ' ' . $join_clause;
		
		if (!empty($where_clause)) {
			$sql .= ' ' . $where_clause;
		}
		
		if (!empty($order_clause)) {
			$sql .= ' ' . $order_clause;
		}
		
		if (!empty($limit_clause)) {
			$sql .= ' ' . $limit_clause;
		}
		
		return $sql;
	}
	
	/**
	 * Prepare value for SQL query
	 * This replaces the current prepare_value method in the CSD_Query_Builder class
	 * 
	 * @param mixed $value Value to prepare
	 * @return string Prepared value
	 */
	private function prepare_value($value) {
		global $wpdb;
		
		// Handle empty values
		if (is_null($value) || $value === '') {
			return "''";
		}
		
		// Check if it's numeric
		if (is_numeric($value)) {
			return $value;
		}
		
		// For text values, use proper escaping with $wpdb->prepare
		// But manually handle the % characters for LIKE queries
		if (strpos($value, '%') !== false) {
			// Replace % with escaped version first to avoid SQL injection
			$escaped_value = str_replace('%', '%%', $value);
			// Then use $wpdb->prepare for proper escaping
			$prepared = $wpdb->prepare('%s', $escaped_value);
			// Then put back the % signs for LIKE queries
			return str_replace('%%', '%', $prepared);
		} else {
			// Normal string escaping
			return $wpdb->prepare('%s', $value);
		}
	}
	
	/**
	 * Execute SQL query
	 * 
	 * @param string $sql SQL query
	 * @return array Query results
	 */
	private function execute_query($sql) {
		$wpdb = csd_db_connection();
		
		// Run the query
		$results = $wpdb->get_results($sql, ARRAY_A);
		
		if ($wpdb->last_error) {
			throw new Exception('Database error: ' . $wpdb->last_error);
		}
		
		return $results;
	}
	
	/**
	 * Generate HTML for query results
	 * 
	 * @param array $results Query results
	 * @return string HTML
	 */
	private function generate_results_html($results) {
		$html = '';
		
		if (empty($results)) {
			$html = '<div class="notice notice-warning"><p>' . __('No results found.', 'csd-manager') . '</p></div>';
		} else {
			$html .= '<div class="csd-results-table-wrapper">';
			$html .= '<table class="wp-list-table widefat fixed striped csd-resizable-table">';
			
			// Table headers
			$html .= '<thead><tr>';
			foreach (array_keys($results[0]) as $column) {
				$label = $column;
				
				// Try to make the column header more readable
				$label = str_replace('_', ' ', $label);
				$label = ucwords($label);
				
				$html .= '<th><div class="th-content">' . esc_html($label) . '</div><div class="resize-handle"></div></th>';
			}
			$html .= '</tr></thead>';
			
			// Table body
			$html .= '<tbody>';
			foreach ($results as $row) {
				$html .= '<tr>';
				foreach ($row as $key => $value) {
					// Format the value for display
					$display_value = $this->format_value_for_display($key, $value);
					$html .= '<td>' . $display_value . '</td>';
				}
				$html .= '</tr>';
			}
			$html .= '</tbody>';
			
			$html .= '</table>';
			$html .= '</div>';
		}
		
		return $html;
	}
	
	/**
	 * Format a value for display in results table
	 * 
	 * @param string $key Column key
	 * @param mixed $value Value to format
	 * @return string Formatted value
	 */
	private function format_value_for_display($key, $value) {
		// Handle null/empty values
		if ($value === null || $value === '') {
			return '&mdash;';
		}
		
		// Format dates
		if (strpos($key, 'date_') !== false && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
			return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($value));
		}
		
		// Format URLs
		if (strpos($key, 'website') !== false || strpos($key, 'url') !== false) {
			if (filter_var($value, FILTER_VALIDATE_URL)) {
				return '<a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a>';
			}
		}
		
		// Format email
		if (strpos($key, 'email') !== false) {
			if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
				return '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
			}
		}
		
		// Default formatting
		return esc_html($value);
	}
	
	/**
	 * AJAX handler for getting field values
	 */
	public function ajax_get_field_values() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
			return;
		}
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
			return;
		}
		
		// Get field
		$field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
		
		if (empty($field) || !strpos($field, '.')) {
			wp_send_json_error(array('message' => __('Invalid field.', 'csd-manager')));
			return;
		}
		
		list($table, $field_name) = explode('.', $field);
		
		if (!isset($this->tables_config[$table]) || !isset($this->tables_config[$table]['fields'][$field_name])) {
			wp_send_json_error(array('message' => __('Field not found.', 'csd-manager')));
			return;
		}
		
		$wpdb = csd_db_connection();
		
		// Get distinct values
		$values = $wpdb->get_col("
			SELECT DISTINCT {$field_name}
			FROM {$this->tables_config[$table]['table']}
			WHERE {$field_name} IS NOT NULL AND {$field_name} != ''
			ORDER BY {$field_name}
			LIMIT 100
		");
		
		wp_send_json_success(array('values' => $values));
	}
	
	/**
	 * AJAX handler for saving a query
	 */
	public function ajax_save_query() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
			return;
		}
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
			return;
		}
		
		$query_name = isset($_POST['query_name']) ? sanitize_text_field($_POST['query_name']) : '';
		
		if (empty($query_name)) {
			wp_send_json_error(array('message' => __('Query name is required.', 'csd-manager')));
			return;
		}
		
		// Parse form data
		parse_str($_POST['form_data'], $form_data);
		
		// Initialize variables
		$wpdb = csd_db_connection();
		
		// Check if a query with this name already exists
		$existing_query_id = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM " . csd_table('saved_queries') . " WHERE query_name = %s",
			$query_name
		));
		
		// Prepare query data
		$query_data = array(
			'query_name' => $query_name,
			'query_settings' => json_encode($form_data),
			'date_created' => current_time('mysql')
		);
		
		if ($existing_query_id) {
			// Update existing query
			$result = $wpdb->update(
				csd_table('saved_queries'),
				$query_data,
				array('id' => $existing_query_id)
			);
			
			$query_id = $existing_query_id;
		} else {
			// Insert new query
			$result = $wpdb->insert(
				csd_table('saved_queries'),
				$query_data
			);
			
			$query_id = $wpdb->insert_id;
		}
		
		if ($result === false) {
			wp_send_json_error(array('message' => __('Error saving query.', 'csd-manager')));
			return;
		}
		
		wp_send_json_success(array(
			'message' => __('Query saved successfully.', 'csd-manager'),
			'query_id' => $query_id
		));
	}
	
	/**
	 * AJAX handler for loading a query
	 */
	public function ajax_load_query() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
			return;
		}
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
			return;
		}
		
		$query_id = isset($_POST['query_id']) ? intval($_POST['query_id']) : 0;
		
		if (!$query_id) {
			wp_send_json_error(array('message' => __('Invalid query ID.', 'csd-manager')));
			return;
		}
		
		$wpdb = csd_db_connection();
		
		// Get query data
		$query = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . csd_table('saved_queries') . " WHERE id = %d",
			$query_id
		));
		
		if (!$query) {
			wp_send_json_error(array('message' => __('Query not found.', 'csd-manager')));
			return;
		}
		
		// Parse query settings
		$query_settings = json_decode($query->query_settings, true);
		
		if (!$query_settings) {
			wp_send_json_error(array('message' => __('Invalid query settings.', 'csd-manager')));
			return;
		}
		
		// Add query name
		$query_settings['query_name'] = $query->query_name;
		
		wp_send_json_success(array(
			'query_data' => $query_settings
		));
	}
	
	/**
	 * AJAX handler for deleting a query
	 */
	public function ajax_delete_query() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'csd-manager')));
			return;
		}
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
			return;
		}
		
		$query_id = isset($_POST['query_id']) ? intval($_POST['query_id']) : 0;
		
		if (!$query_id) {
			wp_send_json_error(array('message' => __('Invalid query ID.', 'csd-manager')));
			return;
		}
		
		$wpdb = csd_db_connection();
		
		// Delete query
		$result = $wpdb->delete(
			csd_table('saved_queries'),
			array('id' => $query_id)
		);
		
		if ($result === false) {
			wp_send_json_error(array('message' => __('Error deleting query.', 'csd-manager')));
			return;
		}
		
		wp_send_json_success(array(
			'message' => __('Query deleted successfully.', 'csd-manager')
		));
	}
	
	/**
	 * AJAX handler for exporting query results
	 */
	public function ajax_export_query_results() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csd-ajax-nonce')) {
			wp_die(__('Security check failed.', 'csd-manager'));
		}
		
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have permission to perform this action.', 'csd-manager'));
		}
		
		$sql = isset($_POST['sql']) ? trim($_POST['sql']) : '';
		
		if (empty($sql)) {
			wp_die(__('SQL query is empty.', 'csd-manager'));
		}
		
		// Only allow SELECT queries
		if (!preg_match('/^\s*SELECT\s/i', $sql)) {
			wp_die(__('Only SELECT queries are allowed.', 'csd-manager'));
		}
		
		try {
			// Run the query
			$results = $this->execute_query($sql);
			
			if (empty($results)) {
				wp_die(__('No results to export.', 'csd-manager'));
			}
			
			// Set filename
			$filename = 'csd-query-export-' . date('Y-m-d') . '.csv';
			
			// Set headers for CSV download
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $filename);
			
			// Create output handle
			$output = fopen('php://output', 'w');
			
			// Add headers
			fputcsv($output, array_keys($results[0]));
			
			// Add data
			foreach ($results as $row) {
				fputcsv($output, $row);
			}
			
			fclose($output);
			exit;
		} catch (Exception $e) {
			wp_die($e->getMessage());
		}
	}
	
	/**
	 * Create saved queries table
	 */
	public function create_saved_queries_table() {
		$wpdb = csd_db_connection();
		
		$table_name = csd_table('saved_queries');
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			query_name varchar(255) NOT NULL,
			query_settings longtext NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}
