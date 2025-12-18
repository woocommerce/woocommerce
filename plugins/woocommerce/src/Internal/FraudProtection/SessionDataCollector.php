<?php
/**
 * SessionDataCollector class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Collects comprehensive session and order data for fraud protection analysis.
 *
 * This class provides manual data collection for fraud protection events, gathering
 * session, customer, order, address, and payment information in the exact nested format
 * required by the WPCOM fraud protection service. All data collection is designed to
 * degrade gracefully when fields are unavailable, ensuring checkout never fails due to
 * missing fraud protection data.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class SessionDataCollector {

	/**
	 * SessionClearanceManager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private SessionClearanceManager $session_clearance_manager;

	/**
	 * Constructor.
	 *
	 * @param SessionClearanceManager $session_clearance_manager The SessionClearanceManager instance.
	 */
	public function __construct( SessionClearanceManager $session_clearance_manager ) {
		$this->session_clearance_manager = $session_clearance_manager;
	}

	/**
	 * Collect comprehensive session and order data for fraud protection.
	 *
	 * This method is called manually at specific points in the checkout/payment flow
	 * to gather all relevant data for fraud analysis. It returns data in the nested
	 * format expected by the WPCOM fraud protection service.
	 *
	 * @param string|null $event_type Optional event type identifier (e.g., 'checkout_started', 'payment_attempt').
	 * @param array       $event_data Optional event-specific additional context data.
	 * @return array Nested array containing all collected fraud protection data.
	 */
	public function collect( ?string $event_type = null, array $event_data = array() ): array {
		return array(
			'event_type'        => $event_type,
			'timestamp'         => gmdate( 'Y-m-d H:i:s' ),
			'session'           => array(),
			'customer'          => array(),
			'order'             => array(),
			'shipping_address'  => array(),
			'billing_address'   => array(),
			'payment'           => array(),
			'event_data'        => $event_data,
		);
	}
}
