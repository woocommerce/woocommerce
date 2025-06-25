<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Class FulfillmentsController
 *
 * Base controller for fulfillments management.
 */
class FulfillmentsController {

	/**
	 * Provides the list of classes that this controller provides.
	 *
	 * @var string[]
	 */
	private $provides = array(
		FulfillmentsRenderer::class,
		FulfillmentsManager::class,
		FulfillmentsSettings::class,
		OrderFulfillmentsRestController::class,
	);

	/**
	 * Initialize the controller.
	 *
	 * @return void
	 */
	public function register() {
		/**
		 * FeaturesController instance.
		 *
		 * @var FeaturesController $features_controller
		 */
		$features_controller = wc_get_container()->get( FeaturesController::class );
		if ( ! $features_controller->feature_is_enabled( 'fulfillments' ) ) {
			return;
		}

		$container = wc_get_container();
		foreach ( $this->provides as $class ) {
			$class = $container->get( $class );
			if ( method_exists( $class, 'register' ) ) {
				$class->register();
			}
		}
	}
}
