<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\AbstractShippingProvider;
use WC_Order;

/**
 * Class FulfillmentUtils
 *
 * Utility class for handling order fulfillments.
 */
class FulfillmentUtils {

	/**
	 * Get pending items for an order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments An array of fulfillments to check.
	 * @param bool     $without_refunds Whether to exclude refunded items from the pending items.
	 *
	 * @return array An array of pending items.
	 */
	public static function get_pending_items( WC_Order $order, $fulfillments, $without_refunds = true ): array {
		$items_in_fulfillments = self::get_all_items_of_fulfillments( $fulfillments );
		$order_items           = array_map(
			function ( $item ) use ( $order, $without_refunds ) {
				// Refunded item quantities are saved as negative values in the order.
				return array(
					'item_id' => $item->get_id(),
					'item'    => $item,
					'qty'     => $item->get_quantity() + ( $without_refunds ? $order->get_qty_refunded_for_item( $item->get_id() ) : 0 ),
				);
			},
			$order->get_items() ?? array()
		);

		// If there are items in fulfillments, subtract their quantities from the order items.
		if ( ! empty( $items_in_fulfillments ) ) {
			foreach ( $order_items as $item_id => &$item ) {
				if ( isset( $items_in_fulfillments[ $item_id ] ) ) {
					$item['qty'] = $item['qty'] - $items_in_fulfillments[ $item_id ];
				}
			}
		}

		return array_filter(
			$order_items,
			function ( $item ) {
				return $item['qty'] > 0; // Only return items with a positive quantity.
			}
		);
	}

	/**
	 * Get refunded items for an order.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return array An array of refunded items with their IDs and quantities.
	 */
	public static function get_refunded_items( WC_Order $order ): array {
		$items_refunded = array();
		foreach ( $order->get_items() as $item ) {
			$items_refunded[ $item->get_id() ] = -1 * $order->get_qty_refunded_for_item( $item->get_id() );
		}
		return array_filter(
			$items_refunded,
			function ( $qty ) {
				return $qty > 0; // Only include items that have been refunded.
			}
		);
	}

	/**
	 * Get order items for a fulfillment.
	 *
	 * @param WC_Order    $order The order object.
	 * @param Fulfillment $fulfillment The fulfillment object.
	 *
	 * @return array An array of order items.
	 */
	public static function get_fulfillment_items( WC_Order $order, Fulfillment $fulfillment ): array {
		$fulfillment_items = array_combine(
			array_column( $fulfillment->get_items(), 'item_id' ),
			array_column( $fulfillment->get_items(), 'qty' )
		);

		$order_items = array_map(
			function ( $item ) use ( $order ) {
				return array(
					'item_id' => $item->get_id(),
					'item'    => $item,
					'qty'     => $item->get_quantity() - $order->get_qty_refunded_for_item( $item ),
				);
			},
			$order->get_items()
		);

		return array_map(
			function ( $item ) use ( $fulfillment_items ) {
				$item['qty'] = $fulfillment_items[ $item['item_id'] ];
				return $item;
			},
			array_filter(
				$order_items,
				function ( $item ) use ( $fulfillment_items ) {
					return isset( $fulfillment_items[ $item['item_id'] ] );
				}
			)
		);
	}

	/**
	 * Check if an order has pending items.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments An array of fulfillments to check.
	 *
	 * @return bool True if there are pending items, false otherwise.
	 */
	public static function has_pending_items( WC_Order $order, array $fulfillments ): bool {
		$pending_items = self::get_pending_items( $order, $fulfillments );
		return ! empty( $pending_items );
	}

	/**
	 * Get the fulfillment status of the entity. This runs like a computed property, where
	 * it checks the fulfillment status of each fulfillment attached to the order,
	 * and computes the overall fulfillment status of the order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments An array of fulfillments to check.
	 *
	 * @return string The fulfillment status.
	 */
	public static function calculate_order_fulfillment_status( WC_Order $order, $fulfillments = array() ): string {
		$has_fulfillments = ! empty( $fulfillments );
		if ( $has_fulfillments ) {
			$pending_items = self::get_pending_items( $order, $fulfillments );

			$all_fulfilled  = true;
			$some_fulfilled = false;

			foreach ( $fulfillments as $fulfillment ) {
				if ( ! $fulfillment->get_is_fulfilled() ) {
					$all_fulfilled = false;
				} else {
					$some_fulfilled = true;
				}
			}

			if ( $all_fulfilled && empty( $pending_items ) ) {
				$status = 'fulfilled';
			} elseif ( $some_fulfilled ) {
				$status = 'partially_fulfilled';
			} else {
				$status = 'unfulfilled';
			}
		} else {
			$status = 'no_fulfillments';
		}

		/**
		 * This filter allows plugins to modify the fulfillment status of an order.
		 *
		 * @since 10.1.0
		 *
		 * @param string $status The default fulfillment status.
		 * @param WC_Order $order The order object.
		 * @param array $fulfillments An array of fulfillments for the order.
		 */
		return apply_filters(
			'woocommerce_fulfillment_calculate_order_fulfillment_status',
			$status,
			$order,
			$fulfillments
		);
	}

	/**
	 * Get all items from the fulfillments.
	 *
	 * @param array $fulfillments An array of fulfillments.
	 *
	 * @return array An associative array of item IDs and their quantities.
	 */
	public static function get_all_items_of_fulfillments( array $fulfillments ): array {
		$items = array();
		foreach ( $fulfillments as $fulfillment ) {
			$fulfillment_items = $fulfillment->get_items();
			foreach ( $fulfillment_items as $item ) {
				if ( ! isset( $items[ $item['item_id'] ] ) ) {
					$items[ $item['item_id'] ] = 0; // Initialize if not set.
				}
				// Sum the quantities for each item.
				$items[ $item['item_id'] ] += $item['qty'];
			}
		}
		return $items;
	}

	/**
	 * Get the HTML for the fulfillment tracking number.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 *
	 * @return string The HTML for the tracking number.
	 */
	public static function get_tracking_info_html( Fulfillment $fulfillment ): string {
		$tracking_html   = '';
		$tracking_url    = $fulfillment->get_meta( '_tracking_url', true );
		$tracking_number = $fulfillment->get_meta( '_tracking_number', true );
		if ( ! empty( $tracking_url ) && ! empty( $tracking_number ) ) {
			$tracking_html .= '<a href="' . esc_url( $tracking_url ) . '" target="_blank" rel="noopener noreferrer">';
			$tracking_html .= esc_html( $tracking_number );
			$tracking_html .= '</a>';
		} elseif ( ! empty( $tracking_number ) ) {
			$tracking_html .= esc_html( $tracking_number );
		} else {
			$tracking_html .= '<span class="no-tracking">' . esc_html__( 'No tracking number available', 'woocommerce' ) . '</span>';
		}
		return $tracking_html;
	}

	/**
	 * Get the fulfillment status text for an order.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return string The fulfillment status text.
	 */
	public static function get_order_fulfillment_status_text( WC_Order $order ): string {
		// Ensure the order is a valid WC_Order object.
		if ( ! $order instanceof WC_Order ) {
			return '';
		}

		// Check if the order meta exists for fulfillment status.
		$fulfillment_status = $order->meta_exists( '_fulfillment_status' ) ? $order->get_meta( '_fulfillment_status', true ) : 'no_fulfillments';

		$fulfillment_status_text = '';
		switch ( $fulfillment_status ) {
			case 'fulfilled':
				$fulfillment_status_text = ' ' . __( 'It has been <mark class="fulfillment-status">Fulfilled</mark>.', 'woocommerce' );
				break;
			case 'partially_fulfilled':
				$fulfillment_status_text = ' ' . __( 'It has been <mark class="fulfillment-status">Partially fulfilled</mark>.', 'woocommerce' );
				break;
			case 'unfulfilled':
				$fulfillment_status_text = ' ' . __( 'It is currently <mark class="fulfillment-status">Unfulfilled</mark>.', 'woocommerce' );
				break;
			case 'no_fulfillments':
				$fulfillment_status_text = ' ' . __( 'It has <mark class="fulfillment-status">no fulfillments</mark> yet.', 'woocommerce' );
				break;
		}

		/**
		 * This filter allows plugins to modify the fulfillment status text for an order for their custom fulfillment statuses.
		 *
		 * @since 10.1.0
		 *
		 * @param string $fulfillment_status_text The default fulfillment status text.
		 * @param string $fulfillment_status The fulfillment status of the order.
		 * @param WC_Order $order The order object.
		 */
		return apply_filters(
			'woocommerce_fulfillment_order_fulfillment_status_text',
			$fulfillment_status_text,
			$fulfillment_status,
			$order
		);
	}

	/**
	 * Check if the given fulfillment status is valid.
	 *
	 * @param string|null $status The fulfillment status to check.
	 *
	 * @return bool True if the status is valid, false otherwise.
	 */
	public static function is_valid_order_fulfillment_status( ?string $status ): bool {
		if ( is_null( $status ) ) {
			return false;
		}
		$order_fulfillment_statuses = self::get_order_fulfillment_statuses();
		return in_array( $status, array_keys( $order_fulfillment_statuses ), true );
	}

	/**
	 * Check if the given fulfillment status is valid.
	 *
	 * @param string|null $status The fulfillment status to check.
	 *
	 * @return bool True if the status is valid, false otherwise.
	 */
	public static function is_valid_fulfillment_status( ?string $status ): bool {
		if ( is_null( $status ) ) {
			return false;
		}
		$fulfillment_statuses = self::get_fulfillment_statuses();
		return in_array( $status, array_keys( $fulfillment_statuses ), true );
	}

	/**
	 * Get the order fulfillment statuses.
	 *
	 * This method provides the order fulfillment statuses that can be used
	 * in the WooCommerce Fulfillments system. It can be filtered using the
	 * `woocommerce_fulfillment_order_fulfillment_statuses` filter.
	 *
	 * @return array An associative array of order fulfillment statuses.
	 */
	public static function get_order_fulfillment_statuses(): array {
		/**
		 * This filter allows plugins to modify the list of order fulfillment statuses.
		 * It can be used to add, remove, or change the order fulfillment statuses available in the
		 * WooCommerce Fulfillments system.
		 *
		 * @since 10.1.0
		 *
		 * @param array $order_fulfillment_statuses The default list of order fulfillment statuses.
		 */
		return apply_filters(
			'woocommerce_fulfillment_order_fulfillment_statuses',
			self::get_default_order_fulfillment_statuses()
		);
	}

	/**
	 * Get the fulfillment statuses.
	 *
	 * This method provides the fulfillment statuses that can be used
	 * in the WooCommerce Fulfillments system. It can be filtered using the
	 * `woocommerce_fulfillment_fulfillment_statuses` filter.
	 *
	 * @return array An associative array of fulfillment statuses.
	 */
	public static function get_fulfillment_statuses(): array {
		/**
		 * This filter allows plugins to modify the list of fulfillment statuses.
		 * It can be used to add, remove, or change the fulfillment statuses available in the
		 * WooCommerce Fulfillments system.
		 *
		 * @since 10.1.0
		 *
		 * @param array $fulfillment_statuses The default list of fulfillment statuses.
		 */
		return apply_filters(
			'woocommerce_fulfillment_fulfillment_statuses',
			self::get_default_fulfillment_statuses()
		);
	}

	/**
	 * Get the shipping providers.
	 *
	 * This method retrieves the shipping providers registered in the WooCommerce Fulfillments system.
	 * It can be filtered using the `woocommerce_fulfillment_shipping_providers` filter.
	 *
	 * @return array An associative array of shipping providers with their details.
	 */
	public static function get_shipping_providers(): array {
		/**
		 * This filter allows plugins to modify the list of shipping providers.
		 * It can be used to add, remove, or change the shipping providers available in the
		 * WooCommerce Fulfillments system.
		 *
		 * @since 10.1.0
		 *
		 * @param array $shipping_providers The default list of shipping providers.
		 */
		return apply_filters(
			'woocommerce_fulfillment_shipping_providers',
			array()
		);
	}

	/**
	 * Get the shipping providers as an array of JS objects, for use in the fulfillment UI.
	 *
	 * @return array An associative array of shipping providers with their details.
	 */
	public static function get_shipping_providers_object(): array {
		$shipping_providers = self::get_shipping_providers();
		if ( ! is_array( $shipping_providers ) ) {
			return array();
		}
		$shipping_providers_object = array();
		foreach ( $shipping_providers as $shipping_provider ) {
			if ( is_string( $shipping_provider )
			&& class_exists( $shipping_provider )
			&& is_subclass_of( $shipping_provider, AbstractShippingProvider::class )
			) {
				try {
					// Instantiate the shipping provider class.
					$shipping_provider_instance = wc_get_container()->get( $shipping_provider );
				} catch ( \Throwable $e ) {
					continue; // Skip if instantiation fails.
				}
				$shipping_providers_object[ $shipping_provider_instance->get_key() ] = array(
					'label' => $shipping_provider_instance->get_name(),
					'icon'  => $shipping_provider_instance->get_icon(),
					'value' => $shipping_provider_instance->get_key(),
				);
			}
			if ( is_object( $shipping_provider ) && $shipping_provider instanceof AbstractShippingProvider ) {
				$shipping_providers_object[ $shipping_provider->get_key() ] = array(
					'label' => $shipping_provider->get_name(),
					'icon'  => $shipping_provider->get_icon(),
					'value' => $shipping_provider->get_key(),
				);
			}
		}

		return $shipping_providers_object;
	}

	/**
	 * Get the default order fulfillment statuses.
	 *
	 * This method provides the default order fulfillment statuses that can be used
	 * in the WooCommerce Fulfillments system. It can be filtered using the
	 * `woocommerce_fulfillment_order_fulfillment_statuses` filter.
	 *
	 * @return array An associative array of default order fulfillment statuses.
	 */
	protected static function get_default_order_fulfillment_statuses(): array {
		return array(
			'fulfilled'           => array(
				'label'            => __( 'Fulfilled', 'woocommerce' ),
				'background_color' => '#C6E1C6',
				'text_color'       => '#13550F',
			),
			'partially_fulfilled' => array(
				'label'            => __( 'Partially fulfilled', 'woocommerce' ),
				'background_color' => '#C8D7E1',
				'text_color'       => '#003D66',
			),
			'unfulfilled'         => array(
				'label'            => __( 'Unfulfilled', 'woocommerce' ),
				'background_color' => '#FBE5E5',
				'text_color'       => '#CC1818',
			),
			'no_fulfillments'     => array(
				'label'            => __( 'No fulfillments', 'woocommerce' ),
				'background_color' => '#F0F0F0',
				'text_color'       => '#2F2F2F',
			),
		);
	}

	/**
	 * Get the default fulfillment statuses.
	 *
	 * This method provides the default fulfillment statuses that can be used
	 * in the WooCommerce Fulfillments system. It can be filtered using the
	 * `woocommerce_fulfillment_fulfillment_statuses` filter.
	 *
	 * @return array An associative array of default fulfillment statuses.
	 */
	protected static function get_default_fulfillment_statuses(): array {
		return array(
			'fulfilled'   => array(
				'label'            => __( 'Fulfilled', 'woocommerce' ),
				'is_fulfilled'     => true,
				'background_color' => '#C6E1C6',
				'text_color'       => '#13550F',
			),
			'unfulfilled' => array(
				'label'            => __( 'Unfulfilled', 'woocommerce' ),
				'is_fulfilled'     => false,
				'background_color' => '#FBE5E5',
				'text_color'       => '#CC1818',
			),
		);
	}

	/**
	 * Calculate the S10 check digit for UPU tracking numbers.
	 *
	 * @param string $tracking_number The tracking number without the check digit.
	 *
	 * @return bool True if the check digit is valid, false otherwise.
	 */
	public static function check_s10_upu_format( string $tracking_number ): bool {
		if ( preg_match( '/^[A-Z]{2}\d{9}[A-Z]{2}$/', $tracking_number ) ) {
			// The tracking number is in the UPU S10 format.
			$tracking_number = substr( $tracking_number, 2, -2 );
		} elseif ( ! preg_match( '/^\d{9}$/', $tracking_number ) ) {
			// Ensure the tracking number is exactly 9 digits.
			return false;
		}

		// Define the weights for the S10 check digit calculation.
		$weights = array( 8, 6, 4, 2, 3, 5, 9, 7 );
		$sum     = 0;

		// Calculate the weighted sum of the digits.
		for ( $i = 0; $i < 8; $i++ ) {
			$sum += $weights[ $i ] * (int) $tracking_number[ $i ];
		}

		// Calculate the check digit.
		$check_digit = 11 - ( $sum % 11 );
		if ( 10 === $check_digit ) {
			$check_digit = 0;
		} elseif ( 11 === $check_digit ) {
			$check_digit = 5;
		}

		// Validate the check digit against the last digit of the tracking number.
		return (int) $tracking_number[8] === $check_digit;
	}

	/**
	 * Validate UPS 1Z tracking number using Mod 10 check digit.
	 *
	 * @param string $tracking_number The UPS 1Z tracking number.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_ups_1z_check_digit( string $tracking_number ): bool {
		if ( ! preg_match( '/^1Z[0-9A-Z]{15,16}$/', $tracking_number ) ) {
			return false;
		}

		// Extract the trackable part (remove 1Z prefix).
		$trackable   = substr( $tracking_number, 2 );
		$check_digit = (int) substr( $trackable, -1 );
		$trackable   = substr( $trackable, 0, -1 );

		$sum          = 0;
		$odd_position = true;

		// Process each character from right to left.
		for ( $i = strlen( $trackable ) - 1; $i >= 0; $i-- ) {
			$char  = $trackable[ $i ];
			$value = is_numeric( $char ) ? (int) $char : ord( $char ) - 55; // A=10, B=11, etc.

			if ( $odd_position ) {
				$value *= 2;
				if ( $value > 9 ) {
					$value = (int) ( $value / 10 ) + ( $value % 10 );
				}
			}

			$sum         += $value;
			$odd_position = ! $odd_position;
		}

		$calculated_check = ( 10 - ( $sum % 10 ) ) % 10;
		return $calculated_check === $check_digit;
	}

	/**
	 * Validate Mod 7 check digit for numeric tracking numbers.
	 *
	 * @param string $tracking_number The numeric tracking number.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_mod7_check_digit( string $tracking_number ): bool {
		if ( ! preg_match( '/^\d+$/', $tracking_number ) || strlen( $tracking_number ) < 2 ) {
			return false;
		}

		$check_digit  = (int) substr( $tracking_number, -1 );
		$number       = substr( $tracking_number, 0, -1 );
		$sum          = 0;
		$weights      = array( 3, 1, 3, 1, 3, 1, 3 ); // Mod 7 weights.
		$weight_index = 0;
		// Process each digit from right to left.
		for ( $i = strlen( $number ) - 1; $i >= 0; $i-- ) {
			$digit = (int) $number[ $i ];
			$sum  += $digit * $weights[ $weight_index % count( $weights ) ];
			++$weight_index;
		}
		$calculated_check = $sum % 7;
		if ( 0 === $calculated_check ) {
			$calculated_check = 7; // If the sum is a multiple of 7, the check digit is 7.
		}
		return $calculated_check === $check_digit;
	}

	/**
	 * Validate Mod 10 check digit for numeric tracking numbers.
	 *
	 * @param string $tracking_number The numeric tracking number.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_mod10_check_digit( string $tracking_number ): bool {
		if ( ! preg_match( '/^\d+$/', $tracking_number ) || strlen( $tracking_number ) < 2 ) {
			return false;
		}

		$check_digit = (int) substr( $tracking_number, -1 );
		$number      = substr( $tracking_number, 0, -1 );

		$sum          = 0;
		$odd_position = true;

		// Process each digit from right to left.
		for ( $i = strlen( $number ) - 1; $i >= 0; $i-- ) {
			$digit = (int) $number[ $i ];

			if ( $odd_position ) {
				$digit *= 2;
				if ( $digit > 9 ) {
					$digit = (int) ( $digit / 10 ) + ( $digit % 10 );
				}
			}

			$sum         += $digit;
			$odd_position = ! $odd_position;
		}

		$calculated_check = ( 10 - ( $sum % 10 ) ) % 10;
		return $calculated_check === $check_digit;
	}

	/**
	 * Validate Mod 11 check digit for tracking numbers (used by DHL).
	 *
	 * @param string $tracking_number The tracking number.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_mod11_check_digit( string $tracking_number ): bool {
		if ( ! preg_match( '/^\d+$/', $tracking_number ) || strlen( $tracking_number ) < 2 ) {
			return false;
		}

		$check_digit = (int) substr( $tracking_number, -1 );
		$number      = substr( $tracking_number, 0, -1 );

		$weights      = array( 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 );
		$sum          = 0;
		$weight_index = 0;

		// Process each digit from right to left.
		for ( $i = strlen( $number ) - 1; $i >= 0; $i-- ) {
			$digit = (int) $number[ $i ];
			$sum  += $digit * $weights[ $weight_index % count( $weights ) ];
			++$weight_index;
		}

		$calculated_check = 11 - ( $sum % 11 );
		if ( 10 === $calculated_check ) {
			$calculated_check = 0;
		} elseif ( 11 === $calculated_check ) {
			$calculated_check = 5;
		}

		return $calculated_check === $check_digit;
	}

	/**
	 * Validate FedEx check digit for 12/14-digit tracking numbers.
	 *
	 * @param string $tracking_number The FedEx tracking number.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_fedex_check_digit( string $tracking_number ): bool {
		if ( ! preg_match( '/^\d{12}$/', $tracking_number ) ) {
			return false;
		}
		$digits           = str_split( substr( $tracking_number, 0, 11 ) );
		$multipliers      = array( 3, 1, 7 );
		$sum              = 0;
		$multiplier_index = 0;
		for ( $i = 10; $i >= 0; $i-- ) {
			$sum             += $digits[ $i ] * $multipliers[ $multiplier_index ];
			$multiplier_index = ( ++$multiplier_index ) % 3;
		}
		$check = $sum % 11;
		if ( 10 === $check ) {
			$check = 0;
		}
		return intval( $tracking_number[11] ) === $check;
	}
}
