<?php
/**
 * New - filter by product variations in order and product reports.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * FilterByProductVariationsInReports.
 */
class FilterByProductVariationsInReports {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-filter-by-product-variations-in-reports';

	/**
	 * Get the note
	 *
	 * @return Note|null
	 */
	public static function get_note() {
		if ( ! self::are_products_with_variations() ) {
			return null;
		}

		$note = new Note();
		$note->set_title( __( 'New - filter by product variations in orders and products reports', 'woocommerce' ) );
		$note->set_content( __( 'One of the most awaited features has just arrived! You can now have insights into each product variation in the orders and products reports.', 'woocommerce' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'banner' );
		$note->set_image(
			plugins_url(
				'/images/admin_notes/filter-by-product-variations-note.svg',
				WC_ADMIN_PLUGIN_FILE
			)
		);
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce' ),
			'https://docs.woocommerce.com/document/woocommerce-analytics/#variations-report'
		);

		return $note;
	}

	/**
	 * Returns whether or not there are variable products.
	 *
	 * @return bool If there are variable products
	 */
	private static function are_products_with_variations() {
		$query    = new \WC_Product_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
				'status'   => array( 'publish' ),
				'type'     => array( 'variable' ),
			)
		);
		$products = $query->get_products();
		$count    = $products->total;

		return 0 !== $count;
	}
}
