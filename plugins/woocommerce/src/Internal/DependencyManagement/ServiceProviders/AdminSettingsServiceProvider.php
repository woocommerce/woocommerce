<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\Jetpack\Connection\Manager as WPCOM_Connection_Manager;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments\WooPaymentsRestController;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsController;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsRestController;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestions;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Service provider for the admin settings controller classes in the Automattic\WooCommerce\Internal\Admin\Settings namespace.
 */
class AdminSettingsServiceProvider extends AbstractInterfaceServiceProvider {
	/**
	 * List services provided by this class.
	 *
	 * @var string[]
	 */
	protected $provides = array(
		PaymentsRestController::class,
		Payments::class,
		PaymentsController::class,
		PaymentProviders::class,
		// Provider-specific.
		WooPaymentsRestController::class,
		WooPaymentsService::class,
	);

	/**
	 * Registers services provided by this class.
	 */
	public function register() {
		$this->share( PaymentProviders::class )
			->addArgument( PaymentExtensionSuggestions::class );
		$this->share( Payments::class )
			->addArguments( array( PaymentProviders::class, PaymentExtensionSuggestions::class ) );
		$this->share( PaymentsController::class )
			->addArgument( Payments::class );
		$this->share_with_implements_tags( PaymentsRestController::class )
			->addArgument( Payments::class );

		// Provider-specific.
		$this->share( WooPaymentsService::class )
			->addArguments( array( PaymentProviders::class, LegacyProxy::class ) );
		$this->share_with_implements_tags( WooPaymentsRestController::class )
			->addArguments( array( Payments::class, WooPaymentsService::class ) );
	}
}
