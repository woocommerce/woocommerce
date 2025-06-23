<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments;

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
		$container = wc_get_container();
		foreach ( $this->provides as $class ) {
			$container->get( $class );
		}
	}
}
