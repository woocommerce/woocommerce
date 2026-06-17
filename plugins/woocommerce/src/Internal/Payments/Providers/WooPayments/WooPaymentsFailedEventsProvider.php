<?php
/**
 * WooPaymentsFailedEventsProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;

/**
 * Provides failed WooPayments webhook events for queue replay.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsFailedEventsProvider {

	/**
	 * Filter used by native tests and development harnesses to inspect or override fetched events.
	 *
	 * @var string
	 */
	const FILTER_FAILED_WEBHOOK_EVENTS = 'woocommerce_native_payments_woopayments_failed_webhook_events';

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient|null
	 */
	private ?WooPaymentsApiClient $api_client = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsApiClient $api_client Native API client.
	 */
	final public function init( WooPaymentsApiClient $api_client ): void {
		$this->api_client = $api_client;
	}

	/**
	 * Get failed webhook events.
	 *
	 * @since 11.0.0
	 *
	 * @return array{data:array<int,array<string,mixed>>,has_more:bool}
	 */
	public function get_failed_webhook_events(): array {
		$events = array(
			'data'     => array(),
			'has_more' => false,
		);

		if ( null !== $this->api_client ) {
			try {
				$events = $this->api_client->get_failed_webhook_events();
			} catch ( WooPaymentsApiException $exception ) {
				wc_get_logger()->error(
					'Can not fetch failed events from the server. Error: ' . $exception->getMessage(),
					array( 'source' => 'wcpay-webhook-reliability' )
				);
			}
		}
		$events = $this->normalize_events_page( $events );

		/**
		 * Filters failed WooPayments webhook events fetched by the native replay service.
		 *
		 * @since 11.0.0
		 *
		 * @param array{data:array<int,array<string,mixed>>,has_more:bool} $events Failed webhook events page.
		 */
		$events = apply_filters(
			self::FILTER_FAILED_WEBHOOK_EVENTS,
			$events
		);

		return $this->normalize_events_page( $events );
	}

	/**
	 * Normalize a failed-events page into the supported response shape.
	 *
	 * @param mixed $events Failed-events page.
	 * @return array{data:array<int,array<string,mixed>>,has_more:bool}
	 */
	private function normalize_events_page( $events ): array {
		if ( ! is_array( $events ) ) {
			return array(
				'data'     => array(),
				'has_more' => false,
			);
		}

		$data = $events['data'] ?? array();

		return array(
			'data'     => is_array( $data ) ? array_values( array_filter( $data, 'is_array' ) ) : array(),
			'has_more' => (bool) ( $events['has_more'] ?? false ),
		);
	}
}
