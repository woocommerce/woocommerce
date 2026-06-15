<?php
/**
 * WooPaymentsFailedEventsProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * Provides failed WooPayments webhook events for queue replay.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsFailedEventsProvider {

	/**
	 * Filter used by the A2 native provider seam.
	 *
	 * @var string
	 */
	const FILTER_FAILED_WEBHOOK_EVENTS = 'woocommerce_native_payments_woopayments_failed_webhook_events';

	/**
	 * Get failed webhook events.
	 *
	 * The default provider returns an empty page. Later stages replace this with
	 * the native server API client once account/server ownership migrates.
	 *
	 * @since 11.0.0
	 *
	 * @return array{data:array<int,array<string,mixed>>,has_more:bool}
	 */
	public function get_failed_webhook_events(): array {
		/**
		 * Filters failed WooPayments webhook events fetched by the native A2 replay seam.
		 *
		 * @since 11.0.0
		 *
		 * @param array{data:array<int,array<string,mixed>>,has_more:bool} $events Failed webhook events page.
		 */
		$events = apply_filters(
			self::FILTER_FAILED_WEBHOOK_EVENTS,
			array(
				'data'     => array(),
				'has_more' => false,
			)
		);

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
