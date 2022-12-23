<?php
/**
 * Handles product form section related methods.
 */

namespace Automattic\WooCommerce\Internal\Admin\ProductForm;

/**
 * Section class.
 */
class Section extends Component {
	/**
	 * Section title.
	 *
	 * @var string
	 */
	protected $title;

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
		parent::__construct( $id, $plugin_id, $additional_args );
		$this->title = $additional_args['title'];
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
				return null === self::get_argument_from_path( $args, $arg_key );
			}
		);
	}
}
