<?php
/**
 * Add a note to the merchant's inbox prompting them to set up additional
 * payment types if they have WooCommerce Payments activated.
 *
 * @package WooCommerce\Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Set_Up_Additional_Payment_Types
 */
class WC_Admin_Notes_Set_Up_Additional_Payment_Types {
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
	 * Executes when the WooCommerce Payments plugin is activated. Possibly
	 * adds the note if it isn't already in the database and if it matches any
	 * criteria (see get_note()).
	 */
	public static function on_activate_wcpay() {
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
	 * Get the note.
	 */
	public static function get_note() {
		$content = __( 'Set up additional payment providers, using third-party services or other methods.', 'woocommerce-admin' );

		$note = new WC_Admin_Note();

		$note->set_title( __( 'Set up additional payment providers', 'woocommerce-admin' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'set-up-payments',
			__( 'Set up payments', 'woocommerce-admin' ),
			wc_admin_url( '&task=payments' ),
			'unactioned',
			true
		);

		return $note;
	}
}
