<?php
/**
 * Variable product module.
 *
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variable product module class.
 */
class WC_Product_Module_Variable extends WC_Product_Module {

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 3.0.0
	 * @var object
	 */
	protected $data_store;

	/**
	 * Array of children variation IDs. Determined by children.
	 *
	 * @var array
	 */
	protected $children = null;

	/**
	 * Constructor.
	 */
	public function __construct( $product ) {
		parent::__construct( $product );

		$this->data_store = WC_Data_Store::load( 'product-variable' );
	}

	/**
	 * Get the name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'Variable';
	}

    /**
	 * Get the slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'variable';
	}

	public static function get_passthrough_methods() {
		return array(
			'is_variable'
		);
	}

	public function is_variable() {
		return true;
	}

	/**
	 * Get compatible modules.
	 */
	public static function get_compatible_modules() {
		return array_merge(
			array_keys( WC()->product_modules()->get_base_product_modules() ),
		);
	}

	/**
	 * Return a products child ids.
	 *
	 * This is lazy loaded as it's not used often and does require several queries.
	 *
	 * @param bool|string $visible_only Visible only.
	 * @return array Children ids
	 */
	public function get_children( $visible_only = '' ) {
		if ( is_bool( $visible_only ) ) {
			wc_deprecated_argument( 'visible_only', '3.0', 'WC_Product_Variable::get_visible_children' );

			return $visible_only ? $this->get_visible_children() : $this->get_children();
		}

		if ( null === $this->children ) {
			$children = $this->data_store->read_children( $this->product );
			$this->set_children( $children['all'] );
			$this->set_visible_children( $children['visible'] );
		}

		return apply_filters( 'woocommerce_get_children', $this->children, $this->product, false );
	}

	/**
	 * Sets an array of children for the product.
	 *
	 * @since 3.0.0
	 * @param array $children Children products.
	 */
	public function set_children( $children ) {
		$this->children = array_filter( wp_parse_id_list( (array) $children ) );
	}

	/**
	 * Sets an array of visible children only.
	 *
	 * @since 3.0.0
	 * @param array $visible_children List of visible children products.
	 */
	public function set_visible_children( $visible_children ) {
		$this->visible_children = array_filter( wp_parse_id_list( (array) $visible_children ) );
	}

}
