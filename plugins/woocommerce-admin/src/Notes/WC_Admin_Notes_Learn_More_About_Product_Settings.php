<?php
/**
 * WooCommerce Admin: Learn more about Product Settings
 *
 * Adds a note about learning more about product settings.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Features\Onboarding;

/**
 * WC_Admin_Notes_Learn_More_About_Product_Settings.
 */
class WC_Admin_Notes_Learn_More_About_Product_Settings {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-learn-more-about-product-settings';

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
			isset( $onboarding_profile['setup_client'] )
			&& $onboarding_profile['setup_client']
		) {
			return;
		}

		// Make sure that products were added at least one day ago.
		$query    = new \WC_Product_Query(
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
		if ( ( time() - $oldest_product_timestamp ) < DAY_IN_SECONDS ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Learn more about Product Settings', 'woocommerce-admin' ) );
		$note->set_content( __( 'In this video you\'ll find information about configuring product settings, such as how they are displayed, editing inventory options and so on.', 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more-about-product-settings',
			__( 'Watch tutorial', 'woocommerce-admin' ),
			'https://www.youtube.com/watch?v=FEmwJsE8xDY&t=',
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);
		return $note;
	}
}
