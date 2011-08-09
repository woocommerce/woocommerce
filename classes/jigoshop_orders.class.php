<?php
/**
 * Orders
 * @class jigoshop_orders
 * 
 * The JigoShop orders class loads orders and calculates counts
 *
 * @author 		Jigowatt
 * @category 	Classes
 * @package 	JigoShop
 */
class jigoshop_orders {

	var $orders;
	var $count;
	var $completed_count;
	var $pending_count;
	var $cancelled_count;
	var $on_hold_count;
	var $processing_count;
	var $refunded_count;
	
	/** Loads orders and counts them */
	function jigoshop_orders() {
		$this->orders = array();
		
		// Get Counts
		$this->pending_count 	= get_term_by( 'slug', 'pending', 'shop_order_status' )->count;
		$this->completed_count  = get_term_by( 'slug', 'completed', 'shop_order_status' )->count;
		$this->cancelled_count  = get_term_by( 'slug', 'cancelled', 'shop_order_status' )->count;
		$this->on_hold_count    = get_term_by( 'slug', 'on-hold', 'shop_order_status' )->count;
		$this->refunded_count 	= get_term_by( 'slug', 'refunded', 'shop_order_status' )->count;
		$this->processing_count = get_term_by( 'slug', 'processing', 'shop_order_status' )->count;
		$this->count			= wp_count_posts( 'shop_order' )->publish;
	}
	
	/**
	 * Loads a customers orders
	 *
	 * @param   int		$user_id	ID of the user to load the orders for
	 * @param   int		$limit		How many orders to load
	 */
	function get_customer_orders( $user_id, $limit = 5 ) {
		
		$args = array(
		    'numberposts'     => $limit,
		    'meta_key'        => 'customer_user',
		    'meta_value'	  => $user_id,
		    'post_type'       => 'shop_order',
		    'post_status'     => 'publish' 
		);
		
		$results = get_posts($args);

		if ($results) :
			foreach ($results as $result) :
				$order = &new jigoshop_order();
				$order->populate($result);
				$this->orders[] = $order;
			endforeach;
		endif;
	}
}	