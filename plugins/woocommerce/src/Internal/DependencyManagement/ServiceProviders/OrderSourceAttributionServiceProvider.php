<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Orders\SourceAttributionController;
use Automattic\WooCommerce\Proxies\LegacyProxy;

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
	}
}
