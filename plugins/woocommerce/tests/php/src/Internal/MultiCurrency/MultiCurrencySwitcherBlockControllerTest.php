<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySwitcherBlockController;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRuntimeServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;
use WC_Unit_Test_Case;
use WP_Block_Type_Registry;

/**
 * Tests for the MultiCurrencySwitcherBlockController class.
 */
class MultiCurrencySwitcherBlockControllerTest extends WC_Unit_Test_Case {

	private const BLOCK_NAME = 'woocommerce-payments/multi-currency-switcher';

	/**
	 * Hooks touched by the switcher block controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'init',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		$this->unregister_block_type();

		unset( $_GET['currency'], $_GET['orderby'] );

		parent::tearDown();
	}

	/**
	 * @testdox Should not register init hook when plugin owns runtime.
	 */
	public function test_does_not_register_init_hook_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assertFalse( has_action( 'init', array( $sut, 'handle_init' ) ) );
	}

	/**
	 * @testdox Should register init hook when core owns runtime.
	 */
	public function test_registers_init_hook_when_core_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_action( 'init', array( $sut, 'handle_init' ) ) );
	}

	/**
	 * @testdox Should register switcher block type with preserved attributes.
	 */
	public function test_registers_switcher_block_type_with_preserved_attributes(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->handle_init();

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK_NAME );

		$this->assertNotFalse( $block_type );
		$this->assertSame( 3, $block_type->api_version );
		$this->assertSame( array( $sut, 'render_block_widget' ), $block_type->render_callback );
		$expected_attributes = array(
			'symbol'          => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'flag'            => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'fontSize'        => array(
				'type'    => 'integer',
				'default' => 14,
			),
			'fontLineHeight'  => array(
				'type'    => 'number',
				'default' => 1.5,
			),
			'fontColor'       => array(
				'type'    => 'string',
				'default' => '#000000',
			),
			'border'          => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'borderRadius'    => array(
				'type'    => 'integer',
				'default' => 3,
			),
			'borderColor'     => array(
				'type'    => 'string',
				'default' => '#000000',
			),
			'backgroundColor' => array(
				'type'    => 'string',
				'default' => 'transparent',
			),
		);

		$this->assertSame(
			$expected_attributes,
			array_intersect_key( $block_type->attributes, $expected_attributes )
		);
		$this->assertSame( array(), $block_type->editor_script_handles );
	}

	/**
	 * @testdox Should render callback delegate to projection service.
	 */
	public function test_render_callback_delegates_to_projection_service(): void {
		$projection       = $this->create_projection_service();
		$sut              = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $projection );
		$_GET['currency'] = 'EUR';
		$_GET['orderby']  = 'price';

		$markup = $sut->render_block_widget(
			array(
				'fontSize' => 18,
				'symbol'   => false,
			)
		);

		$this->assertSame( '<form>Block switcher</form>', $markup );
		$this->assertSame(
			array(
				'fontSize' => 18,
				'symbol'   => false,
			),
			$projection->last_block_attributes
		);
		$this->assertSame(
			array(
				'currency' => 'EUR',
				'orderby'  => 'price',
			),
			$projection->last_query_args
		);
		$this->assertFalse( $projection->last_switching_disabled );
	}

	/**
	 * @testdox Should render callback pass switching disabled decision.
	 */
	public function test_render_callback_passes_switching_disabled_decision(): void {
		$projection = $this->create_projection_service();
		$sut        = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $projection, true );

		$sut->render_block_widget( array() );

		$this->assertTrue( $projection->last_switching_disabled );
	}

	/**
	 * @testdox Should bootstrap switcher block controller.
	 */
	public function test_bootstrap_registers_switcher_block_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencySwitcherBlockController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencySwitcherBlockController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySwitcherBlockController::class )->register()', $bootstrap_source );
	}

	/**
	 * Create a switcher block controller.
	 *
	 * @param string                                      $owner               Runtime owner.
	 * @param MultiCurrencySwitcherProjectionService|null $projection          Projection service.
	 * @param bool                                        $switching_disabled Whether switching is disabled.
	 * @return MultiCurrencySwitcherBlockController
	 */
	private function create_controller(
		string $owner,
		?MultiCurrencySwitcherProjectionService $projection = null,
		bool $switching_disabled = false
	): MultiCurrencySwitcherBlockController {
		$controller = new MultiCurrencySwitcherBlockController();
		$controller->init(
			$this->create_arbiter( $owner ),
			$this->create_compatibility_controller( $switching_disabled ),
			wc_get_container()->get( MultiCurrencyRuntimeServiceFactory::class )
		);
		$controller->set_switcher_projection_service( $projection ?? $this->create_projection_service() );

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
	 * Create a compatibility controller test double.
	 *
	 * @param bool $switching_disabled Whether switching is disabled.
	 * @return MultiCurrencyCompatibilityController
	 */
	private function create_compatibility_controller( bool $switching_disabled = false ): MultiCurrencyCompatibilityController {
		return new class( $switching_disabled ) extends MultiCurrencyCompatibilityController {
			/**
			 * Whether switching is disabled.
			 *
			 * @var bool
			 */
			private bool $switching_disabled;

			/**
			 * Constructor.
			 *
			 * @param bool $switching_disabled Whether switching is disabled.
			 */
			public function __construct( bool $switching_disabled ) {
				$this->switching_disabled = $switching_disabled;
			}

			/**
			 * Tell whether currency switching should be disabled.
			 *
			 * @return bool
			 */
			public function should_disable_currency_switching(): bool {
				return $this->switching_disabled;
			}
		};
	}

	/**
	 * Create a switcher projection service test double.
	 *
	 * @return MultiCurrencySwitcherProjectionService
	 */
	private function create_projection_service(): MultiCurrencySwitcherProjectionService {
		return new class() extends MultiCurrencySwitcherProjectionService {
			/**
			 * Last block attributes.
			 *
			 * @var array<string,mixed>|null
			 */
			public ?array $last_block_attributes = null;

			/**
			 * Last query args.
			 *
			 * @var array<string,mixed>|null
			 */
			public ?array $last_query_args = null;

			/**
			 * Last switching-disabled flag.
			 *
			 * @var bool|null
			 */
			public ?bool $last_switching_disabled = null;

			/**
			 * Constructor.
			 */
			public function __construct() {}

			/**
			 * Project block markup.
			 *
			 * @param array<string,mixed> $block_attributes   Block attributes.
			 * @param array<string,mixed> $query_args         Query arguments to preserve.
			 * @param bool                $switching_disabled Whether switching is disabled.
			 * @return string
			 */
			public function get_block_markup(
				array $block_attributes = array(),
				array $query_args = array(),
				bool $switching_disabled = false
			): string {
				$this->last_block_attributes   = $block_attributes;
				$this->last_query_args         = $query_args;
				$this->last_switching_disabled = $switching_disabled;

				return '<form>Block switcher</form>';
			}
		};
	}

	/**
	 * Unregister the switcher block type if a test registered it.
	 */
	private function unregister_block_type(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( self::BLOCK_NAME ) ) {
			$registry->unregister( self::BLOCK_NAME );
		}
	}
}
