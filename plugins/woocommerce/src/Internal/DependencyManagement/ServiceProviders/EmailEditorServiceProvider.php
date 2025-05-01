<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\EmailPatterns\PatternsController;
use Automattic\WooCommerce\Internal\EmailEditor\EmailApiController;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\TemplateApiController;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\TemplatesController;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\PageRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTagManager;
use Automattic\WooCommerce\Internal\EmailEditor\TransactionalEmailPersonalizer;
use Automattic\WooCommerce\Internal\EmailEditor\WooContentProcessor;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;

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
		WooContentProcessor::class,
		BlockEmailRenderer::class,
		TemplateApiController::class,
		WCTransactionalEmails::class,
		EmailApiController::class,
		TransactionalEmailPersonalizer::class,
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
		$this->share( WooContentProcessor::class );
		$this->share( BlockEmailRenderer::class )->addArgument( WooContentProcessor::class );
		$this->share( WCTransactionalEmails::class );
		$this->share( TemplateApiController::class );
		$this->share( EmailApiController::class );
		$this->share( TransactionalEmailPersonalizer::class );
	}
}
