<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreviewRestController;

/**
 * Service provider for the EmailPreview namespace.
 */
class EmailPreviewServiceProvider extends AbstractInterfaceServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		EmailPreview::class,
		EmailPreviewRestController::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( EmailPreview::class );
		$this->share_with_implements_tags( EmailPreviewRestController::class );
	}
}
