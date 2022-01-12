<?php

namespace Automattic\WooCommerce\Admin\Schedulers;

/**
 * Class MailchimpScheduler
 *
 * @package Automattic\WooCommerce\Admin\Schedulers
 */
class MailchimpScheduler {

	const SUBSCRIBE_ENDPOINT     = 'https://woocommerce.com/wp-json/wccom/v1/subscribe';
	const SUBSCRIBE_ENDPOINT_DEV = 'http://woocommerce.test/wp-json/wccom/v1/subscribe';

	const SUBSCRIBED_OPTION_NAME = 'woocommerce_onboarding_subscribed_to_mailchimp';

	const LOGGER_CONTEXT = 'mailchimp_scheduler';

	/**
	 * The logger instance.
	 *
	 * @var \WC_Logger_Interface|null
	 */
	private $logger;

	/**
	 * MailchimpScheduler constructor.
	 *
	 * @param \WC_Logger_Interface|null $logger Logger instance.
	 */
	public function __construct( \WC_Logger_Interface $logger = null ) {
		if ( null === $logger ) {
			$logger = wc_get_logger();
		}
		$this->logger = $logger;
	}

	/**
	 * Attempt to subscribe store_email to MailChimp.
	 */
	public function run() {
		// Abort if we've already subscribed to MailChimp.
		if ( 'yes' === get_option( self::SUBSCRIBED_OPTION_NAME ) ) {
			return false;
		}

		$profile_data = get_option( 'woocommerce_onboarding_profile' );

		if ( ! isset( $profile_data['is_agree_marketing'] ) || false === $profile_data['is_agree_marketing'] ) {
			return false;
		}

		// Abort if store_email doesn't exist.
		if ( ! isset( $profile_data['store_email'] ) ) {
			return false;
		}

		$response = $this->make_request( $profile_data['store_email'] );

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			$this->logger->error(
				'Error getting a response from Mailchimp API.',
				array( 'source' => self::LOGGER_CONTEXT )
			);
			return false;
		} else {
			$body = json_decode( $response['body'] );
			if ( isset( $body->success ) && true === $body->success ) {
				update_option( self::SUBSCRIBED_OPTION_NAME, 'yes' );
				return true;
			} else {
				$this->logger->error(
				// phpcs:ignore
					'Incorrect response from Mailchimp API with: ' . print_r( $body, true ),
					array( 'source' => self::LOGGER_CONTEXT )
				);
				return false;
			}
		}
	}

	/**
	 * Make an HTTP request to the API.
	 *
	 * @param string $store_email Email address to subscribe.
	 *
	 * @return mixed
	 */
	public function make_request( $store_email ) {
		if ( true === defined( 'WP_ENVIRONMENT_TYPE' ) && 'development' === constant( 'WP_ENVIRONMENT_TYPE' ) ) {
			$subscribe_endpoint = self::SUBSCRIBE_ENDPOINT_DEV;
		} else {
			$subscribe_endpoint = self::SUBSCRIBE_ENDPOINT;
		}

		return wp_remote_post(
			$subscribe_endpoint,
			array(
				'method' => 'POST',
				'body'   => array(
					'email' => $store_email,
				),
			)
		);
	}
}
