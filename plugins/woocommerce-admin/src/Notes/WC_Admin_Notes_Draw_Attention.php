<?php
/**
 * WooCommerce Admin Draw Attention note provider
 *
 * Adds a note to the merchant's inbox
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Draw_Attention
 */
class WC_Admin_Notes_Draw_Attention {
	const NOTE_NAME   = 'wc-admin-draw-attention';
	const OPTION_NAME = 'woocommerce_onboarding_profile';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Trigger this when the onboarding options are updated.
		add_filter(
			'update_option_' . self::OPTION_NAME,
			array( $this, 'possibly_add_draw_attention_note' ),
			10,
			3
		);
	}

	/**
	 * Possibly add a draw attention note.
	 *
	 * @param object $old_value The old option value.
	 * @param object $value     The new option value.
	 * @param string $option    The name of the option.
	 */
	public static function possibly_add_draw_attention_note( $old_value, $value, $option ) {
		// Skip adding if this store is being set up for a client.
		if ( ! isset( $value['setup_client'] ) || $value['setup_client'] ) {
			return;
		}

		// Skip adding if the merchant has no products.
		if ( ! isset( $value['product_count'] ) || '0' === $value['product_count'] ) {
			return;
		}

		// Exit early if there is already a note.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		// Create the note.
		$note = new WC_Admin_Note();

		$note->set_title( __( 'How to draw attention to your online store', 'woocommerce-admin' ) );
		$note->set_content( __( 'To get you started, here are seven ways to boost your sales and avoid getting drowned out by similar, mass-produced products competing for the same buyers.', 'woocommerce-admin' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_ADMIN_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'info' );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/posts/how-to-make-your-online-store-stand-out/?utm_source=inbox',
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);

		$note->save();
	}
}
