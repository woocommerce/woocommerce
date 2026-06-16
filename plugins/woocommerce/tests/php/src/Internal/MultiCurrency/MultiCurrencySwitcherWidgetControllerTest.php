<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySwitcherWidget;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySwitcherWidgetController;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySwitcherWidgetController and widget classes.
 */
class MultiCurrencySwitcherWidgetControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the switcher widget controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'widgets_init',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		$this->remove_registered_switcher_widgets();

		unset( $_GET['currency'], $_GET['s'] );

		parent::tearDown();
	}

	/**
	 * @testdox Should not register widget hook when plugin owns runtime.
	 */
	public function test_does_not_register_widget_hook_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assertFalse( has_action( 'widgets_init', array( $sut, 'handle_widgets_init' ) ) );
	}

	/**
	 * @testdox Should register widget hook when core owns runtime.
	 */
	public function test_registers_widget_hook_when_core_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_action( 'widgets_init', array( $sut, 'handle_widgets_init' ) ) );
	}

	/**
	 * @testdox Should register single widget instance on widgets init.
	 */
	public function test_registers_single_widget_instance_on_widgets_init(): void {
		global $wp_widget_factory;

		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->handle_widgets_init();
		$first_widget = $sut->get_registered_widget();
		$sut->handle_widgets_init();

		$this->assertInstanceOf( MultiCurrencySwitcherWidget::class, $first_widget );
		$this->assertSame( $first_widget, $sut->get_registered_widget() );
		$this->assertSame( $first_widget, $wp_widget_factory->widgets[ spl_object_hash( $first_widget ) ] ?? null );
	}

	/**
	 * @testdox Should expose preserved widget metadata and sentence case settings.
	 */
	public function test_widget_exposes_preserved_metadata_and_sentence_case_settings(): void {
		$widget = $this->create_widget();

		$this->assertSame( 'currency_switcher_widget', $widget->widget_id );
		$this->assertSame( 'Currency switcher widget', $widget->widget_name );
		$this->assertSame( 'Let customers switch between enabled currencies.', $widget->widget_description );
		$this->assertSame( 'Title', $widget->settings['title']['label'] );
		$this->assertSame( 'Display currency symbols', $widget->settings['symbol']['label'] );
		$this->assertSame( 'Display flags on supported devices', $widget->settings['flag']['label'] );
	}

	/**
	 * @testdox Should render projection markup with query args.
	 */
	public function test_widget_renders_projection_markup_with_query_args(): void {
		$projection       = $this->create_projection_service();
		$_GET['currency'] = 'EUR';
		$_GET['s']        = 'shirts';
		$widget           = $this->create_widget( $projection );

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
			),
			array(
				'title'  => 'Shop currency',
				'symbol' => false,
			)
		);
		$output = ob_get_clean();

		$this->assertSame( '<form>Switcher</form>', $output );
		$this->assertSame(
			array(
				'title'  => 'Shop currency',
				'symbol' => false,
			),
			$projection->last_instance
		);
		$this->assertSame(
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
			),
			$projection->last_args
		);
		$this->assertSame(
			array(
				'currency' => 'EUR',
				's'        => 'shirts',
			),
			$projection->last_query_args
		);
		$this->assertFalse( $projection->last_switching_disabled );
	}

	/**
	 * @testdox Should pass switching disabled decision to projection.
	 */
	public function test_widget_passes_switching_disabled_decision_to_projection(): void {
		$projection = $this->create_projection_service();
		$widget     = $this->create_widget( $projection, true );

		ob_start();
		$widget->widget( array(), array() );
		ob_end_clean();

		$this->assertTrue( $projection->last_switching_disabled );
	}

	/**
	 * @testdox Should bootstrap switcher widget controller.
	 */
	public function test_bootstrap_registers_switcher_widget_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencySwitcherWidgetController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencySwitcherWidgetController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySwitcherWidgetController::class )->register()', $bootstrap_source );
	}

	/**
	 * Create a switcher widget controller.
	 *
	 * @param string                                 $owner      Runtime owner.
	 * @param MultiCurrencySwitcherProjectionService $projection Projection service.
	 * @return MultiCurrencySwitcherWidgetController
	 */
	private function create_controller(
		string $owner,
		?MultiCurrencySwitcherProjectionService $projection = null
	): MultiCurrencySwitcherWidgetController {
		$controller = new MultiCurrencySwitcherWidgetController();
		$controller->init( $this->create_arbiter( $owner ), $this->create_compatibility_controller() );
		$controller->set_switcher_projection_service( $projection ?? $this->create_projection_service() );

		return $controller;
	}

	/**
	 * Create a switcher widget.
	 *
	 * @param MultiCurrencySwitcherProjectionService|null $projection          Projection service.
	 * @param bool                                        $switching_disabled Whether switching is disabled.
	 * @return MultiCurrencySwitcherWidget
	 */
	private function create_widget(
		?MultiCurrencySwitcherProjectionService $projection = null,
		bool $switching_disabled = false
	): MultiCurrencySwitcherWidget {
		return new MultiCurrencySwitcherWidget(
			$projection ?? $this->create_projection_service(),
			$this->create_compatibility_controller( $switching_disabled )
		);
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
			 * Last widget instance settings.
			 *
			 * @var array<string,mixed>|null
			 */
			public ?array $last_instance = null;

			/**
			 * Last widget wrapper args.
			 *
			 * @var array<string,mixed>|null
			 */
			public ?array $last_args = null;

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
			 * Project widget markup.
			 *
			 * @param array<string,mixed> $instance           Widget instance settings.
			 * @param array<string,mixed> $args               Widget wrapper arguments.
			 * @param array<string,mixed> $query_args         Query arguments to preserve.
			 * @param bool                $switching_disabled Whether switching is disabled.
			 * @return string
			 */
			public function get_widget_markup(
				array $instance = array(),
				array $args = array(),
				array $query_args = array(),
				bool $switching_disabled = false
			): string {
				$this->last_instance           = $instance;
				$this->last_args               = $args;
				$this->last_query_args         = $query_args;
				$this->last_switching_disabled = $switching_disabled;

				return '<form>Switcher</form>';
			}
		};
	}

	/**
	 * Remove native switcher widgets from the global widget factory.
	 */
	private function remove_registered_switcher_widgets(): void {
		global $wp_widget_factory;

		foreach ( $wp_widget_factory->widgets as $widget_key => $widget ) {
			if ( $widget instanceof MultiCurrencySwitcherWidget ) {
				unset( $wp_widget_factory->widgets[ $widget_key ] );
			}
		}
	}
}
