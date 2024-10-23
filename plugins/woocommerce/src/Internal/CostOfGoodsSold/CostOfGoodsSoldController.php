<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CostOfGoodsSold;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * Main controller for the Cost of Goods Sold feature.
 */
class CostOfGoodsSoldController implements RegisterHooksInterface {

	use AccessiblePrivateMethods;

	/**
	 * The instance of FeaturesController to use.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Register hooks.
	 */
	public function register() {
		self::add_action( 'woocommerce_register_feature_definitions', array( $this, 'add_feature_definition' ) );
	}

	/**
	 * Initialize the instance, runs when the instance is created by the dependency injection container.
	 *
	 * @internal
	 * @param FeaturesController $features_controller The instance of FeaturesController to use.
	 */
	final public function init( FeaturesController $features_controller ) {
		$this->features_controller = $features_controller;
	}

	/**
	 * Is the Cost of Goods Sold engine enabled?
	 *
	 * @return bool True if the engine is enabled, false otherwise.
	 */
	public function feature_is_enabled(): bool {
		return $this->features_controller->feature_is_enabled( 'cost_of_goods_sold' );
	}

	/**
	 * Add the feature information for the features settings page.
	 *
	 * @param FeaturesController $features_controller The instance of FeaturesController to use.
	 */
	private function add_feature_definition( $features_controller ) {
		$definition = array(
			'description'        => __( 'Allows entering cost of goods sold information for products. Feature under active development, enable only for testing purposes', 'woocommerce' ),
			'is_experimental'    => true,
			'enabled_by_default' => false,
		);

		$features_controller->add_feature_definition(
			'cost_of_goods_sold',
			__( 'Cost of Goods Sold', 'woocommerce' ),
			$definition
		);
	}
}
