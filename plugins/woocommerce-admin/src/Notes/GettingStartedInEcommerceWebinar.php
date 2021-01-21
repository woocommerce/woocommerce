<?php
/**
 * WooCommerce Admin: Getting started in ecommerce, watch a webinar.
 *
 * Adds a note to remind the client they can watch a webinar to get started
 * with WooCommerce.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Getting started in ecommmerce note class.
 */
class GettingStartedInEcommerceWebinar {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Onboarding traits.
	 */
	use OnboardingTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-getting-started-ecommerce-webinar';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {

		if ( ! self::onboarding_profile_started() ) {
			return;
		}

		if ( self::store_setup_for_client() ) {
			return;
		}

		if ( ! self::revenue_is_within( 0, 2500 ) ) {
			return;
		}

		// Don't show if there are products.
		$query    = new \WC_Product_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
				'status'   => array( 'publish' ),
			)
		);
		$products = $query->get_products();
		$count    = $products->total;

		if ( 0 !== $count ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Getting Started in eCommerce - webinar', 'woocommerce-admin' ) );
		$note->set_content( __( 'We want to make eCommerce and this process of getting started as easy as possible for you. Watch this webinar to get tips on how to have our store up and running in a breeze.', 'woocommerce-admin' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'getting-started-webinar',
			__( 'Watch the webinar', 'woocommerce-admin' ),
			'https://youtu.be/V_2XtCOyZ7o'
		);

		return $note;
	}
}
