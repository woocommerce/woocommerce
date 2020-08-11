<?php
/**
 * WooCommerce Admin Test Checkout.
 *
 * Adds a note to remind the user to test their store checkout.
 *
 * @package WooCommerce\Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Test_Checkout
 */
class WC_Admin_Notes_Test_Checkout {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-test-checkout';

	/**
	 * Completed tasks option name.
	 */
	const TASK_LIST_TRACKED_TASKS = 'woocommerce_task_list_tracked_completed_tasks';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'update_option_' . self::TASK_LIST_TRACKED_TASKS, array( $this, 'possibly_add_note' ) );
	}

	/**
	 * Get the note.
	 */
	public static function get_note() {
		$onboarding_profile = get_option( 'woocommerce_onboarding_profile', array() );

		// Confirm that $onboarding_profile is set.
		if ( empty( $onboarding_profile ) ) {
			return;
		}

		// Make sure that the person who filled out the OBW was not setting up
		// the store for their customer/client.
		if (
			! isset( $onboarding_profile['setup_client'] ) ||
			$onboarding_profile['setup_client']
		) {
			return;
		}

		// Make sure payments task was completed.
		$completed_tasks = get_option( self::TASK_LIST_TRACKED_TASKS, array() );
		if ( ! in_array( 'payments', $completed_tasks, true ) ) {
			return;
		}

		// Make sure that products were added within the previous 1/2 hour.
		$query = new \WC_Product_Query(
			array(
				'limit'   => 1,
				'status'  => 'publish',
				'orderby' => 'date',
				'order'   => 'ASC',
			)
		);

		$products = $query->get_products();
		if ( 0 === count( $products ) ) {
			return;
		}

		$oldest_product_timestamp = $products[0]->get_date_created()->getTimestamp();
		$half_hour_in_seconds     = 30 * MINUTE_IN_SECONDS;
		if ( ( time() - $oldest_product_timestamp ) > $half_hour_in_seconds ) {
			return;
		}

		$content = __( 'Make sure that your checkout is working properly before you launch your store. Go through your checkout process in its entirety: from adding a product to your cart, choosing a shipping location, and making a payment.', 'woocommerce-admin' );

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Don\'t forget to test your checkout', 'woocommerce-admin' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'test-checkout', __( 'Test checkout', 'woocommerce-admin' ), wc_get_page_permalink( 'shop' ) );
		return $note;
	}
}
