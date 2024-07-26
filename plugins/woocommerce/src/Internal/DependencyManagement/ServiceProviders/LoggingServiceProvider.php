<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Admin\Logging\{ PageController, Settings };
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\Logging\RemoteLogger;

/**
 * LoggingServiceProvider class.
 */
class LoggingServiceProvider extends AbstractServiceProvider {
	/**
	 * List services provided by this class.
	 *
	 * @var string[]
	 */
	protected $provides = array(
		FileController::class,
		PageController::class,
		Settings::class,
		RemoteLogger::class,
	);

	/**
	 * Registers services provided by this class.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( FileController::class );

		$this->share( PageController::class )->addArguments(
			array(
				FileController::class,
				Settings::class,
			)
		);

		$this->share( Settings::class );

		$this->share( RemoteLogger::class );
	}
}
