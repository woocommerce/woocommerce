<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings;
use ReflectionMethod;
use WC_Settings_Page;
use WC_Unit_Test_Case;

/**
 * Locks the "modern-settings flag off = zero change" guarantee for the modernised
 * settings SDK. These tests are intentionally paired (no-op + teeth) so that
 * removing the flag check at either site fails the suite.
 */
class ModernSettingsFeatureFlagTest extends WC_Unit_Test_Case {

	/**
	 * Settings page id used by the anonymous modern test page.
	 */
	private const TEST_PAGE_ID = 'wcprd_3489_test';

	/**
	 * Snapshot of $_GET so we can restore it in tearDown.
	 *
	 * @var array<string, mixed>
	 */
	private $original_get = array();

	/**
	 * Snapshot of $GLOBALS['hide_save_button'] so we can restore it in tearDown.
	 *
	 * @var mixed
	 */
	private $original_hide_save_button;

	/**
	 * Whether $GLOBALS['hide_save_button'] existed before the test ran.
	 *
	 * @var bool
	 */
	private $hide_save_button_existed = false;

	/**
	 * Filter callback used to enable the modern-settings feature.
	 *
	 * Stored on the instance so we can remove the exact same callable in tearDown.
	 *
	 * @var callable|null
	 */
	private $enable_filter;

	/**
	 * Filter callback used to disable the modern-settings feature.
	 *
	 * @var callable|null
	 */
	private $disable_filter;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Test setup mutates $_GET to simulate the WooCommerce settings page; no form submission involved.
		$this->original_get              = $_GET;
		$this->hide_save_button_existed  = array_key_exists( 'hide_save_button', $GLOBALS );
		$this->original_hide_save_button = $this->hide_save_button_existed
			? $GLOBALS['hide_save_button']
			: null;

		// Simulate being on the WooCommerce settings page so that
		// PageController::is_settings_page() returns true.
		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'general';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$this->enable_filter = function ( $features ) {
			return array_values( array_unique( array_merge( (array) $features, array( 'modern-settings' ) ) ) );
		};

		$this->disable_filter = function ( $features ) {
			return array_values( array_diff( (array) $features, array( 'modern-settings' ) ) );
		};
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( null !== $this->enable_filter ) {
			remove_filter( 'woocommerce_admin_features', $this->enable_filter );
		}
		if ( null !== $this->disable_filter ) {
			remove_filter( 'woocommerce_admin_features', $this->disable_filter );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restoring $_GET that we mutated in setUp.
		$_GET = $this->original_get;

		if ( $this->hide_save_button_existed ) {
			$GLOBALS['hide_save_button'] = $this->original_hide_save_button;
		} else {
			unset( $GLOBALS['hide_save_button'] );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Settings::add_react_settings_data is a no-op when the modern-settings flag is disabled.
	 */
	public function test_add_react_settings_data_is_noop_when_flag_disabled(): void {
		add_filter( 'woocommerce_admin_features', $this->disable_filter );

		$input  = array( 'existing' => 'value' );
		$result = $this->invoke_add_react_settings_data( $input );

		$this->assertSame(
			$input,
			$result,
			'With modern-settings disabled, add_react_settings_data() must not modify the settings array.'
		);
		$this->assertArrayNotHasKey(
			'settings',
			$result,
			'With modern-settings disabled, no nested settings payload should be written.'
		);
	}

	/**
	 * @testdox Settings::add_react_settings_data writes the React payload when the modern-settings flag is enabled.
	 */
	public function test_add_react_settings_data_writes_payload_when_flag_enabled(): void {
		add_filter( 'woocommerce_admin_features', $this->enable_filter );

		$input  = array( 'existing' => 'value' );
		$result = $this->invoke_add_react_settings_data( $input );

		$this->assertSame(
			'value',
			$result['existing'] ?? null,
			'Pre-existing keys should be preserved on the settings array.'
		);
		$this->assertArrayHasKey( 'settings', $result, 'A "settings" payload key should be written when the flag is enabled.' );
		$this->assertArrayHasKey( 'general', $result['settings'], 'The current tab payload should be written.' );
		$this->assertArrayHasKey( 'default', $result['settings']['general'], 'The default section payload should be written.' );

		$payload = $result['settings']['general']['default'];
		$this->assertArrayHasKey( 'id', $payload );
		$this->assertArrayHasKey( 'title', $payload );
		$this->assertArrayHasKey( 'groups', $payload );
		$this->assertArrayHasKey( 'values', $payload );
		$this->assertSame( 'general', $payload['id'], 'Payload id should reflect the current tab.' );
	}

	/**
	 * @testdox WC_Settings_Page::output omits the React mount div when the modern-settings flag is disabled.
	 */
	public function test_settings_page_output_omits_react_mount_div_when_flag_disabled(): void {
		add_filter( 'woocommerce_admin_features', $this->disable_filter );

		$page = $this->create_modern_settings_page();

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString(
			'data-wc-modern-settings',
			$html,
			'With modern-settings disabled, the React mount div must NOT be emitted on any settings page.'
		);
		$this->assertStringNotContainsString(
			'data-wc-settings-tab',
			$html,
			'No React mount markers should leak into the legacy form output.'
		);
	}

	/**
	 * @testdox WC_Settings_Page::output emits the React mount div when the modern-settings flag is enabled.
	 */
	public function test_settings_page_output_emits_react_mount_div_when_flag_enabled(): void {
		add_filter( 'woocommerce_admin_features', $this->enable_filter );

		$page = $this->create_modern_settings_page();

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString(
			'data-wc-modern-settings="1"',
			$html,
			'With modern-settings enabled and a page exposing supported field types, the React mount div must be emitted (teeth).'
		);
		$this->assertStringContainsString(
			'data-wc-settings-tab="' . self::TEST_PAGE_ID . '"',
			$html,
			'The mount div must carry the tab id attribute.'
		);
		$this->assertStringContainsString(
			'data-wc-settings-section=""',
			$html,
			'The mount div must carry the (default, empty) section attribute.'
		);
	}

	/**
	 * Invoke Settings::add_react_settings_data() via reflection.
	 *
	 * The method is private, so we bypass visibility rather than driving it through
	 * the public filter callback (which has many incidental side effects).
	 *
	 * @param array<string, mixed> $settings Input settings array.
	 * @return array<string, mixed>
	 */
	private function invoke_add_react_settings_data( array $settings ): array {
		$instance = new Settings();
		$method   = new ReflectionMethod( Settings::class, 'add_react_settings_data' );
		$method->setAccessible( true );

		/** @var array<string, mixed> $result */
		$result = $method->invoke( $instance, $settings );
		return $result;
	}

	/**
	 * Build an anonymous WC_Settings_Page subclass exposing one supported (text)
	 * field — sufficient to make the React renderer fire when the modern-settings
	 * flag is on (and stay silent when it isn't).
	 *
	 * @return WC_Settings_Page
	 */
	private function create_modern_settings_page(): WC_Settings_Page {
		$page_id = self::TEST_PAGE_ID;

		return new class( $page_id ) extends WC_Settings_Page {
			/**
			 * Constructor — declare a page with one supported field.
			 *
			 * @param string $page_id Settings page id.
			 */
			public function __construct( string $page_id ) {
				$this->id    = $page_id;
				$this->label = 'Modern Settings Flag Test';
				// Intentionally skip parent::__construct() — we don't want to register
				// hooks for this throw-away page in the unit-test process.
			}

			/**
			 * Provide one supported field so the React branch can render.
			 *
			 * @return array<int, array<string, mixed>>
			 */
			public function get_settings_for_default_section() {
				return array(
					array(
						'type' => 'title',
						'id'   => 'wcprd_3489_group',
					),
					array(
						'id'      => 'wcprd_3489_setting',
						'type'    => 'text',
						'title'   => 'Field',
						'default' => '',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'wcprd_3489_group',
					),
				);
			}
		};
	}
}
