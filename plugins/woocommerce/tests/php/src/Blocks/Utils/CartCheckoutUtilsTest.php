<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
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
	 * Test finding express checkout attributes in top-level blocks.
	 */
	public function test_find_express_checkout_attributes_top_level() {
		$post_content = '<!-- wp:woocommerce/cart-express-payment-block {"buttonStyle":"dark","buttonHeight":48} /-->';

		$result = CartCheckoutUtils::find_express_checkout_attributes( $post_content, 'cart' );

		$this->assertEquals(
			array(
				'buttonStyle'  => 'dark',
				'buttonHeight' => 48,
			),
			$result
		);
	}

	/**
	 * Test finding express checkout attributes in nested blocks.
	 */
	public function test_find_express_checkout_attributes_nested() {
		$post_content = '<!-- wp:woocommerce/cart -->
    <!-- wp:woocommerce/cart-express-payment-block {"buttonStyle":"light","buttonHeight":48} /-->
    <!-- /wp:woocommerce/cart -->';

		$result = CartCheckoutUtils::find_express_checkout_attributes( $post_content, 'cart' );

		$this->assertEquals(
			array(
				'buttonStyle'  => 'light',
				'buttonHeight' => 48,
			),
			$result
		);
	}

	/**
	 * Test finding express checkout returns null when no block is present.
	 */
	public function test_find_express_checkout_attributes_not_found() {
		$post_content = '<!-- wp:paragraph --> <p>This is a paragraph block.</p> <!-- /wp:paragraph -->';

		$result = CartCheckoutUtils::find_express_checkout_attributes( $post_content, 'cart' );

		$this->assertNull( $result );
	}
}
