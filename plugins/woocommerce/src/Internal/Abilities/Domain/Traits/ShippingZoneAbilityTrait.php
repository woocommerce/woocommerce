<?php
/**
 * Shipping zone ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\ShippingZoneMethodService;

defined( 'ABSPATH' ) || exit;

/**
 * Formats zones and methods for the shipping abilities, and writes method changes.
 */
trait ShippingZoneAbilityTrait {

	/**
	 * Settings that the write abilities accept, keyed by method ID. Each list is a subset of the method's instance form fields.
	 *
	 * @var array<string, string[]>
	 */
	private static array $writable_method_settings = array(
		'flat_rate'     => array( 'title', 'tax_status', 'cost' ),
		'free_shipping' => array( 'title', 'requires', 'min_amount', 'ignore_discounts' ),
		'local_pickup'  => array( 'title', 'tax_status', 'cost' ),
	);

	/**
	 * Whether the method is one of Core's own. Extensions keep credentials in method settings under any name, so only these get settings.
	 *
	 * @param \WC_Shipping_Method $method Method.
	 * @return bool
	 */
	private static function is_core_method( \WC_Shipping_Method $method ): bool {
		return in_array( get_class( $method ), array( \WC_Shipping_Flat_Rate::class, \WC_Shipping_Free_Shipping::class, \WC_Shipping_Local_Pickup::class ), true );
	}

	/**
	 * Check shipping settings edit access.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_edit_shipping(): bool {
		return (bool) wc_rest_check_manager_permissions( 'settings', 'edit' );
	}

	/**
	 * Get the error for a write while shipping is turned off, when checkout would offer none of what it saves.
	 *
	 * @return \WP_Error|null
	 */
	private static function get_shipping_disabled_error(): ?\WP_Error {
		if ( wc_shipping_enabled() ) {
			return null;
		}

		return new \WP_Error(
			'woocommerce_ability_shipping_disabled',
			__( 'Shipping is turned off, so checkout would offer no shipping method. Turn it on under WooCommerce > Settings > General > Shipping location(s) first.', 'woocommerce' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Format a zone for ability output.
	 *
	 * @param \WC_Shipping_Zone $zone Zone.
	 * @return array
	 */
	private static function format_zone_for_response( \WC_Shipping_Zone $zone ): array {
		return array(
			'id'        => $zone->get_id(),
			'name'      => $zone->get_zone_name(),
			'order'     => $zone->get_zone_order(),
			'locations' => array_map(
				static function ( $location ): array {
					return array(
						'code' => (string) $location->code,
						'type' => (string) $location->type,
					);
				},
				array_values( $zone->get_zone_locations() )
			),
			'methods'   => array_map( static fn( \WC_Shipping_Method $method ): array => self::format_method( $method ), array_values( $zone->get_shipping_methods() ) ),
		);
	}

	/**
	 * Format a shipping method of a zone. Only Core methods get settings, limited to the
	 * fields Core declares, since extensions can add fields to them through filters.
	 *
	 * @param \WC_Shipping_Method $method Method.
	 * @return array
	 */
	private static function format_method( \WC_Shipping_Method $method ): array {
		$data = array(
			'instance_id' => (int) $method->get_instance_id(),
			'method_id'   => (string) $method->id,
			'title'       => (string) $method->get_title(),
			'enabled'     => 'yes' === $method->enabled,
			'order'       => (int) $method->method_order,
		);

		if ( self::is_core_method( $method ) ) {
			$class = get_class( $method );
			// A fresh instance, since a woocommerce_shipping_zone_shipping_methods callback can change the zone's one.
			$core = new $class( $method->get_instance_id() );
			$core->init_instance_settings();
			$settings = array();
			foreach ( array_keys( $core->instance_form_fields ) as $key ) {
				$value = $core->instance_settings[ $key ] ?? null;
				if ( 'title' !== $key && is_scalar( $value ) ) {
					$settings[ $key ] = (string) $value;
				}
			}
			$data['settings'] = (object) $settings;
		}

		return $data;
	}

	/**
	 * Read the method back after a write, so the response shows what was stored.
	 *
	 * @param int $instance_id Method instance ID.
	 * @return array|\WP_Error
	 */
	private static function get_saved_method_response( int $instance_id ) {
		$method = \WC_Shipping_Zones::get_shipping_method( $instance_id );
		if ( ! $method instanceof \WC_Shipping_Method ) {
			return new \WP_Error( 'woocommerce_ability_shipping_method_not_found', __( 'The shipping method was saved but could not be read back.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		return array( 'method' => self::format_method( $method ) );
	}

	/**
	 * Write the enabled state, order and settings of a Core method, the way the shipping settings screen saves them.
	 *
	 * @param \WC_Shipping_Method $method  Method of the zone.
	 * @param \WC_Shipping_Zone   $zone    Zone of the method.
	 * @param array               $input   Ability input with optional `enabled`, `order` and `settings`.
	 * @return null|\WP_Error
	 */
	private static function write_method_changes( \WC_Shipping_Method $method, \WC_Shipping_Zone $zone, array $input ): ?\WP_Error {
		$data = array_intersect_key( $input, array_flip( array( 'enabled', 'order' ) ) );

		if ( isset( $input['settings'] ) ) {
			$settings = self::prepare_method_settings( $method, $input['settings'] );
			if ( is_wp_error( $settings ) ) {
				return $settings;
			}
			// The setting validators unslash, as they expect the slashed form data of the settings screen.
			$data['settings'] = wp_slash( $settings );
		}

		if ( empty( $data ) ) {
			return null;
		}

		$result = wc_get_container()->get( ShippingZoneMethodService::class )->update_shipping_zone_method( $method, $method->get_instance_id(), $data, $zone->get_id() );

		// The service bumps the cache version only when the enabled state or order changes, and not when that fails after the settings were saved.
		\WC_Cache_Helper::get_transient_version( 'shipping', true );

		if ( is_wp_error( $result ) ) {
			$code = 'woocommerce_rest_shipping_method_invalid_setting' === $result->get_error_code() ? 'woocommerce_ability_shipping_method_setting_invalid' : 'woocommerce_ability_shipping_method_update_failed';
			return new \WP_Error( $code, $result->get_error_message(), array( 'status' => 'woocommerce_ability_shipping_method_setting_invalid' === $code ? 400 : 500 ) );
		}

		return null;
	}

	/**
	 * Check the settings against the method, and convert them to the values its settings form posts.
	 *
	 * @param \WC_Shipping_Method $method   Method.
	 * @param array               $settings Settings input.
	 * @return array|\WP_Error
	 */
	private static function prepare_method_settings( \WC_Shipping_Method $method, array $settings ) {
		$writable = self::$writable_method_settings[ $method->id ] ?? array();
		$unknown  = array_diff( array_keys( $settings ), $writable );
		if ( ! empty( $unknown ) ) {
			return new \WP_Error(
				'woocommerce_ability_shipping_method_setting_unknown',
				/* translators: 1: setting names, 2: shipping method ID, 3: setting names the method accepts. */
				sprintf( __( 'The %2$s method has no setting %1$s. It accepts: %3$s.', 'woocommerce' ), implode( ', ', $unknown ), $method->id, implode( ', ', $writable ) ),
				array( 'status' => 400 )
			);
		}

		// The price validator turns text without digits into an empty minimum, which every cart meets.
		if ( isset( $settings['min_amount'] ) ) {
			$min_amount = wc_format_decimal( $settings['min_amount'] );
			if ( ! is_numeric( $min_amount ) || (float) $min_amount < 0 ) {
				return new \WP_Error( 'woocommerce_ability_shipping_method_setting_invalid', __( 'The minimum order amount must be a number of 0 or more.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		// Free shipping ignores the minimum unless it is required, and an agent that sets one means it to apply.
		if ( 'free_shipping' === $method->id && isset( $settings['min_amount'] ) && ! isset( $settings['requires'] ) && '' === (string) $method->get_option( 'requires' ) ) {
			$settings['requires'] = 'min_amount';
		}

		// The checkbox validator reads any posted value as checked, and a missing one as unchecked.
		if ( array_key_exists( 'ignore_discounts', $settings ) ) {
			$settings['ignore_discounts'] = $settings['ignore_discounts'] ? 'yes' : null;
		}

		return $settings;
	}

	/**
	 * Get the zone, or an error when there is none with that ID.
	 *
	 * @param int $zone_id Zone ID. Zero is the zone for locations no other zone covers.
	 * @return \WC_Shipping_Zone|\WP_Error
	 */
	private static function get_zone_or_error( int $zone_id ) {
		$zone = \WC_Shipping_Zones::get_zone( $zone_id );
		if ( ! $zone instanceof \WC_Shipping_Zone || ( 0 !== $zone->get_id() && '' === $zone->get_zone_name() ) ) {
			return new \WP_Error(
				'woocommerce_ability_shipping_zone_not_found',
				/* translators: %d: shipping zone ID. */
				sprintf( __( 'There is no shipping zone with ID %d.', 'woocommerce' ), $zone_id ),
				array( 'status' => 404 )
			);
		}

		return $zone;
	}

	/**
	 * Get the error for a method that the write abilities do not change.
	 *
	 * @param string $method_id Shipping method ID.
	 * @return \WP_Error
	 */
	private static function get_unsupported_method_error( string $method_id ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_shipping_method_unsupported',
			/* translators: %s: shipping method ID. */
			sprintf( __( 'The %s method is not supported. Only the flat rate, free shipping and local pickup methods can be added or changed here.', 'woocommerce' ), $method_id ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Get the input schema of the method writes.
	 *
	 * @return array
	 */
	private static function get_method_write_input_properties(): array {
		return array(
			'enabled'  => array(
				'type'        => 'boolean',
				'description' => __( 'Whether customers can choose the method at checkout.', 'woocommerce' ),
			),
			'order'    => array(
				'type'        => 'integer',
				'description' => __( 'Position of the method in its zone. Lower comes first.', 'woocommerce' ),
				'minimum'     => 0,
			),
			'settings' => array(
				'type'                 => 'object',
				'description'          => __( 'Settings to change. Flat rate and local pickup take title, tax_status and cost. Free shipping takes title, requires, min_amount and ignore_discounts.', 'woocommerce' ),
				'properties'           => array(
					'title'            => array(
						'type'        => 'string',
						'description' => __( 'Name customers see at checkout.', 'woocommerce' ),
					),
					'tax_status'       => array(
						'type' => 'string',
						'enum' => array( 'taxable', 'none' ),
					),
					'cost'             => array(
						'type'        => 'string',
						'description' => __( 'Cost before tax, such as "5" or a formula like "10 + [qty] * 2". [qty] is the number of items, [cost] the order cost, and [fee percent="10" min_fee="2"] a percentage fee.', 'woocommerce' ),
					),
					'requires'         => array(
						'type'        => 'string',
						'description' => __( 'What earns free shipping: nothing, a free shipping coupon, the minimum amount, either one, or both. Sending min_amount alone on a method with no requirement sets this to min_amount.', 'woocommerce' ),
						'enum'        => array( '', 'coupon', 'min_amount', 'either', 'both' ),
					),
					'min_amount'       => array(
						'type'        => 'string',
						'description' => __( 'Order amount that earns free shipping, as a decimal string without a currency symbol.', 'woocommerce' ),
					),
					'ignore_discounts' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the minimum applies to the order amount before coupon discounts.', 'woocommerce' ),
					),
				),
				'additionalProperties' => false,
			),
		);
	}

	/**
	 * Get the schema of a zone in a response.
	 *
	 * @return array
	 */
	private static function get_zone_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'        => array( 'type' => 'integer' ),
				'name'      => array( 'type' => 'string' ),
				'order'     => array( 'type' => 'integer' ),
				'locations' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'code' => array(
								'type'        => 'string',
								'description' => __( 'Country code, COUNTRY:STATE, postcode pattern or continent code, by type.', 'woocommerce' ),
							),
							'type' => array(
								'type' => 'string',
								'enum' => array( 'country', 'state', 'postcode', 'continent' ),
							),
						),
						'additionalProperties' => false,
					),
				),
				'methods'   => array(
					'type'  => 'array',
					'items' => self::get_method_output_schema(),
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the schema of a shipping method in a response.
	 *
	 * @return array
	 */
	private static function get_method_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'instance_id' => array( 'type' => 'integer' ),
				'method_id'   => array( 'type' => 'string' ),
				'title'       => array( 'type' => 'string' ),
				'enabled'     => array( 'type' => 'boolean' ),
				'order'       => array( 'type' => 'integer' ),
				'settings'    => array(
					'type'                 => 'object',
					'description'          => __( 'Settings such as cost or min_amount, as stored. Only for the flat rate, free shipping and local pickup methods.', 'woocommerce' ),
					'additionalProperties' => array( 'type' => 'string' ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
