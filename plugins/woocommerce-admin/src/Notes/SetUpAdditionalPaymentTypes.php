<?php
/**
 * Add a note to the merchant's inbox prompting them to set up additional
 * payment types if they have WooCommerce Payments activated.
 *
 * @package WooCommerce\Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;

/**
 * Set_Up_Additional_Payment_Types
 */
class SetUpAdditionalPaymentTypes {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-set-up-additional-payment-types';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action(
			'activate_woocommerce-payments/woocommerce-payments.php',
			array(
				$this,
				'on_activate_wcpay',
			)
		);
		add_action(
			'deactivate_woocommerce-payments/woocommerce-payments.php',
			array(
				$this,
				'on_deactivate_wcpay',
			)
		);
	}

	/**
	 * Executes when the WooCommerce Payments plugin is activated. It does nothing
	 * if WooCommerce Payments plugin is not included in onboarding business extensions,
	 * otherwise it possibly adds the note if it isn't already in the database and if
	 * it matches any criteria (see get_note()).
	 */
	public static function on_activate_wcpay() {
		$onboarding_profile = get_option( 'woocommerce_onboarding_profile', array() );
		if ( is_array( $onboarding_profile ) && array_key_exists( 'business_extensions', $onboarding_profile ) ) {
			if ( ! is_array( $onboarding_profile['business_extensions'] ) ||
				! in_array( 'woocommerce-payments', $onboarding_profile['business_extensions'], true )
			) {
				return;
			}
		}

		self::possibly_add_note();
	}

	/**
	 * Executes when the WooCommerce Payments plugin is deactivated. Possibly
	 * hard-deletes the note if it is in the database. Hard-delete is used
	 * instead of soft-delete or actioning the note because we need to
	 * show the note if the plugin is activated again.
	 */
	public static function on_deactivate_wcpay() {
		self::possibly_delete_note();
	}

	/**
	 * Check if this note should exist.
	 */
	public static function is_applicable() {
		$woocommerce_payments = new WooCommercePayments();
		return ! $woocommerce_payments->can_view();
	}

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$content = __( 'Set up additional payment providers, using third-party services or other methods.', 'woocommerce-admin' );

		$note = new Note();

		$note->set_title( __( 'Set up additional payment providers', 'woocommerce-admin' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'set-up-payments',
			__( 'Set up payments', 'woocommerce-admin' ),
			wc_admin_url( '&task=payments' ),
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			true
		);

		return $note;
	}
}
