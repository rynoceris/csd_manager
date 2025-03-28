<?php
/**
 * Database connection setup
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get direct database tables names
 * This removes the WordPress prefix and uses the actual table names
 *
 * @return array Array of table names
 */
function csd_get_table_names() {
	return array(
		'schools' => 'csd_schools',
		'staff' => 'csd_staff',
		'school_staff' => 'csd_school_staff',
		'shortcode_views' => 'csd_shortcode_views', // This table will be created by the plugin
		'saved_queries' => 'csd_saved_queries'
	);
}

/**
 * Helper function to get the table name
 * 
 * @param string $table Table key
 * @return string Full table name
 */
function csd_table($table) {
	$tables = csd_get_table_names();
	return isset($tables[$table]) ? $tables[$table] : '';
}

/**
 * Get database connection
 * This ensures we're using the correct database
 */
function csd_db_connection() {
	global $wpdb;
	
	// The database details from your initial description
	$db_name = 'collegesportsdir_live';
	$db_user = 'collegesportsdir_live';
	$db_password = 'kKn^8fsZnOoH';
	
	// If we're already connected to the right database, use the global $wpdb
	if ($wpdb->dbname === $db_name) {
		return $wpdb;
	}
	
	// Otherwise, create a new connection
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$wpdb_custom = new wpdb($db_user, $db_password, $db_name, $wpdb->dbhost);
	
	return $wpdb_custom;
}

/**
 * Map column names to match your actual database schema
 */
function csd_column_map() {
	return array(
		'date_recorded' => 'date_created',  // Map expected column to your actual column
		// 'date_updated' remains 'date_updated' so no mapping needed
	);
}

/**
 * Get the mapped column name
 */
function csd_column($expected_name) {
	$map = csd_column_map();
	return isset($map[$expected_name]) ? $map[$expected_name] : $expected_name;
}
