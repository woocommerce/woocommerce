<?php
/**
 * WooCommerce Admin: Welcome to WooCommerce for store users.
 */

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Notes\Note;
use \Automattic\WooCommerce\Admin\Notes\NoteTraits;

/**
 * Welcome to WooCommerce for store users.
 */
class WelcomeToWooCommerceForStoreUsers {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-welcome-to-woocommerce-for-store-users';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'possibly_add_note' ) );
	}

	/**
	 * Get the note.
	 *
	 * @return Note|null
	 */
	public static function get_note() {
		// Only add if coming from Calypso.
		if ( ! isset( $_GET['from-calypso'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Welcome to your new store management experience', 'woocommerce' ) );
		$note->set_content( __( "We've designed your navigation and home screen to help you focus on the things that matter most in managing your online store.", 'woocommerce' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce' ),
			'https://wordpress.com/support/new-woocommerce-experience-on-wordpress-dot-com/"',
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);

		return $note;
	}
}
