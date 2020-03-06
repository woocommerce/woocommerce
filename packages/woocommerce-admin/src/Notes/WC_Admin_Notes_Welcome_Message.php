<?php
/**
 * WooCommerce Admin: Welcome to WooCommerce Admin Note
 *
 * Adds welcome notes to store alerts area.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Install;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_Admin_Notes_Welcome_Message {
	const NOTE_NAME = 'wc-admin-welcome-note';

	/**
	 * Creates a note for welcome message.
	 */
	public static function add_welcome_note() {
		// Check if plugin is upgrading if yes then don't create this note.
		$is_upgrading = get_option( Install::VERSION_OPTION );
		if ( $is_upgrading ) {
			return;
		}

		// See if we've already created this kind of note so we don't do it again.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'New feature(s)', 'woocommerce' ) );
		$note->set_content( __( 'Welcome to the new WooCommerce experience! In this new release you\'ll be able to have a glimpse of how your store is doing in the Dashboard, manage important aspects of your business (such as managing orders, stock, reviews) from anywhere in the interface, dive into your store data with a completely new Analytics section and more!', 'woocommerce' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'info' );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce' ),
			'https://woocommerce.wordpress.com/'
		);

		$note->save();
	}
}
