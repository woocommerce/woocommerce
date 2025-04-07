<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\PointOfSale;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Class that handles the Point of Sale feature.
 */
class PointOfSaleController implements RegisterHooksInterface {

    /**
     * The FeaturesController instance.
     *
     * @var FeaturesController
     */
    private FeaturesController $features_controller;

    /**
     * Register all hooks used by the class.
     */
    public function register() {
        if ( ! $this->feature_is_enabled() ) {
            return;
        }

        // Add hooks here when feature is enabled
        add_action( 'admin_init', array( $this, 'init_pos_settings' ) );
    }

    /**
	 * Initialize the instance, runs when the instance is created by the dependency injection container.
	 *
	 * @internal
	 * @param FeaturesController $features_controller The instance of FeaturesController to use.
	 */
	final public function init( FeaturesController $features_controller ) {
		$this->features_controller = $features_controller;
        // $this->add_feature_definition( $features_controller );
	}

	/**
	 * Is the Point of Sale feature enabled?
	 *
	 * @return bool True if the feature is enabled, false otherwise.
	 */
	public function feature_is_enabled(): bool {
		return $this->features_controller->feature_is_enabled( 'point_of_sale' );
	}

    /**
	 * Add the feature information for the features settings page.
	 *
	 * @param FeaturesController $features_controller The instance of FeaturesController to use.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function add_feature_definition( $features_controller ) {
		$definition = array(
            'description'        => __( 'Enable Point of Sale functionality for your WooCommerce store', 'woocommerce' ),
            'is_experimental'    => true,
            'enabled_by_default' => true,
            'order'             => 20,
            'disable_ui'        => true,
		);

		$features_controller->add_feature_definition(
			'point_of_sale',
			__( 'Point of Sale', 'woocommerce' ),
			$definition
		);
	}

    /**
     * Initialize POS settings.
     */
    public function init_pos_settings() {
        // Add settings initialization here when needed
    }
}
