<?php
/**
 * DownloadUtilServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\DownloadUtil;

/**
 * Service provider for the DownloadUtil class.
 */
class DownloadUtilServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		DownloadUtil::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( DownloadUtil::class );
	}
}
