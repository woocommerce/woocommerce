<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use ReflectionMethod;
use WC_Settings_Page;
use WC_Unit_Test_Case;

/**
 * Tests for the legacy-settings-fallback developer notice emitted by
 * WC_Settings_Page::warn_legacy_settings_fallback().
 */
class SettingsPageFallbackNoticeTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Settings_Page
	 */
	private $sut;

	/**
	 * Captured `wc_doing_it_wrong` messages keyed by function name.
	 *
	 * @var array<string, string[]>
	 */
	private array $captured_messages = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new class() extends WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'test-tab';
				$this->label = 'Test';
				// Intentionally skip parent::__construct() so this bare subclass
				// does not register hooks against the global settings pipeline.
			}
		};

		$this->captured_messages = array();
		add_action( 'doing_it_wrong_run', array( $this, 'capture_doing_it_wrong' ), 10, 3 );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'doing_it_wrong_run', array( $this, 'capture_doing_it_wrong' ), 10 );
		remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		parent::tearDown();
	}

	/**
	 * Capture the arguments passed to wc_doing_it_wrong() for later assertions.
	 *
	 * @param string $function Function name that triggered the notice.
	 * @param string $message  Notice message.
	 * @param string $version  Version in which the notice was introduced.
	 */
	public function capture_doing_it_wrong( $function, $message, $version ): void {
		unset( $version ); // Avoid parameter not used PHPCS errors.
		$this->captured_messages[ (string) $function ][] = (string) $message;
	}

	/**
	 * Invoke the protected warn_legacy_settings_fallback() via reflection.
	 *
	 * @param string $tab                Tab id.
	 * @param string $section            Section id.
	 * @param array  $unsupported_fields Unsupported field payloads.
	 */
	private function invoke_warn( string $tab, string $section, array $unsupported_fields ): void {
		$method = new ReflectionMethod( WC_Settings_Page::class, 'warn_legacy_settings_fallback' );
		$method->setAccessible( true );
		$method->invoke( $this->sut, $tab, $section, $unsupported_fields );
	}

	/**
	 * @testdox Should raise wc_doing_it_wrong naming the method when unsupported fields are present.
	 */
	public function test_raises_doing_it_wrong_for_unsupported_fields(): void {
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::warn_legacy_settings_fallback' );

		$this->invoke_warn(
			'general',
			'',
			array(
				array(
					'id'              => 'my_field',
					'type'            => 'custom_type',
					'normalized_type' => 'custom_type',
				),
			)
		);

		$this->assertArrayHasKey(
			'WC_Settings_Page::warn_legacy_settings_fallback',
			$this->captured_messages,
			'Fallback notice should be attributed to warn_legacy_settings_fallback().'
		);
	}

	/**
	 * @testdox Should include tab, section (with default normalisation), and unsupported field details in the message.
	 */
	public function test_message_includes_tab_section_and_field_details(): void {
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::warn_legacy_settings_fallback' );

		$this->invoke_warn(
			'general',
			'',
			array(
				array(
					'id'              => 'my_field',
					'type'            => 'custom_type',
					'normalized_type' => 'custom_type',
				),
				array(
					'id'              => 'other_field',
					'type'            => 'legacy_alias',
					'normalized_type' => 'text',
				),
			)
		);

		$messages = $this->captured_messages['WC_Settings_Page::warn_legacy_settings_fallback'] ?? array();
		$this->assertNotEmpty( $messages, 'Expected at least one captured doing_it_wrong message.' );

		$message = $messages[0];

		$this->assertStringContainsString( 'tab "general"', $message, 'Message should name the tab.' );
		$this->assertStringContainsString( 'section "default"', $message, 'Empty section should be normalised to "default".' );
		$this->assertStringContainsString( 'my_field', $message, 'Message should name the first unsupported field id.' );
		$this->assertStringContainsString( 'custom_type', $message, 'Message should name the first unsupported field type.' );
		$this->assertStringContainsString( 'other_field', $message, 'Message should name the second unsupported field id.' );
		$this->assertStringContainsString( 'legacy_alias', $message, 'Message should name the second unsupported field original type.' );
		$this->assertStringContainsString( 'text', $message, 'Message should name the second unsupported field normalized type.' );
		$this->assertStringContainsString( 'registerFieldTypeTransformer', $message, 'Message should guide authors towards the transformer API.' );
		$this->assertStringContainsString( 'ReactSettingsPageInterface::get_extra_supported_types', $message, 'Message should point at the interface opt-in for adding supported types.' );
	}

	/**
	 * @testdox Should preserve the explicit section id in the message when it is non-empty.
	 */
	public function test_message_preserves_non_default_section(): void {
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::warn_legacy_settings_fallback' );

		$this->invoke_warn(
			'products',
			'inventory',
			array(
				array(
					'id'              => 'stock_threshold',
					'type'            => 'mystery',
					'normalized_type' => 'mystery',
				),
			)
		);

		$messages = $this->captured_messages['WC_Settings_Page::warn_legacy_settings_fallback'] ?? array();
		$this->assertNotEmpty( $messages, 'Expected at least one captured doing_it_wrong message.' );

		$this->assertStringContainsString( 'tab "products"', $messages[0], 'Message should name the tab.' );
		$this->assertStringContainsString( 'section "inventory"', $messages[0], 'Message should preserve a non-empty section id verbatim.' );
	}

	/**
	 * @testdox Should not raise wc_doing_it_wrong when the unsupported fields list is empty.
	 */
	public function test_no_notice_when_no_unsupported_fields(): void {
		$this->invoke_warn( 'general', '', array() );

		$this->assertArrayNotHasKey(
			'WC_Settings_Page::warn_legacy_settings_fallback',
			$this->captured_messages,
			'No notice should be raised when there are no unsupported fields.'
		);
	}
}
