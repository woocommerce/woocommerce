<?php
/**
 * WooCommerce Admin Draw Attention note provider
 *
 * Adds a note to the merchant's inbox
 */

namespace Automattic\WooCommerce\Admin\Notes;

use \Automattic\WooCommerce\Admin\Features\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Draw_Attention
 */
class DrawAttention {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-draw-attention';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Trigger this when the onboarding options are updated.
		add_filter( 'update_option_' . Onboarding::PROFILE_DATA_OPTION, array( $this, 'possibly_add_note' ) );
	}

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		// We want to show the note after 3 days.
		if ( ! self::is_wc_admin_active_in_date_range( 'week-1', 3 * DAY_IN_SECONDS ) ) {
			return;
		}

		$profile_data = get_option( Onboarding::PROFILE_DATA_OPTION, array() );

		// Skip adding if this store is being set up for a client.
		if ( ! isset( $profile_data['setup_client'] ) || $profile_data['setup_client'] ) {
			return;
		}

		// Skip adding if the merchant has no products.
		if ( ! isset( $profile_data['product_count'] ) || '0' === $profile_data['product_count'] ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Get noticed: how to draw attention to your online store', 'woocommerce-admin' ) );
		$note->set_content( __( 'To get you started, here are seven ways to boost your sales and avoid getting drowned out by similar, mass-produced products competing for the same buyers.', 'woocommerce-admin' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/posts/how-to-make-your-online-store-stand-out/?utm_source=inbox&utm_medium=product',
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);
		return $note;
	}
}
