<?php
/**
 * WooCommerce Admin (Dashboard) Giving feedback notes provider.
 *
 * Adds notes to the merchant's inbox about giving feedback.
 *
 * @package WooCommerce Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Giving_Feedback_Notes
 */
class WC_Admin_Notes_Giving_Feedback_Notes {
	/**
	 * Add notes for admin giving feedback.
	 */
	public static function add_notes_for_admin_giving_feedback() {
		self::possibly_add_admin_giving_feedback_note();
	}

	/**
	 * Possibly add a notice setting moved note.
	 */
	protected static function possibly_add_admin_giving_feedback_note() {
		$name = 'wc-admin-store-notice-giving-feedback';

		$data_store = WC_Data_Store::load( 'admin-note' );

		// We already have this note? Then exit, we're done.
		$note_ids = $data_store->get_notes_with_name( $name );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		// Getting install timestamp reference class-wc-admin-install.php.
		$wc_admin_installed = get_option( 'wc_admin_install_timestamp', false );
		if ( false === $wc_admin_installed ) {
			$wc_admin_installed = time();
			update_option( 'wc_admin_install_timestamp', $wc_admin_installed );
		}

		$current_time          = time();
		$three_days_in_seconds = 259200;

		// We need to show Admin Giving feeback notification after 3 days of install.
		if ( $current_time - $wc_admin_installed < $three_days_in_seconds ) {
			return;
		}

		// Otherwise, create our new note.
		$note = new WC_Admin_Note();
		$note->set_title( __( 'Review your experience', 'woocommerce-admin' ) );
		$note->set_content( __( 'If you like WooCommerce Admin please leave us a 5 star rating. A huge thanks in advance!', 'woocommerce-admin' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'info' );
		$note->set_name( $name );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'share-feedback',
			__( 'Review', 'woocommerce-admin' ),
			'https://wordpress.org/support/plugin/woocommerce-admin/reviews/?rate=5#new-post'
		);
		$note->save();
	}
}
