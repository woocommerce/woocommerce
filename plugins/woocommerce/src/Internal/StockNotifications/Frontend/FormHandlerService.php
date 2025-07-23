<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Config;

/**
 * Class for handling the form submission.
 */
class FormHandlerService {

	/**
	 * The signup service.
	 *
	 * @var SignupService
	 */
	private SignupService $signup_service;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Initialize the service.
	 *
	 * @internal
	 *
	 * @param SignupService $signup_service The signup service.
	 */
	final public function init( SignupService $signup_service ) {
		$this->signup_service = $signup_service;
		$this->logger         = \wc_get_logger();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_signup' ) );
	}

	/**
	 * Handle the form submit event.
	 */
	public function handle_signup() {

		// Sanity checks.
		if ( ! Config::allows_signups() ) {
			return;
		}

		if ( ! isset( $_POST['wc_bis_register'] ) ) { // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			return;
		}

		try {

			if ( self::requires_nonce_check() ) {
				if ( ! isset( $_POST['wc_bis_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wc_bis_nonce'] ), 'wc_bis_signup' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					wc_add_notice( $this->signup_service->get_error_message( SignupService::ERROR_INVALID_REQUEST ), 'error' );
					return;
				}
			}

			$data = $this->signup_service->parse( $_POST );
			if ( \is_wp_error( $data ) ) {
				wc_add_notice( $this->signup_service->get_error_message( $data->get_error_code() ), 'error' );
				return;
			}

			$result = $this->signup_service->signup(
				$data['product_id'],
				$data['user_id'],
				$data['user_email'],
				$data['posted_attributes'] ?? array()
			);

			if ( \is_wp_error( $result ) ) {
				wc_add_notice( $this->signup_service->get_error_message( $result->get_error_code() ), 'error' );
				return;
			}

			wc_add_notice( $this->signup_service->get_signup_user_message( $result->get_code(), $result->get_notification() ), 'success' );
		} catch ( \Throwable $e ) {
			wc_add_notice( $this->signup_service->get_error_message( SignupService::ERROR_FAILED ), 'error' );
			$this->logger->error( $e->getMessage(), array( 'source' => 'stock-notifications-signup-errors' ) );
			return;
		}
	}

	/**
	 * Whether the form requires a nonce check.
	 *
	 * Note: Nonce checks may be disabled for guest signups to support HTML caching.
	 *
	 * @return bool True if the form requires a nonce check, false otherwise.
	 */
	public static function requires_nonce_check(): bool {

		$requires_account = ProductPageIntegration::is_personalization_enabled() && ( Config::requires_account() || \is_user_logged_in() );

		/**
		 * Filter to require nonce check.
		 *
		 * @since 10.2.0
		 *
		 * @param bool $requires_nonce_check Whether to require nonce check.
		 * @return bool
		 */
		return (bool) apply_filters( 'woocommerce_customer_stock_notifications_requires_nonce_check', $requires_account );
	}
}
