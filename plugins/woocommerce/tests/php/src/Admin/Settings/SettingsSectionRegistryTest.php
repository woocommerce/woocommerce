<?php
/**
 * Settings section registry tests.
 *
 * @package WooCommerce\Tests\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionInterface;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIRequestContext;
use WC_Unit_Test_Case;

/**
 * Tests for settings section registration.
 */
class SettingsSectionRegistryTest extends WC_Unit_Test_Case {

	/**
	 * Original request globals.
	 *
	 * @var array
	 */
	private array $original_get = array();

	/**
	 * Original combined request globals.
	 *
	 * @var array
	 */
	private array $original_request = array();

	/**
	 * Original current settings section.
	 *
	 * @var mixed
	 */
	private $original_current_section = null;

	/**
	 * Original current settings tab.
	 *
	 * @var mixed
	 */
	private $original_current_tab = null;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';

		global $current_section, $current_tab;
		$this->original_get             = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_request         = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_current_section = $current_section ?? null;
		$this->original_current_tab     = $current_tab ?? null;

		SettingsSectionRegistry::get_instance()->unregister_all();
		SettingsUIRequestContext::reset();
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		global $current_section, $current_tab;
		$_GET            = $this->original_get;
		$_REQUEST        = $this->original_request;
		$current_section = $this->original_current_section;
		$current_tab     = $this->original_current_tab;

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		SettingsSectionRegistry::get_instance()->unregister_all();
		SettingsUIRequestContext::reset();

		parent::tearDown();
	}

	/**
	 * @testdox Should register sections through the registration action.
	 */
	public function test_registers_sections_through_registration_action(): void {
		$page    = $this->get_parent_page();
		$section = $this->get_registered_section();
		$action  = static function ( SettingsSectionRegistry $registry ) use ( $section ): void {
			$registry->register( $section );
		};

		add_action( 'woocommerce_settings_sections_registration', $action );

		try {
			$sections = $page->get_sections();
		} finally {
			remove_action( 'woocommerce_settings_sections_registration', $action );
		}

		$this->assertArrayHasKey( 'acme_payments', $sections, 'Registered section should be exposed by its parent page.' );
		$this->assertSame( 'Acme Payments', $sections['acme_payments'] );
	}

	/**
	 * @testdox Should provide registered section legacy settings to the parent page.
	 */
	public function test_provides_registered_section_legacy_settings(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section() );

		$settings = $page->get_settings_for_section( 'acme_payments' );

		$this->assertSame( 'registered_acme_payments_setting', $settings[0]['id'] );
	}

	/**
	 * @testdox Should resolve a registered section settings UI adapter before the parent page adapter.
	 */
	public function test_resolves_registered_section_settings_ui_adapter(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section() );

		$settings_ui_page = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_settings_ui_page();

		$this->assertInstanceOf( SettingsUIPageInterface::class, $settings_ui_page );
		$this->assertSame( 'checkout', $settings_ui_page->get_page_id() );
		$this->assertSame( array( 'acme-payments-settings-ui' ), $settings_ui_page->get_script_handles( 'acme_payments' ) );
		$this->assertSame( 'form_post', $settings_ui_page->get_save_adapter( 'acme_payments' ) );

		$schema = $settings_ui_page->get_schema( 'acme_payments' );
		$this->assertSame( 'Acme Payments', $schema['title'] );
		$this->assertSame( 'Acme Payments', $schema['shell']['title'] );
	}

	/**
	 * @testdox Should resolve a registered section native Settings UI page when provided.
	 */
	public function test_resolves_registered_section_native_settings_ui_page(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section_with_native_settings_ui_page() );

		$settings_ui_page = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_settings_ui_page();

		$this->assertInstanceOf( SettingsUIPageInterface::class, $settings_ui_page );
		$this->assertSame( 'acme_native', $settings_ui_page->get_page_id() );
		$this->assertSame( array( 'acme-native-settings-ui' ), $settings_ui_page->get_script_handles( 'acme_payments' ) );
		$this->assertSame( 'custom', $settings_ui_page->get_save_adapter( 'acme_payments' ) );

		$schema = $settings_ui_page->get_schema( 'acme_payments' );
		$this->assertSame( 'acme_native', $schema['id'] );
		$this->assertSame( 'native_tab', $schema['section'] );
		$this->assertArrayHasKey( 'native_group', $schema['groups'] );
		$this->assertArrayNotHasKey( 'registered_acme_payments_setting', $schema['groups'] );
	}

	/**
	 * @testdox Should invoke the native Settings UI page provider once per cached request context.
	 */
	public function test_invokes_native_settings_ui_page_provider_once_per_request_context(): void {
		$provider_calls = 0;
		$page           = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register(
			$this->get_registered_section_with_native_settings_ui_page(
				static function () use ( &$provider_calls ): void {
					++$provider_calls;
				}
			)
		);

		$context = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' );
		$context->get_settings_ui_page();
		$repeat_context = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' );
		$repeat_context->get_settings_ui_page();

		$this->assertSame( $context, $repeat_context, 'Request contexts should be cached per settings page and section.' );
		$this->assertSame( 1, $provider_calls, 'The native Settings UI page provider should only be invoked once per request context.' );
	}

	/**
	 * @testdox Should default drill-down pages to no section navigation when a native Settings UI schema omits it.
	 */
	public function test_defaults_drill_down_pages_to_no_section_navigation_when_native_settings_ui_schema_omits_it(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register(
			$this->get_registered_section_with_native_settings_ui_page( null, array( 'title' => 'Acme native settings' ) )
		);

		$schema = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_schema();

		$this->assertSame( array(), $schema['shell']['sectionNavigation'], 'Drill-down pages replace section navigation with header breadcrumbs.' );
	}

	/**
	 * @testdox Should keep custom section navigation provided by a native Settings UI schema.
	 */
	public function test_keeps_custom_section_navigation_from_native_settings_ui_schema(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section_with_native_settings_ui_page() );

		$schema = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_schema();

		$navigation = $schema['shell']['sectionNavigation'];
		$this->assertSame( array( 'native_tab' ), array_column( $navigation, 'id' ) );
	}

	/**
	 * @testdox Should keep an explicitly empty section navigation in a native Settings UI schema.
	 */
	public function test_keeps_explicitly_empty_section_navigation_in_native_settings_ui_schema(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register(
			$this->get_registered_section_with_native_settings_ui_page( null, array( 'sectionNavigation' => array() ) )
		);

		$schema = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_schema();

		$this->assertSame( array(), $schema['shell']['sectionNavigation'] );
	}

	/**
	 * @testdox Should fall back to the default adapter when a registered section native Settings UI page provider fails.
	 */
	public function test_falls_back_to_default_adapter_when_registered_section_native_settings_ui_page_provider_fails(): void {
		$this->setExpectedIncorrectUsage( SettingsUIRequestContext::class . '::resolve_settings_ui_page' );

		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section( 'acme_payments', new \Error( 'Unable to resolve native settings UI page.' ) ) );

		$settings_ui_page = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_settings_ui_page();

		$this->assertInstanceOf( SettingsUIPageInterface::class, $settings_ui_page );
		$this->assertSame( 'checkout', $settings_ui_page->get_page_id() );

		$schema = $settings_ui_page->get_schema( 'acme_payments' );
		$this->assertSame( 'registered_acme_payments_setting', $schema['groups']['default']['fields'][0]['id'] );
	}

	/**
	 * @testdox Should report exceptions from a registered section native Settings UI page provider and fall back to the default adapter.
	 */
	public function test_falls_back_to_default_adapter_when_registered_section_native_settings_ui_page_provider_throws_exception(): void {
		$this->setExpectedIncorrectUsage( SettingsUIRequestContext::class . '::resolve_settings_ui_page' );

		$caught   = array();
		$listener = static function ( $exception ) use ( &$caught ): void {
			$caught[] = $exception;
		};
		add_action( 'woocommerce_caught_exception', $listener );

		$page      = $this->get_parent_page();
		$exception = new \RuntimeException( 'Unable to resolve native settings UI page.' );
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section( 'acme_payments', $exception ) );

		try {
			$settings_ui_page = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_settings_ui_page();
		} finally {
			remove_action( 'woocommerce_caught_exception', $listener );
		}

		$this->assertInstanceOf( SettingsUIPageInterface::class, $settings_ui_page );
		$this->assertSame( 'checkout', $settings_ui_page->get_page_id() );
		$this->assertSame( array( $exception ), $caught, 'Provider exceptions should be reported through wc_caught_exception().' );
	}

	/**
	 * @testdox Should fall back to the page provider when the registry lookup itself fails.
	 */
	public function test_falls_back_to_page_provider_when_registry_lookup_fails(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section() );

		// Make the lookup itself throw: get_registered() normalises ids
		// through sanitize_title(), which runs this filter. Scoped to the
		// section id so logging inside the guard is unaffected.
		$break_lookup = static function ( $title, $raw_title = '' ) {
			if ( 'acme_payments' === $raw_title ) {
				throw new \RuntimeException( 'Broken registry lookup.' );
			}
			return $title;
		};
		add_filter( 'sanitize_title', $break_lookup, 10, 2 );

		try {
			$settings_ui_page = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_settings_ui_page();
		} finally {
			remove_filter( 'sanitize_title', $break_lookup );
		}

		// The registered section is unreachable, so resolution falls through
		// to the page's own provider (null on the base class) without fataling.
		$this->assertNull( $settings_ui_page );
	}

	/**
	 * @testdox Should keep direct SettingsSectionInterface implementations on the default adapter path.
	 */
	public function test_direct_settings_section_interface_implementation_uses_default_adapter(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_direct_registered_section() );

		$settings_ui_page = SettingsUIRequestContext::for_settings_page( $page, 'direct_payments' )->get_settings_ui_page();

		$this->assertInstanceOf( SettingsUIPageInterface::class, $settings_ui_page );
		$this->assertSame( 'checkout', $settings_ui_page->get_page_id() );
		$this->assertSame( array( 'direct-payments-settings-ui' ), $settings_ui_page->get_script_handles( 'direct_payments' ) );
	}

	/**
	 * @testdox Should render a registered section through the settings UI when the feature is enabled.
	 */
	public function test_renders_registered_section_with_settings_ui(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section() );

		global $current_section;
		$current_section = 'acme_payments';
		$page            = $this->get_parent_page();

		ob_start();
		$page->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertStringContainsString( 'data-wc-settings-page="checkout"', $output );
		$this->assertStringNotContainsString( 'name="registered_acme_payments_setting"', $output );
	}

	/**
	 * @testdox Should render a registered section native Settings UI page when provided.
	 */
	public function test_renders_registered_section_native_settings_ui_page(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section_with_native_settings_ui_page() );

		global $current_section;
		$current_section = 'acme_payments';
		$page            = $this->get_parent_page();

		ob_start();
		$page->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertStringContainsString( 'data-wc-settings-page="acme_native"', $output );
		$this->assertStringNotContainsString( 'name="registered_acme_payments_setting"', $output );
	}

	/**
	 * @testdox Should suppress legacy section navigation for registered section native Settings UI pages.
	 */
	public function test_suppresses_legacy_section_navigation_for_registered_native_settings_ui_page(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section_with_native_settings_ui_page() );

		$output = $this->render_settings_view_for_checkout_section( 'acme_payments' );

		$this->assertStringContainsString( 'data-wc-settings-page="acme_native"', $output );
		$this->assertStringNotContainsString( 'class="subsubsub"', $output );
	}

	/**
	 * @testdox Should hide the top-level tabs for drill-down Settings UI pages.
	 */
	public function test_hides_top_level_tabs_for_drill_down_settings_ui_pages(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section_with_native_settings_ui_page() );

		$output = $this->render_settings_view_for_checkout_section( 'acme_payments' );

		$this->assertStringContainsString( 'data-wc-settings-page="acme_native"', $output );
		$this->assertStringNotContainsString( 'nav-tab-wrapper', $output, 'Drill-down pages replace the top-level tabs with the shell header.' );
	}

	/**
	 * @testdox Should preserve classic navigation when a registered drill-down falls back.
	 *
	 * @dataProvider settings_ui_failure_stages
	 *
	 * @param string $failure_stage Settings UI resolution stage that should fail.
	 */
	public function test_preserves_classic_navigation_when_registered_drill_down_falls_back( string $failure_stage ): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section_with_native_settings_ui_page( null, null, $failure_stage ) );
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::output' );

		$output = $this->render_settings_view_for_checkout_section( 'acme_payments' );

		$this->assertStringContainsString( 'woo-nav-tab-wrapper', $output, 'The top-level settings tabs should remain available.' );
		$this->assertStringContainsString( 'class="subsubsub"', $output, 'The classic section links should remain available.' );
		$this->assertStringContainsString( 'name="registered_acme_payments_setting"', $output, 'The legacy settings fields should render.' );
		$this->assertStringNotContainsString( 'data-wc-settings-ui="1"', $output, 'The Settings UI mount point should not render.' );
	}

	/**
	 * Settings UI failure stages.
	 *
	 * @return array<string, array{string}>
	 */
	public static function settings_ui_failure_stages(): array {
		return array(
			'schema resolution'        => array( 'schema' ),
			'script handle resolution' => array( 'script_handles' ),
		);
	}

	/**
	 * @testdox Should contain registration action failures.
	 */
	public function test_registration_action_failures_are_contained(): void {
		$calls  = 0;
		$action = static function () use ( &$calls ): void {
			++$calls;
			throw new \Error( 'Broken settings section registration.' );
		};
		add_action( 'woocommerce_settings_sections_registration', $action );

		try {
			$sections      = SettingsSectionRegistry::get_instance()->get_sections_for_page( 'checkout' );
			$second_lookup = SettingsSectionRegistry::get_instance()->get_sections_for_page( 'checkout' );
		} finally {
			remove_action( 'woocommerce_settings_sections_registration', $action );
		}

		$this->assertSame( array(), $sections );
		$this->assertSame( array(), $second_lookup );
		$this->assertSame( 1, $calls, 'The registration action should not be retried after a failure.' );
	}

	/**
	 * @testdox Should reject checkout sections that collide with payment gateway ids.
	 */
	public function test_rejects_checkout_sections_that_collide_with_payment_gateway_ids(): void {
		$this->setExpectedIncorrectUsage( SettingsSectionRegistry::class . '::register' );

		$result = SettingsSectionRegistry::get_instance()->register( $this->get_registered_section( 'bacs' ) );

		$this->assertFalse( $result );
		$this->assertNull( SettingsSectionRegistry::get_instance()->get_registered( 'checkout', 'bacs' ) );
	}

	/**
	 * Enable the settings UI feature flag.
	 *
	 * @param array $features Feature flags.
	 * @return array
	 */
	public function enable_settings_ui_feature( array $features ): array {
		$features[] = 'settings-ui';
		return array_values( array_unique( $features ) );
	}

	/**
	 * Build a parent settings page.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_parent_page(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'checkout';
				$this->label = 'Payments';
			}
		};
	}

	/**
	 * Build a registered test section.
	 *
	 * @param string          $section_id Section id.
	 * @param \Throwable|null $settings_ui_page_failure Throwable the Settings UI page provider should throw, if any.
	 * @return SettingsSectionInterface
	 */
	private function get_registered_section( string $section_id = 'acme_payments', ?\Throwable $settings_ui_page_failure = null ): SettingsSectionInterface {
		return new class( $section_id, $settings_ui_page_failure ) extends SettingsSection {
			/**
			 * Section id.
			 *
			 * @var string
			 */
			private string $section_id;

			/**
			 * Throwable the Settings UI page provider should throw, if any.
			 *
			 * @var \Throwable|null
			 */
			private ?\Throwable $settings_ui_page_failure;

			/**
			 * Constructor.
			 *
			 * @param string          $section_id Section id.
			 * @param \Throwable|null $settings_ui_page_failure Throwable the Settings UI page provider should throw, if any.
			 */
			public function __construct( string $section_id, ?\Throwable $settings_ui_page_failure ) {
				$this->section_id               = $section_id;
				$this->settings_ui_page_failure = $settings_ui_page_failure;
			}

			/**
			 * Get the parent page id.
			 *
			 * @return string
			 */
			public function get_parent_page_id(): string {
				return 'checkout';
			}

			/**
			 * Get the section id.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return $this->section_id;
			}

			/**
			 * Get the section label.
			 *
			 * @return string
			 */
			public function get_label(): string {
				return 'Acme Payments';
			}

			/**
			 * Get legacy settings.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return array
			 */
			public function get_settings( \WC_Settings_Page $parent_page ): array {
				return array(
					array(
						'id'    => 'registered_' . $this->section_id . '_setting',
						'type'  => 'text',
						'title' => 'Registered Acme Payments setting',
					),
				);
			}

			/**
			 * Get script handles.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return string[]
			 */
			public function get_script_handles( \WC_Settings_Page $parent_page ): array {
				return array( 'acme-payments-settings-ui' );
			}

			/**
			 * Get the native Settings UI page.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page( \WC_Settings_Page $parent_page ): ?SettingsUIPageInterface {
				if ( $this->settings_ui_page_failure ) {
					throw $this->settings_ui_page_failure;
				}

				return parent::get_settings_ui_page( $parent_page );
			}

		};
	}

	/**
	 * Build a registered section that provides a native Settings UI page.
	 *
	 * @param callable|null $on_settings_ui_page_call Callback invoked every time the Settings UI page provider runs.
	 * @param array|null    $shell Schema shell for the native page. Null uses the fixture default with custom section navigation.
	 * @param string|null   $failure_stage Settings UI resolution stage that should fail.
	 * @return SettingsSectionInterface
	 */
	private function get_registered_section_with_native_settings_ui_page( ?callable $on_settings_ui_page_call = null, ?array $shell = null, ?string $failure_stage = null ): SettingsSectionInterface {
		return new class( $on_settings_ui_page_call, $shell, $failure_stage ) extends SettingsSection {
			/**
			 * Callback invoked every time the Settings UI page provider runs.
			 *
			 * @var callable|null
			 */
			private $on_settings_ui_page_call;

			/**
			 * Schema shell for the native page, or null for the fixture default.
			 *
			 * @var array|null
			 */
			private ?array $shell;

			/**
			 * Settings UI resolution stage that should fail.
			 *
			 * @var string|null
			 */
			private ?string $failure_stage;

			/**
			 * Constructor.
			 *
			 * @param callable|null $on_settings_ui_page_call Callback invoked every time the Settings UI page provider runs.
			 * @param array|null    $shell Schema shell for the native page, or null for the fixture default.
			 * @param string|null   $failure_stage Settings UI resolution stage that should fail.
			 */
			public function __construct( ?callable $on_settings_ui_page_call, ?array $shell, ?string $failure_stage ) {
				$this->on_settings_ui_page_call = $on_settings_ui_page_call;
				$this->shell                    = $shell;
				$this->failure_stage            = $failure_stage;
			}

			/**
			 * Get the parent page id.
			 *
			 * @return string
			 */
			public function get_parent_page_id(): string {
				return 'checkout';
			}

			/**
			 * Get the section id.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return 'acme_payments';
			}

			/**
			 * Get the section label.
			 *
			 * @return string
			 */
			public function get_label(): string {
				return 'Acme Payments';
			}

			/**
			 * Get legacy settings.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return array
			 */
			public function get_settings( \WC_Settings_Page $parent_page ): array {
				return array(
					array(
						'id'    => 'registered_acme_payments_setting',
						'type'  => 'text',
						'title' => 'Registered Acme Payments setting',
					),
				);
			}

			/**
			 * Get the native Settings UI page.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page( \WC_Settings_Page $parent_page ): ?SettingsUIPageInterface {
				if ( $this->on_settings_ui_page_call ) {
					( $this->on_settings_ui_page_call )();
				}

				return new class( $this->shell, $this->failure_stage ) implements SettingsUIPageInterface {
					/**
					 * Schema shell, or null for the fixture default.
					 *
					 * @var array|null
					 */
					private ?array $shell;

					/**
					 * Settings UI resolution stage that should fail.
					 *
					 * @var string|null
					 */
					private ?string $failure_stage;

					/**
					 * Constructor.
					 *
					 * @param array|null  $shell Schema shell, or null for the fixture default.
					 * @param string|null $failure_stage Settings UI resolution stage that should fail.
					 */
					public function __construct( ?array $shell, ?string $failure_stage ) {
						$this->shell         = $shell;
						$this->failure_stage = $failure_stage;
					}

					/**
					 * Get the page id.
					 *
					 * @return string
					 */
					public function get_page_id(): string {
						return 'acme_native';
					}

					/**
					 * Get the native schema.
					 *
					 * @param string $section Section id.
					 * @return array
					 */
					public function get_schema( string $section ): array {
						if ( 'schema' === $this->failure_stage ) {
							throw new \RuntimeException( 'Unable to resolve the Settings UI schema.' );
						}

						return array(
							'id'      => 'acme_native',
							'title'   => 'Acme native settings',
							'section' => 'native_tab',
							'shell'   => $this->shell ?? array(
								'title'             => 'Acme native settings',
								'sectionNavigation' => array(
									array(
										'id'     => 'native_tab',
										'label'  => 'Native tab',
										'href'   => 'https://example.com/native-tab',
										'active' => true,
									),
								),
							),
							'groups'  => array(
								'native_group' => array(
									'id'     => 'native_group',
									'title'  => 'Native group',
									'fields' => array(),
								),
							),
							'save'    => array(
								'adapter' => 'custom',
								'handler' => 'acme/save',
							),
						);
					}

					/**
					 * Get script handles.
					 *
					 * @param string $section Section id.
					 * @return string[]
					 */
					public function get_script_handles( string $section ): array {
						if ( 'script_handles' === $this->failure_stage ) {
							throw new \RuntimeException( 'Unable to resolve Settings UI script handles.' );
						}

						return array( 'acme-native-settings-ui' );
					}

					/**
					 * Get the save adapter.
					 *
					 * @param string $section Section id.
					 * @return string
					 */
					public function get_save_adapter( string $section ): string {
						return 'custom';
					}
				};
			}
		};
	}

	/**
	 * Build a direct SettingsSectionInterface implementation.
	 *
	 * @return SettingsSectionInterface
	 */
	private function get_direct_registered_section(): SettingsSectionInterface {
		return new class() implements SettingsSectionInterface {
			/**
			 * Get the parent page id.
			 *
			 * @return string
			 */
			public function get_parent_page_id(): string {
				return 'checkout';
			}

			/**
			 * Get the section id.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return 'direct_payments';
			}

			/**
			 * Get the section label.
			 *
			 * @return string
			 */
			public function get_label(): string {
				return 'Direct Payments';
			}

			/**
			 * Get legacy settings.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return array
			 */
			public function get_settings( \WC_Settings_Page $parent_page ): array {
				return array(
					array(
						'id'    => 'direct_payments_setting',
						'type'  => 'text',
						'title' => 'Direct payments setting',
					),
				);
			}

			/**
			 * Get script handles.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return string[]
			 */
			public function get_script_handles( \WC_Settings_Page $parent_page ): array {
				return array( 'direct-payments-settings-ui' );
			}

			/**
			 * Get save adapter.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return string
			 */
			public function get_save_adapter( \WC_Settings_Page $parent_page ): string {
				return 'form_post';
			}
		};
	}

	/**
	 * Render the full settings view for a checkout section as an administrator.
	 *
	 * @param string $section Section id.
	 * @return string Rendered settings view output.
	 */
	private function render_settings_view_for_checkout_section( string $section ): string {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		global $current_section, $current_tab;
		$current_tab     = 'checkout';
		$current_section = $section;
		$_GET['page']    = 'wc-settings';
		$_GET['tab']     = 'checkout';
		$_GET['section'] = $section;
		// PHP builds $_REQUEST once at request start, so runtime $_GET changes need mirroring.
		$_REQUEST['section'] = $section;

		$page                   = $this->get_parent_page();
		$original_settings      = $this->replace_wc_admin_settings_pages( array( $page ) );
		$tabs                   = array(
			'general'  => 'General',
			'checkout' => 'Payments',
		);
		$original_sections_hook = $this->replace_hook_callbacks( 'woocommerce_sections_checkout' );
		$original_settings_hook = $this->replace_hook_callbacks( 'woocommerce_settings_checkout' );

		add_action( 'woocommerce_sections_checkout', array( $page, 'output_sections' ) );
		add_action( 'woocommerce_settings_checkout', array( $page, 'output' ) );

		$buffer_level = ob_get_level();
		ob_start();

		try {
			include WC_ABSPATH . 'includes/admin/views/html-admin-settings.php';
			return ob_get_clean();
		} finally {
			// Drain buffers left open when rendering throws.
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			$this->restore_hook_callbacks( 'woocommerce_sections_checkout', $original_sections_hook );
			$this->restore_hook_callbacks( 'woocommerce_settings_checkout', $original_settings_hook );
			$this->replace_wc_admin_settings_pages( $original_settings );
		}
	}

	/**
	 * Replace callbacks for a hook.
	 *
	 * @param string $hook_name Hook name.
	 * @return \WP_Hook|null Previous hook callbacks.
	 */
	private function replace_hook_callbacks( string $hook_name ): ?\WP_Hook {
		global $wp_filter;

		$previous_hook = isset( $wp_filter[ $hook_name ] ) ? clone $wp_filter[ $hook_name ] : null;
		remove_all_actions( $hook_name );

		return $previous_hook;
	}

	/**
	 * Restore callbacks for a hook.
	 *
	 * @param string        $hook_name Hook name.
	 * @param \WP_Hook|null $hook Previous hook callbacks.
	 */
	private function restore_hook_callbacks( string $hook_name, ?\WP_Hook $hook ): void {
		remove_all_actions( $hook_name );

		if ( ! $hook ) {
			return;
		}

		foreach ( $hook->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				add_action( $hook_name, $callback['function'], $priority, $callback['accepted_args'] );
			}
		}
	}

	/**
	 * Replace WC admin settings pages for a focused view test.
	 *
	 * @param array $settings_pages Settings page instances.
	 * @return array Previous settings page instances.
	 */
	private function replace_wc_admin_settings_pages( array $settings_pages ): array {
		$settings_property = new \ReflectionProperty( \WC_Admin_Settings::class, 'settings' );
		$settings_property->setAccessible( true );

		$previous_settings = (array) $settings_property->getValue();
		$settings_property->setValue( null, $settings_pages );

		return $previous_settings;
	}
}
