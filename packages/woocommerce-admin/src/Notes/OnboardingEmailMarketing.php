<?php
/**
 * WooCommerce Admin Onboarding Email Marketing Note Provider.
 *
 * Adds a note to sign up to email marketing after completing the profiler.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding_Email_Marketing
 */
class OnboardingEmailMarketing {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-onboarding-email-marketing';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$content = __( 'We\'re here for you - get tips, product updates and inspiration straight to your email box', 'woocommerce-admin' );

		$note = new Note();
		$note->set_title( __( 'Tips, product updates, and inspiration', 'woocommerce-admin' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'yes-please', __( 'Yes please!', 'woocommerce-admin' ), 'https://woocommerce.us8.list-manage.com/subscribe/post?u=2c1434dc56f9506bf3c3ecd21&amp;id=13860df971&amp;SIGNUPPAGE=plugin' );
		return $note;
	}
}
