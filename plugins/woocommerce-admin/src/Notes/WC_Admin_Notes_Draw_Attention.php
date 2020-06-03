<?php
/**
 * WooCommerce Admin Draw Attention note provider
 *
 * Adds a note to the merchant's inbox
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

use \Automattic\WooCommerce\Admin\Features\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Draw_Attention
 */
class WC_Admin_Notes_Draw_Attention {
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
		add_filter(
			'update_option_' . Onboarding::PROFILE_DATA_OPTION,
			array( $this, 'check_onboarding_profile' ),
			10,
			3
		);
	}

	/**
	 * Check to see if profiler options match before possibly adding note.
	 *
	 * @param object $old_value The old option value.
	 * @param object $value     The new option value.
	 * @param string $option    The name of the option.
	 */
	public static function check_onboarding_profile( $old_value, $value, $option ) {
		// Skip adding if this store is being set up for a client.
		if ( ! isset( $value['setup_client'] ) || $value['setup_client'] ) {
			return;
		}

		// Skip adding if the merchant has no products.
		if ( ! isset( $value['product_count'] ) || '0' === $value['product_count'] ) {
			return;
		}

		self::possibly_add_note();
	}

	/**
	 * Get the note.
	 */
	public static function get_note() {
		$note = new WC_Admin_Note();
		$note->set_title( __( 'How to draw attention to your online store', 'woocommerce-admin' ) );
		$note->set_content( __( 'To get you started, here are seven ways to boost your sales and avoid getting drowned out by similar, mass-produced products competing for the same buyers.', 'woocommerce-admin' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_ADMIN_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/posts/how-to-make-your-online-store-stand-out/?utm_source=inbox',
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);
		return $note;
	}
}
