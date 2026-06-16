<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\AccountCreationIntegration;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\CustomerEmailVerification;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\LoginGate;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\OrderLinker;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationEventListener;
use WC_Unit_Test_Case;

/**
 * Tests for the CustomerEmailVerification boot class.
 */
class CustomerEmailVerificationTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Boot class should resolve from the DI container.
	 */
	public function test_boot_class_resolves_from_container(): void {
		$instance = wc_get_container()->get( CustomerEmailVerification::class );

		$this->assertInstanceOf(
			CustomerEmailVerification::class,
			$instance,
			'CustomerEmailVerification must be resolvable from the DI container.'
		);
	}

	/**
	 * @testdox All subsystem controllers should resolve from the DI container.
	 */
	public function test_all_subsystem_controllers_resolve(): void {
		$container = wc_get_container();

		$this->assertInstanceOf(
			VerificationController::class,
			$container->get( VerificationController::class ),
			'VerificationController must be resolvable from the DI container.'
		);

		$this->assertInstanceOf(
			LoginGate::class,
			$container->get( LoginGate::class ),
			'LoginGate must be resolvable from the DI container.'
		);

		$this->assertInstanceOf(
			OrderLinker::class,
			$container->get( OrderLinker::class ),
			'OrderLinker must be resolvable from the DI container.'
		);

		$this->assertInstanceOf(
			AccountCreationIntegration::class,
			$container->get( AccountCreationIntegration::class ),
			'AccountCreationIntegration must be resolvable from the DI container.'
		);

		$this->assertInstanceOf(
			VerificationEventListener::class,
			$container->get( VerificationEventListener::class ),
			'VerificationEventListener must be resolvable from the DI container.'
		);
	}
}
