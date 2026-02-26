<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use WC_Helper_Product;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * ProductSchemaTest class.
 */
class ProductSchemaTest extends TestCase {
	/**
	 * The system under test.
	 *
	 * @var ProductSchema
	 */
	private $sut;

	/**
	 * Set up before test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$schema_controller = new SchemaController( new ExtendSchema( $formatters ) );
		$this->sut         = $schema_controller->get( ProductSchema::IDENTIFIER );
	}

	/**
	 * Tear down after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset( $_GET['password'] );
		$this->sut = null;
	}

	/**
	 * Test that the schema includes the password_required property.
	 */
	public function test_schema_has_password_required_property(): void {
		$properties = $this->sut->get_properties();
		$this->assertArrayHasKey( 'password_required', $properties );
		$this->assertTrue( $properties['password_required']['readonly'] );
		$this->assertFalse( $properties['password_required']['default'] );
	}

	/**
	 * Test that a non-protected product returns the full response with password_required false.
	 */
	public function test_get_item_response_non_protected_product(): void {
		$product = WC_Helper_Product::create_simple_product();

		$result = $this->sut->get_item_response( $product );

		$this->assertArrayHasKey( 'password_required', $result );
		$this->assertFalse( $result['password_required'] );
		$this->assertArrayHasKey( 'prices', $result );
		$this->assertArrayHasKey( 'description', $result );
		$this->assertArrayHasKey( 'images', $result );
		$this->assertArrayHasKey( 'add_to_cart', $result );

		$product->delete( true );
	}

	/**
	 * Test that a password-protected product returns only minimal metadata.
	 */
	public function test_get_item_response_password_protected_returns_minimal_data(): void {
		$product = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'secret123',
			)
		);

		$result = $this->sut->get_item_response( $product );

		// Minimal fields should be present.
		$this->assertTrue( $result['password_required'] );
		$this->assertEquals( $product->get_id(), $result['id'] );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'slug', $result );
		$this->assertArrayHasKey( 'permalink', $result );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'parent', $result );

		// Sensitive fields should be omitted.
		$this->assertArrayNotHasKey( 'prices', $result );
		$this->assertArrayNotHasKey( 'description', $result );
		$this->assertArrayNotHasKey( 'short_description', $result );
		$this->assertArrayNotHasKey( 'images', $result );
		$this->assertArrayNotHasKey( 'add_to_cart', $result );
		$this->assertArrayNotHasKey( 'is_purchasable', $result );
		$this->assertArrayNotHasKey( 'is_in_stock', $result );
		$this->assertArrayNotHasKey( 'sku', $result );
		$this->assertArrayNotHasKey( 'on_sale', $result );
		$this->assertArrayNotHasKey( 'attributes', $result );
		$this->assertArrayNotHasKey( 'variations', $result );

		// Extension data should not be present.
		$this->assertArrayNotHasKey( 'extensions', $result );

		$product->delete( true );
	}

	/**
	 * Test that providing the correct password unlocks the full response.
	 */
	public function test_get_item_response_correct_password_returns_full_data(): void {
		$product = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'secret123',
			)
		);

		$_GET['password'] = 'secret123';

		$result = $this->sut->get_item_response( $product );

		$this->assertFalse( $result['password_required'] );
		$this->assertArrayHasKey( 'prices', $result );
		$this->assertArrayHasKey( 'description', $result );
		$this->assertArrayHasKey( 'images', $result );
		$this->assertArrayHasKey( 'add_to_cart', $result );

		$product->delete( true );
	}

	/**
	 * Test that providing an incorrect password still returns minimal data.
	 */
	public function test_get_item_response_wrong_password_returns_minimal_data(): void {
		$product = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'secret123',
			)
		);

		$_GET['password'] = 'wrong_password';

		$result = $this->sut->get_item_response( $product );

		$this->assertTrue( $result['password_required'] );
		$this->assertArrayNotHasKey( 'prices', $result );
		$this->assertArrayNotHasKey( 'description', $result );

		$product->delete( true );
	}
}
