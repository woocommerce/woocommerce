<?php
/**
 * Order
 *
 * @class    WC_Order
 * @version  2.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */

class WC_Order extends WC_Abstract_Order {

	/**
	 * Initialize the order refund.
	 *
	 * @param int|WC_Order $order
	 */
	public function __construct( $order ) {
		$this->order_type = 'simple';

		parent::__construct( $order );
	}

	/**
	 * Get order refunds
	 *
	 * @since 2.2
	 * @return array
	 */
	public function get_refunds() {
		global $wpdb;

		$refunds      = array();
		$refund_items = get_posts(
			array(
				'post_type'      => 'shop_order',
				'post_parent'    => $this->id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'order_type',
						'terms'    => 'refund',
						'field'    => 'slug'
					)
				)
			)
		);

		foreach ( $refund_items as $refund_id ) {
			$refunds[] = new WC_Order_Refund( $refund_id );
		}

		return $refunds;
	}
}
