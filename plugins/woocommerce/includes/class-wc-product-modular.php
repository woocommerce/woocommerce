<?php
/**
 * Modular Product
 *
 * The WooCommerce product class handles individual product data.
 *
 * @version 3.0.0
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Modular product class.
 */
class WC_Product_Modular extends WC_Product {

    /**
	 * Modules.
	 *
	 * @var array
	 */
    private $modules = array();

    /**
	 * Aliased methods.
	 *
	 * @var array
	 */
    private $aliased_methods = array();

    /**
     * Allow modules to alias certain methods and pass through to the module.
     */
    public function __call( $name, $parameters ) {
        if ( ! isset( $this->aliased_methods[ $name ] ) ) {
            throw new BadMethodCallException( "Method {$name} does not exist." );
        }

        $method = $this->aliased_methods[ $name ];

        return call_user_func_array( $method, $parameters );
    }

    /**
	 * Get the product if ID is passed, otherwise the product is new and empty.
	 * This class should NOT be instantiated, but the wc_get_product() function
	 * should be used. It is possible, but the wc_get_product() is preferred.
	 *
	 * @param int|WC_Product|object $product Product to init.
	 * @param array                 $modules Modules to instantiate with the product.
	 */
	public function __construct( $product = 0, $modules = array() ) {
		parent::__construct( $product );

        foreach ( $modules as $module ) {
            $module_instance                      = new $module( $this );
            $this->modules[ $module::get_slug() ] = $module_instance;
            $passthrough_methods                  = $module::get_passthrough_methods();
            foreach ( $passthrough_methods as $method ) {
                $this->aliased_methods[ $method ] = array( $module_instance, $method );
            }
        }
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		// @todo Possibly return deprecated type to allow backwards compatibility.
		return 'modular';
	}

    /**
     * Get a module by slug.
     *
     * @param string $slug Module slug.
     * @return WC_Product_Module|false
     */
    public function get_module( $slug ) {
        if ( isset( $this->modules[ $slug ] ) ) {
            return $this->modules[ $slug ];
        }

        return false;
    }

}
