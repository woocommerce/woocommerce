<?php
/**
 * ImportExportServiceProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Admin\ImportExport\CSVUploadHelper;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

/**
 * Service provider for the import/export classes.
 *
 * @since 9.3.0
 */
class ImportExportServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		CSVUploadHelper::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( CSVUploadHelper::class );
	}
}
