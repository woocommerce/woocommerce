<?php
/**
 * SettingsImportExportServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\DownloadUtil;
use Automattic\WooCommerce\Internal\SettingsImportExport;

/**
 * Service provider for the SettingsImportExport class.
 */
class SettingsImportExportServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		SettingsImportExport::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( SettingsImportExport::class )->addArgument( DownloadUtil::class );
	}
}
