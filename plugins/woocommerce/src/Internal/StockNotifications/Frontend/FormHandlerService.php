<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

/**
 * Class for integrating with the product page.
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
	 * @param EligibilityService $eligibility_service The eligibility service.
	 */
	final public function init( SignupService $signup_service ) {
		$this->signup_service      = $signup_service;
		$this->logger              = \wc_get_logger();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'handle_signup' ) );
	}

	/**
	 * Handle the form submit event.
	 */
	public function handle_signup() {
		if ( ! isset( $_POST['wc_bis_register'] ) ) { // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			return;
		}

		try {
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
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'stock-notifications-signup-errors' ) );
			return;
		}
	}
}
