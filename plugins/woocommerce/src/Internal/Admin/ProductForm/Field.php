<?php
/**
 * Handles product form field related methods.
 */

namespace Automattic\WooCommerce\Internal\Admin\ProductForm;

/**
 * Field class.
 */
class Field extends Component {
	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Array of required arguments.
	 *
	 * @var array
	 */
	const REQUIRED_ARGUMENTS = array(
		'type',
		'section',
		'properties.name',
		'properties.label',
	);

	/**
	 * Constructor
	 *
	 * @param string $id Field id.
	 * @param string $plugin_id Plugin id.
	 * @param array  $additional_args Array containing the necessary arguments.
	 *     $args = array(
	 *       'type'            => (string) Field type. Required.
	 *       'section'         => (string) Field location. Required.
	 *       'order'           => (int) Field order.
	 *       'properties'      => (array) Field properties.
	 *     ).
	 * @throws \Exception If there are missing arguments.
	 */
	public function __construct( $id, $plugin_id, $additional_args ) {
		parent::__construct( $id, $plugin_id, $additional_args );

		$missing_arguments = self::get_missing_arguments( $additional_args );
		if ( count( $missing_arguments ) > 0 ) {
			throw new \Exception(
				sprintf(
				/* translators: 1: Missing arguments list. */
					esc_html__( 'You are missing required arguments of WooCommerce ProductForm Field: %1$s', 'woocommerce' ),
					join( ', ', $missing_arguments )
				)
			);
		}
		$this->type = $additional_args['type'];
	}

	/**
	 * Get missing arguments of args array.
	 *
	 * @param array $args field arguments.
	 * @return array
	 */
	public static function get_missing_arguments( $args ) {
		return array_values(
			array_filter(
				self::REQUIRED_ARGUMENTS,
				function( $arg_key ) use ( $args ) {
					return null === self::get_argument_from_path( $args, $arg_key );
				}
			)
		);
	}
}
