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
	 * Name of the "other milestones" note.
	 */
	const ORDERS_MILESTONE_NOTE_NAME = 'wc-admin-orders-milestone';

	/**
	 * Option key name to store last order milestone.
	 */
	const LAST_ORDER_MILESTONE_OPTION_KEY = 'wc_admin_last_orders_milestone';

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
	 * Further order milestone thresholds.
	 *
	 * @var array
	 */
	protected $milestones = array(
		100,
		250,
		500,
		1000,
		5000,
		10000,
		500000,
		1000000,
	);

	/**
	 * Delay hook attachment until after the WC post types have been registered.
	 *
	 * This is required for retrieving the order count.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_register_post_type', array( $this, 'init' ) );
	}

	/**
	 * Hook everything up.
	 */
	public function init() {
		add_action( 'wc_admin_installed', array( $this, 'backfill_last_milestone' ) );

		if ( 10 <= $this->get_orders_count() ) {
			add_action( 'woocommerce_new_order', array( $this, 'first_two_milestones' ) );
		}

		add_action( 'wc_admin_daily', array( $this, 'other_milestones' ) );
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
			$note->set_title(
				sprintf(
					/* translators: Number of orders processed. */
					__( 'Congratulations on processing %s orders!', 'woocommerce-admin' ),
					wc_format_decimal( 10 )
				)
			);
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

	/**
	 * Backfill the store's current milestone.
	 *
	 * Used to avoid celebrating milestones that were reached before plugin activation.
	 */
	public function backfill_last_milestone() {
		$this->set_last_milestone( $this->get_current_milestone() );
	}

	/**
	 * Get the store's last milestone.
	 *
	 * @return int Last milestone reached.
	 */
	public function get_last_milestone() {
		return get_option( self::LAST_ORDER_MILESTONE_OPTION_KEY, 0 );
	}

	/**
	 * Update the last reached milestone.
	 *
	 * @param int $milestone Last milestone reached.
	 */
	public function set_last_milestone( $milestone ) {
		update_option( self::LAST_ORDER_MILESTONE_OPTION_KEY, $milestone );
	}

	/**
	 * Calculate the current orders milestone.
	 *
	 * Based on the threshold values in $this->milestones.
	 *
	 * @return int Current orders milestone.
	 */
	public function get_current_milestone() {
		$milestone_reached = 0;
		$orders_count      = $this->get_orders_count();

		foreach ( $this->milestones as $milestone ) {
			if ( $milestone <= $orders_count ) {
				$milestone_reached = $milestone;
			}
		}

		return $milestone_reached;
	}

	/**
	 * Add milestone notes for other significant thresholds.
	 */
	public function other_milestones() {
		$last_milestone    = $this->get_last_milestone();
		$current_milestone = $this->get_current_milestone();

		if ( $current_milestone <= $last_milestone ) {
			return;
		}

		$this->set_last_milestone( $current_milestone );

		// Add the milestone note.
		$note = new WC_Admin_Note();
		$note->set_title(
			sprintf(
				/* translators: Number of orders processed. */
				__( 'Congratulations on processing %s orders!', 'woocommerce-admin' ),
				wc_format_decimal( $current_milestone )
			)
		);
		$note->set_content(
			__( "Another order milestone! Have you shared news with your subscribers lately? Maybe it's time for an update.", 'woocommerce-admin' )
		);
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'trophy' );
		$note->set_name( self::ORDERS_MILESTONE_NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'review', __( 'Review processed orders', 'woocommerce-admin' ), '?page=wc-admin#/analytics/orders' );
		$note->save();
	}
}

new WC_Admin_Notes_Order_Milestones();
