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
		// A WooPayments incentive must be visible.
		if ( ! WcPayWelcomePage::instance()->has_incentive() ) {
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
		/* translators: 1: Payment provider name. */
		$content = sprintf( __( 'Save up to $800 in fees by managing transactions with %1$s. With %1$s, you can securely accept major cards, Apple Pay, and payments in over 100 currencies.', 'woocommerce' ), 'WooPayments' );

		$note = new Note();
		/* translators: %s: Payment provider name. */
		$note->set_title( sprintf( __( 'Save big with %s', 'woocommerce' ), 'WooPayments' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'learn-more', __( 'Learn more', 'woocommerce' ), admin_url( 'admin.php?page=wc-admin&path=/wc-pay-welcome-page' ) );
		return $note;
	}
}
