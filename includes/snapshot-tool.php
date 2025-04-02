<?php
/**
 * Snapshot Fetching Tool for College Sports Directory Manager
 *
 * @package College Sports Directory Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Utility function to load .env file
 */
function csd_load_env_file($file_path) {
	// Check if file exists
	if (!file_exists($file_path)) {
		error_log("CSD Snapshot Tool: .env file not found at $file_path");
		return [];
	}

	// Read the file
	$env_contents = file_get_contents($file_path);
	
	// Parse the file line by line
	$env_vars = [];
	$lines = explode("\n", $env_contents);
	
	foreach ($lines as $line) {
		// Trim whitespace
		$line = trim($line);
		
		// Skip empty lines and comments
		if (empty($line) || strpos($line, '#') === 0) {
			continue;
		}
		
		// Split into key and value
		$parts = explode('=', $line, 2);
		
		if (count($parts) === 2) {
			$key = trim($parts[0]);
			$value = trim($parts[1]);
			
			// Remove quotes if present
			$value = trim($value, '"\'');
			
			$env_vars[$key] = $value;
		}
	}
	
	return $env_vars;
}

/**
 * Snapshot Tool Class
 */
class CSD_Snapshot_Tool {
	/**
	 * API key for changedetection.io
	 */
	private $api_key;
	
	/**
	 * Queue directory path
	 */
	private $queue_dir;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// Load configuration from .env file
		$env_path = '/home/collegesportsdir/config/.env';
		$env_vars = csd_load_env_file($env_path);
		
		// Set API key
		$this->api_key = $env_vars['CHANGEDETECTION_API_KEY'] ?? '';
		
		// Set queue directory from .env, with fallback to WordPress uploads
		$this->queue_dir = $env_vars['QUEUE_DIR'] ?? wp_upload_dir()['basedir'] . '/csd-snapshots';
		
		// Validate API key
		if (empty($this->api_key)) {
			error_log('CSD Snapshot Tool: No API key found in .env file');
		}
		
		// Validate queue directory
		if (!is_dir($this->queue_dir)) {
			// Try to create the directory if it doesn't exist
			if (!wp_mkdir_p($this->queue_dir)) {
				error_log('CSD Snapshot Tool: Failed to create queue directory at ' . $this->queue_dir);
			}
		}
		
		// Ensure directory is writable
		if (!is_writable($this->queue_dir)) {
			error_log('CSD Snapshot Tool: Queue directory is not writable: ' . $this->queue_dir);
		}
	}
	
	/**
	 * Render snapshot tool page
	 */
	public function render_page() {
		// Localize script with AJAX URL and nonce
		wp_localize_script('jquery', 'csd_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('csd-ajax-nonce')
		));

		// Check if API key is set
		if (empty($this->api_key)) {
			?>
			<div class="wrap">
				<div class="notice notice-error">
					<p><?php _e('Error: No changedetection.io API key found. Please check your .env configuration.', 'csd-manager'); ?></p>
				</div>
			</div>
			<?php
			return;
		}
		
		// Check queue directory status
		$queue_status = is_dir($this->queue_dir) && is_writable($this->queue_dir) 
			? 'valid' 
			: 'invalid';
		
		?>
		<div class="wrap">
			<h1><?php _e('Snapshot Fetching Tool', 'csd-manager'); ?></h1>
			
			<?php if ($queue_status === 'invalid'): ?>
			<div class="notice notice-warning">
				<p><?php 
					printf(
						__('Warning: Queue directory is not valid or writable. Current path: %s', 'csd-manager'), 
						esc_html($this->queue_dir)
					); 
				?></p>
			</div>
			<?php endif; ?>
			
			<div class="csd-snapshot-tool">
				<form id="csd-snapshot-form" method="post">
					<?php wp_nonce_field('csd_fetch_snapshot', 'csd_snapshot_nonce'); ?>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="uuid"><?php _e('UUID', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="uuid" name="uuid" class="regular-text" required 
									placeholder="<?php _e('Enter UUID from changedetection.io', 'csd-manager'); ?>">
								<p class="description"><?php _e('The unique identifier for the website being monitored.', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="school_name"><?php _e('School Name', 'csd-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="school_name" name="school_name" class="regular-text" required 
									placeholder="<?php _e('Enter the school name', 'csd-manager'); ?>">
								<p class="description"><?php _e('The name of the school for this snapshot.', 'csd-manager'); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row"><?php _e('Queue Directory', 'csd-manager'); ?></th>
							<td>
								<code><?php echo esc_html($this->queue_dir); ?></code>
								<p class="description">
									<?php 
									echo $queue_status === 'valid' 
										? __('<i style="color:green" class="fas fa-circle-check"></i> Queue directory is valid and writable.', 'csd-manager')
										: __('<i style="color:red;" class="fas fa-circle-xmark"></i> Queue directory is not valid or writable.', 'csd-manager'); 
									?>
								</p>
							</td>
						</tr>
					</table>
					
					<div class="submit">
						<button type="submit" class="button button-primary" <?php 
							echo $queue_status === 'invalid' ? 'disabled' : ''; 
						?>>
							<?php _e('Fetch and Queue Snapshot', 'csd-manager'); ?>
						</button>
					</div>
				</form>
				
				<div id="csd-snapshot-results" class="notice" style="display:none;"></div>
				
				<div id="csd-snapshot-preview" class="notice" style="display:none; margin-top: 20px;"></div>
			</div>
		</div>
		
		<script type="text/javascript">
		(function($) {
			// Fallback for csd_ajax object if not defined
			if (typeof csd_ajax === 'undefined') {
				window.csd_ajax = {
					ajax_url: ajaxurl,
					nonce: '<?php echo wp_create_nonce('csd-ajax-nonce'); ?>'
				};
			}

			jQuery(document).ready(function($) {
				$('#csd-snapshot-form').on('submit', function(e) {
					e.preventDefault();
					
					var formData = $(this).serialize();
					
					$.ajax({
						url: csd_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'csd_fetch_snapshot',
							form_data: formData,
							nonce: csd_ajax.nonce
						},
						beforeSend: function() {
							$('#csd-snapshot-results').hide().removeClass('notice-success notice-error');
							$('#csd-snapshot-preview').hide().empty();
							$('button[type="submit"]').prop('disabled', true).text('<?php _e('Fetching...', 'csd-manager'); ?>');
						},
						success: function(response) {
							$('button[type="submit"]').prop('disabled', false).text('<?php _e('Fetch and Queue Snapshot', 'csd-manager'); ?>');
							
							var resultsDiv = $('#csd-snapshot-results');
							var previewDiv = $('#csd-snapshot-preview');
							
							if (response.success) {
								resultsDiv.addClass('notice-success').html(
									'<p><strong>Success!</strong> ' + response.data.message + '</p>' +
									'<p>Queue ID: ' + response.data.queue_id + '</p>' +
									'<p>Snapshot Length: ' + response.data.snapshot_length + ' bytes</p>'
								).show();
								
								// Show snapshot preview
								previewDiv.html(
									'<h3>Snapshot Preview</h3>' +
									'<pre style="max-height: 300px; overflow: auto; background: #f4f4f4; padding: 10px; border: 1px solid #ddd;">' + 
									$('<div>').text(response.data.snapshot_preview).html() + 
									'</pre>'
								).show();
							} else {
								resultsDiv.addClass('notice-error').html(
									'<p><strong>Error:</strong> ' + response.data.message + '</p>'
								).show();
							}
						},
						error: function() {
							$('button[type="submit"]').prop('disabled', false).text('<?php _e('Fetch and Queue Snapshot', 'csd-manager'); ?>');
							$('#csd-snapshot-results').addClass('notice-error')
								.html('<p><strong>Error:</strong> Failed to communicate with server.</p>').show();
						}
					});
				});
			});
		})(jQuery);
		</script>
		<?php
	}
	
	/**
	 * AJAX handler for fetching snapshot
	 */
	public function ajax_fetch_snapshot() {
		// Verify nonce
		check_ajax_referer('csd-ajax-nonce', 'nonce');
		
		// Verify WordPress permissions
		if (!current_user_can('manage_csd')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'csd-manager')));
		}
		
		// Validate queue directory
		if (!is_dir($this->queue_dir) || !is_writable($this->queue_dir)) {
			wp_send_json_error(array(
				'message' => sprintf(
					__('Queue directory is invalid or not writable: %s', 'csd-manager'), 
					$this->queue_dir
				)
			));
		}
		
		// Verify form data
		parse_str($_POST['form_data'], $form_data);
		
		// Validate inputs
		if (empty($form_data['uuid']) || empty($form_data['school_name'])) {
			wp_send_json_error(array('message' => __('UUID and School Name are required.', 'csd-manager')));
		}
		
		// Fetch snapshot
		$snapshot = $this->fetch_snapshot($form_data['uuid']);
		
		if ($snapshot === false) {
			wp_send_json_error(array('message' => __('Failed to fetch snapshot.', 'csd-manager')));
		}
		
		// Create preview (first 500 characters)
		$snapshot_preview = substr($snapshot, 0, 500);
		
		// Create queue item
		$queueData = [
			'action' => 'changedetection_notify',
			'school_name' => sanitize_text_field($form_data['school_name']),
			'UUID' => sanitize_text_field($form_data['uuid']),
			'current_snapshot' => $snapshot
		];
		
		// Write to queue file
		$filename = $this->queue_dir . '/' . uniqid('webhook_', true) . '.json';
		
		if (file_put_contents($filename, json_encode($queueData, JSON_UNESCAPED_SLASHES)) === false) {
			wp_send_json_error(array('message' => __('Failed to write queue file.', 'csd-manager')));
		}
		
		// Log success
		error_log('Snapshot queued for ' . $form_data['school_name'] . ' (UUID: ' . $form_data['uuid'] . ')');
		
		wp_send_json_success(array(
			'message' => __('Snapshot successfully queued.', 'csd-manager'),
			'queue_id' => basename($filename),
			'snapshot_length' => strlen($snapshot),
			'snapshot_preview' => $snapshot_preview
		));
	}
	
	/**
	 * Fetch snapshot from changedetection.io
	 * 
	 * @param string $uuid UUID of the site
	 * @return string|false Snapshot content or false on failure
	 */
	private function fetch_snapshot($uuid) {
		// If no API key is set, immediately return false
		if (empty($this->api_key)) {
			error_log('CSD Snapshot Tool: Cannot fetch snapshot - No API key set');
			return false;
		}
		
		$url = "https://lemonade.changedetection.io/amicably-affordable/api/v1/watch/$uuid/history/latest?";
		
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'x-api-key: ' . $this->api_key
			]
		]);
		
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if (curl_errno($ch)) {
			error_log('CURL error: ' . curl_error($ch));
			curl_close($ch);
			return false;
		}
		
		curl_close($ch);
		
		if ($httpCode !== 200) {
			error_log('API returned non-200 status code: ' . $httpCode);
			return false;
		}
		
		// Try to parse JSON and extract content
		$data = json_decode($response, true);
		
		if ($data && is_array($data)) {
			// Look for content in common field names
			foreach(['content', 'snapshot', 'html', 'body', 'text', 'data'] as $field) {
				if (isset($data[$field])) {
					return $data[$field];
				}
			}
		}
		
		// If we can't parse or find content field, return the raw response
		return $response;
	}
}
