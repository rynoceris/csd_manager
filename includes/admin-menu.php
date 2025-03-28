<?php
/**
 * Admin Menu Setup
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin Menu Class
 */
class CSD_Admin_Menu {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('admin_menu', array($this, 'add_menu_pages'));
	}
	
	/**
	 * Add menu pages
	 */
	public function add_menu_pages() {
		// Determine appropriate capability
		$capability = current_user_can('manage_csd') ? 'manage_csd' : 'manage_options';
		
		// Add main menu
		add_menu_page(
			__('College Sports Directory', 'csd-manager'),
			__('Sports Directory', 'csd-manager'),
			$capability,
			'csd-manager',
			array($this, 'main_page'),
			'dashicons-groups',
			30
		);
		
		// Add schools submenu
		add_submenu_page(
			'csd-manager',
			__('Schools', 'csd-manager'),
			__('Schools', 'csd-manager'),
			$capability,
			'csd-schools',
			array(new CSD_Schools_Manager(), 'render_page')
		);
		
		// Add staff submenu
		add_submenu_page(
			'csd-manager',
			__('Staff', 'csd-manager'),
			__('Staff', 'csd-manager'),
			$capability,
			'csd-staff',
			array(new CSD_Staff_Manager(), 'render_page')
		);
		
		// Add import/export submenu
		add_submenu_page(
			'csd-manager',
			__('Import/Export', 'csd-manager'),
			__('Import/Export', 'csd-manager'),
			$capability,
			'csd-import-export',
			array(new CSD_Import_Export(), 'render_page')
		);
		
		// Add shortcodes submenu
		add_submenu_page(
			'csd-manager',
			__('Shortcodes', 'csd-manager'),
			__('Shortcodes', 'csd-manager'),
			$capability,
			'csd-shortcodes',
			array(new CSD_Shortcodes(), 'render_admin_page')
		);
		
		// Add query builder submenu
		add_submenu_page(
			'csd-manager',
			__('Query Builder', 'csd-manager'),
			__('Query Builder', 'csd-manager'),
			$capability,
			'csd-query-builder',
			array(new CSD_Query_Builder(), 'render_page')
		);
	}
	
	/**
	 * Render main page
	 */
	public function main_page() {
		?>
		<div class="wrap">
			<h1><?php _e('College Sports Directory Manager', 'csd-manager'); ?></h1>
			
			<div class="csd-dashboard-wrapper">
				<div class="csd-dashboard-column">
					<div class="csd-dashboard-box">
						<h2><?php _e('Quick Stats', 'csd-manager'); ?></h2>
						<div class="csd-stats">
							<?php
							$wpdb = csd_db_connection();
							
							// Get counts
							$schools_count = $wpdb->get_var("SELECT COUNT(*) FROM " . csd_table('schools'));
							$staff_count = $wpdb->get_var("SELECT COUNT(*) FROM "  . csd_table('staff'));
							$views_count = $wpdb->get_var("SELECT COUNT(*) FROM " . csd_table('shortcode_views'));
							?>
							
							<div class="csd-stat-item">
								<span class="csd-stat-number"><?php echo $schools_count; ?></span>
								<span class="csd-stat-label"><?php _e('Schools', 'csd-manager'); ?></span>
							</div>
							
							<div class="csd-stat-item">
								<span class="csd-stat-number"><?php echo $staff_count; ?></span>
								<span class="csd-stat-label"><?php _e('Staff Members', 'csd-manager'); ?></span>
							</div>
							
							<div class="csd-stat-item">
								<span class="csd-stat-number"><?php echo $views_count; ?></span>
								<span class="csd-stat-label"><?php _e('Saved Views', 'csd-manager'); ?></span>
							</div>
						</div>
					</div>
					
					<div class="csd-dashboard-box">
						<h2><?php _e('Recent Schools', 'csd-manager'); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php _e('School Name', 'csd-manager'); ?></th>
									<th><?php _e('Division', 'csd-manager'); ?></th>
									<th><?php _e('State', 'csd-manager'); ?></th>
									<th><?php _e('Date Added', 'csd-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$recent_schools = $wpdb->get_results("SELECT * FROM " . csd_table('schools') . " ORDER BY date_created DESC LIMIT 25");
								
								if ($recent_schools) {
									foreach ($recent_schools as $school) {
										?>
										<tr>
											<td>
												<a href="<?php echo admin_url('admin.php?page=csd-schools&action=edit&id=' . $school->id); ?>">
													<?php echo esc_html($school->school_name); ?>
												</a>
											</td>
											<td><?php echo esc_html($school->school_divisions); ?></td>
											<td><?php echo esc_html($school->state); ?></td>
											<td><?php echo date('M j, Y', strtotime($school->date_created)); ?></td>
										</tr>
										<?php
									}
								} else {
									?>
									<tr>
										<td colspan="4"><?php _e('No schools found.', 'csd-manager'); ?></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<p class="csd-view-all">
							<a href="<?php echo admin_url('admin.php?page=csd-schools'); ?>" class="button">
								<?php _e('View All Schools', 'csd-manager'); ?>
							</a>
						</p>
					</div>
				</div>
				
				<div class="csd-dashboard-column">
					<div class="csd-dashboard-box">
						<h2><?php _e('Recent Staff Members', 'csd-manager'); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php _e('Name', 'csd-manager'); ?></th>
									<th><?php _e('Title', 'csd-manager'); ?></th>
									<th><?php _e('Sport/Department', 'csd-manager'); ?></th>
									<th><?php _e('School', 'csd-manager'); ?></th>
									<th><?php _e('Date Added', 'csd-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$recent_staff = $wpdb->get_results("
									SELECT s.*, sch.school_name 
									FROM " . csd_table('staff') . " s
									LEFT JOIN " . csd_table('school_staff') . " ss ON s.id = ss.staff_id
									LEFT JOIN " . csd_table('schools') . " sch ON ss.school_id = sch.id
									ORDER BY s.date_created DESC 
									LIMIT 25
								");
								
								if ($recent_staff) {
									foreach ($recent_staff as $staff) {
										?>
										<tr>
											<td>
												<a href="<?php echo admin_url('admin.php?page=csd-staff&action=edit&id=' . $staff->id); ?>">
													<?php echo esc_html($staff->full_name); ?>
												</a>
											</td>
											<td><?php echo esc_html($staff->title); ?></td>
											<td><?php echo esc_html($staff->sport_department); ?></td>
											<td><?php echo esc_html($staff->school_name); ?></td>
											<td><?php echo date('M j, Y', strtotime($staff->date_created)); ?></td>
										</tr>
										<?php
									}
								} else {
									?>
									<tr>
										<td colspan="4"><?php _e('No staff members found.', 'csd-manager'); ?></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<p class="csd-view-all">
							<a href="<?php echo admin_url('admin.php?page=csd-staff'); ?>" class="button">
								<?php _e('View All Staff', 'csd-manager'); ?>
							</a>
						</p>
					</div>
					
					<div class="csd-dashboard-box">
						<h2><?php _e('Quick Links', 'csd-manager'); ?></h2>
						<ul class="csd-quick-links">
							<li>
								<a href="<?php echo admin_url('admin.php?page=csd-schools&action=add'); ?>" class="button button-primary">
									<?php _e('Add New School', 'csd-manager'); ?>
								</a>
							</li>
							<li>
								<a href="<?php echo admin_url('admin.php?page=csd-staff&action=add'); ?>" class="button button-primary">
									<?php _e('Add New Staff Member', 'csd-manager'); ?>
								</a>
							</li>
							<li>
								<a href="<?php echo admin_url('admin.php?page=csd-import-export'); ?>" class="button">
									<?php _e('Import/Export Data', 'csd-manager'); ?>
								</a>
							</li>
							<li>
								<a href="<?php echo admin_url('admin.php?page=csd-shortcodes&action=add'); ?>" class="button">
									<?php _e('Create New Shortcode View', 'csd-manager'); ?>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
