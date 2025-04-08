<?php
/**
 * Service provider for order meta boxes.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomerHistory;
use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomMetaBox;
use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\OrderAttribution;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

/**
 * OrderMetaBoxServiceProvider class.
 */
class OrderMetaBoxServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		CustomerHistory::class,
		CustomMetaBox::class,
		OrderAttribution::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( CustomerHistory::class );
		$this->share( CustomMetaBox::class );
		$this->share( OrderAttribution::class );
	}
}
