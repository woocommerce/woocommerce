<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Tests\Blocks\Mocks\CartCheckoutUtilsMock;
use WP_UnitTestCase;

/**
 * Tests for the CartCheckoutUtils class.
 */
class CartCheckoutUtilsTest extends WP_UnitTestCase {

	/**
	 * Holds an instance of the dependency injection container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Setup test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_option( 'woocommerce_checkout_phone_field' );
		delete_option( 'woocommerce_checkout_company_field' );
		delete_option( 'woocommerce_checkout_address_2_field' );
	}

	/**
	 * Test migrate_checkout_block_field_visibility_attributes() function.
	 */
	public function test_migrate_checkout_block_field_visibility_attributes() {
		// Default migration without checkout page.
		delete_option( 'woocommerce_checkout_page_id' );

		CartCheckoutUtilsMock::migrate_checkout_block_field_visibility_attributes_test();
		$this->assertEquals( 'optional', get_option( 'woocommerce_checkout_phone_field' ) );
		$this->assertEquals( 'hidden', get_option( 'woocommerce_checkout_company_field' ) );
		$this->assertEquals( 'optional', get_option( 'woocommerce_checkout_address_2_field' ) );

		// Populate checkout page.
		$page = array(
			'name'    => 'blocks-page',
			'title'   => 'Checkout',
			'content' => '',
		);

		$page_id         = wc_create_page( $page['name'], 'woocommerce_checkout_page_id', $page['title'], $page['content'] );
		$updated_content = '<!-- wp:woocommerce/checkout {"showApartmentField":false,"showCompanyField":false,"showPhoneField":false,"requireApartmentField":false,"requireCompanyField":false,"requirePhoneField":false} --> <div class="wp-block-woocommerce-checkout is-loading"></div> <!-- /wp:woocommerce/checkout -->';
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => $updated_content,
			]
		);

		CartCheckoutUtilsMock::migrate_checkout_block_field_visibility_attributes_test();
		$this->assertEquals( 'hidden', get_option( 'woocommerce_checkout_phone_field' ) );
		$this->assertEquals( 'hidden', get_option( 'woocommerce_checkout_company_field' ) );
		$this->assertEquals( 'hidden', get_option( 'woocommerce_checkout_address_2_field' ) );

		// Repeat with different settings.
		$updated_content = '<!-- wp:woocommerce/checkout {"showApartmentField":true,"showCompanyField":true,"showPhoneField":true,"requireApartmentField":true,"requireCompanyField":true,"requirePhoneField":true} --> <div class="wp-block-woocommerce-checkout is-loading"></div> <!-- /wp:woocommerce/checkout -->';
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => $updated_content,
			]
		);

		CartCheckoutUtilsMock::migrate_checkout_block_field_visibility_attributes_test();
		$this->assertEquals( 'required', get_option( 'woocommerce_checkout_phone_field' ) );
		$this->assertEquals( 'required', get_option( 'woocommerce_checkout_company_field' ) );
		$this->assertEquals( 'required', get_option( 'woocommerce_checkout_address_2_field' ) );
	}

	/**
	 * Test has_cart_page() function.
	 */
	public function test_has_cart_page() {
		wc_create_page( 'cart', 'woocommerce_cart_page_id', 'Cart', '' );
		$this->assertTrue( CartCheckoutUtils::has_cart_page() );
		delete_option( 'woocommerce_cart_page_id' );
		$this->assertFalse( CartCheckoutUtils::has_cart_page() );
	}

	/**
	 * Test is_cart_block_default() function.
	 */
	public function test_is_cart_block_default() {
		// Test when no cart page exists.
		delete_option( 'woocommerce_cart_page_id' );
		$this->assertFalse( CartCheckoutUtils::is_cart_block_default() );

		// Create cart page without block.
		$page_id = wc_create_page( 'cart', 'woocommerce_cart_page_id', 'Cart', '[woocommerce_cart]' );
		$this->assertFalse( CartCheckoutUtils::is_cart_block_default() );

		// Update cart page with cart block.
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => '<!-- wp:woocommerce/cart --><div class="wp-block-woocommerce-cart"></div><!-- /wp:woocommerce/cart -->',
			]
		);
		$this->assertTrue( CartCheckoutUtils::is_cart_block_default() );

		// Test with empty content.
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => '',
			]
		);
		$this->assertFalse( CartCheckoutUtils::is_cart_block_default() );

		// Test with multiple blocks including cart block.
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => '<!-- wp:paragraph --><p>Some text</p><!-- /wp:paragraph --><!-- wp:woocommerce/cart --><div class="wp-block-woocommerce-cart"></div><!-- /wp:woocommerce/cart -->',
			]
		);
		$this->assertTrue( CartCheckoutUtils::is_cart_block_default() );
	}

	/**
	 * Test is_checkout_block_default() function.
	 */
	public function test_is_checkout_block_default() {
		// Test when no checkout page exists.
		delete_option( 'woocommerce_checkout_page_id' );
		$this->assertFalse( CartCheckoutUtils::is_checkout_block_default() );

		// Create checkout page without block.
		$page_id = wc_create_page( 'checkout', 'woocommerce_checkout_page_id', 'Checkout', '[woocommerce_checkout]' );
		$this->assertFalse( CartCheckoutUtils::is_checkout_block_default() );

		// Update checkout page with checkout block.
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->',
			]
		);
		$this->assertTrue( CartCheckoutUtils::is_checkout_block_default() );

		// Test with empty content.
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => '',
			]
		);
		$this->assertFalse( CartCheckoutUtils::is_checkout_block_default() );

		// Test with multiple blocks including checkout block.
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => '<!-- wp:paragraph --><p>Some text</p><!-- /wp:paragraph --><!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->',
			]
		);
		$this->assertTrue( CartCheckoutUtils::is_checkout_block_default() );
	}

	/**
	 * Test is_cart_block_default() with block theme templates.
	 */
	public function test_is_cart_block_default_with_block_theme() {
		// Enable mock block theme.
		CartCheckoutUtilsMock::$mock_block_theme = true;

		// Test with template without cart block.
		CartCheckoutUtilsMock::$mock_templates = [
			(object) [
				'id'      => 'theme//cart',
				'slug'    => 'cart',
				'content' => '<!-- wp:paragraph --><p>Custom cart without block</p><!-- /wp:paragraph -->',
				'source'  => 'theme',
				'type'    => 'wp_template',
			],
		];
		$this->assertFalse( CartCheckoutUtilsMock::is_cart_block_default() );

		// Test with template with cart block.
		CartCheckoutUtilsMock::$mock_templates = [
			(object) [
				'id'      => 'theme//cart',
				'slug'    => 'cart',
				'content' => '<!-- wp:woocommerce/cart --><div class="wp-block-woocommerce-cart"></div><!-- /wp:woocommerce/cart -->',
				'source'  => 'theme',
				'type'    => 'wp_template',
			],
		];
		$this->assertTrue( CartCheckoutUtilsMock::is_cart_block_default() );

		// Clean up.
		CartCheckoutUtilsMock::$mock_block_theme = false;
		CartCheckoutUtilsMock::$mock_templates   = [];
	}

	/**
	 * Test is_checkout_block_default() with block theme templates.
	 */
	public function test_is_checkout_block_default_with_block_theme() {
		// Enable mock block theme.
		CartCheckoutUtilsMock::$mock_block_theme = true;

		// Test with template without checkout block.
		CartCheckoutUtilsMock::$mock_templates = [
			(object) [
				'id'      => 'theme//checkout',
				'slug'    => 'checkout',
				'content' => '<!-- wp:paragraph --><p>Custom checkout without block</p><!-- /wp:paragraph -->',
				'source'  => 'theme',
				'type'    => 'wp_template',
			],
		];
		$this->assertFalse( CartCheckoutUtilsMock::is_checkout_block_default() );

		// Test with template with checkout block.
		CartCheckoutUtilsMock::$mock_templates = [
			(object) [
				'id'      => 'theme//checkout',
				'slug'    => 'checkout',
				'content' => '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->',
				'source'  => 'theme',
				'type'    => 'wp_template',
			],
		];
		$this->assertTrue( CartCheckoutUtilsMock::is_checkout_block_default() );

		// Clean up.
		CartCheckoutUtilsMock::$mock_block_theme = false;
		CartCheckoutUtilsMock::$mock_templates   = [];
	}
}
