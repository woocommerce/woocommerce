<?php
/**
 * WooCommerce Admin SimpleInboxNote example class.
 *
 * Adds an example note to the merchant's inbox.
 *
 * @package WooCommerce\Admin
 */

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

/**
 * SimpleInboxNote class.
 */
class SimpleInboxNote {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'simple-inbox-note';

	/**
	 * Check to run to find out if we should display our note.
	 *
	 * @return Note
	 */
	public static function is_applicable() {
		// We want to show the note after five days.
		if ( ! self::is_wc_admin_active_in_date_range( 'week-1-4', 5 * DAY_IN_SECONDS ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		// Optionally, you can add any logic here in order to
		// control when the note should be displayed.
		// In the future, this would be automatically handled by our NoteTraits.
		// if ( ! self::is_applicable() ) {
		// return;
		// } //.

		// Instantiate a new Note object.
		$note = new Note();

		// Set our note's title.
		$note->set_title( 'Hello From Inbox Note!' );

		// Set our note's content. Please keep in mind that the note content
		// is limited to 320 displayable characters.
		// It is also not advisable to include Unicode characters such as emojis
		// since they could possibly not work on some user's sites where database
		// collation doesn't support Unicode.
		$note->set_content(
			sprintf(
				'This is your note example contents. You may enter up to 320 displayable characters here. For more information on character limit, visit our <a href="https://developer.woocommerce.com/2021/11/10/introducing-a-320-character-limit-to-inbox-notes/">blogpost</a>.'
			)
		);

		// In addition to content, notes also support structured content.
		// You can use this property to re-localize notes on the fly, but
		// that is just one use. You can store other data here too. This
		// is backed by a longtext column in the database.
		$note->set_content_data(
			(object) array(
				'getting_started' => true,
			)
		);

		// Set the type of the note. Note types are defined as enum-style
		// constants in the Note class. Available note types are:
		// error, warning, update, info, marketing.
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );

		// Set the type of layout the note uses. Supported layout types are:
		// 'banner', 'plain', 'thumbnail'.
		$note->set_layout( 'plain' );

		// Set the image for the note. This property renders as the src
		// attribute for an img tag, so use a string here.
		$note->set_image( '' );

		// Set the note name and source. You should store your extension's
		// name (slug) in the source property of the note. You can use
		// the name property of the note to support multiple sub-types of
		// notes. This also gives you a handy way of namespacing your notes.
		$note->set_source( 'simple-inbox-note-example' );
		$note->set_name( self::NOTE_NAME );

		// Add action buttons to the note. A note can support 0, 1, or 2 actions.
		// The first parameter is the action name, which can be used for event handling.
		// The second parameter renders as the label for the button.
		// The third parameter is an optional URL for actions that require navigation.
		$note->add_action(
			'settings',
			'Open Settings',
			'?page=wc-settings&tab=general'
		);

		// Return the note object.
		return $note;
	}
}
