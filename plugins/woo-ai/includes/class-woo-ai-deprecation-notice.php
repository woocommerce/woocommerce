<?php
/**
 * Class to handle deprecation notice tracking
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Woo_AI_Deprecation_Notice class
 * Shows a notice to users that the WooCommerce AI plugin is being deprecated, if they interact with the plugin somehow.
 */
class Woo_AI_Deprecation_Notice {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_footer', array( $this, 'output_deprecation_notice_data' ) );

		// Register AJAX actions
		add_action( 'wp_ajax_woo_ai_dismiss_deprecation', array( $this, 'dismiss_notice' ) );

		// Check for reset query parameter for testing purposes
		// TODO: Remove this before merging
		if ( isset( $_GET['reset_woo_ai_notice'] ) && current_user_can( 'manage_options' ) ) {
			delete_option( 'woo_ai_deprecation_dismissed' );
			$current_url = remove_query_arg( 'reset_woo_ai_notice', $_SERVER['REQUEST_URI'] );
			wp_safe_redirect( $current_url );
			exit;
		}
	}

	/**
	 * Output deprecation notice data directly to the page
	 * This is used to pass the nonce and dismissed state to the frontend.
	 */
	public function output_deprecation_notice_data() {
		if ( ! $this->should_output_data() ) {
			return;
		}

		$data = array(
			'nonce' => wp_create_nonce( 'woo_ai_tracker' ),
			'dismissed' => (bool) get_option( 'woo_ai_deprecation_dismissed', false ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		);

		?>
		<script type="text/javascript">
			window.wooAITracker = <?php echo wp_json_encode( $data ); ?>;
		</script>
		<?php
	}

	/**
	 * Check if deprecation notice data should be output, only on the product screen
	 *
	 * @return bool
	 */
	private function should_output_data() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		return in_array( $screen->id, array( 'product', 'edit-product' ), true );
	}

	/**
	 * Ajax handler for dismissing the notice
	 * This means the user has dismissed the notice, so we should not show it to them again.
	 */
	public function dismiss_notice() {
		// Get the nonce from POST data
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		// Verify the nonce
		if ( ! wp_verify_nonce( $nonce, 'woo_ai_tracker' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			return;
		}

		update_option( 'woo_ai_deprecation_dismissed', true );
		wp_send_json_success();
	}
}
