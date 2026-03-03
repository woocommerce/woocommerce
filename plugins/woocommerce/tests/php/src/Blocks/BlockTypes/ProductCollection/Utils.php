<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Class Utils
 *
 * Provides utility methods for ProductCollection block tests.
 */
class Utils {
	/**
	 * Return starting point for parsed block test data.
	 * Using a method instead of property to avoid sharing data between tests.
	 */
	public static function get_base_parsed_block() {
		return array(
			'blockName' => 'woocommerce/product-collection',
			'attrs'     => array(
				'query' => array(
					'perPage'                  => 9,
					'pages'                    => 0,
					'offset'                   => 0,
					'postType'                 => 'product',
					'order'                    => 'desc',
					'orderBy'                  => 'date',
					'search'                   => '',
					'exclude'                  => array(),
					'sticky'                   => '',
					'inherit'                  => true,
					'isProductCollectionBlock' => true,
					'woocommerceAttributes'    => array(),
					'woocommerceStockStatus'   => array(
						ProductStockStatus::IN_STOCK,
						ProductStockStatus::OUT_OF_STOCK,
						ProductStockStatus::ON_BACKORDER,
					),
				),
			),
		);
	}

	/**
	 * Build a simplified request for testing.
	 *
	 * @param array $params The parameters to set on the request.
	 * @return WP_REST_Request
	 */
	public static function build_request( $params = array() ) {
		$params = wp_parse_args(
			$params,
			array(
				'featured'               => false,
				'woocommerceOnSale'      => false,
				'woocommerceAttributes'  => array(),
				'woocommerceStockStatus' => array(),
				'timeFrame'              => array(),
				'priceRange'             => array(),
			)
		);

		$params['isProductCollectionBlock'] = true;

		$request = new \WP_REST_Request( 'GET', '/wp/v2/product' );
		foreach ( $params as $param => $value ) {
			$request->set_param( $param, $value );
		}

		return $request;
	}

	/**
	 * Build the merged_query for testing
	 *
	 * @param object $block_instance The block instance.
	 * @param array  $parsed_block Parsed block data.
	 * @param array  $query        Query data.
	 */
	public static function initialize_merged_query( $block_instance, $parsed_block = array(), $query = array() ) {
		if ( empty( $parsed_block ) ) {
			$parsed_block = self::get_base_parsed_block();
		}

		$block_instance->set_parsed_block( $parsed_block );

		$block          = new \stdClass();
		$block->context = $parsed_block['attrs'];

		return $block_instance->build_frontend_query( $query, $block, 1 );
	}
}
