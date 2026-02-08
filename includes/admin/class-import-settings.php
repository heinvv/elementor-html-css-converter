<?php
/**
 * Import Settings Admin Page
 *
 * Admin settings page for configuring GitHub import integration.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Settings {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_admin_menu() {
		add_options_page(
			'Import Settings',
			'Import Settings',
			'manage_options',
			'ehcc-import-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings() {
		register_setting( 'ehcc_import_settings', 'ehcc_github_repo', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		] );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['submit'] ) && check_admin_referer( 'ehcc_import_settings' ) ) {
			update_option( 'ehcc_github_repo', sanitize_text_field( $_POST['ehcc_github_repo'] ?? '' ) );
			echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
		}

		$github_repo = get_option( 'ehcc_github_repo', '' );
		$example_webhook_url = rest_url( 'html-css-converter/v1/import-results' );

		?>
		<div class="wrap">
			<h1>Import Settings</h1>
			<form method="post" action="">
				<?php wp_nonce_field( 'ehcc_import_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="ehcc_github_repo">GitHub Repository</label>
						</th>
						<td>
							<input type="text" id="ehcc_github_repo" name="ehcc_github_repo" value="<?php echo esc_attr( $github_repo ); ?>" class="regular-text" placeholder="owner/repo" />
							<p class="description">Repository in format <code>owner/repo</code> (e.g., <code>heinvv-abangani/elementor-playwright-scraper</code>)</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Webhook URL</th>
						<td>
							<code><?php echo esc_html( $example_webhook_url ); ?></code>
							<p class="description">This is the endpoint where GitHub Actions will send results. The webhook URL is automatically constructed from this WordPress site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Security Note</th>
						<td>
							<p class="description">
								<strong>Current Status:</strong> Endpoints are publicly accessible (no authentication required)<br>
								<strong>GitHub Integration:</strong> Will be handled later<br>
								<strong>Secrets:</strong> Will be handled later (not stored in WordPress)
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
