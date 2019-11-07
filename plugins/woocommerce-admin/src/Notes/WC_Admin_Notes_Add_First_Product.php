<?php
/**
 * WooCommerce Admin Add First Product Note Provider.
 *
 * Adds a note to the merchant's inbox prompting them to add their first product.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Add_First_Product
 */
class WC_Admin_Notes_Add_First_Product {
	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-add-first-product';

	/**
	 * Possibly add the note.
	 */
	public static function possibly_add_first_product_note() {
		// Only show the note to stores without products.
		$products = wp_count_posts( 'product' );

		if ( 0 < (int) $products->publish ) {
			return;
		}

		$data_store = \WC_Data_Store::load( 'admin-note' );

		// We already have this note? Then exit, we're done.
		$note_ids = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		$content = __( 'Grow your revenue by adding products to your store. Add products manually, import from a sheet, or migrate from another platform.', 'woocommerce-admin' );

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Add your first product', 'woocommerce-admin' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'product' );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'add-a-product', __( 'Add a product', 'woocommerce-admin' ), admin_url( 'post-new.php?post_type=product' ), WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED, true );

		$note->save();
	}
}
