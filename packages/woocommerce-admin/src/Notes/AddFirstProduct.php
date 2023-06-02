<?php
/**
 * WooCommerce Admin: Add First Product.
 *
 * Adds a note (type `email`) to bring the client back to the store setup flow.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Add_First_Product.
 */
class AddFirstProduct {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-add-first-product-note';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		// We want to show the note after 3 days and before 30.
		if ( ! self::wc_admin_active_for( 3 * DAY_IN_SECONDS ) || self::wc_admin_active_for( 30 * DAY_IN_SECONDS ) ) {
			return;
		}

		// Don't show if there is a product.
		$query    = new \WC_Product_Query(
			array(
				'limit'  => 1,
				'return' => 'ids',
				'status' => array( 'publish' ),
			)
		);
		$products = $query->get_products();
		if ( 0 !== count( $products ) ) {
			return;
		}

		// Don't show if there is an orders.
		$args   = array(
			'limit'  => 1,
			'return' => 'ids',
		);
		$orders = wc_get_orders( $args );
		if ( 0 !== count( $orders ) ) {
			return;
		}

		$content_lines = array(
			__( 'Nice one, you’ve created a WooCommerce store! Now it’s time to add your first product.<br/><br/>', 'woocommerce' ),
			__( 'There are three ways to add your products: you can <strong>create products manually, import them at once via CSV file</strong>, or <strong>migrate them from another service</strong>.<br/><br/>', 'woocommerce' ),
			__( '<a href="https://docs.woocommerce.com/document/managing-products/?utm_source=help_panel">Explore our docs</a> for more information, or just get started!', 'woocommerce' ),
		);

		$additional_data = array(
			'role' => 'administrator',
		);

		$note = new Note();
		$note->set_title( __( 'Store setup', 'woocommerce' ) );
		$note->set_content( implode( '', $content_lines ) );
		$note->set_content_data( (object) $additional_data );
		$note->set_image(
			plugins_url(
				'/images/admin_notes/openbox+purple.png',
				WC_ADMIN_PLUGIN_FILE
			)
		);
		$note->set_type( Note::E_WC_ADMIN_NOTE_EMAIL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'add-first-product', __( 'Add a product', 'woocommerce' ), admin_url( 'admin.php?page=wc-admin&task=products' ) );
		return $note;
	}
}
