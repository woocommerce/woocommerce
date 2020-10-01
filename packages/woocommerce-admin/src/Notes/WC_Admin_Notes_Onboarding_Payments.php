<?php
/**
 * WooCommerce Admin: Payments reminder note.
 *
 * Adds a notes to complete the payment methods.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Onboarding_Payments.
 */
class WC_Admin_Notes_Onboarding_Payments {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-onboarding-payments-reminder';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		// We want to show the note after five days.
		if ( ! self::wc_admin_active_for( 5 * DAY_IN_SECONDS ) ) {
			return;
		}

		// Check to see if any gateways have been added.
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array_filter(
			$gateways,
			function( $gateway ) {
				return 'yes' === $gateway->enabled;
			}
		);
		if ( ! empty( $enabled_gateways ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Start accepting payments on your store!', 'woocommerce' ) );
		$note->set_content( __( 'Take payments with the provider that’s right for you - choose from 100+ payment gateways for WooCommerce.', 'woocommerce' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'view-payment-gateways',
			__( 'Learn more', 'woocommerce' ),
			'https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/',
			'actioned',
			true
		);
		return $note;
	}
}
