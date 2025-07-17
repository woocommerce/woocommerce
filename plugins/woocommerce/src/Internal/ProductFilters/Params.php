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
		foreach ( self::$params as $taxonomy => $params ) {
			$keys = array_merge( $keys, array_values( $params ) );
			if ( 'attribute' === $taxonomy ) {
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
