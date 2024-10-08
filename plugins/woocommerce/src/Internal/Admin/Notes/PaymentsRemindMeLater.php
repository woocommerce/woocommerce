<?php
/**
 * WooCommerce Admin Payment Reminder Me later
 */

namespace Automattic\WooCommerce\Internal\Admin\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;
use Automattic\WooCommerce\Internal\Admin\WcPayWelcomePage;

defined( 'ABSPATH' ) || exit;

/**
 * PaymentsRemindMeLater
 */
class PaymentsRemindMeLater {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-payments-remind-me-later';

	/**
	 * Should this note exist?
	 */
	public static function is_applicable() {
		return self::should_display_note();
	}

	/**
	 * Returns true if we should display the note.
	 *
	 * @return bool
	 */
	public static function should_display_note() {
		// WooPayments incentive must be visible.
		if ( ! WcPayWelcomePage::instance()->is_incentive_visible() ) {
			return false;
		}

		// Less than 3 days since viewing welcome page.
		$view_timestamp = get_option( 'wcpay_welcome_page_viewed_timestamp', false );
		if ( ! $view_timestamp ||
			( time() - $view_timestamp < 3 * DAY_IN_SECONDS )
		) {
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
		if ( ! self::should_display_note() ) {
			return;
		}
		$content = __( 'Save up to $800 in fees by managing transactions with WooPayments. With WooPayments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies.', 'woocommerce' );

		$note = new Note();
		$note->set_title( __( 'Save big with WooPayments', 'woocommerce' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'learn-more', __( 'Learn more', 'woocommerce' ), admin_url( 'admin.php?page=wc-admin&path=/wc-pay-welcome-page' ) );
		return $note;
	}
}
