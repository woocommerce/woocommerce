<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Mutations\Products;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Enums\Products\ProductStatus;
use Automattic\WooCommerce\Api\InputTypes\Products\DimensionsInput;
use Automattic\WooCommerce\Api\InputTypes\Products\UpdateProductInput;
use Automattic\WooCommerce\Api\Mutations\Products\UpdateProduct;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see UpdateProduct}.
 */
class UpdateProductTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var UpdateProduct
	 */
	private UpdateProduct $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new UpdateProduct();
	}

	/**
	 * @testdox execute() throws NOT_FOUND when the product ID does not exist.
	 */
	public function test_execute_throws_not_found_for_missing_product(): void {
		$input     = new UpdateProductInput();
		$input->id = 999999;

		try {
			$this->sut->execute( $input );
			$this->fail( 'Expected ApiException was not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'Product not found.', $e->getMessage() );
			$this->assertSame( 'NOT_FOUND', $e->getErrorCode() );
			$this->assertSame( 404, $e->getStatusCode() );
		}
	}

	/**
	 * @testdox execute() updates only fields that were marked provided.
	 */
	public function test_execute_updates_only_provided_fields(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'        => 'Original Name',
				'description' => 'Original description.',
			)
		);

		$input       = new UpdateProductInput();
		$input->id   = $product->get_id();
		$input->name = 'Updated Name';
		$input->mark_provided( 'name' );

		$this->sut->execute( $input );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertSame( 'Updated Name', $reloaded->get_name() );
		$this->assertSame( 'Original description.', $reloaded->get_description() );
	}

	/**
	 * @testdox execute() does not touch fields that were not marked provided, even if set on the DTO.
	 */
	public function test_execute_ignores_fields_not_marked_provided(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Original Name' ) );

		$input       = new UpdateProductInput();
		$input->id   = $product->get_id();
		$input->name = 'Should Not Apply';

		$this->sut->execute( $input );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertSame( 'Original Name', $reloaded->get_name() );
	}

	/**
	 * @testdox execute() skips set_status() when status is provided as null.
	 */
	public function test_execute_skips_status_when_provided_null(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );

		$input         = new UpdateProductInput();
		$input->id     = $product->get_id();
		$input->status = null;
		$input->mark_provided( 'status' );

		$this->sut->execute( $input );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertSame( 'draft', $reloaded->get_status() );
	}

	/**
	 * @testdox execute() applies a non-null status enum.
	 */
	public function test_execute_applies_status_enum(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );

		$input         = new UpdateProductInput();
		$input->id     = $product->get_id();
		$input->status = ProductStatus::Published;
		$input->mark_provided( 'status' );

		$this->sut->execute( $input );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertSame( 'publish', $reloaded->get_status() );
	}

	/**
	 * @testdox execute() clears a price when explicit null is provided.
	 */
	public function test_execute_clears_price_when_explicit_null(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '19.99' ) );

		$input                = new UpdateProductInput();
		$input->id            = $product->get_id();
		$input->regular_price = null;
		$input->mark_provided( 'regular_price' );

		$this->sut->execute( $input );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertSame( '', $reloaded->get_regular_price() );
	}

	/**
	 * @testdox execute() applies provided dimension fields and leaves others alone.
	 */
	public function test_execute_applies_dimensions_selectively(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_length( '10' );
		$product->set_width( '5' );
		$product->save();

		$dimensions         = new DimensionsInput();
		$dimensions->length = 20.0;
		$dimensions->mark_provided( 'length' );

		$input             = new UpdateProductInput();
		$input->id         = $product->get_id();
		$input->dimensions = $dimensions;
		$input->mark_provided( 'dimensions' );

		$this->sut->execute( $input );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertSame( '20', $reloaded->get_length() );
		$this->assertSame( '5', $reloaded->get_width() );
	}
}
