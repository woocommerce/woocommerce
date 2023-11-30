<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Integrations\WPConsentAPI;
use Automattic\WooCommerce\Internal\Orders\OrderAttributionController;
use Automattic\WooCommerce\Internal\Orders\OrderAttributionBlocksController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Class OrderAttributionServiceProvider
 *
 * @since 8.5.0
 */
class OrderAttributionServiceProvider extends AbstractInterfaceServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		OrderAttributionController::class,
		OrderAttributionBlocksController::class,
		WPConsentAPI::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share_with_implements_tags( OrderAttributionController::class )
			->addArguments(
				array(
					LegacyProxy::class,
					FeaturesController::class,
				)
			);
		$this->share_with_implements_tags( OrderAttributionBlocksController::class )
			->addArguments(
				array(
					StoreApi::container()->get( ExtendSchema::class ),
					FeaturesController::class,
					OrderAttributionController::class,
				)
			);
		$this->share_with_implements_tags( WPConsentAPI::class );
	}
}
