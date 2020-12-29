<?php
/**
 * WooCommerce Admin: Help us improve the WooCommerce Home screen
 *
 * Adds a note to ask the client to provide feedback about the home screen.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Home_Screen_Feedback.
 */
class HomeScreenFeedback {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-home-screen-feedback';

	const HOMESCREEN_ACCESSED_OPTION_NAME = 'wc_admin_note_home_screen_feedback_homescreen_accessed';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'on_init' ) );
	}

	/**
	 * Watch for the homescreen being accessed (by checking to see if it's
	 * enabled) and set a time stamp for when it is.
	 */
	public function on_init() {
		// If the accessed option is already set, return early.
		if ( false !== get_option( self::HOMESCREEN_ACCESSED_OPTION_NAME ) ) {
			return;
		}

		// Set the current time stamp, as the home screen is the default
		// experience.
		update_option( self::HOMESCREEN_ACCESSED_OPTION_NAME, time() );
	}

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		// Homescreen first accessed at least 12 days ago.
		$homescreen_accessed_time = get_option(
			self::HOMESCREEN_ACCESSED_OPTION_NAME
		);
		if ( ! $homescreen_accessed_time ) {
			return;
		}
		if ( time() - $homescreen_accessed_time < 12 * DAY_IN_SECONDS ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Help us improve the WooCommerce Home screen', 'woocommerce' ) );
		$note->set_content( __( 'We\'d love your input to shape the future of the WooCommerce Home screen together. Feel free to share any feedback, ideas or suggestions that you have.', 'woocommerce' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'home-screen-feedback-share-feedback',
			__( 'Share feedback', 'woocommerce' ),
			'https://automattic.survey.fm/home-screen-survey'
		);

		return $note;
	}
}
