<?php
/**
 * WooCommerce Admin Digital/Downloadable Producdt Handling note provider
 *
 * Adds a note with a link to the downlaodable product handling
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * FirstDownlaodableProduct.
 */
class FirstDownlaodableProduct {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-first-downloadable-product';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$query    = new \WC_Product_Query(
			array(
				'limit'        => 1,
				'paginate'     => true,
				'return'       => 'ids',
				'downloadable' => 1,
				'status'       => array( 'publish' ),
			)
		);
		$products = $query->get_products();

		// There must be at least 1 downloadable product.
		if ( 0 === $products->total ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Learn more about digital/downloadable products', 'woocommerce-admin' ) );
		$note->set_content(
			__(
				'Congrats on adding your first digital product! You can learn more about how to handle digital or downloadable products in our documentation.',
				'woocommerce-admin'
			)
		);
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'first-downloadable-product-handling',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/document/digital-downloadable-product-handling/?utm_source=inbox&utm_medium=product'
		);

		return $note;
	}
}
