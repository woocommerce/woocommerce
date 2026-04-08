<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypes\Cart as CartBlock;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;

/**
 * Tests that Cart block disables WordPress emoji detection to prevent
 * React DOM corruption.
 *
 * @since 10.8.0
 */
class CartEmojiTest extends \WP_UnitTestCase {

	/**
	 * @var CartBlock
	 */
	private $cart_block;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$asset_api            = Package::container()->get( API::class );
		$registry             = new AssetDataRegistryMock( $asset_api );
		$integration_registry = new IntegrationRegistry();
		$this->cart_block     = new CartBlock( $asset_api, $registry, $integration_registry );

		// Ensure emoji actions are registered as WordPress does by default.
		add_action( 'wp_head', 'print_emoji_detection_script', 7 );
		add_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Tear down after test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Restore emoji actions.
		add_action( 'wp_head', 'print_emoji_detection_script', 7 );
		add_action( 'wp_print_styles', 'print_emoji_styles' );
		parent::tearDown();
	}

	/**
	 * Test that emoji detection is disabled on pages containing the Cart block.
	 *
	 * @return void
	 */
	public function test_disable_wp_emoji_on_cart_page() {
		$page_id = $this->factory->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => '<!-- wp:woocommerce/cart --> <div class="wp-block-woocommerce-cart"></div> <!-- /wp:woocommerce/cart -->',
				'post_status'  => 'publish',
			)
		);

		$this->go_to( get_permalink( $page_id ) );

		$this->cart_block->disable_wp_emoji();

		$this->assertFalse(
			has_action( 'wp_head', 'print_emoji_detection_script' ),
			'Emoji detection script should be removed on pages with the Cart block.'
		);
		$this->assertFalse(
			has_action( 'wp_print_styles', 'print_emoji_styles' ),
			'Emoji styles should be removed on pages with the Cart block.'
		);

		wp_delete_post( $page_id, true );
	}

	/**
	 * Test that emoji detection is NOT disabled on pages without the Cart block.
	 *
	 * @return void
	 */
	public function test_emoji_preserved_on_non_cart_pages() {
		$page_id = $this->factory->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => '<p>Just a normal page</p>',
				'post_status'  => 'publish',
			)
		);

		$this->go_to( get_permalink( $page_id ) );

		$this->cart_block->disable_wp_emoji();

		$this->assertNotFalse(
			has_action( 'wp_head', 'print_emoji_detection_script' ),
			'Emoji detection script should remain on pages without the Cart block.'
		);

		wp_delete_post( $page_id, true );
	}
}
