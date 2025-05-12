<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\AddressProvider\AddressProviderController;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use WC_Address_Provider;

/**
 * Service class for managing address providers.
 */
class AddressProviderServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array( AddressProviderController::class );

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( AddressProviderController::class );
	}
}
