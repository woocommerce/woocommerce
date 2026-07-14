<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Mutations\Products;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Mutations\Products\DeleteProduct;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see DeleteProduct}.
 */
class DeleteProductTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var DeleteProduct
	 */
	private DeleteProduct $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new DeleteProduct();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_pre_delete_product' );
		parent::tearDown();
	}

	/**
	 * @testdox execute() throws NOT_FOUND when the product ID does not exist.
	 */
	public function test_execute_throws_not_found_for_missing_product(): void {
		try {
			$this->sut->execute( 999999 );
			$this->fail( 'Expected ApiException was not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'Product not found.', $e->getMessage() );
			$this->assertSame( 'NOT_FOUND', $e->getErrorCode() );
			$this->assertSame( 404, $e->getStatusCode() );
		}
	}

	/**
	 * @testdox execute() trashes the product when force=false.
	 */
	public function test_execute_trashes_product_without_force(): void {
		$product = WC_Helper_Product::create_simple_product();

		$deleted = $this->sut->execute( $product->get_id(), false );

		$this->assertTrue( $deleted );
		$this->assertSame( 'trash', get_post_status( $product->get_id() ) );
	}

	/**
	 * @testdox execute() permanently deletes the product when force=true.
	 */
	public function test_execute_force_deletes_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();

		$deleted = $this->sut->execute( $id, true );

		$this->assertTrue( $deleted );
		$this->assertNull( get_post( $id ) );
	}

	/**
	 * @testdox execute() defaults to non-force deletion (trash).
	 */
	public function test_execute_defaults_to_trash(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->sut->execute( $product->get_id() );

		$this->assertSame( 'trash', get_post_status( $product->get_id() ) );
	}

	/**
	 * @testdox execute() returns false when woocommerce_pre_delete_product short-circuits to false.
	 */
	public function test_execute_returns_false_when_pre_delete_filter_returns_false(): void {
		$product = WC_Helper_Product::create_simple_product();

		add_filter( 'woocommerce_pre_delete_product', '__return_false' );

		$this->assertFalse( $this->sut->execute( $product->get_id(), true ) );
	}

	/**
	 * @testdox execute() surfaces a WP_Error from woocommerce_pre_delete_product as an INTERNAL_ERROR ApiException.
	 */
	public function test_execute_translates_wp_error_to_api_exception(): void {
		$product = WC_Helper_Product::create_simple_product();

		add_filter(
			'woocommerce_pre_delete_product',
			static function () {
				return new \WP_Error( 'wc_delete_failed', 'Something went wrong.' );
			}
		);

		try {
			$this->sut->execute( $product->get_id(), true );
			$this->fail( 'Expected ApiException was not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'Something went wrong.', $e->getMessage() );
			$this->assertSame( 'INTERNAL_ERROR', $e->getErrorCode() );
			$this->assertSame( 500, $e->getStatusCode() );
		}
	}
}
