<?php
/**
 * Data fields class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Resources;

use Automattic\WooCommerce\Abilities\AbilityResources;

defined( 'ABSPATH' ) || exit;

/**
 * Field helpers for resources of WC_Data objects, such as `order`. Props are read
 * through their getters in the view context, so the getter filters apply.
 *
 * @since 11.4.0
 */
class DataFields {

	/**
	 * A get_callback that reads a prop through its getter.
	 *
	 * @param string        $prop       Prop name, such as `total` for get_total().
	 * @param callable|null $transform  Optional. Changes the value the getter returns, such as false to null.
	 * @param string|null   $applies_to Optional. Class the object must be an instance of. Other objects return null.
	 * @return callable
	 */
	public static function getter( string $prop, ?callable $transform = null, ?string $applies_to = null ): callable {
		$getter = 'get_' . $prop;
		return static function ( \WC_Data $subject ) use ( $getter, $transform, $applies_to ) {
			if ( null !== $applies_to && ! $subject instanceof $applies_to ) {
				return null;
			}
			$value = $subject->$getter();
			return null === $transform ? $value : $transform( $value );
		};
	}

	/**
	 * Register a local and a GMT field for each date prop, such as `date_created` and `date_created_gmt`.
	 *
	 * @param string $resource_name Resource.
	 * @param string ...$names      Date names, such as `created` for get_date_created().
	 */
	public static function register_dates( string $resource_name, string ...$names ): void {
		$schema = array(
			'type'   => array( 'string', 'null' ),
			'format' => 'date-time',
		);
		foreach ( $names as $name ) {
			$getter = 'get_date_' . $name;
			AbilityResources::register_field(
				$resource_name,
				'date_' . $name,
				array(
					'schema'       => $schema,
					'get_callback' => static fn( \WC_Data $subject ) => wc_rest_prepare_date_response( $subject->$getter(), false ),
				)
			);
			AbilityResources::register_field(
				$resource_name,
				'date_' . $name . '_gmt',
				array(
					'schema'       => $schema,
					'get_callback' => static fn( \WC_Data $subject ) => wc_rest_prepare_date_response( $subject->$getter() ),
				)
			);
		}
	}

	/**
	 * Register the `currency` code and its `currency_symbol`.
	 *
	 * @param string   $resource_name Resource.
	 * @param callable $get_code      Returns the currency code for an object.
	 */
	public static function register_currency( string $resource_name, callable $get_code ): void {
		AbilityResources::register_field(
			$resource_name,
			'currency',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => array_keys( get_woocommerce_currencies() ),
				),
				'get_callback' => $get_code,
			)
		);
		AbilityResources::register_field(
			$resource_name,
			'currency_symbol',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Data $subject ) => html_entity_decode(
					get_woocommerce_currency_symbol( $get_code( $subject ) ),
					ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
				),
			)
		);
	}
}
