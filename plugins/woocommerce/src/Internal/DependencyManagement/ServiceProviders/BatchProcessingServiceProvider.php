<?php
/**
 * Service provider for the batch processing classes.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\OrderCouponDataMigrator;

/**
 * Class BatchProcessingServiceProvider
 *
 * @package Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders
 */
class BatchProcessingServiceProvider extends AbstractInterfaceServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @var string[]
	 */
	protected $provides = array(
		BatchProcessingController::class,
		OrderCouponDataMigrator::class,
	);

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( BatchProcessingController::class, new BatchProcessingController() );
		$this->share_with_implements_tags( OrderCouponDataMigrator::class );
	}
}
