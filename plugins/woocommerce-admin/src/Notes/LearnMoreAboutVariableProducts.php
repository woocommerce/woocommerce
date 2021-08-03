<?php
/**
 * WooCommerce Admin learn more about variable products note provider
 *
 * Adds a note when the store owner adds the first product.
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
class LearnMoreAboutVariableProducts {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-learn-more-about-variable-products';

	/**
	 * Add transition_post_status action.
	 *
	 * LearnMoreAboutVariableProducts constructor.
	 */
	public function __construct() {
		add_action( 'transition_post_status', array( $this, 'maybe_add_new_note' ), 10, 3 );
	}

	/**
	 * Maybe attempt to add a new note if product is published.
	 *
	 * @param string $new_status new status.
	 * @param string $old_status old status.
	 * @param object $post post object.
	 */
	public function maybe_add_new_note( $new_status, $old_status, $post ) {
		if ( 'publish' === $new_status && 'publish' !== $old_status && 'product' === $post->post_type ) {
			$product = wc_get_product( $post->ID );
			if ( ! $product ) {
				return;
			}

			$product->is_type( 'simple' ) && static::possibly_add_note();
		}
	}

	/**
	 * Get the note.
	 *
	 * @return Note|null
	 */
	public static function get_note() {
		$note = new Note();
		$note->set_title( __( 'Learn more about variable products', 'woocommerce-admin' ) );
		$note->set_content(
			__(
				'Variable products are a powerful product type that lets you offer a set of variations on a product, with control over prices, stock, image and more for each variation. They can be used for a product like a shirt, where you can offer a large, medium and small and in different colors.',
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
			'https://docs.woocommerce.com/document/variable-product/?utm_source=inbox&utm_medium=product'
		);

		return $note;
	}
}
