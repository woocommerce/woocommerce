<?php

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
}
