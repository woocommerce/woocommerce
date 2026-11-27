<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AiAccessSettings;
use WC_Unit_Test_Case;

/**
 * Tests for the AI access section on the Site visibility settings page.
 */
class AiAccessSettingsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var AiAccessSettings
	 */
	private $sut;

	/**
	 * Act as an admin with the agent_readiness feature enabled.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		$this->sut = wc_get_container()->get( AiAccessSettings::class );
	}

	/**
	 * Clear request globals and the admin screen, which the base class doesn't reset.
	 */
	public function tearDown(): void {
		try {
			$_GET     = array();
			$_POST    = array();
			$_REQUEST = array();
			set_current_screen( 'front' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Hooks into the Site visibility page only while the agent_readiness feature is enabled.
	 */
	public function test_hooks_only_with_feature_enabled(): void {
		set_current_screen( 'dashboard' );
		remove_all_actions( 'woocommerce_update_options_site-visibility' );

		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		$this->sut->handle_init();
		$this->assertFalse( has_action( 'woocommerce_update_options_site-visibility', array( $this->sut, 'handle_save' ) ), 'No save hook with the feature off' );

		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		$this->sut->handle_init();
		$this->assertNotFalse( has_action( 'woocommerce_update_options_site-visibility', array( $this->sut, 'handle_save' ) ), 'Save hook with the feature on' );
	}

	/**
	 * @testdox Saves yes or no and ignores other values.
	 */
	public function test_save_accepts_only_yes_or_no(): void {
		update_option( AiAccessSettings::ANSWERS_OPTION, 'yes' );
		update_option( AiAccessSettings::TRAINING_OPTION, 'no' );
		$this->set_post(
			array(
				AiAccessSettings::ANSWERS_OPTION  => 'no',
				AiAccessSettings::TRAINING_OPTION => 'maybe',
			)
		);

		$this->sut->handle_save();

		$this->assertSame( 'no', get_option( AiAccessSettings::ANSWERS_OPTION ), 'A valid value is saved' );
		$this->assertSame( 'no', get_option( AiAccessSettings::TRAINING_OPTION ), 'An invalid value is ignored' );
	}

	/**
	 * @testdox Leaves an option alone when it isn't posted, as when the controls are disabled.
	 */
	public function test_save_skips_missing_values(): void {
		update_option( AiAccessSettings::TRAINING_OPTION, 'yes' );
		$this->set_post( array( AiAccessSettings::ANSWERS_OPTION => 'no' ) );

		$this->sut->handle_save();

		$this->assertSame( 'yes', get_option( AiAccessSettings::TRAINING_OPTION ) );
	}

	/**
	 * @testdox Saves nothing without a valid settings nonce.
	 */
	public function test_save_requires_nonce(): void {
		$_POST[ AiAccessSettings::ANSWERS_OPTION ] = 'no';
		$_REQUEST['_wpnonce']                      = 'invalid';

		$this->sut->handle_save();

		$this->assertFalse( get_option( AiAccessSettings::ANSWERS_OPTION ) );
	}

	/**
	 * @testdox Passes the defaults and the default status to the settings page.
	 */
	public function test_shared_settings_defaults(): void {
		set_current_screen( 'woocommerce_page_wc-settings' );

		$settings = $this->sut->handle_admin_shared_settings( array() );

		$this->assertArrayHasKey( 'aiAccessSettings', $settings );
		$this->assertSame( 'yes', $settings['aiAccessSettings'][ AiAccessSettings::ANSWERS_OPTION ] );
		$this->assertSame( 'no', $settings['aiAccessSettings'][ AiAccessSettings::TRAINING_OPTION ] );
		$this->assertSame( AiAccessSettings::STATUS_DEFAULT, $settings['aiAccessSettings']['status'] );
	}

	/**
	 * @testdox Passes the real AI shopping switch value and the AI shopping settings link, without changing the switch.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $ucp_option Saved value of the UCP feature option.
	 * @param bool   $expected   Expected aiShoppingEnabled value.
	 */
	public function test_shared_settings_reads_ai_shopping_switch( string $ucp_option, bool $expected ): void {
		set_current_screen( 'woocommerce_page_wc-settings' );
		update_option( 'woocommerce_feature_ucp_enabled', $ucp_option );

		$settings = $this->sut->handle_admin_shared_settings( array() );

		$this->assertSame( $expected, $settings['aiAccessSettings']['aiShoppingEnabled'] );
		$this->assertStringContainsString( 'tab=agentic-commerce&section=shopping', $settings['aiAccessSettings']['aiShoppingUrl'] );
		$this->assertStringContainsString( 'page=jetpack-seo', $settings['aiAccessSettings']['jetpackUrl'] );
		$this->assertSame( $ucp_option, get_option( 'woocommerce_feature_ucp_enabled' ), 'The switch is only read' );
	}

	/**
	 * @testdox Adds nothing outside the settings page.
	 */
	public function test_shared_settings_only_on_settings_page(): void {
		set_current_screen( 'dashboard' );

		$this->assertArrayNotHasKey( 'aiAccessSettings', $this->sut->handle_admin_shared_settings( array() ) );
	}

	/**
	 * @testdox The preview query argument forces a status, and unknown values fall back to the default.
	 * @testWith ["robots_file", "robots_file"]
	 *           ["jetpack", "jetpack"]
	 *           ["other_plugin", "other_plugin"]
	 *           ["something_else", "default"]
	 *
	 * @param string $preview  Query argument value.
	 * @param string $expected Expected status.
	 */
	public function test_status_from_preview( string $preview, string $expected ): void {
		$_GET[ AiAccessSettings::PREVIEW_QUERY_ARG ] = $preview;

		$this->assertSame( $expected, $this->sut->get_status() );
	}

	/**
	 * Post values with a valid settings nonce.
	 *
	 * @param array<string, string> $values Posted values.
	 */
	private function set_post( array $values ): void {
		$_POST                = $values;
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'woocommerce-settings' );
	}
}
