<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Integrations\WPConsentAPI;
use Automattic\WooCommerce\Internal\Orders\SourceAttributionController;
use Automattic\WooCommerce\Internal\Orders\SourceAttributionBlocksController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Class OrderSourceAttributionServiceProvider
 *
 * @since x.x.x
 */
class OrderSourceAttributionServiceProvider extends AbstractInterfaceServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		SourceAttributionController::class,
		SourceAttributionBlocksController::class,
		WPConsentAPI::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share_with_implements_tags( SourceAttributionController::class )
			->addArguments(
				array(
					LegacyProxy::class,
					FeaturesController::class,
				)
			);
		$this->share_with_implements_tags( SourceAttributionBlocksController::class )
			->addArguments(
				array(
					// Once Blocks are moved to the monorepo, hopefully, we can use the following code.

					/*
					AssetApi::class,
					*/
					StoreApi::container()->get( ExtendSchema::class ),
					FeaturesController::class,
					SourceAttributionController::class,
				)
			);
		$this->share_with_implements_tags( WPConsentAPI::class );
	}
}
