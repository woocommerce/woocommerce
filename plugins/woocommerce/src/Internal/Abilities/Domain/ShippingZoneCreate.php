<?php
/**
 * Shipping zone create ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ShippingZoneAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a shipping zone without methods.
 *
 * Not an ActionableAbility, which would make the zone a resource that extensions add fields to.
 */
class ShippingZoneCreate extends AbstractDomainAbility implements AbilityDefinition {

	use ShippingZoneAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/shipping-zone-create';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Create shipping zone', 'woocommerce' ),
			'description'         => __( 'Create a shipping zone for a set of regions. It has no methods yet; add them with shipping-zone-method-create. A customer gets the first zone in order that matches their address.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'name'      => array(
						'type'        => 'string',
						'description' => __( 'Zone name, shown only to the store team.', 'woocommerce' ),
					),
					'locations' => array(
						'type'        => 'array',
						'description' => __( 'Regions the zone covers.', 'woocommerce' ),
						'minItems'    => 1,
						'items'       => array(
							'type'                 => 'object',
							'properties'           => array(
								'code' => array(
									'type'        => 'string',
									'description' => __( 'ISO country code such as DE, COUNTRY:STATE such as US:CA, continent code such as EU, or a postcode, a range such as 10000...19999, or a wildcard such as 10*.', 'woocommerce' ),
									'minLength'   => 1,
									'maxLength'   => 200,
								),
								'type' => array(
									'type' => 'string',
									'enum' => array( 'country', 'state', 'postcode', 'continent' ),
								),
							),
							'required'             => array( 'code', 'type' ),
							'additionalProperties' => false,
						),
					),
					'order'     => array(
						'type'        => 'integer',
						'description' => __( 'Position among the zones. Lower is matched first.', 'woocommerce' ),
						'minimum'     => 0,
					),
				),
				'required'             => array( 'name', 'locations' ),
				'additionalProperties' => false,
			),
			'output_schema'       => self::get_entity_output_schema( 'zone', self::get_zone_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_edit_shipping' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Create the zone.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$disabled = self::get_shipping_disabled_error();
		if ( null !== $disabled ) {
			return $disabled;
		}

		$name = sanitize_text_field( $input['name'] );
		if ( '' === $name ) {
			return new \WP_Error( 'woocommerce_ability_shipping_zone_name_empty', __( 'The zone name is empty.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$locations = array();
		$invalid   = array();
		foreach ( $input['locations'] ?? array() as $location ) {
			$code  = sanitize_text_field( $location['code'] );
			$known = self::get_known_location_code( $code, $location['type'] );
			if ( null === $known ) {
				$invalid[] = sprintf( '%s (%s)', $code, $location['type'] );
				continue;
			}
			$locations[] = array(
				'code' => $known,
				'type' => $location['type'],
			);
		}

		if ( ! empty( $invalid ) ) {
			return new \WP_Error(
				'woocommerce_ability_shipping_zone_location_invalid',
				/* translators: %s: location codes with their types, such as "UK (country), US:ZZ (state)". */
				sprintf( __( 'WooCommerce does not know these location codes, or they do not fit their type: %s. Use ISO country codes such as GB, COUNTRY:STATE codes WooCommerce lists for the country, and continent codes such as EU.', 'woocommerce' ), implode( ', ', $invalid ) ),
				array( 'status' => 400 )
			);
		}

		$zone = new \WC_Shipping_Zone( null );
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $input['order'] ?? 0 );
		$zone->set_locations( $locations );
		$zone->save();

		if ( ! $zone->get_id() ) {
			return new \WP_Error( 'woocommerce_ability_shipping_zone_create_failed', __( 'The shipping zone could not be saved.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		return array( 'zone' => self::format_zone_for_response( new \WC_Shipping_Zone( $zone->get_id() ) ) );
	}

	/**
	 * Get the code as WooCommerce lists it, matched without case, so a saved zone matches addresses.
	 * Postcode patterns pass as given. A state must be one WooCommerce lists for its country.
	 *
	 * @param string $code Location code.
	 * @param string $type Location type.
	 * @return string|null Null when WooCommerce does not know the code for the type.
	 */
	private static function get_known_location_code( string $code, string $type ): ?string {
		$countries = WC()->countries;
		if ( 'country' === $type ) {
			return $countries->country_exists( strtoupper( $code ) ) ? strtoupper( $code ) : null;
		}

		if ( 'continent' === $type ) {
			$known = array_keys( $countries->get_continents() );
		} elseif ( 'state' === $type ) {
			$parts   = explode( ':', $code, 2 );
			$country = strtoupper( $parts[0] );
			$states  = isset( $parts[1] ) ? $countries->get_states( $country ) : array();
			$known   = array_map( static fn( $state ): string => $country . ':' . $state, array_keys( is_array( $states ) ? $states : array() ) );
		} else {
			return $code;
		}

		foreach ( $known as $candidate ) {
			if ( 0 === strcasecmp( (string) $candidate, $code ) ) {
				return (string) $candidate;
			}
		}

		return null;
	}
}
