<?php
/**
 * WooCommerce Admin (Dashboard) Order Milestones Note Provider.
 *
 * Adds a note to the merchant's inbox when certain order milestones are reached.
 *
 * @package WooCommerce Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Order_Milestones
 */
class WC_Admin_Notes_Order_Milestones {
	/**
	 * Name of the "first order" note.
	 */
	const FIRST_ORDER_NOTE_NAME = 'wc-admin-first-order';

	/**
	 * Name of the "ten orders" note.
	 */
	const TEN_ORDERS_NOTE_NAME = 'wc-admin-ten-orders';

	/**
	 * Allowed order statuses for calculating milestones.
	 *
	 * @var array
	 */
	protected $allowed_statuses = array(
		'pending',
		'processing',
		'completed',
	);

	/**
	 * Orders count cache.
	 *
	 * @var int
	 */
	protected $orders_count = null;

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		if ( 10 <= $this->get_orders_count() ) {
			add_action( 'woocommerce_new_order', array( $this, 'first_two_milestones' ) );
		}
	}

	/**
	 * Get the total count of orders (in the allowed statuses).
	 *
	 * @return int Total orders count.
	 */
	public function get_orders_count() {
		if ( is_null( $this->orders_count ) ) {
			$status_counts       = array_map( 'wc_orders_count', $this->allowed_statuses );
			$this->orders_count  = array_sum( $status_counts );
		}

		return $this->orders_count;
	}

	/**
	 * Add a milestone notes for the store's first order and first 10 orders.
	 *
	 * @param int $order_id WC_Order ID.
	 */
	public function first_two_milestones( $order_id ) {
		$order = wc_get_order( $order_id );

		// Make sure this is the first pending/processing/completed order.
		if ( ! in_array( $order->get_status(), $this->allowed_statuses ) ) {
			return;
		}

		$orders_count = $this->get_orders_count();

		if ( 1 === $orders_count ) {
			// Add the first order note.
			$note = new WC_Admin_Note();
			$note->set_title( __( 'First order', 'woocommerce-admin' ) );
			$note->set_content(
				__( 'Congratulations on getting your first order from a customer! Learn how to manage your orders.', 'woocommerce-admin' )
			);
			$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
			$note->set_icon( 'trophy' );
			$note->set_name( self::FIRST_ORDER_NOTE_NAME );
			$note->set_source( 'woocommerce-admin' );
			$note->add_action( 'learn-more', __( 'Learn more', 'woocommerce-admin' ), 'https://docs.woocommerce.com/document/managing-orders/' );
			$note->save();
		}

		if ( 10 === $orders_count ) {
			// Add the first ten orders note.
			$note = new WC_Admin_Note();
			$note->set_title( __( 'Congratulations on processing 10 orders!', 'woocommerce-admin' ) );
			$note->set_content(
				__( "You've hit the 10 orders milestone! Look at you go. Browse some WooCommerce success stories for inspiration.", 'woocommerce-admin' )
			);
			$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
			$note->set_icon( 'trophy' );
			$note->set_name( self::TEN_ORDERS_NOTE_NAME );
			$note->set_source( 'woocommerce-admin' );
			$note->add_action( 'browse', __( 'Browse', 'woocommerce-admin' ), 'https://woocommerce.com/success-stories/' );
			$note->save();
		}
	}
}

new WC_Admin_Notes_Order_Milestones();
