<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegulatoryFields\Formats\FormatInterface;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use WC_Order;
use WC_Order_Item_Shipping;
use WP_Error;

/**
 * Fields a country's rules require a store to collect with an order, such as the CPF/CNPJ in Brazil.
 *
 * Core owns the list of fields and decides when each one applies. Values are stored as order meta.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 *
 * @phpstan-type RegulatoryField array{label: string, required: bool, format: class-string<FormatInterface>}
 */
class RegulatoryFields {

	/**
	 * Prefix of the order meta key a field's value is stored under.
	 */
	public const META_PREFIX = '_wc_regulatory_';

	/**
	 * Session key the values typed at checkout are kept under until the order is placed.
	 */
	private const SESSION_KEY = 'store_api_regulatory_fields';

	/**
	 * Field definitions keyed by country code, then by field ID.
	 *
	 * @var array<string, array<string, RegulatoryField>>|null
	 */
	private ?array $definitions = null;

	/**
	 * Get the fields to collect for an order delivered to a country.
	 *
	 * @param string $country Country code.
	 * @return array<string, RegulatoryField> Fields keyed by ID.
	 */
	public function get_fields_for_country( string $country ): array {
		return $this->get_definitions()[ $country ] ?? array();
	}

	/**
	 * Get the fields to collect for the current cart.
	 *
	 * @return array<string, RegulatoryField> Fields keyed by ID.
	 */
	public function get_fields_for_cart(): array {
		return $this->get_fields_for_country( $this->get_cart_country() );
	}

	/**
	 * Get the fields to collect for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, RegulatoryField> Fields keyed by ID.
	 */
	public function get_fields_for_order( WC_Order $order ): array {
		return $this->get_fields_for_country( $this->get_order_country( $order ) );
	}

	/**
	 * Get the country the current cart is delivered to.
	 *
	 * This is the shipping country, the pickup location's country when the shopper collects the order,
	 * or the billing country when nothing is shipped.
	 *
	 * @return string Country code, or an empty string when there is no cart or customer.
	 */
	public function get_cart_country(): string {
		$cart     = WC()->cart;
		$customer = WC()->customer;

		if ( ! $cart || ! $customer ) {
			return '';
		}

		if ( ! $cart->needs_shipping() ) {
			return (string) $customer->get_billing_country();
		}

		$chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
		$chosen_methods = is_array( $chosen_methods ) ? array_filter( $chosen_methods, 'is_string' ) : array();

		if ( $this->are_all_local_pickup( array_map( array( $this, 'get_method_id' ), $chosen_methods ) ) ) {
			return $this->get_cart_pickup_country( (string) reset( $chosen_methods ) );
		}

		$shipping_country = (string) $customer->get_shipping_country();

		return '' !== $shipping_country ? $shipping_country : (string) $customer->get_billing_country();
	}

	/**
	 * Get the country an order is delivered to, following the same rule as the cart.
	 *
	 * @param WC_Order $order Order.
	 * @return string Country code.
	 */
	public function get_order_country( WC_Order $order ): string {
		// The woocommerce_order_get_items filter can add items of any type to the list.
		$shipping_lines = array_filter(
			$order->get_shipping_methods(),
			static fn( $item ) => $item instanceof WC_Order_Item_Shipping
		);

		if ( empty( $shipping_lines ) ) {
			return $order->get_billing_country();
		}

		$method_ids = array();
		foreach ( $shipping_lines as $shipping_line ) {
			$method_ids[] = $shipping_line->get_method_id();
		}

		if ( $this->are_all_local_pickup( $method_ids ) ) {
			$pickup_address = reset( $shipping_lines )->get_meta( '_pickup_location_address' );
			$pickup_country = is_array( $pickup_address ) ? (string) ( $pickup_address['country'] ?? '' ) : '';

			return '' !== $pickup_country ? $pickup_country : WC()->countries->get_base_country();
		}

		$shipping_country = $order->get_shipping_country();

		return '' !== $shipping_country ? $shipping_country : $order->get_billing_country();
	}

	/**
	 * Clean and check the values submitted for a set of fields.
	 *
	 * @param array<string, RegulatoryField> $fields Fields keyed by ID.
	 * @param mixed                          $values Submitted values keyed by field ID.
	 * @param WP_Error                       $errors Receives one error per field that is missing or not valid, with the field ID as its code.
	 * @return array<string, string> Normalized values keyed by field ID. Fields left empty are not included.
	 */
	public function get_valid_values( array $fields, $values, WP_Error $errors ): array {
		$values = is_array( $values ) ? $values : array();
		$valid  = array();

		foreach ( $fields as $field_id => $field ) {
			$format = $this->get_format( $field );
			$value  = $values[ $field_id ] ?? '';
			$value  = is_scalar( $value ) ? $format->normalize( sanitize_text_field( (string) $value ) ) : '';

			if ( '' === $value ) {
				if ( $field['required'] ) {
					/* translators: %s: field label, such as CPF/CNPJ. */
					$errors->add( $field_id, sprintf( __( '%s is a required field.', 'woocommerce' ), $field['label'] ) );
				}
				continue;
			}

			if ( ! $format->is_valid( $value ) ) {
				$errors->add( $field_id, $format->get_invalid_message() );
				continue;
			}

			$valid[ $field_id ] = $value;
		}

		return $valid;
	}

	/**
	 * Replace the values stored on an order. A field without a value in the list is cleared.
	 *
	 * The order is not saved.
	 *
	 * @param WC_Order              $order  Order.
	 * @param array<string, string> $values Values keyed by field ID, as returned by get_valid_values().
	 */
	public function set_order_values( WC_Order $order, array $values ): void {
		foreach ( array_keys( $this->get_all_fields() ) as $field_id ) {
			$value = $values[ $field_id ] ?? '';

			if ( '' === $value ) {
				$order->delete_meta_data( self::META_PREFIX . $field_id );
			} else {
				$order->update_meta_data( self::META_PREFIX . $field_id, $value );
			}
		}
	}

	/**
	 * Get the values stored on an order, including for fields that no longer apply to it.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, string> Normalized values keyed by field ID. Fields without a value are not included.
	 */
	public function get_order_values( WC_Order $order ): array {
		$values = array();

		foreach ( array_keys( $this->get_all_fields() ) as $field_id ) {
			$value = $order->get_meta( self::META_PREFIX . $field_id );

			if ( is_string( $value ) && '' !== $value ) {
				$values[ $field_id ] = $value;
			}
		}

		return $values;
	}

	/**
	 * Keep what the shopper has typed so far in the session, so a checkout page reload does not lose it.
	 *
	 * The values are kept as typed and are not validated until the order is placed.
	 *
	 * @param mixed $values Typed values keyed by field ID.
	 */
	public function set_session_values( $values ): void {
		if ( ! WC()->session ) {
			return;
		}

		$values  = is_array( $values ) ? $values : array();
		$cleaned = array();

		foreach ( array_keys( $this->get_all_fields() ) as $field_id ) {
			$value = $values[ $field_id ] ?? '';

			if ( is_scalar( $value ) && '' !== (string) $value ) {
				$cleaned[ $field_id ] = sanitize_text_field( (string) $value );
			}
		}

		WC()->session->set( self::SESSION_KEY, empty( $cleaned ) ? null : $cleaned );
	}

	/**
	 * Get what the shopper has typed so far in this session.
	 *
	 * @return array<string, string> Typed values keyed by field ID.
	 */
	public function get_session_values(): array {
		$values = WC()->session ? WC()->session->get( self::SESSION_KEY ) : null;

		if ( ! is_array( $values ) ) {
			return array();
		}

		return array_filter( array_intersect_key( $values, $this->get_all_fields() ), 'is_string' );
	}

	/**
	 * Get the values stored on an order, ready to show to people.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, array{label: string, value: string}> Label and formatted value keyed by field ID.
	 */
	public function get_order_display_values( WC_Order $order ): array {
		$all_fields = $this->get_all_fields();
		$display    = array();

		foreach ( $this->get_order_values( $order ) as $field_id => $value ) {
			$display[ $field_id ] = array(
				'label' => $all_fields[ $field_id ]['label'],
				'value' => $this->get_format( $all_fields[ $field_id ] )->format( $value ),
			);
		}

		return $display;
	}

	/**
	 * Get every field of every country.
	 *
	 * @return array<string, RegulatoryField> Fields keyed by ID.
	 */
	public function get_all_fields(): array {
		return array_merge( array(), ...array_values( $this->get_definitions() ) );
	}

	/**
	 * Get the object that cleans, checks and formats a field's value.
	 *
	 * @param array $field Field definition.
	 * @phpstan-param RegulatoryField $field
	 * @return FormatInterface
	 */
	public function get_format( array $field ): FormatInterface {
		return wc_get_container()->get( $field['format'] );
	}

	/**
	 * Load the field definitions.
	 *
	 * @return array<string, array<string, RegulatoryField>>
	 */
	private function get_definitions(): array {
		if ( null === $this->definitions ) {
			$definitions       = include WC()->plugin_path() . '/i18n/regulatory-fields.php';
			$this->definitions = is_array( $definitions ) ? $definitions : array();
		}

		return $this->definitions;
	}

	/**
	 * Get the method ID from a chosen shipping rate ID, such as `pickup_location` from `pickup_location:0`.
	 *
	 * @param string $rate_id Shipping rate ID.
	 * @return string
	 */
	private function get_method_id( string $rate_id ): string {
		return explode( ':', $rate_id )[0];
	}

	/**
	 * Whether there is at least one shipping method and every one is a local pickup method.
	 *
	 * @param string[] $method_ids Shipping method IDs.
	 * @return bool
	 */
	private function are_all_local_pickup( array $method_ids ): bool {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in WC_Abstract_Order::get_tax_location().
		$filtered_ids = apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) );
		$pickup_ids   = array_merge( is_array( $filtered_ids ) ? $filtered_ids : array(), LocalPickupUtils::get_local_pickup_method_ids() );

		return ! empty( $method_ids ) && empty( array_diff( $method_ids, $pickup_ids ) );
	}

	/**
	 * Get the country of the pickup location chosen in the cart.
	 *
	 * Only the built-in pickup locations have an address. Other pickup methods use the store's country.
	 *
	 * @param string $rate_id Chosen shipping rate ID, such as `pickup_location:0`.
	 * @return string Country code.
	 */
	private function get_cart_pickup_country( string $rate_id ): string {
		$parts = explode( ':', $rate_id );

		if ( 'pickup_location' === $parts[0] ) {
			$locations = get_option( 'pickup_location_pickup_locations', array() );
			$country   = is_array( $locations ) ? ( $locations[ $parts[1] ?? 0 ]['address']['country'] ?? '' ) : '';

			if ( is_string( $country ) && '' !== $country ) {
				return $country;
			}
		}

		return WC()->countries->get_base_country();
	}
}
