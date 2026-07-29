<?php
/**
 * Tests for WC_Post_Types.
 *
 * @package WooCommerce\Tests\PostTypes
 */

declare( strict_types = 1 );

/**
 * Tests for WC_Post_Types.
 */
class WC_Post_Types_Test extends WC_Unit_Test_Case {

	/**
	 * Original stored theme support value.
	 *
	 * @var mixed
	 */
	private $original_theme_support;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_theme_support = get_option( 'current_theme_supports_woocommerce', '__missing__' );
	}

	/**
	 * Restore test fixtures.
	 */
	public function tearDown(): void {
		if ( '__missing__' === $this->original_theme_support ) {
			delete_option( 'current_theme_supports_woocommerce' );
		} else {
			update_option( 'current_theme_supports_woocommerce', $this->original_theme_support );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Product archive registration uses runtime support unless the active theme may be skipped.
	 * @dataProvider provide_product_archive_theme_support_cases
	 *
	 * @param bool   $runtime_support     Whether the current request reports theme support.
	 * @param bool   $theme_may_be_skipped Whether the request may not have loaded the active theme.
	 * @param string $stored_support      Stored support from the last trusted request.
	 * @param bool   $expected             Expected resolved support.
	 */
	public function test_should_register_product_archive(
		bool $runtime_support,
		bool $theme_may_be_skipped,
		string $stored_support,
		bool $expected
	): void {
		update_option( 'current_theme_supports_woocommerce', $stored_support );

		$method = new ReflectionMethod( WC_Post_Types::class, 'should_register_product_archive' );
		$method->setAccessible( true );

		$this->assertSame(
			$expected,
			$method->invoke( null, $runtime_support, $theme_may_be_skipped ),
			'Product archive support should only fall back to trusted stored support when the theme may be absent.'
		);
	}

	/**
	 * Data provider for product archive theme support resolution.
	 *
	 * @return array<string, array{bool, bool, string, bool}>
	 */
	public function provide_product_archive_theme_support_cases(): array {
		return array(
			'loaded supported theme'                => array( true, false, 'no', true ),
			'loaded unsupported theme'              => array( false, false, 'yes', false ),
			'skipped previously supported theme'    => array( false, true, 'yes', true ),
			'skipped previously unsupported theme'  => array( false, true, 'no', false ),
			'supported theme in unreliable context' => array( true, true, 'no', true ),
		);
	}
}
