<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\AdminLists\Orders;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

class AdminListServiceProvider extends AbstractServiceProvider {

	protected $provides = array(
		Orders::class
	);

	public function register() {
		$this->share( Orders::class )->addArgument( OrdersTableDataStore::class );
	}
}
