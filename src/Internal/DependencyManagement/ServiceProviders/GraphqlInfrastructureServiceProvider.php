<?php
/**
 * GraphqlInfrastructureServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\GraphQL\RootMutationType;
use Automattic\WooCommerce\Internal\GraphQL\RootQueryType;

/**
 * Service provider for the infrastructure classes in the Automattic\WooCommerce\Internal\GraphQL namespace.
 */
class GraphqlInfrastructureServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		RootQueryType::class,
		RootMutationType::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		foreach ( $this->provides as $class_name ) {
			$this->share_with_auto_arguments( $class_name );
		}
	}
}
