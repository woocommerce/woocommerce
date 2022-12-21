<?php
/**
 * Handles product form card related methods.
 */

namespace Automattic\WooCommerce\Internal\Admin\ProductForm;

/**
 * Card class.
 */
class Card {
	/**
	 * Product Component traits.
	 */
	use ComponentTrait;

	/**
	 * Card additional arguments.
	 *
	 * @var array
	 */
	protected $additional_args;

	/**
	 * Constructor
	 *
	 * @param string $id Card id.
	 * @param string $plugin_id Plugin id.
	 * @param array  $additional_args Array containing additional arguments.
	 *     $args = array(
	 *       'order'       => (int) Card order.
	 *     ).
	 */
	public function __construct( $id, $plugin_id, $additional_args ) {
		$this->id              = $id;
		$this->plugin_id       = $plugin_id;
		$this->additional_args = $additional_args;
	}

	/**
	 * Sorting function for cards.
	 *
	 * @param Card  $a Card a.
	 * @param Card  $b Card b.
	 * @param array $sort_by key and order to sort by.
	 * @return int
	 */
	public static function sort( $a, $b, $sort_by = array() ) {
		$key   = $sort_by['key'];
		$a_val = $a->additional_args[ $key ] ?? false;
		$b_val = $b->additional_args[ $key ] ?? false;
		if ( 'asc' === $sort_by['order'] ) {
			return $a_val <=> $b_val;
		} else {
			return $b_val <=> $a_val;
		}
	}
}
