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
		if ( empty( self::$params ) ) {
			$this->init_params();
		}

		$keys = array();
		foreach ( self::$params as $type => $params ) {
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
		if ( empty( self::$params ) ) {
			$this->init_params();
		}

		return self::$params[ $type ] ?? array();
	}

	/**
	 * Initialize the params.
	 *
	 * @return void
	 */
	private function init_params(): void {
		$params             = array(
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
		);
		$params['taxonomy'] = $this->get_taxonomy_params( $params );
		self::$params       = $params;
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
	 * @param array $other_params Params claimed by other filter types.
	 * @return array
	 */
	private function get_taxonomy_params( array $other_params ): array {
		$public_product_taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);

		$built_in_taxonomies = array(
			'product_cat'   => 'categories',
			'product_tag'   => 'tags',
			'product_brand' => 'brands',
		);

		$params = array();

		foreach ( $public_product_taxonomies as $taxonomy ) {
			if ( is_array( $taxonomy->object_type ) && in_array( 'product', $taxonomy->object_type, true ) ) {
				$params[ $taxonomy->name ] = $built_in_taxonomies[ $taxonomy->name ] ?? "filter_$taxonomy->name";
			}
		}

		/**
		 * Filters product taxonomy URL parameters: `product_cat` => `categories`, `product_tag` => `tags`,
		 * `product_brand` => `brands`; other product taxonomies use `filter_{taxonomy}`.
		 * Rename with a non-empty, unused parameter name; omitted or invalid entries keep their defaults.
		 * Register callbacks before Params is first read; the map is cached per request.
		 *
		 * @hook woocommerce_product_filter_taxonomy_params
		 * @since 11.3.0
		 *
		 * @param array $params Map of taxonomy name to URL parameter name.
		 * @return array Map of taxonomy name to URL parameter name.
		 */
		$filtered = apply_filters( 'woocommerce_product_filter_taxonomy_params', $params );

		if ( ! is_array( $filtered ) ) {
			return $params;
		}

		$used_params = array_merge( array_values( $params ), ...array_values( $other_params ) );
		foreach ( array_keys( $other_params['attribute'] ) as $attribute ) {
			$used_params[] = 'query_type_' . $attribute;
		}

		foreach ( $params as $taxonomy => $default_param ) {
			$param = $filtered[ $taxonomy ] ?? null;
			if ( is_string( $param ) && '' !== $param && ( $param === $default_param || ! in_array( $param, $used_params, true ) ) ) {
				$params[ $taxonomy ] = $param;
				$used_params[]       = $param;
			}
		}

		return $params;
	}
}
