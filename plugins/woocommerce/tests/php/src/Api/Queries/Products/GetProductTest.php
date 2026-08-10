<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Queries\Products;

use Automattic\WooCommerce\Api\Queries\Products\GetProduct;
use Automattic\WooCommerce\Api\UnauthorizedException;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see GetProduct}.
 */
class GetProductTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var GetProduct
	 */
	private GetProduct $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( 0 );
		$this->sut = new GetProduct();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox authorize() returns true for an admin reading any product.
	 */
	public function test_authorize_allows_admin_for_any_product(): void {
		$author  = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$product = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'          => $product->get_id(),
				'post_author' => $author,
			)
		);

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$this->assertTrue( $this->sut->authorize( $product->get_id(), false ) );
	}

	/**
	 * @testdox authorize() returns true when the caller owns the product.
	 */
	public function test_authorize_allows_owner(): void {
		$user    = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$product = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'          => $product->get_id(),
				'post_author' => $user,
			)
		);
		wp_set_current_user( $user );

		$this->assertTrue( $this->sut->authorize( $product->get_id(), false ) );
	}

	/**
	 * @testdox authorize() returns true when _preauthorized is true and the product exists.
	 */
	public function test_authorize_honors_preauthorized_flag(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertTrue( $this->sut->authorize( $product->get_id(), true ) );
	}

	/**
	 * @testdox authorize() still throws for a non-existent ID even when _preauthorized is true.
	 */
	public function test_authorize_rejects_missing_product_even_when_preauthorized(): void {
		$this->expectException( UnauthorizedException::class );
		$this->expectExceptionMessage( 'Product not found.' );

		$this->sut->authorize( 999999, true );
	}

	/**
	 * @testdox authorize() still throws for a non-product post even when _preauthorized is true.
	 */
	public function test_authorize_rejects_non_product_post_even_when_preauthorized(): void {
		$post_id = self::factory()->post->create();

		$this->expectException( UnauthorizedException::class );
		$this->expectExceptionMessage( 'Product not found.' );

		$this->sut->authorize( $post_id, true );
	}

	/**
	 * @testdox authorize() throws "Product not found." for a non-positive ID.
	 *
	 * @dataProvider provider_non_positive_ids
	 *
	 * @param int $id The non-positive ID to reject.
	 */
	public function test_authorize_rejects_non_positive_id( int $id ): void {
		$this->expectException( UnauthorizedException::class );
		$this->expectExceptionMessage( 'Product not found.' );

		$this->sut->authorize( $id, false );
	}

	/**
	 * @return array<string, array{int}>
	 */
	public function provider_non_positive_ids(): array {
		return array(
			'zero'     => array( 0 ),
			'negative' => array( -1 ),
		);
	}

	/**
	 * @testdox authorize() throws "Product not found." for a non-existent ID.
	 */
	public function test_authorize_rejects_missing_product(): void {
		$this->expectException( UnauthorizedException::class );
		$this->expectExceptionMessage( 'Product not found.' );

		$this->sut->authorize( 999999, false );
	}

	/**
	 * @testdox authorize() throws "Product not found." for a non-product post.
	 */
	public function test_authorize_rejects_non_product_post(): void {
		$post_id = self::factory()->post->create();
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$this->expectException( UnauthorizedException::class );
		$this->expectExceptionMessage( 'Product not found.' );

		$this->sut->authorize( $post_id, false );
	}

	/**
	 * @testdox authorize() throws "Product not found." when a non-owner tries to read.
	 */
	public function test_authorize_rejects_non_owner(): void {
		$owner   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$other   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$product = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'          => $product->get_id(),
				'post_author' => $owner,
			)
		);
		wp_set_current_user( $other );

		$this->expectException( UnauthorizedException::class );
		$this->expectExceptionMessage( 'Product not found.' );

		$this->sut->authorize( $product->get_id(), false );
	}

	/**
	 * @testdox authorize() rejects an anonymous caller even when post_author is 0.
	 */
	public function test_authorize_rejects_anonymous_caller_for_authorless_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'          => $product->get_id(),
				'post_author' => 0,
			)
		);

		$this->expectException( UnauthorizedException::class );
		$this->expectExceptionMessage( 'Product not found.' );

		$this->sut->authorize( $product->get_id(), false );
	}

	/**
	 * @testdox execute() returns a product DTO for a valid ID.
	 */
	public function test_execute_returns_product_for_valid_id(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Test Widget' ) );

		$result = $this->sut->execute( $product->get_id() );

		$this->assertIsObject( $result );
		$this->assertSame( $product->get_id(), $result->id );
		$this->assertSame( 'Test Widget', $result->name );
	}

	/**
	 * @testdox execute() returns null for a non-positive ID.
	 */
	public function test_execute_returns_null_for_non_positive_id(): void {
		$this->assertNull( $this->sut->execute( 0 ) );
		$this->assertNull( $this->sut->execute( -1 ) );
	}

	/**
	 * @testdox execute() returns null when the ID does not point to a product.
	 */
	public function test_execute_returns_null_for_non_product(): void {
		$post_id = self::factory()->post->create();

		$this->assertNull( $this->sut->execute( $post_id ) );
		$this->assertNull( $this->sut->execute( 999999 ) );
	}
}
