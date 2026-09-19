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
		 * Filters the URL query parameter that product filters claim for each product taxonomy, as a map
		 * keyed by taxonomy name: by default `array( 'product_cat' => 'categories', 'product_tag' => 'tags' )`.
		 * Prefer renaming a parameter to dropping its entry, since `ProductFilterTaxonomy::render()` renders
		 * nothing for a taxonomy that is missing from the map.
		 *
		 * Register the callback before `parse_request` runs, on `plugins_loaded` or `init`. The param names
		 * become public query vars through the one-shot `query_vars` filter, so a callback added after that
		 * renames the param here but never registers it, and filtering stops working; for the same reason the
		 * map must stay stable for the whole request. A return value that is not an array falls back to the
		 * unfiltered map, and entries that are not non-empty string pairs are discarded.
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
