<?php
/**
 * AdminSettingsHandler class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin settings for fraud protection.
 *
 * @since 10.4.0
 */
class AdminSettingsHandler {

	/**
	 * Jetpack connection manager instance.
	 *
	 * @var JetpackConnectionManager
	 */
	private $connection_manager;

	/**
	 * Constructor. Sets up hooks on instantiation.
	 *
	 * @param JetpackConnectionManager $connection_manager Jetpack connection manager instance.
	 */
	public function __construct( JetpackConnectionManager $connection_manager ) {
		$this->connection_manager = $connection_manager;

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

		// Get connection status from connection manager.
		$connection_status = $this->connection_manager->get_connection_status();

		if ( ! $connection_status['connected'] ) {
			// Get authorization URL for connecting.
			$redirect_url   = admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' );
			$connection_url = $this->connection_manager->get_authorization_url( $redirect_url );

			// If we couldn't get authorization URL, show error message.
			if ( ! $connection_url ) {
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo $field_id; ?>"><?php echo $field_title; ?></label>
					</th>
					<td class="forminp forminp-button">
						<p class="description" style="color: #dc3232;">
							<?php echo esc_html( $connection_status['error'] ); ?>
						</p>
					</td>
				</tr>
				<?php
				return;
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
						data-connection-url="<?php echo esc_url( $connection_url ); ?>"
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
					<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
					<span><?php esc_html_e( 'Connected to Jetpack', 'woocommerce' ); ?></span>
					<p class="description">
						<?php
						printf(
							/* translators: %d: Blog ID */
							esc_html__( 'Site ID: %d', 'woocommerce' ),
							(int) $connection_status['blog_id']
						);
						?>
					</p>
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

				var connectionUrl = $(this).data('connection-url');
				window.location.href = connectionUrl;
			});
		});
		";

		wp_add_inline_script( 'jquery', $script );
	}
}
