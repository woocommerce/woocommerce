<?php
/**
 * Handles product form section related methods.
 */

namespace Automattic\WooCommerce\Internal\Admin\ProductForm;

/**
 * Section class.
 */
class Section {
	/**
	 * Product Component traits.
	 */
	use ComponentTrait;

	/**
	 * Section title.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Section additional arguments.
	 *
	 * @var array
	 */
	protected $additional_args;

	/**
	 * Array of required arguments.
	 *
	 * @var array
	 */
	const REQUIRED_ARGUMENTS = array(
		'title',
	);

	/**
	 * Constructor
	 *
	 * @param string $id Section id.
	 * @param string $plugin_id Plugin id.
	 * @param array  $additional_args Array containing additional arguments.
	 *     $args = array(
	 *       'order'       => (int) Section order.
	 *       'title'       => (string) Section description.
	 *       'description' => (string) Section description.
	 *     ).
	 */
	public function __construct( $id, $plugin_id, $additional_args ) {
		$this->id              = $id;
		$this->title           = $additional_args['title'];
		$this->plugin_id       = $plugin_id;
		$this->additional_args = $additional_args;
	}

	/**
	 * Get missing arguments of args array.
	 *
	 * @param array $args section arguments.
	 * @return array
	 */
	public static function get_missing_arguments( $args ) {
		return array_filter(
			self::REQUIRED_ARGUMENTS,
			function( $arg_key ) use ( $args ) {
				return ! isset( $args[ $arg_key ] );
			}
		);
	}

	/**
	 * Sorting function for sections.
	 *
	 * @param Section $a Section a.
	 * @param Section $b Section b.
	 * @param array   $sort_by key and order to sort by.
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
