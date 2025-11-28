<?php
/**
 * PayPal Notices Class
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wc-gateway-paypal-helper.php';

/**
 * Class WC_Gateway_Paypal_Notices.
 */
class WC_Gateway_Paypal_Notices {
	/**
	 * The name of the notice for PayPal migration.
	 *
	 * @var string
	 */
	private const PAYPAL_MIGRATION_NOTICE = 'paypal_migration_completed';

	/**
	 * The name of the notice for PayPal account restriction.
	 *
	 * @var string
	 */
	private const PAYPAL_ACCOUNT_RESTRICTED_NOTICE = 'paypal_account_restricted';

	/**
	 * The PayPal gateway instance.
	 *
	 * @var WC_Gateway_Paypal
	 */
	private $gateway;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->gateway = WC_Gateway_Paypal::get_instance();
		if ( ! $this->gateway ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'add_paypal_notices' ) );

		// Use admin_head to inject notice on payments settings page.
		// This bypasses the suppress_admin_notices() function which removes all admin_notices hooks on the payments page.
		// This is a workaround to avoid the notice being suppressed by the suppress_admin_notices() function.
		add_action( 'admin_head', array( $this, 'add_paypal_notices_on_payments_settings_page' ) );

		// Listen for PayPal order responses to manage account restriction notices.
		add_action( 'woocommerce_paypal_standard_order_created_response', array( $this, 'handle_paypal_response' ), 10, 3 );
	}

	/**
	 * Add PayPal Standard notices.
	 *
	 * @return void
	 */
	public function add_paypal_notices() {
		// Show only to users who can manage the site.
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip if the gateway is not available or the merchant has not been onboarded.
		if ( ! WC_Gateway_Paypal_Helper::is_paypal_gateway_available() || ! $this->gateway->should_use_orders_v2() ) {
			return;
		}

		$this->add_paypal_migration_notice();
		$this->add_paypal_account_restricted_notice();
	}

	/**
	 * Add PayPal notices on the payments settings page.
	 *
	 * @return void
	 */
	public function add_paypal_notices_on_payments_settings_page() {
		global $current_tab, $current_section;
		
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		
		$is_payments_settings_page = 'woocommerce_page_wc-settings' === $screen->id && 'checkout' === $current_tab && empty( $current_section );

		// Only add the notice from this callback on the payments settings page.
		if ( ! $is_payments_settings_page ) {
			return;
		}

		$this->add_paypal_notices();
	}

	/**
	 * Add notice warning about the migration to PayPal Payments.
	 *
	 * @return void
	 */
	private function add_paypal_migration_notice() {
		// Skip if the notice has been dismissed.
		if ( $this->is_notice_dismissed( self::PAYPAL_MIGRATION_NOTICE ) ) {
			return;
		}

		$doc_url     = 'https://woocommerce.com/document/woocommerce-paypal-payments/paypal-payments-upgrade-guide/';
		$dismiss_url = $this->get_dismiss_url( self::PAYPAL_MIGRATION_NOTICE );
		$message     = sprintf(
			/* translators: 1: opening <a> tag, 2: closing </a> tag */
			esc_html__( 'WooCommerce has upgraded your PayPal integration from PayPal Standard to PayPal Payments (PPCP), for a more reliable and modern checkout experience. If you do not prefer the upgraded integration in WooCommerce, we recommend switching to %1$sPayPal Payments%2$s extension.', 'woocommerce' ),
			'<a href="' . esc_url( $doc_url ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>',
		);

		$notice_html = '<div class="notice notice-warning is-dismissible">'
			. '<a class="woocommerce-message-close notice-dismiss" style="text-decoration: none;" href="' . esc_url( $dismiss_url ) . '"></a>'
			. '<p>' . $message . '</p>'
			. '</div>';

		echo wp_kses_post( $notice_html );
	}

	/**
	 * Add notice warning about PayPal account restriction.
	 *
	 * @return void
	 */
	private function add_paypal_account_restricted_notice() {
		// Skip if there's no account restriction flag.
		if ( ! $this->has_account_restriction_flag() ) {
			return;
		}

		// Skip if the notice has been dismissed.
		if ( $this->is_notice_dismissed( self::PAYPAL_ACCOUNT_RESTRICTED_NOTICE ) ) {
			return;
		}

		$support_url = 'https://www.paypal.com/smarthelp/contact-us';
		$dismiss_url = $this->get_dismiss_url( self::PAYPAL_ACCOUNT_RESTRICTED_NOTICE );
		$message     = sprintf(
			/* translators: 1: opening <a> tag, 2: closing </a> tag */
			esc_html__( 'Your PayPal account has been restricted by PayPal. This may prevent customers from completing payments. Please %1$scontact PayPal support%2$s to resolve this issue and restore full functionality to your account.', 'woocommerce' ),
			'<a href="' . esc_url( $support_url ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>',
		);

		$notice_html = '<div class="notice notice-error is-dismissible">'
			. '<a class="woocommerce-message-close notice-dismiss" style="text-decoration: none;" href="' . esc_url( $dismiss_url ) . '"></a>'
			. '<p><strong>' . esc_html__( 'PayPal Account Restricted', 'woocommerce' ) . '</strong></p>'
			. '<p>' . $message . '</p>'
			. '</div>';

		echo wp_kses_post( $notice_html );
	}

	/**
	 * Get the dismiss URL for a notice.
	 *
	 * @param string $notice_name The name of the notice.
	 * @return string
	 */
	private function get_dismiss_url( string $notice_name ): string {
		return wp_nonce_url(
			add_query_arg( 'wc-hide-notice', $notice_name ),
			'woocommerce_hide_notices_nonce',
			'_wc_notice_nonce'
		);
	}

	/**
	 * Check if the notice has been dismissed.
	 *
	 * @param string $notice_name The name of the notice.
	 * @return bool
	 */
	private function is_notice_dismissed( string $notice_name ): bool {
		return (bool) get_user_meta( get_current_user_id(), 'dismissed_' . $notice_name . '_notice', true );
	}

	/**
	 * Check if there's a flag indicating PayPal account restriction.
	 *
	 * @return bool
	 */
	private function has_account_restriction_flag(): bool {
		return 'yes' === $this->gateway->get_option( 'paypal_account_restricted', 'no' );
	}

	/**
	 * Set the flag indicating PayPal account restriction.
	 *
	 * @return void
	 */
	public static function set_account_restriction_flag(): void {
		$gateway = WC_Gateway_Paypal::get_instance();
		if ( $gateway && 'no' === $gateway->get_option( 'paypal_account_restricted', 'no' ) ) {
			$gateway->update_option( 'paypal_account_restricted', 'yes' );
		}
	}

	/**
	 * Clear the flag indicating PayPal account restriction.
	 *
	 * @return void
	 */
	public static function clear_account_restriction_flag(): void {
		$gateway = WC_Gateway_Paypal::get_instance();
		if ( $gateway && 'yes' === $gateway->get_option( 'paypal_account_restricted', 'no' ) ) {
			$gateway->update_option( 'paypal_account_restricted', 'no' );
		}
	}

	/**
	 * Handle PayPal order response to manage account restriction notices.
	 *
	 * This method is called via the 'woocommerce_paypal_standard_order_created_response' hook
	 * and manages the account restriction flag based on PayPal API responses.
	 *
	 * Extensions can disable this feature using the filter:
	 * add_filter( 'woocommerce_paypal_account_restriction_notices_enabled', '__return_false' );
	 *
	 * @param int        $http_code     The HTTP status code from the PayPal API response.
	 * @param array|null $response_data The decoded response data from the PayPal API, or null if decoding failed.
	 * @param WC_Order   $order         The WooCommerce order object.
	 * @return void
	 */
	public function handle_paypal_response( int $http_code, $response_data, $order ): void {
		/**
		 * Filters whether account restriction notices should be enabled.
		 *
		 * This filter allows extensions to opt out of the account restriction notice functionality.
		 *
		 * @since 10.4.0
		 *
		 * @param bool $enabled Whether account restriction notices are enabled. Default true.
		 */
		if ( ! apply_filters( 'woocommerce_paypal_account_restriction_notices_enabled', true ) ) {
			return;
		}

		// Clear the restriction flag on successful responses.
		if ( in_array( $http_code, array( 200, 201 ), true ) ) {
			self::clear_account_restriction_flag();
			return;
		}

		// Set the restriction flag for account-related errors.
		if ( 422 === $http_code && is_array( $response_data ) ) {
			$issue = isset( $response_data['details'][0]['issue'] ) ? $response_data['details'][0]['issue'] : null;
			if ( in_array( $issue, array( 'PAYEE_ACCOUNT_LOCKED_OR_CLOSED', 'PAYEE_ACCOUNT_RESTRICTED' ), true ) ) {
				self::set_account_restriction_flag();
			}
		}
	}
}
