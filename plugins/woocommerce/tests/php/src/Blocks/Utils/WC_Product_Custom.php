<?php
//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use WC_Product;

/**
 * Custom product class.
 */
class WC_Product_Custom extends WC_Product {
	/**
	 * Initialize custom product.
	 *
	 * @param WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {
		parent::__construct();
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'custom';
	}
}

add_filter(
	'woocommerce_product_class',
	function ( $classname, $product_type ) {
		if ( 'custom' === $product_type ) {
			$classname = 'Automattic\WooCommerce\Tests\Blocks\Utils\WC_Product_Custom';
		}
		return $classname;
	},
	10,
	2
);
