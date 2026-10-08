<?php
/**
 * Ability product types class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;

defined( 'ABSPATH' ) || exit;

/**
 * Product type aliases that extensions add to the product abilities, next to
 * the Core aliases such as `physical` and `affiliate`. An alias names a
 * product type and lists the product fields that it accepts. The product
 * abilities then create, update and query products of that type.
 *
 * @since 11.3.0
 */
class AbilityProductTypes {

	/**
	 * Aliases that Core defines. An extension cannot replace them.
	 */
	private const CORE_ALIASES = array( 'physical', 'virtual', 'digital', 'affiliate', 'grouped' );

	/**
	 * Alias configurations keyed by alias.
	 *
	 * @var array<string, array{wc_type: string, fields: array<int, string>, product_props: array<string, mixed>, query_props: array<string, mixed>}>
	 */
	private static array $aliases = array();

	/**
	 * Register a product type alias for a product type that an extension adds.
	 *
	 * Register it before the abilities register, on `wp_abilities_api_init` at a priority lower
	 * than 10, so their schemas list it. Agents send the alias, so use a plain name such as
	 * `subscription`. A later registration of the same alias replaces it.
	 *
	 * @since 11.3.0
	 *
	 * @param string $alias  Alias that agents send as `product_type_alias`. Lowercase letters, numbers, dashes and underscores.
	 * @param array  $config {
	 *     Alias configuration.
	 *
	 *     @type string $wc_type       Product type of the products, for example `subscription`.
	 *     @type array  $fields        Product fields that the type accepts, such as `name` and `regular_price`.
	 *     @type array  $product_props Optional. Product props that a product of the alias has, such as
	 *                                 `array( 'virtual' => true )`. Core sets them on create and on an update
	 *                                 with the alias. When several aliases have the same type, a product
	 *                                 gets the first alias whose true or false props match it, or else
	 *                                 the first alias of its type.
	 *     @type array  $query_props   Optional. Product props that a query by the alias filters on.
	 * }
	 */
	public static function register( string $alias, array $config ): void {
		if ( in_array( $alias, self::CORE_ALIASES, true ) || sanitize_key( $alias ) !== $alias ) {
			wc_doing_it_wrong( __METHOD__, sprintf( 'The alias "%s" is a Core alias or is not a valid key.', $alias ), '11.3.0' );
			return;
		}
		if ( ! is_string( $config['wc_type'] ?? null ) || '' === $config['wc_type'] ) {
			wc_doing_it_wrong( __METHOD__, 'The "wc_type" argument must be a product type.', '11.3.0' );
			return;
		}
		if ( ! is_array( $config['fields'] ?? null ) || empty( $config['fields'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "fields" argument must list the product fields that the type accepts.', '11.3.0' );
			return;
		}

		self::$aliases[ $alias ] = array(
			'wc_type'       => $config['wc_type'],
			'fields'        => array_values( $config['fields'] ),
			'product_props' => is_array( $config['product_props'] ?? null ) ? $config['product_props'] : array(),
			'query_props'   => is_array( $config['query_props'] ?? null ) ? $config['query_props'] : array(),
		);
	}

	/**
	 * The registered aliases, keyed by alias. Nothing is returned when the feature is off.
	 *
	 * @internal
	 *
	 * @return array<string, array{wc_type: string, fields: array<int, string>, product_props: array<string, mixed>, query_props: array<string, mixed>}>
	 */
	public static function get_all(): array {
		return AbilityContracts::is_enabled() ? self::$aliases : array();
	}

	/**
	 * The registered alias of a product: the first alias of its type whose props match it, or else
	 * the first alias of its type. Null when no alias has its type.
	 *
	 * @internal
	 *
	 * @param \WC_Product $product Product.
	 * @return string|null
	 */
	public static function get_alias_for( \WC_Product $product ): ?string {
		$first = null;
		foreach ( self::get_all() as $alias => $config ) {
			if ( $product->get_type() !== $config['wc_type'] ) {
				continue;
			}
			if ( self::props_match( $product, $config['product_props'] ) ) {
				return $alias;
			}
			$first = $first ?? $alias;
		}
		return $first;
	}

	/**
	 * Whether the true or false props of an alias match a product.
	 *
	 * @param \WC_Product          $product Product.
	 * @param array<string, mixed> $props   Alias product props.
	 * @return bool
	 */
	private static function props_match( \WC_Product $product, array $props ): bool {
		foreach ( $props as $prop => $value ) {
			$getter = 'get_' . $prop;
			if ( is_bool( $value ) && is_callable( array( $product, $getter ) ) && (bool) $product->{$getter}( 'edit' ) !== $value ) {
				return false;
			}
		}
		return true;
	}
}
