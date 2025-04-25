<?php
/**
 * Trait for handling POS email customizations.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Trait for handling POS email customizations.
 */
trait WC_POS_Email_Customizations {
	/**
	 * Add actions and filters before generating email content.
	 */
	protected function add_pos_customizations() {
		// Add action to display unit price in the beginning of the order item meta.
		add_action( 'woocommerce_order_item_meta_start', array( $this, 'add_unit_price' ), 10, 4 );
	}

	/**
	 * Remove actions and filters after generating email content.
	 */
	protected function remove_pos_customizations() {
		// Remove actions and filters after generating content to avoid affecting other emails.
		remove_action( 'woocommerce_order_item_meta_start', array( $this, 'add_unit_price' ), 10 );
	}

	/**
	 * Add unit price to order item meta start position.
	 *
	 * @param int        $item_id       Order item ID.
	 * @param array     $item          Order item data.
	 * @param WC_Order  $order         Order object.
	 * @param bool      $plain_text    Whether is plain text email.
	 */
	public function add_unit_price( $item_id, $item, $order, $plain_text = false ) {
		$unit_price = $this->get_formatted_item_subtotal($order, $item);
		echo '<br /><small>' . $unit_price . '</small>';
	}

	/**
	* Get item subtotal for unit price - formatted for display.
	*
	* @param object $order Order object.
	* @param object $item Item for filter `woocommerce_order_formatted_item_subtotal`.
	* @return string
	*/
	private function get_formatted_item_subtotal( $order, $item ) {
		$subtotal = wc_price( 
			$order->get_item_subtotal( $item, true ), 
			array( 'currency' => $order->get_currency() ) 
		);
		return apply_filters( 'woocommerce_order_formatted_item_subtotal', $subtotal, $item, $this );
	}
}
