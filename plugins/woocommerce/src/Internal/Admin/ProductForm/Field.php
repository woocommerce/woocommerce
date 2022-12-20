<?php
/**
 * Handles product form field related methods.
 */

namespace Automattic\WooCommerce\Internal\Admin\ProductForm;

/**
 * Task class.
 */
class Field {
	/**
	 * Task traits.
	 */
	use ComponentTrait;

	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Field arguments.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Array of default tasks.
	 *
	 * @var array
	 */
	const REQUIRED_ARGUMENTS = array(
		'name',
		'type',
		'location',
	);

	/**
	 * Constructor
	 *
	 * @param string $id Field id.
	 * @param string $plugin_id Plugin id.
	 * @param array  $args Array containing the necessary arguments.
	 *     $args = array(
	 *       'type'            => (string) Field type. Required.
	 *       'location'        => (string) Field location. Required.
	 *       'order'           => (int) Field order.
	 *       'properties'      => (array) Field properties.
	 *     ).
	 */
	public function __construct( $id, $plugin_id, $args ) {
		$this->id        = $id;
		$this->type      = $args['type'];
		$this->plugin_id = $plugin_id;
		$this->args      = $args;
	}

	/**
	 * Field arguments.
	 *
	 * @return array
	 */
	public function get_field_arguments() {
		return $this->args;
	}

	/**
	 * Get missing arguments of args array.
	 *
	 * @param array $args field arguments.
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
	 * Sorting function for fields.
	 *
	 * @param Field $a Field a.
	 * @param Field $b Field b.
	 * @param array $sort_by key and order to sort by.
	 * @return int
	 */
	public static function sort( $a, $b, $sort_by = array() ) {
		$key   = $sort_by['key'];
		$a_val = $a->args[ $key ] ?? false;
		$b_val = $b->args[ $key ] ?? false;
		if ( 'asc' === $sort_by['order'] ) {
			return $a_val <=> $b_val;
		} else {
			return $b_val <=> $a_val;
		}
	}
}
