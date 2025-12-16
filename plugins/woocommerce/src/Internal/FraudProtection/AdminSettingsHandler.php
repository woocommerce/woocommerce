<?php
/**
 * AdminSettingsHandler class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\Admin\API\OnboardingPlugins;

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin settings for fraud protection.
 *
 * @since 10.4.0
 */
class AdminSettingsHandler {

	/**
	 * Constructor. Sets up hooks on instantiation.
	 */
	public function __construct() {
		add_action( 'woocommerce_admin_field_fraud_protection_reset_sessions', array( $this, 'handle_output_reset_button' ), 10, 1 );
		add_action( 'woocommerce_admin_field_jetpack_connection', array( $this, 'handle_output_jetpack_connection_button' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'handle_reset_sessions_action' ), 10, 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'handle_enqueue_admin_scripts' ), 10, 1 );
	}

	/**
	 * Output the reset sessions button field.
	 *
	 * @internal
	 *
	 * @param array $value Field configuration.
	 * @return void
	 */
	public function handle_output_reset_button( $value ): void {
		$field_id          = esc_attr( $value['id'] );
		$field_title       = esc_html( $value['title'] );
		$field_description = esc_html( $value['desc'] );
		$reset_url         = wp_nonce_url(
			add_query_arg( 'wc_fraud_protection_reset_sessions', '1' ),
			'wc_fraud_protection_reset_sessions'
		);

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo $field_id; ?>"><?php echo $field_title; ?></label>
			</th>
			<td class="forminp forminp-button">
				<button
					type="button"
					id="<?php echo $field_id; ?>"
					class="button button-secondary wc-fraud-protection-reset-button"
					data-reset-url="<?php echo esc_url( $reset_url ); ?>"
				>
					<?php esc_html_e( 'Reset All Sessions', 'woocommerce' ); ?>
				</button>
				<p class="description"><?php echo $field_description; ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output the reset sessions button field.
	 *
	 * @internal
	 *
	 * @param array $value Field configuration.
	 * @return void
	 */
	public function handle_output_jetpack_connection_button( $value ): void {
		$field_id          = esc_attr( $value['id'] );
		$field_title       = esc_html( $value['title'] );
		$field_description = esc_html( $value['desc'] );


		$manager = new Manager( 'woocommerce' );
		if ( ! $manager->is_connected() ) {
			$request = new \WP_REST_Request();
			$request->set_param( 'redirect_url', admin_url() );
			$plugin_onboarding = new OnboardingPlugins();
			$result = $plugin_onboarding->get_jetpack_authorization_url( $request );

			// Todo: change parameters
			if ( ! empty( $result['url'] ) ) {
				$result['url'] = add_query_arg(
					array(
						'redirect_uri' => urlencode( admin_url() . 'admin.php/?page=wc-settings&tab=advanced&section=features' ),
						// We use the new WooDNA value.
						'from'         => 'woocommerce-setup-wizard',
						// We inform Calypso that this is a WooPayments onboarding flow.
						'plugin_name'  => 'woocommerce',
						// Use the current user's WP admin color scheme.
						'color_scheme' => $result['color_scheme'],
					),
					$result['url']
				);
				$connection_url = $result['url'];
			}

			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $field_id; ?>"><?php echo $field_title; ?></label>
				</th>
				<td class="forminp forminp-button">
					<button
						type="button"
						id="<?php echo $field_id; ?>"
						class="button button-secondary jetpack_connection_button"
						data-reset-url="<?php echo esc_url( $connection_url ); ?>"
					>
						<?php esc_html_e( 'Connect to Jetpack', 'woocommerce' ); ?>
					</button>
					<p class="description"><?php echo $field_description; ?></p>
				</td>
			</tr>
			<?php
		} else {
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $field_id; ?>"><?php echo $field_title; ?></label>
				</th>
				<td class="forminp forminp-button">
					<p class="description"><?php _e( 'Already connected to Jetpack', 'woocommerce' )  ?></p>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Handle reset sessions action.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_reset_sessions_action(): void {
		// Check if we're on the settings page and the action is set.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wc_fraud_protection_reset_sessions'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wc_fraud_protection_reset_sessions' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'woocommerce' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'woocommerce' ) );
		}

		// Perform the reset.
		$count = SessionClearanceManager::reset_all_sessions();

		// Add admin notice.
		add_settings_error(
			'woocommerce',
			'wc_fraud_protection_reset_success',
			sprintf(
				/* translators: %d: number of sessions reset */
				__( 'Successfully reset %d session clearances.', 'woocommerce' ),
				$count
			),
			'success'
		);

		// Redirect to remove query args.
		$redirect_url = remove_query_arg( array( 'wc_fraud_protection_reset_sessions', '_wpnonce' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Enqueue admin scripts for the reset button.
	 *
	 * @internal
	 *
	 * @param string $hook Page hook.
	 * @return void
	 */
	public function handle_enqueue_admin_scripts( $hook ): void {
		// Only on WooCommerce settings page.
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		// Check if we're on the features section.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		if ( 'features' !== $section ) {
			return;
		}

		$script = "
		jQuery(document).ready(function($) {
			$('.wc-fraud-protection-reset-button').on('click', function(e) {
				e.preventDefault();

				if (!confirm('" . esc_js( __( 'Are you sure you want to reset all session clearances? This will require all users to clear their sessions again.', 'woocommerce' ) ) . "')) {
					return;
				}

				var resetUrl = $(this).data('reset-url');
				window.location.href = resetUrl;
			});
			$('.jetpack_connection_button').on('click', function(e) {
				e.preventDefault();

				var resetUrl = $(this).data('reset-url');
				window.location.href = resetUrl;
			});
		});
		";

		wp_add_inline_script( 'jquery', $script );
	}
}
