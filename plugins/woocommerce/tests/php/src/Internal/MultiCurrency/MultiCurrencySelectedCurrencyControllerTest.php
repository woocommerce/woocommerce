<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySelectedCurrencyController;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySelectedCurrencyPersistenceService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySelectedCurrencyController class.
 */
class MultiCurrencySelectedCurrencyControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the selected currency controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'init',
		'woocommerce_created_customer',
		'woocommerce_edit_account_form',
		'woocommerce_save_account_details',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		unset( $_GET['currency'], $_POST['wcpay_selected_currency'] );

		parent::tearDown();
	}

	/**
	 * @testdox Should not register selected currency hooks when plugin owns runtime.
	 */
	public function test_does_not_register_selected_currency_hooks_when_plugin_owns_runtime(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN, $service );

		$sut->register();

		$this->assertFalse( has_action( 'init', array( $sut, 'handle_init' ) ) );
		$this->assertFalse( has_action( 'woocommerce_save_account_details', array( $sut, 'handle_woocommerce_save_account_details' ) ) );
	}

	/**
	 * @testdox Should register selected currency hooks when core owns runtime.
	 */
	public function test_registers_selected_currency_hooks_when_core_owns_runtime(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );

		$sut->register();
		$sut->register();

		$this->assertSame( 11, has_action( 'init', array( $sut, 'handle_init' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_created_customer', array( $sut, 'handle_woocommerce_created_customer' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_edit_account_form', array( $sut, 'handle_woocommerce_edit_account_form' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_save_account_details', array( $sut, 'handle_woocommerce_save_account_details' ) ) );
	}

	/**
	 * @testdox Should register only account hooks in blocked request context.
	 */
	public function test_registers_only_account_hooks_in_blocked_request_context(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$service,
			$this->create_request_context( false )
		);

		$sut->register();

		$this->assertFalse( has_action( 'init', array( $sut, 'handle_init' ) ) );
		$this->assertFalse( has_action( 'woocommerce_created_customer', array( $sut, 'handle_woocommerce_created_customer' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_edit_account_form', array( $sut, 'handle_woocommerce_edit_account_form' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_save_account_details', array( $sut, 'handle_woocommerce_save_account_details' ) ) );
	}

	/**
	 * @testdox Should register writer hooks in Store API request context.
	 */
	public function test_registers_writer_hooks_for_store_api_context(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$service,
			$this->create_request_context( true )
		);

		$sut->register();

		$this->assertSame( 11, has_action( 'init', array( $sut, 'handle_init' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_created_customer', array( $sut, 'handle_woocommerce_created_customer' ) ) );
	}

	/**
	 * @testdox Should update currency from URL parameter.
	 */
	public function test_updates_currency_from_url_parameter(): void {
		$service          = $this->create_persistence_service();
		$sut              = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_GET['currency'] = ' gbp ';

		$sut->handle_init();

		$this->assertSame( array( 'GBP' ), $service->updated_currencies );
	}

	/**
	 * @testdox Should render account currency field when multiple currencies are enabled.
	 */
	public function test_renders_account_currency_field_when_multiple_currencies_are_enabled(): void {
		$service = $this->create_persistence_service( true, 'GBP' );
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );

		ob_start();
		$sut->handle_woocommerce_edit_account_form();
		$markup = (string) ob_get_clean();

		$this->assertStringContainsString( '<label for="wcpay_selected_currency">Default currency</label>', $markup );
		$this->assertStringContainsString( '<option value="GBP" selected>', $markup );
	}

	/**
	 * @testdox Should save account currency field from posted data.
	 */
	public function test_saves_account_currency_field_from_posted_data(): void {
		$service                          = $this->create_persistence_service();
		$sut                              = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_POST['wcpay_selected_currency'] = ' jpy ';

		$sut->handle_woocommerce_save_account_details();

		$this->assertSame( array( 'JPY' ), $service->updated_currencies );
	}

	/**
	 * Create a selected currency controller with a static runtime owner.
	 *
	 * @param string                           $owner           Runtime owner.
	 * @param object                           $service         Persistence service test double.
	 * @param MultiCurrencyRequestContext|null $request_context Request context.
	 * @return MultiCurrencySelectedCurrencyController
	 */
	private function create_controller(
		string $owner,
		object $service,
		?MultiCurrencyRequestContext $request_context = null
	): MultiCurrencySelectedCurrencyController {
		$controller = new MultiCurrencySelectedCurrencyController();
		$controller->init( $this->create_arbiter( $owner ) );
		$controller->set_persistence_service( $service );
		if ( null !== $request_context && method_exists( $controller, 'set_request_context' ) ) {
			$controller->set_request_context( $request_context );
		}

		return $controller;
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

	/**
	 * Create a persistence service test double.
	 *
	 * @param bool   $has_additional_currencies Whether multiple currencies are enabled.
	 * @param string $selected_code             Selected currency code.
	 * @return MultiCurrencySelectedCurrencyPersistenceService&object{updated_currencies: string[], new_customer_ids: int[]}
	 */
	private function create_persistence_service(
		bool $has_additional_currencies = true,
		string $selected_code = 'USD'
	): MultiCurrencySelectedCurrencyPersistenceService {
		return new class( $has_additional_currencies, $selected_code ) extends MultiCurrencySelectedCurrencyPersistenceService {
			/**
			 * Updated currencies.
			 *
			 * @var string[]
			 */
			public array $updated_currencies = array();

			/**
			 * New customer IDs.
			 *
			 * @var int[]
			 */
			public array $new_customer_ids = array();

			/**
			 * Whether multiple currencies are enabled.
			 *
			 * @var bool
			 */
			private bool $has_additional_currencies;

			/**
			 * Selected currency code.
			 *
			 * @var string
			 */
			private string $selected_code;

			/**
			 * Constructor.
			 *
			 * @param bool   $has_additional_currencies Whether multiple currencies are enabled.
			 * @param string $selected_code             Selected currency code.
			 */
			public function __construct( bool $has_additional_currencies, string $selected_code ) {
				$this->has_additional_currencies = $has_additional_currencies;
				$this->selected_code             = $selected_code;
			}

			/**
			 * Persist the selected currency.
			 *
			 * @param string $currency_code  Currency code.
			 * @param bool   $persist_change Whether the change persists.
			 * @return bool
			 */
			public function update_selected_currency( string $currency_code, bool $persist_change = true ): bool {
				unset( $persist_change );

				$this->updated_currencies[] = strtoupper( trim( $currency_code ) );

				return true;
			}

			/**
			 * Persist a new customer's selected currency.
			 *
			 * @param int $customer_id Customer ID.
			 * @return bool
			 */
			public function set_new_customer_currency_meta( int $customer_id ): bool {
				$this->new_customer_ids[] = $customer_id;

				return true;
			}

			/**
			 * Tell whether more than one currency is enabled.
			 *
			 * @return bool
			 */
			public function has_additional_currencies_enabled(): bool {
				return $this->has_additional_currencies;
			}

			/**
			 * Get enabled currency options.
			 *
			 * @return array<int,array{code:string,symbol:string}>
			 */
			public function get_enabled_currency_options(): array {
				return array(
					array(
						'code'   => 'USD',
						'symbol' => get_woocommerce_currency_symbol( 'USD' ),
					),
					array(
						'code'   => 'GBP',
						'symbol' => get_woocommerce_currency_symbol( 'GBP' ),
					),
					array(
						'code'   => 'JPY',
						'symbol' => get_woocommerce_currency_symbol( 'JPY' ),
					),
				);
			}

			/**
			 * Get selected currency code.
			 *
			 * @return string
			 */
			public function get_selected_currency_code(): string {
				return $this->selected_code;
			}
		};
	}

	/**
	 * Create a request context test double.
	 *
	 * @param bool $should_register_entry_hooks Whether selected-currency entry hooks should register.
	 * @return MultiCurrencyRequestContext
	 */
	private function create_request_context( bool $should_register_entry_hooks ): MultiCurrencyRequestContext {
		return new class( $should_register_entry_hooks ) extends MultiCurrencyRequestContext {
			/**
			 * Whether selected-currency entry hooks should register.
			 *
			 * @var bool
			 */
			private bool $should_register_entry_hooks;

			/**
			 * Constructor.
			 *
			 * @param bool $should_register_entry_hooks Whether selected-currency entry hooks should register.
			 */
			public function __construct( bool $should_register_entry_hooks ) {
				$this->should_register_entry_hooks = $should_register_entry_hooks;
			}

			/**
			 * Tell whether selected-currency entry hooks should register.
			 *
			 * @return bool
			 */
			public function should_register_selected_currency_entry_hooks(): bool {
				return $this->should_register_entry_hooks;
			}
		};
	}
}
