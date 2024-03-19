<?php
/**
 * WC_Product_Data_Store_CPT class file.
 *
 * @package WooCommerce\Classes
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\DownloadPermissionsAdjuster;
use Automattic\WooCommerce\Utilities\NumberUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Product Data Store: Stored in CPT.
 *
 * @version  3.0.0
 */
class WC_Product_Modular_Data_Store_CPT extends WC_Product_Data_Store_CPT implements WC_Object_Data_Store_Interface, WC_Product_Data_Store_Interface {

	/**
	 * Make sure we store the product type and version (to track data changes).
	 *
	 * @param WC_Product $product Product object.
	 * @since 3.0.0
	 */
	protected function update_version_and_type( &$product ) {
		$old_type = WC_Product_Factory::get_product_type( $product->get_id() );
		// @todo We might want to offer a way to break out of this type.
		$new_type = 'modular';

		wp_set_object_terms( $product->get_id(), $new_type, 'product_type' );
		update_post_meta( $product->get_id(), '_product_version', Constants::get_constant( 'WC_VERSION' ) );

		// Action for the transition.
		if ( $old_type !== $new_type ) {
			$this->updated_props[] = 'product_type';
			do_action( 'woocommerce_product_type_changed', $product, $old_type, $new_type );
		}
	}

}
