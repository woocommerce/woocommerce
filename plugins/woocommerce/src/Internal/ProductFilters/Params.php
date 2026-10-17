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
	 * Read one collection's filter selections from the request URL.
	 *
	 * @since 11.3.0
	 *
	 * @param int|null $query_id Collection ID, or null for global filters.
	 * @return array<string, string> Selections keyed by effective filter name.
	 */
	public function get_filter_params( ?int $query_id = null ): array {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values are validated below against registered filter parameters.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$query       = wp_parse_url( $request_uri, PHP_URL_QUERY );
		$url_params  = array();
		parse_str( is_string( $query ) ? $query : '', $url_params );
		$prefix = null === $query_id ? '' : 'wcp-' . $query_id . '-';
		$params = array();

		foreach ( $this->get_param_keys() as $key ) {
			$value = $url_params[ $prefix . $key ] ?? null;
			if ( ! is_string( $value ) || '' === $value ) {
				continue;
			}
			if ( in_array( $key, $this->get_param( 'price' ), true ) && ( ! is_numeric( $value ) || ! is_finite( (float) $value ) || (float) $value < 0 ) ) {
				continue;
			}
			if ( 'rating_filter' === $key && ! preg_match( '/^[1-5](,[1-5])*$/', $value ) ) {
				continue;
			}
			if ( 0 === strpos( $key, 'query_type_' ) && ! in_array( $value, array( 'and', 'or' ), true ) ) {
				continue;
			}
			$params[ $key ] = $value;
		}

		return $params;
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
	 * Get the complete effective parameter map.
	 *
	 * @since 11.3.0
	 * @return array Filter types mapped to their URL parameters.
	 */
	public function get_params(): array {
		if ( empty( self::$params ) ) {
			$this->init_params();
		}

		return self::$params;
	}

	/**
	 * Initialize the params.
	 *
	 * @return void
	 */
	private function init_params(): void {
		$params = array(
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

		/**
		 * Filters the complete Product Filters URL parameter map. Only changes to the `taxonomy` entry are currently applied.
		 * That entry maps taxonomy names to URL parameter names, e.g. `product_cat` => `categories`.
		 * Rename with a non-empty, unused parameter name; omitted or invalid entries keep their defaults.
		 * Register callbacks before Params is first read; the map is cached per request.
		 *
		 * @hook woocommerce_product_filter_params
		 * @since 11.3.0
		 *
		 * @param array $params Filter types (`price`, `rating`, `status`, `attribute`, `taxonomy`) mapped to their URL parameter arrays.
		 * @return array Filter types mapped to their URL parameter arrays.
		 */
		$filtered = apply_filters( 'woocommerce_product_filter_params', $params );

		if ( is_array( $filtered ) && isset( $filtered['taxonomy'] ) && is_array( $filtered['taxonomy'] ) ) {
			$used_params = array_merge( ...array_values( array_map( 'array_values', $params ) ) );
			foreach ( array_keys( $params['attribute'] ) as $attribute ) {
				$used_params[] = 'query_type_' . $attribute;
			}

			foreach ( $params['taxonomy'] as $taxonomy => $default_param ) {
				$param = $filtered['taxonomy'][ $taxonomy ] ?? null;
				if ( is_string( $param ) && '' !== $param && ! in_array( $param, $used_params, true ) ) {
					$params['taxonomy'][ $taxonomy ] = $param;
					$used_params[]                   = $param;
				}
			}
		}

		self::$params = $params;
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

		return $params;
	}
}
