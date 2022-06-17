<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\GraphQL\DateTimeType;
use Automattic\WooCommerce\Internal\GraphQL\GraphQLController;
use Automattic\WooCommerce\Internal\GraphQL\OrderAddressType;
use Automattic\WooCommerce\Internal\GraphQL\OrderAddressTypeType;
use Automattic\WooCommerce\Internal\GraphQL\OrderRetriever;
use Automattic\WooCommerce\Internal\GraphQL\OrderType;
use Automattic\WooCommerce\Internal\GraphQL\RootQueryType;

/**
 * Service provider for the classes in the Automattic\WooCommerce\Internal\GraphQL namespace.
 */
class GraphQLServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		GraphQLController::class,
		OrderRetriever::class,
		RootQueryType::class,
		OrderType::class,
		OrderAddressType::class,
		OrderAddressTypeType::class,
		DateTimeType::class
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( GraphQLController::class );
		$this->share( OrderRetriever::class );
		$this->share( RootQueryType::class );
		$this->share( OrderType::class );
		$this->share( OrderAddressType::class );
		$this->share( OrderAddressTypeType::class );
		$this->share( DateTimeType::class );
	}
}
