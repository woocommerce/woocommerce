<?php
/**
 * WooCommerce Product Editor Product Template compatibility shim.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * Removed product editor product template value object.
 *
 * @deprecated 10.9.0 Product editor extension APIs were deprecated. The product block editor was removed in 11.0.0 with no replacement.
 */
class ProductTemplate {
	/**
	 * Whether the removal warning has already been logged for the current request.
	 *
	 * @var bool
	 */
	private static $removal_warning_logged = false;

	/**
	 * Constructor.
	 *
	 * @param array $data Template data.
	 */
	public function __construct( array $data ) {
		unset( $data );

		self::maybe_log_removal_warning();
	}

	/**
	 * Get the template ID.
	 *
	 * @return string
	 */
	public function get_id() {
		self::maybe_log_removal_warning();

		return '';
	}

	/**
	 * Get the template title.
	 *
	 * @return string
	 */
	public function get_title() {
		self::maybe_log_removal_warning();

		return '';
	}

	/**
	 * Get the layout template ID.
	 *
	 * @return string|null
	 */
	public function get_layout_template_id() {
		self::maybe_log_removal_warning();

		return null;
	}

	/**
	 * Set the layout template ID.
	 *
	 * @param string $layout_template_id The layout template ID.
	 * @return void
	 */
	public function set_layout_template_id( string $layout_template_id ) {
		unset( $layout_template_id );

		self::maybe_log_removal_warning();
	}

	/**
	 * Get the product data.
	 *
	 * @return array
	 */
	public function get_product_data() {
		self::maybe_log_removal_warning();

		return array();
	}

	/**
	 * Get the template description.
	 *
	 * @return string|null
	 */
	public function get_description() {
		self::maybe_log_removal_warning();

		return null;
	}

	/**
	 * Set the template description.
	 *
	 * @param string $description The template description.
	 * @return void
	 */
	public function set_description( string $description ) {
		unset( $description );

		self::maybe_log_removal_warning();
	}

	/**
	 * Get the template icon.
	 *
	 * @return string|null
	 */
	public function get_icon() {
		self::maybe_log_removal_warning();

		return null;
	}

	/**
	 * Set the template icon.
	 *
	 * @param string $icon The icon name or an external image URL.
	 * @return void
	 */
	public function set_icon( string $icon ) {
		unset( $icon );

		self::maybe_log_removal_warning();
	}

	/**
	 * Get the template order.
	 *
	 * @return int
	 */
	public function get_order() {
		self::maybe_log_removal_warning();

		return 999;
	}

	/**
	 * Get the selectable attribute.
	 *
	 * @return bool
	 */
	public function get_is_selectable_by_user() {
		self::maybe_log_removal_warning();

		return false;
	}

	/**
	 * Set the template order.
	 *
	 * @param int $order The template order.
	 * @return void
	 */
	public function set_order( int $order ) {
		unset( $order );

		self::maybe_log_removal_warning();
	}

	/**
	 * Get the product template as JSON-like data.
	 *
	 * @return array
	 */
	public function to_json() {
		self::maybe_log_removal_warning();

		return array();
	}

	/**
	 * Log a warning about the removed compatibility class.
	 */
	private static function maybe_log_removal_warning(): void {
		if ( self::$removal_warning_logged || ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		self::$removal_warning_logged = true;

		wc_get_logger()->warning(
			'Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplate is a temporary compatibility shim and will be removed soon. Product editor extension APIs were deprecated in WooCommerce 10.9.0, and the product block editor was removed in WooCommerce 11.0.0 with no replacement.',
			array( 'source' => 'product-block-editor' )
		);
	}
}
