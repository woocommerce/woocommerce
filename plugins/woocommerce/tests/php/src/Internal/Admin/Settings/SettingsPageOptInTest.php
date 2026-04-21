<?php
/**
 * Tests for the per-page `$is_modern` opt-in on WC_Settings_Page.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use WC_Settings_Page;
use WC_Unit_Test_Case;

/**
 * Tests that the modernised React rendering only kicks in when a page
 * explicitly opts in via `$is_modern` AND the `modern-settings` feature
 * flag is enabled, and that the per-section opt-out filter still vetoes
 * opted-in pages.
 */
class SettingsPageOptInTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Settings_Page
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Make sure the WC_Settings_Page base class is loaded.
		if ( ! class_exists( 'WC_Settings_Page', false ) ) {
			require_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
		}

		// Ensure we start each test with a clean `current_section` global.
		global $current_section;
		$current_section = '';
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_modern_settings_feature' ) );
		remove_all_filters( 'woocommerce_react_settings_opt_out' );

		global $current_section, $hide_save_button;
		$current_section  = '';
		$hide_save_button = null;

		parent::tearDown();
	}

	/**
	 * Enable the `modern-settings` feature via the `woocommerce_admin_features` filter.
	 *
	 * @param array $features Existing features.
	 * @return array Modified features.
	 */
	public function enable_modern_settings_feature( $features ) {
		$features[] = 'modern-settings';
		return $features;
	}

	/**
	 * @testdox Should render the legacy form when `$is_modern` is false even with the feature flag enabled.
	 */
	public function test_output_emits_legacy_when_is_modern_is_false_even_with_flag_on(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_modern_settings_feature' ) );

		$this->sut = $this->create_legacy_page();

		ob_start();
		$this->sut->output();
		$output = (string) ob_get_clean();

		$this->assertStringNotContainsString(
			'data-wc-modern-settings="1"',
			$output,
			'Legacy (non-opted-in) pages must never emit the React mount point.'
		);
	}

	/**
	 * @testdox Should render the React mount point when `$is_modern` is true and the feature flag is enabled.
	 */
	public function test_output_emits_react_mount_when_is_modern_is_true_and_flag_on(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_modern_settings_feature' ) );

		$this->sut = $this->create_opted_in_page();

		ob_start();
		$this->sut->output();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString(
			'data-wc-modern-settings="1"',
			$output,
			'Opted-in pages must emit the React mount point when the feature flag is enabled.'
		);
		$this->assertStringContainsString(
			'data-wc-settings-tab="wooprd_3485_optin"',
			$output,
			'Mount element must carry the settings tab id.'
		);
	}

	/**
	 * @testdox Should fall back to the legacy form when the woocommerce_react_settings_opt_out filter vetoes the section.
	 */
	public function test_output_falls_back_when_opt_out_filter_vetoes(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_modern_settings_feature' ) );

		$this->sut = $this->create_opted_in_page();

		add_filter(
			'woocommerce_react_settings_opt_out',
			function ( $opt_out, $tab ) {
				if ( 'wooprd_3485_optin' === $tab ) {
					return true;
				}
				return $opt_out;
			},
			10,
			2
		);

		ob_start();
		$this->sut->output();
		$output = (string) ob_get_clean();

		$this->assertStringNotContainsString(
			'data-wc-modern-settings="1"',
			$output,
			'Opt-out filter must prevent the React mount point from being emitted.'
		);
	}

	/**
	 * @testdox Should expose the is_modern() getter returning the opt-in state of the page.
	 */
	public function test_is_modern_getter_returns_opt_in_state(): void {
		$this->sut = $this->create_legacy_page();
		$this->assertFalse( $this->sut->is_modern(), 'Legacy page must report is_modern() === false.' );

		$this->sut = $this->create_opted_in_page();
		$this->assertTrue( $this->sut->is_modern(), 'Opted-in page must report is_modern() === true.' );
	}

	/**
	 * Build a test page that has NOT opted in to the modern rendering.
	 *
	 * @return WC_Settings_Page
	 */
	private function create_legacy_page(): WC_Settings_Page {
		return new class() extends WC_Settings_Page {
			// phpcs:disable Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
				$this->id    = 'wooprd_3485_legacy';
				$this->label = 'WOOPRD-3485 Legacy';
				// Intentionally do NOT call parent::__construct() to avoid
				// registering hooks with the global admin machinery.
			}

			protected function get_settings_for_default_section() {
				return array(
					array(
						'type'  => 'title',
						'id'    => 'wooprd_3485_legacy_group',
						'title' => 'Test',
					),
					array(
						'type'  => 'text',
						'id'    => 'wooprd_3485_legacy_field',
						'title' => 'Test field',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'wooprd_3485_legacy_group',
					),
				);
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.Missing
		};
	}

	/**
	 * Build a test page that HAS opted in to the modern rendering.
	 *
	 * @return WC_Settings_Page
	 */
	private function create_opted_in_page(): WC_Settings_Page {
		return new class() extends WC_Settings_Page {
			// phpcs:disable Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
				$this->id        = 'wooprd_3485_optin';
				$this->label     = 'WOOPRD-3485 Opt-in';
				$this->is_modern = true;
				// Intentionally do NOT call parent::__construct() to avoid
				// registering hooks with the global admin machinery.
			}

			protected function get_settings_for_default_section() {
				return array(
					array(
						'type'  => 'title',
						'id'    => 'wooprd_3485_optin_group',
						'title' => 'Test',
					),
					array(
						'type'  => 'text',
						'id'    => 'wooprd_3485_optin_field',
						'title' => 'Test field',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'wooprd_3485_optin_group',
					),
				);
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.Missing
		};
	}
}
