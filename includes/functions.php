<?php
/**
 * Helper Functions
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get states for dropdown
 * 
 * @return array Array of states
 */
function csd_get_states() {
	return array(
		'AL' => 'Alabama',
		'AK' => 'Alaska',
		'AZ' => 'Arizona',
		'AR' => 'Arkansas',
		'CA' => 'California',
		'CO' => 'Colorado',
		'CT' => 'Connecticut',
		'DE' => 'Delaware',
		'DC' => 'District of Columbia',
		'FL' => 'Florida',
		'GA' => 'Georgia',
		'HI' => 'Hawaii',
		'ID' => 'Idaho',
		'IL' => 'Illinois',
		'IN' => 'Indiana',
		'IA' => 'Iowa',
		'KS' => 'Kansas',
		'KY' => 'Kentucky',
		'LA' => 'Louisiana',
		'ME' => 'Maine',
		'MD' => 'Maryland',
		'MA' => 'Massachusetts',
		'MI' => 'Michigan',
		'MN' => 'Minnesota',
		'MS' => 'Mississippi',
		'MO' => 'Missouri',
		'MT' => 'Montana',
		'NE' => 'Nebraska',
		'NV' => 'Nevada',
		'NH' => 'New Hampshire',
		'NJ' => 'New Jersey',
		'NM' => 'New Mexico',
		'NY' => 'New York',
		'NC' => 'North Carolina',
		'ND' => 'North Dakota',
		'OH' => 'Ohio',
		'OK' => 'Oklahoma',
		'OR' => 'Oregon',
		'PA' => 'Pennsylvania',
		'RI' => 'Rhode Island',
		'SC' => 'South Carolina',
		'SD' => 'South Dakota',
		'TN' => 'Tennessee',
		'TX' => 'Texas',
		'UT' => 'Utah',
		'VT' => 'Vermont',
		'VA' => 'Virginia',
		'WA' => 'Washington',
		'WV' => 'West Virginia',
		'WI' => 'Wisconsin',
		'WY' => 'Wyoming'
	);
}

/**
 * Get divisions for dropdown
 * 
 * @return array Array of divisions
 */
function csd_get_divisions() {
	return array(
		'NCAA D1' => 'NCAA Division I',
		'NCAA D2' => 'NCAA Division II',
		'NCAA D3' => 'NCAA Division III',
		'NAIA' => 'NAIA',
		'NJCAA' => 'NJCAA',
		'CCCAA' => 'CCCAA',
		'NCCAA' => 'NCCAA',
		'USCAA' => 'USCAA',
		'Other' => 'Other'
	);
}

/**
 * Get school types for dropdown
 * 
 * @return array Array of school types
 */
function csd_get_school_types() {
	return array(
		'Public' => 'Public',
		'Private' => 'Private',
		'Community College' => 'Community College',
		'Technical College' => 'Technical College',
		'Other' => 'Other'
	);
}

/**
 * Check if a school exists by name
 * 
 * @param string $school_name School name
 * @return int|false School ID or false if not found
 */
function csd_school_exists($school_name) {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_var($wpdb->prepare(
		"SELECT id FROM " . csd_table('schools') . " WHERE school_name = %s",
		$school_name
	));
}

/**
 * Check if a staff member exists by name and email
 * 
 * @param string $full_name Full name
 * @param string $email Email address
 * @return int|false Staff ID or false if not found
 */
function csd_staff_exists($full_name, $email = '') {
	$wpdb = csd_db_connection();
	
	if (!empty($email)) {
		return $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM " . csd_table('staff') . " WHERE full_name = %s AND email = %s",
			$full_name,
			$email
		));
	} else {
		return $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM " . csd_table('staff') . " WHERE full_name = %s",
			$full_name
		));
	}
}

/**
 * Get school details by ID
 * 
 * @param int $school_id School ID
 * @return object|false School object or false if not found
 */
function csd_get_school($school_id) {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM " . csd_table('schools') . " WHERE id = %d",
		$school_id
	));
}

/**
 * Get staff details by ID
 * 
 * @param int $staff_id Staff ID
 * @return object|false Staff object or false if not found
 */
function csd_get_staff($staff_id) {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM " . csd_table('staff') . " WHERE id = %d",
		$staff_id
	));
}

/**
 * Get staff members for a school
 * 
 * @param int $school_id School ID
 * @param array $args Additional arguments (sort_by, sort_order, limit)
 * @return array Array of staff objects
 */
function csd_get_school_staff($school_id, $args = array()) {
	$wpdb = csd_db_connection();
	
	$defaults = array(
		'sort_by' => 'full_name',
		'sort_order' => 'ASC',
		'limit' => 0
	);
	
	$args = wp_parse_args($args, $defaults);
	
	// Validate sort by column
	$allowed_sort_columns = array(
		'full_name', 'title', 'sport_department', 'email', 'phone'
	);
	
	if (!in_array($args['sort_by'], $allowed_sort_columns)) {
		$args['sort_by'] = 'full_name';
	}
	
	// Validate sort order
	if ($args['sort_order'] !== 'ASC' && $args['sort_order'] !== 'DESC') {
		$args['sort_order'] = 'ASC';
	}
	
	$query = $wpdb->prepare(
		"SELECT s.*
		 FROM " . csd_table('staff') . " s
		 JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
		 WHERE ss.school_id = %d
		 ORDER BY s.{$args['sort_by']} {$args['sort_order']}",
		$school_id
	);
	
	if ($args['limit'] > 0) {
		$query .= $wpdb->prepare(" LIMIT %d", $args['limit']);
	}
	
	return $wpdb->get_results($query);
}

/**
 * Get school for a staff member
 * 
 * @param int $staff_id Staff ID
 * @return object|false School object or false if not found
 */
function csd_get_staff_school($staff_id) {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_row($wpdb->prepare(
		"SELECT s.*
		 FROM " . csd_table('schools') . " s
		 JOIN " . csd_table('school_staff') . " ss ON s.id = ss.school_id
		 WHERE ss.staff_id = %d",
		$staff_id
	));
}

/**
 * Count schools in database
 * 
 * @return int Number of schools
 */
function csd_count_schools() {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_var("SELECT COUNT(*) FROM " . csd_table('schools'));
}

/**
 * Count staff members in database
 * 
 * @return int Number of staff members
 */
function csd_count_staff() {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_var("SELECT COUNT(*) FROM " . csd_table('staff'));
}

/**
 * Format phone number
 * 
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function csd_format_phone($phone) {
	// Remove all non-numeric characters
	$phone = preg_replace('/[^0-9]/', '', $phone);
	
	// Format the phone number based on length
	if (strlen($phone) === 10) {
		return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
	} elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
		return '1-(' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7);
	}
	
	// Return original if not a standard format
	return $phone;
}

/**
 * Generate a CSV file from array
 * 
 * @param array $data Array of data
 * @param array $headers Array of headers
 * @param string $filename Filename
 */
function csd_generate_csv($data, $headers, $filename) {
	// Set headers for CSV download
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=' . $filename);
	
	// Create output handle
	$output = fopen('php://output', 'w');
	
	// Add headers
	fputcsv($output, $headers);
	
	// Add data
	foreach ($data as $row) {
		fputcsv($output, $row);
	}
	
	fclose($output);
	exit;
}

/**
 * Get distinct sport departments
 * 
 * @return array Array of sport departments
 */
function csd_get_sport_departments() {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_col("
		SELECT DISTINCT sport_department
		FROM " . csd_table('staff') . "
		WHERE sport_department != ''
		ORDER BY sport_department
	");
}

/**
 * Get distinct states
 * 
 * @return array Array of states
 */
function csd_get_distinct_states() {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_col("
		SELECT DISTINCT state
		FROM " . csd_table('schools') . "
		WHERE state != ''
		ORDER BY state
	");
}

/**
 * Get latest schools
 * 
 * @param int $limit Number of schools to get
 * @return array Array of school objects
 */
function csd_get_latest_schools($limit = 5) {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM " . csd_table('schools') . " ORDER BY date_created DESC LIMIT %d",
		$limit
	));
}

/**
 * Get latest staff members
 * 
 * @param int $limit Number of staff members to get
 * @return array Array of staff objects
 */
function csd_get_latest_staff($limit = 5) {
	$wpdb = csd_db_connection();
	
	return $wpdb->get_results($wpdb->prepare(
		"SELECT s.*, sch.school_name
		 FROM " . csd_table('staff') . " s
		 LEFT JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
		 LEFT JOIN " . csd_table('schools') . " sch ON ss.school_id = sch.id
		 ORDER BY s.date_created DESC
		 LIMIT %d",
		$limit
	));
}

/**
 * Add frontend CSS and JS
 */
function csd_enqueue_frontend_assets() {
	global $post;
	
	// Check if post content contains our shortcodes
	if (is_a($post, 'WP_Post') && (
		has_shortcode($post->post_content, 'csd_schools') ||
		has_shortcode($post->post_content, 'csd_staff') ||
		has_shortcode($post->post_content, 'csd_school') ||
		has_shortcode($post->post_content, 'csd_saved_view')
	)) {
		wp_enqueue_style('csd-frontend-styles');
		wp_enqueue_script('csd-frontend-scripts');
		
		// Localize script with AJAX URL
		wp_localize_script('csd-frontend-scripts', 'csd_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php')
		));
	}
}
add_action('wp_enqueue_scripts', 'csd_enqueue_frontend_assets');

/**
 * Register frontend widgets
 */
function csd_register_widgets() {
	register_widget('CSD_Schools_Widget');
	register_widget('CSD_Staff_Widget');
}
add_action('widgets_init', 'csd_register_widgets');

/**
 * Schools Widget
 */
class CSD_Schools_Widget extends WP_Widget {
	/**
	 * Register widget
	 */
	public function __construct() {
		parent::__construct(
			'csd_schools_widget',
			__('CSD Schools', 'csd-manager'),
			array('description' => __('Display a list of schools.', 'csd-manager'))
		);
	}
	
	/**
	 * Front-end display of widget
	 *
	 * @param array $args Widget arguments
	 * @param array $instance Saved values from database
	 */
	public function widget($args, $instance) {
		echo $args['before_widget'];
		
		if (!empty($instance['title'])) {
			echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
		}
		
		$shortcode_atts = array(
			'per_page' => $instance['limit'],
			'sort_by' => $instance['sort_by'],
			'sort_order' => $instance['sort_order'],
			'state' => $instance['state'],
			'division' => $instance['division'],
			'show_search' => 0,
			'show_state_filter' => 0,
			'show_division_filter' => 0
		);
		
		echo do_shortcode('[csd_schools ' . $this->build_shortcode_atts($shortcode_atts) . ']');
		
		echo $args['after_widget'];
	}
	
	/**
	 * Back-end widget form
	 *
	 * @param array $instance Previously saved values from database
	 */
	public function form($instance) {
		$title = !empty($instance['title']) ? $instance['title'] : __('Schools', 'csd-manager');
		$limit = !empty($instance['limit']) ? $instance['limit'] : 5;
		$sort_by = !empty($instance['sort_by']) ? $instance['sort_by'] : 'school_name';
		$sort_order = !empty($instance['sort_order']) ? $instance['sort_order'] : 'ASC';
		$state = !empty($instance['state']) ? $instance['state'] : '';
		$division = !empty($instance['division']) ? $instance['division'] : '';
		
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'csd-manager'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php _e('Number of schools to show:', 'csd-manager'); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" value="<?php echo esc_attr($limit); ?>" min="1" max="20">
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('sort_by')); ?>"><?php _e('Sort by:', 'csd-manager'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('sort_by')); ?>" name="<?php echo esc_attr($this->get_field_name('sort_by')); ?>">
				<option value="school_name" <?php selected($sort_by, 'school_name'); ?>><?php _e('School Name', 'csd-manager'); ?></option>
				<option value="city" <?php selected($sort_by, 'city'); ?>><?php _e('City', 'csd-manager'); ?></option>
				<option value="state" <?php selected($sort_by, 'state'); ?>><?php _e('State', 'csd-manager'); ?></option>
			</select>
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('sort_order')); ?>"><?php _e('Sort order:', 'csd-manager'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('sort_order')); ?>" name="<?php echo esc_attr($this->get_field_name('sort_order')); ?>">
				<option value="ASC" <?php selected($sort_order, 'ASC'); ?>><?php _e('Ascending', 'csd-manager'); ?></option>
				<option value="DESC" <?php selected($sort_order, 'DESC'); ?>><?php _e('Descending', 'csd-manager'); ?></option>
			</select>
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('state')); ?>"><?php _e('Filter by state:', 'csd-manager'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('state')); ?>" name="<?php echo esc_attr($this->get_field_name('state')); ?>">
				<option value=""><?php _e('All States', 'csd-manager'); ?></option>
				<?php
				$states = csd_get_distinct_states();
				
				foreach ($states as $state_option) {
					echo '<option value="' . esc_attr($state_option) . '" ' . selected($state, $state_option, false) . '>' . esc_html($state_option) . '</option>';
				}
				?>
			</select>
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('division')); ?>"><?php _e('Filter by division:', 'csd-manager'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('division')); ?>" name="<?php echo esc_attr($this->get_field_name('division')); ?>">
				<option value=""><?php _e('All Divisions', 'csd-manager'); ?></option>
				<?php
				$divisions = csd_get_divisions();
				
				foreach ($divisions as $key => $label) {
					echo '<option value="' . esc_attr($key) . '" ' . selected($division, $key, false) . '>' . esc_html($label) . '</option>';
				}
				?>
			</select>
		</p>
		<?php
	}
	
	/**
	 * Sanitize widget form values as they are saved
	 *
	 * @param array $new_instance Values just sent to be saved
	 * @param array $old_instance Previously saved values from database
	 * @return array Updated safe values to be saved
	 */
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
		$instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 5;
		$instance['sort_by'] = (!empty($new_instance['sort_by'])) ? sanitize_text_field($new_instance['sort_by']) : 'school_name';
		$instance['sort_order'] = (!empty($new_instance['sort_order'])) ? sanitize_text_field($new_instance['sort_order']) : 'ASC';
		$instance['state'] = (!empty($new_instance['state'])) ? sanitize_text_field($new_instance['state']) : '';
		$instance['division'] = (!empty($new_instance['division'])) ? sanitize_text_field($new_instance['division']) : '';
		
		return $instance;
	}
	
	/**
	 * Build shortcode attributes
	 *
	 * @param array $atts Attributes
	 * @return string Shortcode attributes
	 */
	private function build_shortcode_atts($atts) {
		$shortcode_atts = array();
		
		foreach ($atts as $key => $value) {
			if (!empty($value)) {
				$shortcode_atts[] = $key . '="' . esc_attr($value) . '"';
			}
		}
		
		return implode(' ', $shortcode_atts);
	}
}

/**
 * Staff Widget
 */
class CSD_Staff_Widget extends WP_Widget {
	/**
	 * Register widget
	 */
	public function __construct() {
		parent::__construct(
			'csd_staff_widget',
			__('CSD Staff', 'csd-manager'),
			array('description' => __('Display a list of staff members.', 'csd-manager'))
		);
	}
	
	/**
	 * Front-end display of widget
	 *
	 * @param array $args Widget arguments
	 * @param array $instance Saved values from database
	 */
	public function widget($args, $instance) {
		echo $args['before_widget'];
		
		if (!empty($instance['title'])) {
			echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
		}
		
		$shortcode_atts = array(
			'per_page' => $instance['limit'],
			'sort_by' => $instance['sort_by'],
			'sort_order' => $instance['sort_order'],
			'school_id' => $instance['school_id'],
			'department' => $instance['department'],
			'show_search' => 0,
			'show_school_filter' => 0,
			'show_department_filter' => 0
		);
		
		echo do_shortcode('[csd_staff ' . $this->build_shortcode_atts($shortcode_atts) . ']');
		
		echo $args['after_widget'];
	}
	
	/**
	 * Back-end widget form
	 *
	 * @param array $instance Previously saved values from database
	 */
	public function form($instance) {
		$title = !empty($instance['title']) ? $instance['title'] : __('Staff', 'csd-manager');
		$limit = !empty($instance['limit']) ? $instance['limit'] : 5;
		$sort_by = !empty($instance['sort_by']) ? $instance['sort_by'] : 'full_name';
		$sort_order = !empty($instance['sort_order']) ? $instance['sort_order'] : 'ASC';
		$school_id = !empty($instance['school_id']) ? $instance['school_id'] : 0;
		$department = !empty($instance['department']) ? $instance['department'] : '';
		
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'csd-manager'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php _e('Number of staff to show:', 'csd-manager'); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" value="<?php echo esc_attr($limit); ?>" min="1" max="20">
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('sort_by')); ?>"><?php _e('Sort by:', 'csd-manager'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('sort_by')); ?>" name="<?php echo esc_attr($this->get_field_name('sort_by')); ?>">
				<option value="full_name" <?php selected($sort_by, 'full_name'); ?>><?php _e('Name', 'csd-manager'); ?></option>
				<option value="title" <?php selected($sort_by, 'title'); ?>><?php _e('Title', 'csd-manager'); ?></option>
				<option value="sport_department" <?php selected($sort_by, 'sport_department'); ?>><?php _e('Department', 'csd-manager'); ?></option>
				<option value="school_name" <?php selected($sort_by, 'school_name'); ?>><?php _e('School', 'csd-manager'); ?></option>
			</select>
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('sort_order')); ?>"><?php _e('Sort order:', 'csd-manager'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('sort_order')); ?>" name="<?php echo esc_attr($this->get_field_name('sort_order')); ?>">
				<option value="ASC" <?php selected($sort_order, 'ASC'); ?>><?php _e('Ascending', 'csd-manager'); ?></option>
				<option value="DESC" <?php selected($sort_order, 'DESC'); ?>><?php _e('Descending', 'csd-manager'); ?></option>
			</select>
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('school_id')); ?>"><?php _e('Filter by school:', 'csd-manager'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('school_id')); ?>" name="<?php echo esc_attr($this->get_field_name('school_id')); ?>">
				<option value="0"><?php _e('All Schools', 'csd-manager'); ?></option>
				<?php
				$wpdb = csd_db_connection();
				$schools = $wpdb->get_results("SELECT id, school_name FROM " . csd_table('schools') . " ORDER BY school_name");
				
				foreach ($schools as $school) {
					echo '<option value="' . esc_attr($school->id) . '" ' . selected($school_id, $school->id, false) . '>' . esc_html($school->school_name) . '</option>';
				}
				?>
			</select>
		</p>
		
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('department')); ?>"><?php _e('Filter by department:', 'csd-manager'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('department')); ?>" name="<?php echo esc_attr($this->get_field_name('department')); ?>">
				<option value=""><?php _e('All Departments', 'csd-manager'); ?></option>
				<?php
				$departments = csd_get_sport_departments();
				
				foreach ($departments as $dept) {
					echo '<option value="' . esc_attr($dept) . '" ' . selected($department, $dept, false) . '>' . esc_html($dept) . '</option>';
				}
				?>
			</select>
		</p>
		<?php
	}
	
	/**
	 * Sanitize widget form values as they are saved
	 *
	 * @param array $new_instance Values just sent to be saved
	 * @param array $old_instance Previously saved values from database
	 * @return array Updated safe values to be saved
	 */
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
		$instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 5;
		$instance['sort_by'] = (!empty($new_instance['sort_by'])) ? sanitize_text_field($new_instance['sort_by']) : 'full_name';
		$instance['sort_order'] = (!empty($new_instance['sort_order'])) ? sanitize_text_field($new_instance['sort_order']) : 'ASC';
		$instance['school_id'] = (!empty($new_instance['school_id'])) ? intval($new_instance['school_id']) : 0;
		$instance['department'] = (!empty($new_instance['department'])) ? sanitize_text_field($new_instance['department']) : '';
		
		return $instance;
	}
	
	/**
	 * Build shortcode attributes
	 *
	 * @param array $atts Attributes
	 * @return string Shortcode attributes
	 */
	private function build_shortcode_atts($atts) {
		$shortcode_atts = array();
		
		foreach ($atts as $key => $value) {
			if (!empty($value) || $value === 0) {
				$shortcode_atts[] = $key . '="' . esc_attr($value) . '"';
			}
		}
		
		return implode(' ', $shortcode_atts);
	}
}

/**
 * Add the CSS for the plugin
 */
function csd_add_styles() {
	?>
	<style type="text/css">
		/* Admin styles */
		.csd-dashboard-wrapper {
			display: flex;
			flex-wrap: wrap;
			margin: 0 -10px;
		}
		.csd-dashboard-column {
			flex: 1 1 calc(50% - 20px);
			min-width: 300px;
			margin: 0 10px 20px;
		}
		.csd-dashboard-box {
			background: #fff;
			border: 1px solid #ccd0d4;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
			margin-bottom: 20px;
			padding: 10px 20px 20px;
		}
		.csd-stats {
			display: flex;
			justify-content: space-between;
			margin-bottom: 20px;
		}
		.csd-stat-item {
			text-align: center;
			padding: 10px;
			flex: 1;
			border: 1px solid #eee;
			border-radius: 4px;
			margin: 0 5px;
		}
		.csd-stat-number {
			display: block;
			font-size: 24px;
			font-weight: 700;
			margin-bottom: 5px;
			color: #1e88e5;
		}
		.csd-stat-label {
			font-size: 13px;
			color: #555;
		}
		.csd-quick-links {
			display: flex;
			flex-wrap: wrap;
			margin: 0 -5px;
		}
		.csd-quick-links li {
			margin: 0 5px 10px;
		}
		.csd-view-all {
			text-align: right;
			margin-top: 10px;
		}
		.csd-filters {
			margin-bottom: 20px;
			padding: 15px;
			background: #f8f8f8;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.csd-search-box {
			margin-bottom: 10px;
		}
		.csd-search-box input[type="text"] {
			width: 300px;
			max-width: 100%;
		}
		.csd-filter-controls {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
		}
		.csd-filter-controls select {
			margin-right: 10px;
			margin-bottom: 10px;
		}
		.csd-filter-controls button {
			margin-right: 5px;
			margin-bottom: 10px;
		}
		.csd-table-container {
			overflow-x: auto;
		}
		.csd-pagination {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-top: 15px;
		}
		.csd-pagination-links {
			display: flex;
			align-items: center;
		}
		.csd-pagination-links a, .csd-pagination-links span {
			margin: 0 2px;
		}
		.csd-pagination-dots {
			padding: 0 5px;
		}
		.csd-form-section {
			margin-bottom: 30px;
		}
		.csd-form-submit {
			padding: 15px 0;
			margin-top: 20px;
			border-top: 1px solid #ddd;
		}
		.csd-last-updated {
			float: right;
			color: #666;
			font-style: italic;
			line-height: 28px;
		}
		.csd-school-details, .csd-staff-details {
			background: #fff;
			border: 1px solid #ddd;
			padding: 20px;
			margin-bottom: 20px;
		}
		.csd-detail-section {
			margin-bottom: 30px;
		}
		.csd-staff-actions {
			margin-bottom: 15px;
		}
		.sorted-asc:after {
			content: "▲";
			margin-left: 5px;
			font-size: 10px;
		}
		.sorted-desc:after {
			content: "▼";
			margin-left: 5px;
			font-size: 10px;
		}
		.csd-tabs-wrapper {
			background: #fff;
			border: 1px solid #ccd0d4;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
			margin-top: 20px;
		}
		.csd-tabs {
			display: flex;
			border-bottom: 1px solid #ccd0d4;
		}
		.csd-tab {
			padding: 10px 15px;
			text-decoration: none;
			border-right: 1px solid #ccd0d4;
			background: #f5f5f5;
			color: #555;
			font-weight: 600;
		}
		.csd-tab.active {
			background: #fff;
			color: #23282d;
			border-bottom: 1px solid #fff;
			margin-bottom: -1px;
		}
		.csd-tab-content {
			display: none;
			padding: 20px;
		}
		.csd-tab-content.active {
			display: block;
		}
		.csd-import-section, .csd-export-section {
			max-width: 800px;
		}
		.csd-import-options, .csd-export-options {
			margin: 15px 0;
			padding: 15px;
			background: #f8f8f8;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.csd-import-option, .csd-export-option {
			margin-bottom: 10px;
		}
		.csd-file-upload {
			margin: 15px 0;
		}
		.csd-submit-button {
			margin: 15px 0;
		}
		.csd-column-mapping {
			margin-top: 20px;
			padding: 15px;
			background: #f8f8f8;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.csd-mapping-field {
			margin-bottom: 10px;
			display: flex;
			align-items: center;
		}
		.csd-mapping-field label {
			width: 150px;
			font-weight: 600;
			margin-right: 10px;
		}
		.csd-mapping-field select {
			flex: 1;
		}
		.csd-import-templates {
			margin-top: 30px;
			padding-top: 20px;
			border-top: 1px solid #ddd;
		}
		.csd-import-templates .button {
			margin-right: 10px;
		}
		.csd-shortcode-preview-wrapper {
			margin-top: 20px;
			padding: 15px;
			background: #f8f8f8;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.csd-shortcode-preview {
			display: flex;
			align-items: center;
			background: #fff;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.csd-shortcode-preview code {
			flex: 1;
			padding: 5px;
			font-family: monospace;
			background: #f9f9f9;
			color: #444;
		}
		
		/* Frontend styles */
		.csd-schools-container, .csd-staff-container, .csd-single-school {
			margin-bottom: 30px;
		}
		.csd-schools-table, .csd-staff-table, .csd-info-table {
			width: 100%;
			border-collapse: collapse;
			margin: 15px 0;
			border: 1px solid #ddd;
		}
		.csd-schools-table th, .csd-staff-table th, .csd-info-table th {
			background: #f5f5f5;
			padding: 10px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}
		.csd-schools-table td, .csd-staff-table td, .csd-info-table td {
			padding: 10px;
			border-bottom: 1px solid #ddd;
		}
		.csd-schools-table tr:nth-child(even), .csd-staff-table tr:nth-child(even) {
			background-color: #f9f9f9;
		}
		.csd-schools-table .csd-sortable, .csd-staff-table .csd-sortable {
			cursor: pointer;
		}
		.csd-schools-table .sorted-asc:after, .csd-staff-table .sorted-asc:after {
			content: "▲";
			margin-left: 5px;
			font-size: 10px;
		}
		.csd-schools-table .sorted-desc:after, .csd-staff-table .sorted-desc:after {
			content: "▼";
			margin-left: 5px;
			font-size: 10px;
		}
		.csd-info-table th {
			width: 150px;
		}
		.csd-school-header {
			margin-bottom: 20px;
		}
		.csd-school-name {
			margin-bottom: 5px;
		}
		.csd-school-mascot {
			color: #666;
			font-style: italic;
		}
		.csd-school-section {
			margin-bottom: 30px;
		}
		.csd-pagination {
			margin-top: 15px;
		}
		.csd-pagination-counts {
			color: #666;
		}
		.csd-page-number {
			display: inline-block;
			padding: 5px 10px;
			margin: 0 2px;
			border: 1px solid #ddd;
			text-decoration: none;
			color: #333;
		}
		.csd-page-number.current {
			background: #f5f5f5;
			font-weight: bold;
		}
		.csd-filters {
			margin-bottom: 20px;
			padding: 15px;
			background: #f8f8f8;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.csd-search-box {
			margin-bottom: 10px;
		}
		.csd-filter-controls {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
		}
		.csd-filter-field {
			margin-right: 15px;
			margin-bottom: 10px;
		}
		.csd-filter-field label {
			margin-right: 5px;
			font-weight: bold;
		}
		.csd-filter-buttons {
			margin-bottom: 10px;
		}
		.csd-filter-buttons button {
			margin-right: 5px;
		}
	</style>
	<?php
}
add_action('admin_head', 'csd_add_styles');
add_action('wp_head', 'csd_add_styles');