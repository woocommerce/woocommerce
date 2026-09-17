<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\FilterUrlParam;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for managing all filter params.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class Params implements FilterUrlParam {
	/**
	 * Hold the filter params.
	 *
	 * @var array
	 */
	private static $params = array();

	/**
	 * Get the param keys.
	 *
	 * @return array
	 */
	public function get_param_keys(): array {
		$keys = array();
		foreach ( $this->get_params() as $type => $params ) {
			$keys = array_merge( $keys, array_values( $params ) );
			if ( 'attribute' === $type ) {
				$query_type_params = array_map(
					function ( $param ) {
						return 'query_type_' . $param;
					},
					array_keys( $params )
				);
				$keys              = array_merge( $keys, $query_type_params );
			}
		}

		return $keys;
	}

	/**
	 * Get the param.
	 *
	 * @param string $type The type of param to get.
	 * @return array
	 */
	public function get_param( string $type ): array {
		return $this->get_params()[ $type ] ?? array();
	}

	/**
	 * Get all params, with the taxonomy map passed through its filter.
	 *
	 * The cached map is left unfiltered and the filter is applied on read, so that callbacks
	 * registered after the cache was warmed still take effect.
	 *
	 * @return array
	 */
	private function get_params(): array {
		if ( empty( self::$params ) ) {
			$this->init_params();
		}

		$params             = self::$params;
		$params['taxonomy'] = $this->filter_taxonomy_params( $params['taxonomy'] ?? array() );

		return $params;
	}

	/**
	 * Pass the taxonomy param map through its filter, discarding unusable values.
	 *
	 * @param array $taxonomy_params Map of taxonomy name to URL query parameter name.
	 * @return array
	 */
	private function filter_taxonomy_params( array $taxonomy_params ): array {
		/**
		 * Filters the URL query parameter that product filters claim for each product taxonomy.
		 *
		 * The map is keyed by taxonomy name, with the URL query parameter as the value, for
		 * example `array( 'product_cat' => 'categories' )`. Use it to rename a parameter that
		 * collides with one another plugin already owns.
		 *
		 * Prefer renaming a parameter over removing its entry: `ProductFilterTaxonomy::render()`
		 * returns an empty string for a taxonomy that is missing from this map, so releasing a
		 * parameter also hides the core filter block for that taxonomy.
		 *
		 * Register the callback before `parse_request` runs, on `plugins_loaded` or `init`. The
		 * param names are registered as public query vars through the one-shot `query_vars`
		 * filter, which WordPress fires once per request in `WP::parse_request()`; a callback
		 * added after that renames the param here but never registers it, so `get_query_var()`
		 * returns an empty string and filtering stops working. For the same reason the map must
		 * be stable for the whole request: do not vary it by the current query or request URI.
		 *
		 * A return value that is not an array is discarded in favour of the unfiltered map, as
		 * are entries whose taxonomy name or param name is not a non-empty string.
		 *
		 * @hook woocommerce_product_filter_taxonomy_params
		 * @since 11.3.0
		 *
		 * @param array $taxonomy_params Map of taxonomy name to URL query parameter name.
		 * @return array Map of taxonomy name to URL query parameter name.
		 */
		$filtered = apply_filters( 'woocommerce_product_filter_taxonomy_params', $taxonomy_params );

		if ( ! is_array( $filtered ) ) {
			return $taxonomy_params;
		}

		$valid = array();

		foreach ( $filtered as $taxonomy => $param ) {
			if ( is_string( $taxonomy ) && '' !== $taxonomy && is_string( $param ) && '' !== $param ) {
				$valid[ $taxonomy ] = $param;
			}
		}

		return $valid;
	}

	/**
	 * Initialize the params.
	 *
	 * @return void
	 */
	private function init_params(): void {
		self::$params = array(
			'price'     => array(
				'min_price',
				'max_price',
			),
			'rating'    => array(
				'rating_filter',
			),
			'status'    => array(
				'filter_stock_status',
			),
			'attribute' => $this->get_attribute_params(),
			'taxonomy'  => $this->get_taxonomy_params(),
		);
	}

	/**
	 * Get the attribute params.
	 *
	 * @return array
	 */
	private function get_attribute_params(): array {
		$params = array();
		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$params[ $attribute->attribute_name ] = "filter_$attribute->attribute_name";
		}

		return $params;
	}

	/**
	 * Get the taxonomy params.
	 *
	 * @return array
	 */
	private function get_taxonomy_params(): array {
		$public_product_taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);

		// We have control over built-in taxonomies, so we can use prettier names.
		$map = array(
			'product_cat'   => 'categories',
			'product_tag'   => 'tags',
			'product_brand' => 'brands',
		);

		$params = array();

		foreach ( $public_product_taxonomies as $taxonomy ) {
			if ( is_array( $taxonomy->object_type ) && in_array( 'product', $taxonomy->object_type, true ) ) {
				$params[ $taxonomy->name ] = $map[ $taxonomy->name ] ?? "filter_$taxonomy->name";
			}
		}

		return $params;
	}
}
