<?php
/**
 * BlockTemplatesServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\BlockTemplatesController;
use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\BlockTemplateRegistry;
use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\TemplateTransformer;

/**
 * Service provider for the block templates controller classes in the Automattic\WooCommerce\Internal\BlockTemplateRegistry namespace.
 */
class BlockTemplatesServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		BlockTemplateRegistry::class,
		BlockTemplatesController::class,
		TemplateTransformer::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( TemplateTransformer::class );
		$this->share( BlockTemplateRegistry::class );
        $this->share( BlockTemplatesController::class )->addArguments(
			array(
                BlockTemplateRegistry::class,
				TemplateTransformer::class,
			)
		);
	}
}
