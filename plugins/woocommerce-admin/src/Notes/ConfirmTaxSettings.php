<?php
/**
 * WooCommerce Admin: Confirm tax settings
 *
 * Adds a note to ask the user to confirm tax settings after automated taxes
 * has been automatically enabled (see OnboardingAutomateTaxes).
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * ConfirmTaxSettings.
 */
class ConfirmTaxSettings {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-confirm-tax-settings';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$note = new Note();

		$note->set_title( __( 'Confirm tax settings', 'woocommerce-admin' ) );
		$note->set_content( __( 'Automated tax calculations are enabled on your store through WooCommerce Shipping & Tax. Learn more about automated taxes <a href="https://docs.woocommerce.com/document/woocommerce-services/#section-12">here</a>.', 'woocommerce-admin' ) );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'confirm-tax-settings_edit-tax-settings',
			__( 'Edit tax settings', 'woocommerce-admin' ),
			admin_url( 'admin.php?page=wc-settings&tab=tax' ),
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			true
		);

		return $note;
	}
}
