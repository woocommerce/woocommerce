<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\StoreNotices as StoreNoticesBlockType;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * Tests for the StoreNotices block type.
 *
 * @since 10.4.0
 */
class StoreNotices extends \WP_UnitTestCase {

	/**
	 * Instance of the block being tested.
	 *
	 * @var StoreNoticesBlockType
	 */
	protected $block;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/store-notices' ) ) {
			$registry->unregister( 'woocommerce/store-notices' );
		}

		$this->block = new StoreNoticesBlockType(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		);

		// Ensure the WC session is available so notices can be queued/printed.
		if ( function_exists( 'WC' ) && WC()->session ) {
			wc_clear_notices();
		}
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/store-notices' ) ) {
			$registry->unregister( 'woocommerce/store-notices' );
		}

		if ( function_exists( 'WC' ) && WC()->session ) {
			wc_clear_notices();
		}

		unset( $this->block );
		parent::tearDown();
	}

	/**
	 * Invoke the protected render method.
	 *
	 * @return string|null
	 */
	private function invoke_render() {
		$reflection = new \ReflectionClass( $this->block );
		$method     = $reflection->getMethod( 'render' );
		$method->setAccessible( true );
		return $method->invoke( $this->block, array(), '', null );
	}

	/**
	 * The wrapper should expose a region landmark with an accessible name so
	 * assistive technology can identify the notice container.
	 */
	public function test_wrapper_has_region_role_and_aria_label() {
		if ( ! function_exists( 'wc_add_notice' ) ) {
			$this->markTestSkipped( 'WooCommerce notice functions are unavailable.' );
		}

		wc_add_notice( 'Test error message', 'error' );

		$markup = $this->invoke_render();

		$this->assertIsString( $markup );
		$this->assertStringContainsString( 'role="region"', $markup, 'StoreNotices wrapper should expose a region landmark.' );
		$this->assertStringContainsString( 'aria-label="Store notices"', $markup, 'StoreNotices wrapper should have an accessible name.' );
		$this->assertStringContainsString( 'wc-block-store-notices', $markup, 'StoreNotices wrapper class should be preserved.' );
	}

	/**
	 * When there are no notices, the block should render nothing (no empty
	 * landmark in the accessibility tree).
	 */
	public function test_renders_nothing_when_no_notices() {
		if ( ! function_exists( 'wc_add_notice' ) ) {
			$this->markTestSkipped( 'WooCommerce notice functions are unavailable.' );
		}

		$markup = $this->invoke_render();

		$this->assertEmpty( $markup, 'StoreNotices should not render a wrapper when there are no notices.' );
	}
}
