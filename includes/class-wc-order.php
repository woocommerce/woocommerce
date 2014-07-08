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

	/**
	 * Get amount already refunded
	 *
	 * @since 2.2
	 * @return int|float
	 */
	public function get_total_refunded() {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( postmeta.meta_value )
			FROM $wpdb->postmeta AS postmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order' AND posts.post_parent = %d )
			INNER JOIN $wpdb->term_relationships AS term_relationships ON ( posts.ID = term_relationships.object_id )
			INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON( term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id AND term_taxonomy.taxonomy = 'order_type' )
			INNER JOIN $wpdb->terms AS terms ON( term_taxonomy.term_id = terms.term_id AND terms.name = 'refund' )
			WHERE postmeta.meta_key = '_refund_amount'
			GROUP BY posts.ID
		", $this->id ) );

		return $total;
	}
}
