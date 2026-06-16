<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyStoreCurrencyLifecycleController;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRuntimeServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStoreCurrencyLifecycleService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyStoreCurrencyLifecycleController class.
 */
class MultiCurrencyStoreCurrencyLifecycleControllerTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'init' );

		parent::tearDown();
	}

	/**
	 * @testdox Should not register init hook when plugin owns runtime.
	 */
	public function test_does_not_register_init_hook_when_plugin_owns_runtime(): void {
		$service = $this->create_lifecycle_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN, $service );

		$sut->register();

		$this->assertFalse( has_action( 'init', array( $sut, 'handle_init' ) ) );
	}

	/**
	 * @testdox Should not register init hook when no runtime owns site.
	 */
	public function test_does_not_register_init_hook_when_no_runtime_owns_site(): void {
		$service = $this->create_lifecycle_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_NONE, $service );

		$sut->register();

		$this->assertFalse( has_action( 'init', array( $sut, 'handle_init' ) ) );
	}

	/**
	 * @testdox Should register init hook once when core owns runtime.
	 */
	public function test_registers_init_hook_once_when_core_owns_runtime(): void {
		$service = $this->create_lifecycle_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_action( 'init', array( $sut, 'handle_init' ) ) );
	}

	/**
	 * @testdox Should synchronize store currency on init.
	 */
	public function test_handle_init_synchronizes_store_currency(): void {
		$service = $this->create_lifecycle_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );

		$sut->handle_init();

		$this->assertSame( 1, $service->calls );
	}

	/**
	 * Create a store currency lifecycle controller.
	 *
	 * @param string                                     $owner   Runtime owner.
	 * @param MultiCurrencyStoreCurrencyLifecycleService $service Lifecycle service.
	 * @return MultiCurrencyStoreCurrencyLifecycleController
	 */
	private function create_controller(
		string $owner,
		MultiCurrencyStoreCurrencyLifecycleService $service
	): MultiCurrencyStoreCurrencyLifecycleController {
		$controller = new MultiCurrencyStoreCurrencyLifecycleController();
		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyRuntimeServiceFactory::class )
		);
		$controller->set_lifecycle_service( $service );

		return $controller;
	}

	/**
	 * Create a lifecycle service test double.
	 *
	 * @return MultiCurrencyStoreCurrencyLifecycleService
	 */
	private function create_lifecycle_service(): MultiCurrencyStoreCurrencyLifecycleService {
		return new class() extends MultiCurrencyStoreCurrencyLifecycleService {
			/**
			 * Call count.
			 *
			 * @var int
			 */
			public int $calls = 0;

			/**
			 * Constructor.
			 */
			public function __construct() {}

			/**
			 * Synchronize store currency.
			 *
			 * @return bool
			 */
			public function synchronize_store_currency(): bool {
				++$this->calls;

				return true;
			}
		};
	}

	/**
	 * Create a static multi-currency runtime arbiter.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyRuntimeArbiter
	 */
	private function create_arbiter( string $owner ): MultiCurrencyRuntimeArbiter {
		return new class( $owner ) extends MultiCurrencyRuntimeArbiter {
			/**
			 * Runtime owner.
			 *
			 * @var string
			 */
			private string $owner;

			/**
			 * Constructor.
			 *
			 * @param string $owner Runtime owner.
			 */
			public function __construct( string $owner ) {
				$this->owner = $owner;
			}

			/**
			 * Get the multi-currency runtime owner for the current site.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}

			/**
			 * Tell whether core multi-currency may register hooks.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}
}
