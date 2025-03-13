<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\PageRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTagManager;
use Automattic\WooCommerce\Internal\EmailEditor\EmailPatterns\PatternsController;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\TemplatesController;
use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;

/**
 * Service provider for the EmailEditor namespace.
 */
class EmailEditorServiceProvider extends AbstractInterfaceServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		Integration::class,
		PageRenderer::class,
		PersonalizationTagManager::class,
		PatternsController::class,
		TemplatesController::class,
		BlockEmailRenderer::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( Integration::class );
		$this->share( PageRenderer::class );
		$this->share( PersonalizationTagManager::class );
		$this->share( PatternsController::class );
		$this->share( TemplatesController::class );
		$this->share( BlockEmailRenderer::class );
	}
}
