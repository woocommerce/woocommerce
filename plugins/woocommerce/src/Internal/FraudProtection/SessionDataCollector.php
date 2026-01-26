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
	 * @since 10.5.0
	 *
	 * @param string|null $event_type Optional event type identifier (e.g., 'checkout_started', 'payment_attempt').
	 * @param array       $event_data Optional event-specific additional context data (may include 'order_id').
	 * @return array Nested array containing all collected fraud protection data.
	 */
	public function collect( ?string $event_type = null, array $event_data = array() ): array {
		// Ensure cart and session are loaded.
		$this->session_clearance_manager->ensure_cart_loaded();

		// Extract order ID from event_data if provided.
		// There seem to be no universal way to get order id from session data, so we may start with passing it as a parameter when calling this method.
		$order_id_from_event = $event_data['order_id'] ?? null;

		return array(
			'event_type'       => $event_type,
			'timestamp'        => gmdate( 'Y-m-d H:i:s' ),
			'wc_version'       => WC()->version,
			'session'          => $this->get_session_data(),
			'customer'         => $this->get_customer_data(),
			'order'            => $this->get_order_data( $order_id_from_event ),
			'shipping_address' => $this->get_shipping_address(),
			'billing_address'  => $this->get_billing_address(),
			'event_data'       => $event_data,
		);
	}

	/**
	 * Get current billing country from customer data.
	 *
	 * Reuses the same logic as get_billing_address() but returns only the country.
	 * Tries WC_Customer first, falls back to session data, with graceful error handling.
	 *
	 * @since 10.5.0
	 *
	 * @return string|null Current billing country code or null if unavailable.
	 */
	public function get_current_billing_country(): ?string {
		try {
			if ( WC()->customer instanceof \WC_Customer ) {
				$country = WC()->customer->get_billing_country();
				return ! empty( $country ) ? \sanitize_text_field( $country ) : null;
			} elseif ( WC()->session instanceof \WC_Session ) {
				$customer_data = WC()->session->get( 'customer' );
				if ( is_array( $customer_data ) && ! empty( $customer_data['country'] ) ) {
					return \sanitize_text_field( $customer_data['country'] );
				}
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Graceful degradation.
		}
		return null;
	}

	/**
	 * Get current shipping country from customer data.
	 *
	 * Reuses the same logic as get_shipping_address() but returns only the country.
	 * Tries WC_Customer first, falls back to session data, with graceful error handling.
	 *
	 * @since 10.5.0
	 *
	 * @return string|null Current shipping country code or null if unavailable.
	 */
	public function get_current_shipping_country(): ?string {
		try {
			if ( WC()->customer instanceof \WC_Customer ) {
				$country = WC()->customer->get_shipping_country();
				return ! empty( $country ) ? \sanitize_text_field( $country ) : null;
			} elseif ( WC()->session instanceof \WC_Session ) {
				$customer_data = WC()->session->get( 'customer' );
				if ( is_array( $customer_data ) && ! empty( $customer_data['shipping_country'] ) ) {
					return \sanitize_text_field( $customer_data['shipping_country'] );
				}
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Graceful degradation.
		}
		return null;
	}

	/**
	 * Get session data including session ID, IP address, email, and user agent.
	 *
	 * Collects session identification and tracking data with graceful degradation
	 * for unavailable fields. Email collection follows the fallback chain:
	 * logged-in user email → session customer data → WC_Customer billing email.
	 *
	 * @since 10.5.0
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
	 * Includes lifetime_order_count which counts all orders regardless of status.
	 *
	 * @since 10.5.0
	 *
	 * @return array Customer data array with 4 keys.
	 */
	private function get_customer_data(): array {
		$customer_data = array(
			'first_name'           => null,
			'last_name'            => null,
			'billing_email'        => null,
			'lifetime_order_count' => 0,
		);
		try {
			$lifetime_order_count = 0;

			// Try WC_Customer object first.
			if ( WC()->customer instanceof \WC_Customer ) {
				if ( WC()->customer->get_id() > 0 ) {
					// We need to reload the customer so it uses the correct data store to count the orders.
					$customer             = new \WC_Customer( WC()->customer->get_id() );
					$lifetime_order_count = $customer->get_order_count();
				}

				$customer_data = array_merge(
					$customer_data,
					array(
						'first_name'           => \sanitize_text_field( WC()->customer->get_billing_first_name() ),
						'last_name'            => \sanitize_text_field( WC()->customer->get_billing_last_name() ),
						'billing_email'        => \sanitize_email( \WC()->customer->get_billing_email() ),
						'lifetime_order_count' => $lifetime_order_count,
					)
				);

			} elseif ( WC()->session instanceof \WC_Session ) {
				// Fallback to session customer data if WC_Customer not available.
				$customer_session_data = WC()->session->get( 'customer' );
				if ( is_array( $customer_session_data ) ) {
					$customer_data = array_merge(
						$customer_data,
						array(
							'first_name'    => \sanitize_text_field( $customer_session_data['first_name'] ?? null ),
							'last_name'     => \sanitize_text_field( $customer_session_data['last_name'] ?? null ),
							'billing_email' => \sanitize_email( $customer_session_data['email'] ?? null ),
						)
					);
				}
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Graceful degradation - return as much data as possible.
		}

		return $customer_data;
	}

	/**
	 * Get order data including totals, currency, cart hash, and cart items.
	 *
	 * Collects comprehensive order information from the cart with graceful degradation.
	 * Calculates shipping_tax_rate from shipping tax and shipping total. Sets customer_id
	 * to 'guest' for non-logged-in users.
	 *
	 * @since 10.5.0
	 *
	 * @param int|null $order_id_from_event Optional order ID from event data.
	 * @return array Order data array with 11 keys including items array.
	 */
	private function get_order_data( ?int $order_id_from_event = null ): array {
		try {
			// Initialize default values.
			$order_id          = $order_id_from_event;
			$customer_id       = 'guest';
			$total             = 0;
			$items_total       = 0;
			$shipping_total    = 0;
			$tax_total         = 0;
			$shipping_tax_rate = null;
			$discount_total    = 0;
			$currency          = WC()->call_function( 'get_woocommerce_currency' );
			$cart_hash         = null;
			$items             = array();

			// Get customer ID from WooCommerce customer object if available.
			// We don't need to fallback to session data here, because customer id won't be stored there.
			if ( WC()->customer instanceof \WC_Customer ) {
				$id = WC()->customer->get_id();
				if ( $id ) {
					$customer_id = $id;
				}
			}
			// Get cart data if available.
			if ( WC()->cart instanceof \WC_Cart ) {
				$items_total    = (float) WC()->cart->get_subtotal();
				$shipping_total = (float) WC()->cart->get_shipping_total();
				$tax_total      = (float) WC()->cart->get_cart_contents_tax();
				$discount_total = (float) WC()->cart->get_discount_total();
				$cart_hash      = WC()->cart->get_cart_hash();
				$items          = $this->get_cart_items();
				$total          = (float) WC()->cart->get_total( 'edit' );

				// Calculate shipping_tax_rate.
				$shipping_tax = (float) WC()->cart->get_shipping_tax();
				if ( $shipping_total > 0 && $shipping_tax > 0 ) {
					$shipping_tax_rate = $shipping_tax / $shipping_total;
				}
			}

			return array(
				'order_id'          => $order_id,
				'customer_id'       => $customer_id,
				'total'             => $total,
				'items_total'       => $items_total,
				'shipping_total'    => $shipping_total,
				'tax_total'         => $tax_total,
				'shipping_tax_rate' => $shipping_tax_rate,
				'discount_total'    => $discount_total,
				'currency'          => $currency,
				'cart_hash'         => $cart_hash,
				'items'             => $items,
			);
		} catch ( \Exception $e ) {
			// Graceful degradation - return structure with default values.
			return array(
				'order_id'          => null,
				'customer_id'       => 'guest',
				'total'             => 0,
				'items_total'       => 0,
				'shipping_total'    => 0,
				'tax_total'         => 0,
				'shipping_tax_rate' => null,
				'discount_total'    => 0,
				'currency'          => WC()->call_function( 'get_woocommerce_currency' ),
				'cart_hash'         => null,
				'items'             => array(),
			);
		}
	}

	/**
	 * Get cart items with detailed product information.
	 *
	 * Iterates through cart items and extracts comprehensive product data including
	 * name, description, category, SKU, pricing, quantities, and WooCommerce-specific
	 * attributes. Returns array of item objects with 12 fields each.
	 *
	 * @since 10.5.0
	 *
	 * @return array Array of cart item objects with detailed product information.
	 */
	private function get_cart_items(): array {
		$items = array();

		try {
			if ( ! WC()->cart instanceof \WC_Cart ) {
				return $items;
			}

			foreach ( WC()->cart->get_cart() as $cart_item ) {
				try {
					$product = $cart_item['data'] ?? null;

					if ( ! $product instanceof \WC_Product ) {
						continue;
					}

					$quantity = $cart_item['quantity'] ?? 1;

					// Calculate per-unit amounts.
					$unit_price           = (float) $product->get_price();
					$line_tax             = $cart_item['line_tax'] ?? 0;
					$unit_tax_amount      = $quantity > 0 ? ( (float) $line_tax / $quantity ) : 0;
					$line_discount        = $cart_item['line_subtotal'] - $cart_item['line_total'];
					$unit_discount_amount = $quantity > 0 ? ( (float) $line_discount / $quantity ) : 0;
					$category             = $this->get_product_category_names( $product );

					$items[] = array(
						'name'                 => $product->get_name() ? $product->get_name() : null,
						'description'          => $product->get_description() ? $product->get_description() : null,
						'category'             => $category,
						'sku'                  => $product->get_sku() ? $product->get_sku() : null,
						'quantity'             => $quantity,
						'unit_price'           => $unit_price,
						'unit_tax_amount'      => $unit_tax_amount,
						'unit_discount_amount' => $unit_discount_amount,
						'product_type'         => $product->get_type() ? $product->get_type() : null,
						'is_virtual'           => $product->is_virtual(),
						'is_downloadable'      => $product->is_downloadable(),
						'attributes'           => $product->get_attributes() ? $product->get_attributes() : array(),
					);
				} catch ( \Exception $e ) {
					// Skip this item if there's an error, continue with next item.
					continue;
				}
			}
		} catch ( \Exception $e ) {
			// Return empty array on error.
			return array();
		}

		return $items;
	}

	/**
	 * Get billing address from customer data.
	 *
	 * Collects billing address fields from WC_Customer object with graceful degradation.
	 * Returns array with 6 address fields, sanitized with sanitize_text_field().
	 *
	 * @since 10.5.0
	 *
	 * @return array Billing address array with 6 keys.
	 */
	private function get_billing_address(): array {
		$billing_data = array(
			'first_name' => null,
			'last_name'  => null,
			'address'    => null,
			'address_1'  => null,
			'address_2'  => null,
			'city'       => null,
			'state'      => null,
			'country'    => null,
			'phone'      => null,
			'postcode'   => null,
		);

		try {
			// Try WC_Customer object first.
			if ( WC()->customer instanceof \WC_Customer ) {
				$billing_data = array_merge(
					$billing_data,
					array(
						'first_name' => \sanitize_text_field( WC()->customer->get_billing_first_name() ),
						'last_name'  => \sanitize_text_field( WC()->customer->get_billing_last_name() ),
						'address_1'  => \sanitize_text_field( WC()->customer->get_billing_address_1() ),
						'address_2'  => \sanitize_text_field( WC()->customer->get_billing_address_2() ),
						'city'       => \sanitize_text_field( WC()->customer->get_billing_city() ),
						'state'      => \sanitize_text_field( WC()->customer->get_billing_state() ),
						'country'    => \sanitize_text_field( WC()->customer->get_billing_country() ),
						'phone'      => \sanitize_text_field( WC()->customer->get_billing_phone() ),
						'postcode'   => \sanitize_text_field( WC()->customer->get_billing_postcode() ),
					)
				);
			} elseif ( WC()->session instanceof \WC_Session ) {
				// Fallback to session customer data if WC_Customer not available.
				$customer_data = WC()->session->get( 'customer' );
				if ( is_array( $customer_data ) ) {
					$billing_data = array_merge(
						$billing_data,
						array(
							'first_name' => \sanitize_text_field( $customer_data['first_name'] ?? null ),
							'last_name'  => \sanitize_text_field( $customer_data['last_name'] ?? null ),
							'address'    => \sanitize_text_field( $customer_data['address'] ?? null ),
							'address_1'  => \sanitize_text_field( $customer_data['address_1'] ?? null ),
							'address_2'  => \sanitize_text_field( $customer_data['address_2'] ?? null ),
							'city'       => \sanitize_text_field( $customer_data['city'] ?? null ),
							'state'      => \sanitize_text_field( $customer_data['state'] ?? null ),
							'country'    => \sanitize_text_field( $customer_data['country'] ?? null ),
							'phone'      => \sanitize_text_field( $customer_data['phone'] ?? null ),
							'postcode'   => \sanitize_text_field( $customer_data['postcode'] ?? null ),
						)
					);
				}
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Graceful degradation - prevents any errors from being thrown.
		}

		return $billing_data;
	}

	/**
	 * Get shipping address from customer data.
	 *
	 * Collects shipping address fields from WC_Customer object with graceful degradation.
	 * Returns array with 6 address fields, sanitized with sanitize_text_field().
	 *
	 * @since 10.5.0
	 *
	 * @return array Shipping address array with 6 keys.
	 */
	private function get_shipping_address(): array {
		$shipping_data = array(
			'first_name' => null,
			'last_name'  => null,
			'address'    => null,
			'address_1'  => null,
			'address_2'  => null,
			'city'       => null,
			'state'      => null,
			'postcode'   => null,
			'country'    => null,
		);
		try {
			if ( WC()->customer instanceof \WC_Customer ) {
				$shipping_data = array_merge(
					$shipping_data,
					array(
						'first_name' => \sanitize_text_field( WC()->customer->get_shipping_first_name() ),
						'last_name'  => \sanitize_text_field( WC()->customer->get_shipping_last_name() ),
						'address_1'  => \sanitize_text_field( WC()->customer->get_shipping_address_1() ),
						'address_2'  => \sanitize_text_field( WC()->customer->get_shipping_address_2() ),
						'city'       => \sanitize_text_field( WC()->customer->get_shipping_city() ),
						'state'      => \sanitize_text_field( WC()->customer->get_shipping_state() ),
						'postcode'   => \sanitize_text_field( WC()->customer->get_shipping_postcode() ),
						'country'    => \sanitize_text_field( WC()->customer->get_shipping_country() ),
					)
				);
			} elseif ( WC()->session instanceof \WC_Session ) {
				// Fallback to session customer data if WC_Customer not available.
				$customer_data = WC()->session->get( 'customer' );
				if ( is_array( $customer_data ) ) {
					$shipping_data = array_merge(
						$shipping_data,
						array(
							'first_name' => \sanitize_text_field( $customer_data['shipping_first_name'] ?? null ),
							'last_name'  => \sanitize_text_field( $customer_data['shipping_last_name'] ?? null ),
							'address_1'  => \sanitize_text_field( $customer_data['shipping_address_1'] ?? null ),
							'address_2'  => \sanitize_text_field( $customer_data['shipping_address_2'] ?? null ),
							'city'       => \sanitize_text_field( $customer_data['shipping_city'] ?? null ),
							'state'      => \sanitize_text_field( $customer_data['shipping_state'] ?? null ),
							'postcode'   => \sanitize_text_field( $customer_data['shipping_postcode'] ?? null ),
							'country'    => \sanitize_text_field( $customer_data['shipping_country'] ?? null ),
						)
					);
				}
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Graceful degradation - returns as much data as possible.
		}

		return $shipping_data;
	}

	/**
	 * Get client IP address using WooCommerce geolocation utility.
	 *
	 * @since 10.5.0
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
	 * @since 10.5.0
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
	 * @since 10.5.0
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
	 * Get product category names as comma-separated list.
	 *
	 * Uses WooCommerce helper with caching for better performance.
	 * Returns all categories for the product, not just the primary one.
	 *
	 * @since 10.5.0
	 *
	 * @param \WC_Product $product The product object.
	 * @return string|null Comma-separated category names or null if none.
	 */
	private function get_product_category_names( \WC_Product $product ): ?string {
		$terms = WC()->call_function( 'wc_get_product_terms', $product->get_id(), 'product_cat' );
		if ( empty( $terms ) || ! is_array( $terms ) ) {
			return null;
		}
		$category_names = array_map(
			function ( $term ) {
				return $term->name;
			},
			$terms
		);
		return implode( ', ', $category_names );
	}
}
