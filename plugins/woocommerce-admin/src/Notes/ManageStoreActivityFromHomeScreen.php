<?php
/**
 * New! Manage your store activity from the Home screen.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * New! Manage your store activity from the Home screen.
 */
class ManageStoreActivityFromHomeScreen {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-manage-store-activity-from-home-screen';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		add_action(
			'woocommerce_admin_updated_existing',
			array( $this, 'possibly_add_note' )
		);
	}

	/**
	 * Get the note.
	 *
	 * @return Note|null
	 */
	public static function get_note() {
		$installed_version = get_option( 'woocommerce_admin_version' );
		// the value can be in 1.9.0-rc.3 format for RC or BETA releases
		// get the version without -rc.3.
		$version_segments  = explode( '-', $installed_version );
		$installed_version = $version_segments[0];
		if ( ! version_compare( $installed_version, '1.9.0', '=' ) ) {
			return;
		}

		$note = new Note();

		$note->set_title( __( 'New! Manage your store activity from the Home screen', 'woocommerce-admin' ) );
		$note->set_content( __( 'Start your day knowing the next steps you need to take with your orders, products, and customer feedback.<br/><br/>Read more about how to use the activity panels on the Home screen.', 'woocommerce-admin' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/document/home-screen/?utm_source=inbox&utm_medium=product',
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);

		return $note;
	}
}
