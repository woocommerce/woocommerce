<?php
/**
 * WooCommerce Admin Review Shipping Settings Note Provider.
 *
 * Adds an admin note prompting the admin to review their shipping settings.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Review_Shipping_Settings
 */
class ReviewShippingSettings {
	use NoteTraits;

	const NOTE_NAME = 'wc-admin-review-shipping-settings';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$note = new Note();

		$content_lines = array(
			__(
				"Based on the information that you provided we've configured some shipping rates and options for your store:<br/><br/>",
				'woocommerce-admin'
			),
			__( 'Domestic orders: free shipping<br/>', 'woocommerce-admin' ),
			__( 'International orders: disabled<br/>', 'woocommerce-admin' ),
		);

		if ( 'US' === WC()->countries->get_base_country() ) {
			array_push( $content_lines, __( 'Label printing: enabled', 'woocommerce-admin' ) );
		}

		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->set_title( __( 'Review your shipping settings', 'woocommerce-admin' ) );
		$note->set_content( implode( '', $content_lines ) );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );

		$note->add_action(
			'edit-shipping-settings',
			__( 'Edit shipping settings', 'woocommerce-admin' ),
			admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			true
		);

		return $note;
	}
}
