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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_notices', array( $this, 'display_deprecation_notice' ) );
		add_action( 'wp_ajax_woo_ai_track_deprecation', array( $this, 'track_deprecation_usage_interaction' ) );
		add_action( 'wp_ajax_woo_ai_dismiss_deprecation', array( $this, 'dismiss_notice' ) );
	}

	/**
	 * Enqueue necessary scripts
	 */
	public function enqueue_scripts() {
		if ( ! $this->should_enqueue_scripts() ) {
			return;
		}

		wp_enqueue_script(
			'woo-ai-deprecation-tracker',
			plugins_url( 'assets/js/admin/track-deprecation.js', WOO_AI_FILE ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( WOO_AI_FILE ) . 'assets/js/admin/track-deprecation.js' ),
			true
		);

		wp_localize_script(
			'woo-ai-deprecation-tracker',
			'wooAITracker',
			array(
				'nonce' => wp_create_nonce( 'woo_ai_tracker' ),
			)
		);
	}

	/**
	 * Check if usage tracking scripts should be enqueued, only on the product screen
	 *
	 * @return bool
	 */
	private function should_enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		return in_array( $screen->id, array( 'product', 'edit-product' ), true );
	}

	/**
	 * Display the deprecation notice: only show if the user has interacted with the plugin, and the notice has not been dismissed.
	 */
	public function display_deprecation_notice() {
		if ( ! get_option( 'woo_ai_show_deprecation_notice', false ) || get_option( 'woo_ai_deprecation_dismissed', false ) ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible woo-ai-deprecation-notice">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %1$s: Opening link tag, %2$s: Closing link tag */
						__( 'Notice: The WooCommerce AI plugin is being deprecated. %1$sLearn more%2$s.', 'woo-ai' ),
						'<a href="https://wordpress.com/contact" target="_blank" rel="noopener noreferrer">',
						'</a>'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Ajax handler for tracking deprecation notice state
	 * This means the user has interacted with the plugin in some way, so we should show the notice to them.
	 */
	public function track_deprecation_usage_interaction() {
		check_ajax_referer( 'woo_ai_tracker', 'nonce' );
		update_option( 'woo_ai_show_deprecation_notice', true );
		wp_send_json_success();
	}

	/**
	 * Ajax handler for dismissing the notice
	 * This means the user has dismissed the notice, so we should not show it to them again.
	 */
	public function dismiss_notice() {
		check_ajax_referer( 'woo_ai_tracker', 'nonce' );
		update_option( 'woo_ai_deprecation_dismissed', true );
		wp_send_json_success();
	}
}
