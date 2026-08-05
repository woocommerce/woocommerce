<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Mutations\Products;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Enums\Products\ProductStatus;
use Automattic\WooCommerce\Api\InputTypes\Products\CreateProductInput;
use Automattic\WooCommerce\Api\InputTypes\Products\DimensionsInput;
use Automattic\WooCommerce\Api\Mutations\Products\CreateProduct;
use Automattic\WooCommerce\Api\Utils\Products\ProductRepository;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see CreateProduct}.
 */
class CreateProductTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var CreateProduct
	 */
	private CreateProduct $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CreateProduct();
		$this->sut->init( new ProductRepository() );
	}

	/**
	 * @testdox execute() creates a product with the given name and returns its DTO.
	 */
	public function test_execute_creates_product_with_required_fields(): void {
		$input       = new CreateProductInput();
		$input->name = 'Brand New Widget';

		$result = $this->sut->execute( $input );

		$this->assertIsObject( $result );
		$this->assertSame( 'Brand New Widget', $result->name );
		$this->assertGreaterThan( 0, $result->id );

		$wc_product = wc_get_product( $result->id );
		$this->assertInstanceOf( \WC_Product::class, $wc_product );
		$this->assertSame( 'Brand New Widget', $wc_product->get_name() );
	}

	/**
	 * @testdox execute() persists optional scalar fields when provided.
	 */
	public function test_execute_persists_optional_scalar_fields(): void {
		$input                    = new CreateProductInput();
		$input->name              = 'Detailed Widget';
		$input->slug              = 'detailed-widget';
		$input->sku               = 'SKU-DETAILED-001';
		$input->description       = 'The long description.';
		$input->short_description = 'Short blurb.';
		$input->regular_price     = 19.99;
		$input->sale_price        = 14.99;
		$input->manage_stock      = true;
		$input->stock_quantity    = 42;

		$result = $this->sut->execute( $input );

		$wc_product = wc_get_product( $result->id );
		$this->assertSame( 'detailed-widget', $wc_product->get_slug() );
		$this->assertSame( 'SKU-DETAILED-001', $wc_product->get_sku() );
		$this->assertSame( 'The long description.', $wc_product->get_description() );
		$this->assertSame( 'Short blurb.', $wc_product->get_short_description() );
		$this->assertSame( '19.99', $wc_product->get_regular_price() );
		$this->assertSame( '14.99', $wc_product->get_sale_price() );
		$this->assertTrue( $wc_product->get_manage_stock() );
		$this->assertSame( 42, $wc_product->get_stock_quantity() );
	}

	/**
	 * @testdox execute() applies the status enum when provided.
	 */
	public function test_execute_applies_status_enum(): void {
		$input         = new CreateProductInput();
		$input->name   = 'Draft Widget';
		$input->status = ProductStatus::Draft;

		$result = $this->sut->execute( $input );

		$wc_product = wc_get_product( $result->id );
		$this->assertSame( 'draft', $wc_product->get_status() );
	}

	/**
	 * @testdox execute() applies dimension fields when a DimensionsInput is provided.
	 */
	public function test_execute_applies_dimensions(): void {
		$dimensions         = new DimensionsInput();
		$dimensions->length = 10.5;
		$dimensions->width  = 5.25;
		$dimensions->height = 2.0;
		$dimensions->weight = 1.5;

		$input             = new CreateProductInput();
		$input->name       = 'Boxed Widget';
		$input->dimensions = $dimensions;

		$result = $this->sut->execute( $input );

		$wc_product = wc_get_product( $result->id );
		$this->assertSame( '10.5', $wc_product->get_length() );
		$this->assertSame( '5.25', $wc_product->get_width() );
		$this->assertSame( '2', $wc_product->get_height() );
		$this->assertSame( '1.5', $wc_product->get_weight() );
	}

	/**
	 * @testdox execute() throws VALIDATION_ERROR when the product name is already taken.
	 */
	public function test_execute_rejects_duplicate_name(): void {
		WC_Helper_Product::create_simple_product( true, array( 'name' => 'Duplicate Widget' ) );

		$input       = new CreateProductInput();
		$input->name = 'Duplicate Widget';

		try {
			$this->sut->execute( $input );
			$this->fail( 'Expected ApiException was not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'A product with this name already exists.', $e->getMessage() );
			$this->assertSame( 'VALIDATION_ERROR', $e->getErrorCode() );
			$this->assertSame( 422, $e->getStatusCode() );
			$this->assertArrayHasKey( 'field', $e->getExtensions() );
			$this->assertSame( 'name', $e->getExtensions()['field'] );
		}
	}

	/**
	 * @testdox execute() allows reusing the name of a trashed product.
	 */
	public function test_execute_allows_reusing_trashed_product_name(): void {
		$existing    = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Trashed Widget' ) );
		$existing_id = $existing->get_id();
		$existing->delete( false );

		$input       = new CreateProductInput();
		$input->name = 'Trashed Widget';

		$result = $this->sut->execute( $input );

		$this->assertIsObject( $result );
		$this->assertSame( 'Trashed Widget', $result->name );
		$this->assertNotSame( $existing_id, $result->id );
	}
}
