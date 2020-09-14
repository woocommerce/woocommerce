<?php
/**
 * WooCommerce Admin: WooCommerce Subscriptions.
 *
 * Adds a note to learn more about WooCommerce Subscriptions.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Features\Onboarding;

/**
 * WC_Admin_Notes_WooCommerce_Subscriptions.
 */
class WC_Admin_Notes_WooCommerce_Subscriptions {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-woocommerce-subscriptions';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		$onboarding_data = get_option( Onboarding::PROFILE_DATA_OPTION, array() );

		if ( ! isset( $onboarding_data['product_types'] ) || ! in_array( 'subscriptions', $onboarding_data['product_types'], true ) ) {
			return;
		}

		if ( ! self::wc_admin_active_for( DAY_IN_SECONDS ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Do you need more info about WooCommerce Subscriptions?', 'woocommerce' ) );
		$note->set_content( __( 'WooCommerce Subscriptions allows you to introduce a variety of subscriptions for physical or virtual products and services. Create product-of-the-month clubs, weekly service subscriptions or even yearly software billing packages. Add sign-up fees, offer free trials, or set expiration periods.', 'woocommerce' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_MARKETING );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn More', 'woocommerce' ),
			'https://woocommerce.com/products/woocommerce-subscriptions/?utm_source=inbox',
			'actioned',
			true
		);
		return $note;
	}
}
