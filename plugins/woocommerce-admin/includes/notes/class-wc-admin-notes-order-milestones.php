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
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'woocommerce_new_order', array( $this, 'add_first_order_note' ) );
	}

	/**
	 * Get the total count of orders (in the allowed statuses).
	 *
	 * @return int Total orders count.
	 */
	public function get_orders_count() {
		$status_counts = array_map( 'wc_orders_count', $this->allowed_statuses );
		$orders_count  = array_sum( $status_counts );

		return $orders_count;
	}

	/**
	 * Add a milestone note for the store's first order.
	 *
	 * @param int $order_id WC_Order ID.
	 */
	public function add_first_order_note( $order_id ) {
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
	}
}

new WC_Admin_Notes_Order_Milestones();
