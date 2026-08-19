<?php
/**
 * Trait for building the variable products the Analytics stock report tests run against.
 *
 * @package WooCommerce\Tests\Traits
 */

declare( strict_types=1 );

/**
 * Trait WC_Stock_Report_Fixtures
 */
trait WC_Stock_Report_Fixtures {

	/**
	 * Create a published variable product with a Small and a Large variation.
	 *
	 * @param string $name       Product name, describing the stock configuration under test.
	 * @param array  $variations One entry per variation, in the order Small then Large. Each accepts
	 *                           the 'manage_stock', 'stock_quantity' and 'stock_status' keys.
	 * @return array The WC_Product_Variable, and the variation IDs in the order given.
	 */
	private function create_stock_report_variable_product( $name, array $variations ) {
		$options = array( 'Small', 'Large' );

		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( $options );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$variable = new WC_Product_Variable();
		$variable->set_name( $name );
		$variable->set_attributes( array( $attribute ) );
		$variable->save();

		$variation_ids = array();
		foreach ( $options as $index => $option ) {
			$args = $variations[ $index ];

			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $variable->get_id() );
			$variation->set_attributes( array( 'size' => $option ) );
			$variation->set_regular_price( '10' );
			$variation->set_manage_stock( ! empty( $args['manage_stock'] ) );

			if ( isset( $args['stock_quantity'] ) ) {
				$variation->set_stock_quantity( $args['stock_quantity'] );
			}
			if ( isset( $args['stock_status'] ) ) {
				$variation->set_stock_status( $args['stock_status'] );
			}

			$variation->save();
			$variation_ids[] = $variation->get_id();
		}

		return array( new WC_Product_Variable( $variable->get_id() ), $variation_ids );
	}

	/**
	 * Drop the transients the stock stats are served from.
	 */
	private function clear_stock_count_caches() {
		delete_transient( 'wc_admin_product_count' );
		delete_transient( 'wc_admin_stock_count_lowstock' );
		foreach ( array_keys( wc_get_product_stock_status_options() ) as $status ) {
			delete_transient( 'wc_admin_stock_count_' . $status );
		}
	}
}
