<?php

declare( strict_types = 1 );

/**
 * Order item subclass used to verify that REST updates preserve extension validation.
 */
class WC_REST_Orders_Controller_Rejecting_Order_Item_Product extends WC_Order_Item_Product {

	/**
	 * Whether restoring a non-zero variation ID should fail.
	 *
	 * @var bool
	 */
	public static $reject_variation_restoration = false;

	/**
	 * Set the variation ID.
	 *
	 * @param int $value Variation ID.
	 * @return void
	 * @throws WC_Data_Exception When variation restoration is rejected.
	 */
	public function set_variation_id( $value ) {
		if ( self::$reject_variation_restoration && $value > 0 ) {
			$this->error(
				'order_item_product_invalid_variation_id',
				'Variation restoration rejected by an order item extension.',
				400,
				array( 'variation_id' => $value )
			);
		}

		parent::set_variation_id( $value );
	}
}
