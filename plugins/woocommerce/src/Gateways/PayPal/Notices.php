<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

use WC_Order;
use Automattic\WooCommerce\Gateways\PayPal\Helper as PayPalHelper;

defined( 'ABSPATH' ) || exit;

/**
 * PayPal Notices Class
 *
 * Handles admin notices for PayPal gateway including migration notices,
 * account restriction warnings, and currency support notifications.
 *
 * @since 10.5.0
 */
class Notices {
	/**
	 * The name of the notice for PayPal migration.
	 *
	 * @since 10.5.0
	 * @var string
	 */
	private const PAYPAL_MIGRATION_NOTICE = 'paypal_migration_completed';

	/**
	 * The name of the notice for PayPal account restriction.
	 *
	 * @since 10.5.0
	 * @var string
	 */
	private const PAYPAL_ACCOUNT_RESTRICTED_NOTICE = 'paypal_account_restricted';

	/**
	 * The name of the notice for PayPal unsupported currency.
	 *
	 * @since 10.5.0
	 * @var string
	 */
	private const PAYPAL_UNSUPPORTED_CURRENCY_NOTICE = 'paypal_unsupported_currency';

	/**
	 * PayPal account restriction issue codes from PayPal API.
	 *
	 * @since 10.5.0
	 * @var array
	 */
	protected const PAYPAL_ACCOUNT_RESTRICTION_ISSUES = array(
		Constants::PAYPAL_ISSUE_PAYEE_ACCOUNT_LOCKED_OR_CLOSED,
		Constants::PAYPAL_ISSUE_PAYEE_ACCOUNT_RESTRICTED,
	);

	/**
	 * The PayPal gateway instance.
	 *
	 * @var \WC_Gateway_Paypal
	 */
	private $gateway;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->gateway = \WC_Gateway_Paypal::get_instance();
		if ( ! $this->gateway ) {
			return;
		}

		// Only register admin notice hooks in the admin area.
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'add_paypal_notices' ) );

			// Use admin_head to inject notice on payments settings page.
			// This bypasses the suppress_admin_notices() function which removes all admin_notices hooks on the payments page.
			// This is a workaround to avoid the notice being suppressed by the suppress_admin_notices() function.
			add_action( 'admin_head', array( $this, 'add_paypal_notices_on_payments_settings_page' ) );
		}
	}

	/**
	 * Add PayPal Standard notices.
	 *
	 * @since 10.5.0
	 * @return void
	 */
	public function add_paypal_notices(): void {
		// Show only to users who can manage the site.
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip if the gateway is not available or the merchant has not been onboarded.
		if ( ! PayPalHelper::is_paypal_gateway_available() || ! $this->gateway->should_use_orders_v2() ) {
			return;
		}

		$this->add_paypal_migration_notice();
		$this->add_paypal_account_restricted_notice();
		$this->add_paypal_unsupported_currency_notice();
	}

	/**
	 * Add PayPal notices on the payments settings page.
	 *
	 * @since 10.5.0
	 * @return void
	 */
	public function add_paypal_notices_on_payments_settings_page(): void {
		global $current_tab, $current_section;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$is_payments_settings_page = 'woocommerce_page_wc-settings' === $screen_id && 'checkout' === $current_tab && empty( $current_section );

		// Only add the notice from this callback on the payments settings page.
		if ( ! $is_payments_settings_page ) {
			return;
		}

		$this->add_paypal_notices();
	}

	/**
	 * Add notice warning about the migration to PayPal Payments.
	 *
	 * @since 10.5.0
	 * @return void
	 */
	public function add_paypal_migration_notice(): void {
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
			. '<a class="woocommerce-message-close notice-dismiss" style="text-decoration: none;" href="' . esc_url( $dismiss_url ) . '" aria-label="' . esc_attr__( 'Dismiss this notice', 'woocommerce' ) . '"></a>'
			. '<p>' . $message . '</p>'
			. '</div>';

		echo wp_kses_post( $notice_html );
	}

	/**
	 * Add notice warning about PayPal account restriction.
	 *
	 * @since 10.5.0
	 * @return void
	 */
	private function add_paypal_account_restricted_notice(): void {
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
			. '<a class="woocommerce-message-close notice-dismiss" style="text-decoration: none;" href="' . esc_url( $dismiss_url ) . '" aria-label="' . esc_attr__( 'Dismiss this notice', 'woocommerce' ) . '"></a>'
			. '<p><strong>' . esc_html__( 'PayPal Account Restricted', 'woocommerce' ) . '</strong></p>'
			. '<p>' . $message . '</p>'
			. '</div>';

		echo wp_kses_post( $notice_html );
	}

	/**
	 * Add notice warning when PayPal does not support the store's currency.
	 *
	 * @since 10.5.0
	 * @return void
	 */
	private function add_paypal_unsupported_currency_notice(): void {
		$currency = get_woocommerce_currency();

		// Skip if the currency is supported by PayPal.
		if ( $this->gateway->is_valid_for_use() ) {
			return;
		}

		// Skip if the notice has been dismissed.
		if ( $this->is_notice_dismissed( self::PAYPAL_UNSUPPORTED_CURRENCY_NOTICE ) ) {
			return;
		}

		$dismiss_url = $this->get_dismiss_url( self::PAYPAL_UNSUPPORTED_CURRENCY_NOTICE );
		$message     = sprintf(
			/* translators: %s: Currency code */
			esc_html__( 'PayPal Standard does not support your store currency (%s).', 'woocommerce' ),
			$currency
		);

		$notice_html = '<div class="notice notice-error is-dismissible">'
			. '<a class="woocommerce-message-close notice-dismiss" style="text-decoration: none;" href="' . esc_url( $dismiss_url ) . '" aria-label="' . esc_attr__( 'Dismiss this notice', 'woocommerce' ) . '"></a>'
			. '<p>' . $message . '</p>'
			. '</div>';

		echo wp_kses_post( $notice_html );
	}

	/**
	 * Get the dismiss URL for a notice.
	 *
	 * @since 10.5.0
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
	 * User meta keys for notice dismissals:
	 * - dismissed_paypal_migration_completed_notice
	 * - dismissed_paypal_account_restricted_notice
	 * - dismissed_paypal_unsupported_currency_notice
	 *
	 * The meta keys are set by WC_Admin_Notices when the notice is dismissed by the user.
	 *
	 * @since 10.5.0
	 * @param string $notice_name The name of the notice.
	 * @return bool
	 */
	private function is_notice_dismissed( string $notice_name ): bool {
		return (bool) get_user_meta( get_current_user_id(), 'dismissed_' . $notice_name . '_notice', true );
	}

	/**
	 * Check if there's a flag indicating PayPal account restriction.
	 *
	 * @since 10.5.0
	 * @return bool
	 */
	private function has_account_restriction_flag(): bool {
		return 'yes' === get_option( 'woocommerce_paypal_account_restricted_status', 'no' );
	}

	/**
	 * Set the flag indicating PayPal account restriction.
	 *
	 * @since 10.5.0
	 * @return void
	 */
	public static function set_account_restriction_flag(): void {
		if ( 'no' === get_option( 'woocommerce_paypal_account_restricted_status', 'no' ) ) {
			update_option( 'woocommerce_paypal_account_restricted_status', 'yes', false );
		}
	}

	/**
	 * Clear the flag indicating PayPal account restriction.
	 *
	 * @since 10.5.0
	 * @return void
	 */
	public static function clear_account_restriction_flag(): void {
		if ( 'yes' === get_option( 'woocommerce_paypal_account_restricted_status', 'no' ) ) {
			update_option( 'woocommerce_paypal_account_restricted_status', 'no' );
		}
	}

	/**
	 * Handle PayPal order response to manage account restriction notices.
	 *
	 * @since 10.5.0
	 * @param int|string $http_code     The HTTP status code from the PayPal API response.
	 * @param array      $response_data The decoded response data from the PayPal API.
	 * @param WC_Order   $order         The WooCommerce order object.
	 * @return void
	 */
	public static function manage_account_restriction_flag_for_notice( $http_code, array $response_data, WC_Order $order ): void {
		// Clear the restriction flag on successful responses.
		if ( in_array( (int) $http_code, array( 200, 201 ), true ) ) {
			self::clear_account_restriction_flag();
			return;
		}

		if ( empty( $response_data ) ) {
			return;
		}

		// Set the restriction flag for account-related errors.
		if ( 422 === (int) $http_code ) {
			$issue = isset( $response_data['details'][0]['issue'] ) ? $response_data['details'][0]['issue'] : '';

			if ( in_array( $issue, self::PAYPAL_ACCOUNT_RESTRICTION_ISSUES, true ) ) {
				\WC_Gateway_Paypal::log( 'PayPal account restriction flag set due to issues when handling the order: ' . $order->get_id() );
				self::set_account_restriction_flag();
			}
		}
	}
}
