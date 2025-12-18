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
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param SessionClearanceManager $session_clearance_manager The session clearance manager instance.
	 */
	final public function init( SessionClearanceManager $session_clearance_manager ): void {
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
		// Ensure cart and session are loaded.
		$this->session_clearance_manager->ensure_cart_loaded();

		return array(
			'event_type'       => $event_type,
			'timestamp'        => gmdate( 'Y-m-d H:i:s' ),
			'session'          => $this->get_session_data(),
			'customer'         => $this->get_customer_data(),
			'order'            => array(),
			'shipping_address' => array(),
			'billing_address'  => array(),
			'payment'          => array(),
			'event_data'       => $event_data,
		);
	}

	/**
	 * Get session data including session ID, IP address, email, and user agent.
	 *
	 * Collects session identification and tracking data with graceful degradation
	 * for unavailable fields. Email collection follows the fallback chain:
	 * logged-in user email → session customer data → WC_Customer billing email.
	 *
	 * @return array Session data array with 6 keys.
	 */
	private function get_session_data(): array {
		try {
			$session_id = $this->session_clearance_manager->get_session_id();
			$ip_address = $this->get_ip_address();
			$email      = $this->get_email();
			$user_agent = $this->get_user_agent();

			/**
			 * $is_user_session is flag that we have a real browser session vs API-based interaction.
			 * We start with a very basic check, but we might need a more sophisticated way to detect it in the future.
			 * TODO: Implement more sophisticated way to detect it.
			 */
			$is_user_session = 'no-session' !== $session_id;

			return array(
				'session_id'      => $session_id,
				'ip_address'      => $ip_address,
				'email'           => $email,
				'ja3_hash'        => null,
				'user_agent'      => $user_agent,
				'is_user_session' => $is_user_session,
			);
		} catch ( \Exception $e ) {
			// Graceful degradation - return structure with null values.
			return array(
				'session_id'      => null,
				'ip_address'      => null,
				'email'           => null,
				'ja3_hash'        => null,
				'user_agent'      => null,
				'is_user_session' => false,
			);
		}
	}

	/**
	 * Get customer data including name, billing email, and order history.
	 *
	 * Collects customer identification and history data with graceful degradation.
	 * Tries WC_Customer object first, then falls back to session data if values are empty.
	 * Only includes lifetime_order_count for order history (minimal approach).
	 *
	 * @return array Customer data array with 4 keys.
	 */
	private function get_customer_data(): array {
		try {
			$first_name           = null;
			$last_name            = null;
			$billing_email        = null;
			$lifetime_order_count = 0;

			// Try WC_Customer object first.
			if ( WC()->customer instanceof \WC_Customer ) {
				$first_name    = WC()->customer->get_billing_first_name();
				$last_name     = WC()->customer->get_billing_last_name();
				$billing_email = WC()->customer->get_billing_email();

				// Sanitize email.
				if ( $billing_email ) {
					$billing_email = \sanitize_email( $billing_email );
				}
			} elseif ( WC()->session instanceof \WC_Session ) {
				// Fallback to session customer data if WC_Customer not available.
				$customer_data = WC()->session->get( 'customer' );
				if ( is_array( $customer_data ) ) {
					if ( ! empty( $customer_data['first_name'] ) ) {
						$first_name = \sanitize_text_field( $customer_data['first_name'] );
					}
					if ( ! empty( $customer_data['last_name'] ) ) {
						$last_name = \sanitize_text_field( $customer_data['last_name'] );
					}
					if ( ! empty( $customer_data['email'] ) ) {
						$billing_email = \sanitize_email( $customer_data['email'] );
					}
				}
			}

			// Calculate lifetime order count for logged-in users.
			if ( \is_user_logged_in() ) {
				$user_id              = \get_current_user_id();
				$lifetime_order_count = $this->get_lifetime_order_count( $user_id );
			}

			return array(
				'first_name'           => $first_name ? $first_name : null,
				'last_name'            => $last_name ? $last_name : null,
				'billing_email'        => $billing_email ? $billing_email : null,
				'lifetime_order_count' => $lifetime_order_count,
			);
		} catch ( \Exception $e ) {
			// Graceful degradation - return structure with null values.
			return array(
				'first_name'           => null,
				'last_name'            => null,
				'billing_email'        => null,
				'lifetime_order_count' => 0,
			);
		}
	}

	/**
	 * Get client IP address using WooCommerce geolocation utility.
	 *
	 * @return string|null IP address or null if not available.
	 */
	private function get_ip_address(): ?string {
		if ( class_exists( 'WC_Geolocation' ) ) {
			$ip = \WC_Geolocation::get_ip_address();
			return $ip ? $ip : null;
		}
		return null;
	}

	/**
	 * Get customer email with fallback chain.
	 *
	 * Tries logged-in user email first, then WC_Customer billing email,
	 * then session customer data as fallback.
	 *
	 * @return string|null Email address or null if not available.
	 */
	private function get_email(): ?string {
		// Try logged-in user first.
		if ( \is_user_logged_in() ) {
			$user = \wp_get_current_user();
			if ( $user && $user->user_email ) {
				return \sanitize_email( $user->user_email );
			}
		}

		// Try WC_Customer object.
		if ( WC()->customer instanceof \WC_Customer ) {
			$email = WC()->customer->get_billing_email();
			if ( $email ) {
				return \sanitize_email( $email );
			}
		}

		// Fallback to session customer data if WC_Customer not available.
		if ( WC()->session instanceof \WC_Session ) {
			$customer_data = WC()->session->get( 'customer' );
			if ( is_array( $customer_data ) && ! empty( $customer_data['email'] ) ) {
				return \sanitize_email( $customer_data['email'] );
			}
		}

		return null;
	}

	/**
	 * Get user agent string from HTTP headers.
	 *
	 * @return string|null User agent or null if not available.
	 */
	private function get_user_agent(): ?string {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}
		return null;
	}

	/**
	 * Calculate lifetime order count for a customer.
	 *
	 * Counts orders with 'completed' status only.
	 *
	 * @param int $user_id The user ID.
	 * @return int Number of completed orders.
	 */
	private function get_lifetime_order_count( int $user_id ): int {
		try {
			if ( ! function_exists( 'wc_get_orders' ) ) {
				return 0;
			}

			// ! That might be expensive operation to run on every event, so we might need to cache the result.
			$orders = WC()->call_function(
				'wc_get_orders',
				array(
					'customer_id' => $user_id,
					'status'      => 'completed',
					'limit'       => -1,
					'return'      => 'ids',
				)
			);

			return is_array( $orders ) ? count( $orders ) : 0;
		} catch ( \Exception $e ) {
			return 0;
		}
	}
}
