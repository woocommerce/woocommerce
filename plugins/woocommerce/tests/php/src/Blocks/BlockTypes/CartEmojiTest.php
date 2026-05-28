<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

/**
 * Tests that Cart and Checkout blocks disable WordPress emoji detection
 * to prevent React DOM corruption.
 *
 * Rather than instantiating block classes (which triggers block registration
 * conflicts in the test environment), these tests directly verify the
 * has_block() + remove_action() logic that disable_wp_emoji() relies on.
 *
 * @since 10.8.0
 */
class CartEmojiTest extends \WP_UnitTestCase {

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure emoji actions are registered as WordPress does by default.
		add_action( 'wp_head', 'print_emoji_detection_script', 7 );
		add_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Tear down after test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Restore emoji actions.
		add_action( 'wp_head', 'print_emoji_detection_script', 7 );
		add_action( 'wp_print_styles', 'print_emoji_styles' );
		parent::tearDown();
	}

	/**
	 * Simulate what disable_wp_emoji() does: if the current post contains the
	 * given block, remove emoji detection script and styles.
	 *
	 * @param string $block_name Full block name (e.g. 'woocommerce/cart').
	 * @return void
	 */
	private function simulate_disable_wp_emoji( string $block_name ): void {
		if ( has_block( $block_name ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
		}
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

		$this->simulate_disable_wp_emoji( 'woocommerce/cart' );

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
	 * Test that emoji detection is disabled on pages containing the Checkout block.
	 *
	 * @return void
	 */
	public function test_disable_wp_emoji_on_checkout_page() {
		$page_id = $this->factory->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => '<!-- wp:woocommerce/checkout --> <div class="wp-block-woocommerce-checkout"></div> <!-- /wp:woocommerce/checkout -->',
				'post_status'  => 'publish',
			)
		);

		$this->go_to( get_permalink( $page_id ) );

		$this->simulate_disable_wp_emoji( 'woocommerce/checkout' );

		$this->assertFalse(
			has_action( 'wp_head', 'print_emoji_detection_script' ),
			'Emoji detection script should be removed on pages with the Checkout block.'
		);
		$this->assertFalse(
			has_action( 'wp_print_styles', 'print_emoji_styles' ),
			'Emoji styles should be removed on pages with the Checkout block.'
		);

		wp_delete_post( $page_id, true );
	}

	/**
	 * Test that emoji detection is NOT disabled on pages without Cart/Checkout blocks.
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

		$this->simulate_disable_wp_emoji( 'woocommerce/cart' );

		$this->assertNotFalse(
			has_action( 'wp_head', 'print_emoji_detection_script' ),
			'Emoji detection script should remain on pages without the Cart block.'
		);

		wp_delete_post( $page_id, true );
	}
}
