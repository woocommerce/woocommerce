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
	 * Active modules.
	 *
	 * @var array
	 */
    private $active_modules = array();

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
	public function __construct( $product = 0 ) {
		parent::__construct( $product );
        $modules          = WC()->product_modules()->get_all_modules();
		$this->data_store = WC_Data_Store::load( 'product-modular' );

        foreach ( $modules as $module_class_name ) {
            $module                        = new $module_class_name( $this );
            $module_slug                   = $module::get_slug();
            $this->modules[ $module_slug ] = $module;
            $this->data                    = array_merge( $this->data, $module->get_extra_data() );
            $this->default_data            = $this->data;
            $passthrough_methods           = $module::get_passthrough_methods();
            foreach ( $passthrough_methods as $method ) {
                $this->aliased_methods[ $method ] = array( $module, $method );
            }
        }
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
        foreach ( $this->active_modules as $module_slug ) {
            if ( isset( $this->modules[ $module_slug ] ) && method_exists( $this->modules[ $module_slug ], 'get_deprecated_product_type' ) ) {
                return $this->modules[ $module_slug ]::get_deprecated_product_type();
            }
        }

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

    /**
	 * Activate a module by slug.
	 *
	 * @param string $module_slug Module slug.
	 */
	public function activate_module( $module_slug ) {
		$active_modules = $this->active_modules;
		$module_exists  = (bool) WC()->product_modules()->get_module( $module_slug );

		// @todo Could optionally do some compatibility checking here and throw warnings or disable other modules.
		if ( $module_exists && ! in_array( $module_slug, $active_modules ) ) {
			$active_modules[] = $module_slug;
		}

		$this->active_modules = $active_modules;
	}

	/**
	 * Deactivate a module by slug.
	 *
	 * @param string $module_slug Module slug.
	 */
	public function deactivate_module( $module_slug ) {
		$modules              = $this->get_prop( 'modules' );
		$this->active_modules = array_diff( $modules, array( $module_slug ) );
	}

    /**
     * Check if module is active.
     *
     * @return bool
     */
    public function is_module_active( $module_slug ) {
        return in_array( $module_slug, $this->active_modules, true );
    }

}
