<?php
/**
 * WooCommerce Admin adding and managing products note provider
 *
 * Adds a note with a link to the manging products doc if store has been active > 3 days and
 * product count is 0
 *
 * @package WooCommerce\Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Class AddingAndManangingProducts
 *
 * @package Automattic\WooCommerce\Admin\Notes
 */
class AddingAndManangingProducts {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-adding-and-managing-products';

	/**
	 * Get the note.
	 *
	 * @return Note|null
	 */
	public static function get_note() {
		// Store must have been at least 3 days.
		if ( ! self::is_wc_admin_active_in_date_range( 'week-1', DAY_IN_SECONDS * 3 ) ) {
			return;
		}

		// Total # of products must be 0.
		$query = new \WC_Product_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
				'status'   => array( 'publish' ),
			)
		);

		$products = $query->get_products();
		if ( 0 !== $products->total ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Adding and Managing Products', 'woocommerce-admin' ) );
		$note->set_content(
			__(
				'Learn more about how to set up products in WooCommerce through our useful documentation about adding and managing products.',
				'woocommerce-admin'
			)
		);
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/document/managing-products/?utm_source=inbox&utm_medium=product'
		);

		return $note;
	}
}
