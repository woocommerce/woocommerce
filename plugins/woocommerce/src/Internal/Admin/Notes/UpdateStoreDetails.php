<?php
/**
 * UpdateStoreDetails class
 */

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Notes\Note;
use \Automattic\WooCommerce\Admin\Notes\NoteTraits;

/**
 * Adds a note when the profiler is completed.
 */
class UpdateStoreDetails {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-update-store-details';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$onboarding_profile = get_option( 'woocommerce_onboarding_profile', array() );

		// Bail when profile was set up by client.
		if ( isset( $onboarding_profile['setup_client'] ) && $onboarding_profile['setup_client'] ) {
			return;
		}

		// Bail when profile is not yet completed.
		if ( ! isset( $onboarding_profile['completed'] ) || ! $onboarding_profile['completed'] ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Edit your store details if you need to', 'woocommerce' ) );
		$note->set_content( __( 'Nice work completing your store profile! You can always go back and edit the details you just shared, as needed.', 'woocommerce' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'update-store-details',
			__( 'Update store details', 'woocommerce' ),
			wc_admin_url( '&path=/setup-wizard' )
		);

		return $note;
	}
}
