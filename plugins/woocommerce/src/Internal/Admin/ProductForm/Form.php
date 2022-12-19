<?php
/**
 * WooCommerce Product Form
 *
 * @package Woocommerce ProductForm
 */

namespace Automattic\WooCommerce\Internal\Admin\ProductForm;

/**
 * Contains logic for the WooCommerce Product Form.
 */
class Form {

	/**
	 * Class instance.
	 *
	 * @var Form instance
	 */
	protected static $instance = null;

	/**
	 * Store form fields.
	 *
	 * @var array
	 */
	protected static $form_fields = array();

	/**
	 * Get class instance.
	 */
	final public static function instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Init.
	 */
	public function init() {    }

	/**
	 * Adds a child menu item to the navigation.
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
	public static function add_field( $id, $plugin_id, $args ) {
		if ( isset( self::$form_fields[ $id ] ) ) {
			error_log(  // phpcs:ignore
				sprintf(
				/* translators: 1: Duplicate registered field id. */
					esc_html__( 'You have attempted to register a duplicate form field with WooCommerce Form: %1$s', 'woocommerce' ),
					'`' . $id . '`'
				)
			);
			return;
		}

		$missing_arguments = Field::get_missing_arguments( $args );
		if ( count( $missing_arguments ) > 0 ) {
			error_log(  // phpcs:ignore
				sprintf(
				/* translators: 1: Missing arguments list. */
					esc_html__( 'You are missing required arguments of WooCommerce ProductForm Field: %1$s', 'woocommerce' ),
					join( ', ', $missing_arguments )
				)
			);
			return;
		}

		$defaults = array(
			'order' => 20,
		);

		$field_arguments = wp_parse_args( $args, $defaults );

		$new_field                = new Field( $id, $plugin_id, $field_arguments );
		self::$form_fields[ $id ] = $new_field;
	}

	/**
	 * Returns form config.
	 *
	 * @return array form config.
	 */
	public static function get_form_config() {
		return array(
			'fields' => self::get_fields(),
		);
	}

	/**
	 * Returns list of registered fields.
	 *
	 * @return array list of registered fields.
	 */
	public static function get_fields() {
		return array_values( self::$form_fields );
	}
}

