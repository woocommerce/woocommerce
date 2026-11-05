<?php
/**
 * Product attribute create ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Ability;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductAttributeCreate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductAttributesQuery;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductUpdate;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests global attribute creation through the registered ability.
 */
class ProductAttributeCreateTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Ability
	 */
	private $sut;

	/**
	 * Set up the ability and an administrator.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
		$this->sut = new Ability( ProductAttributeCreate::get_name(), ProductAttributeCreate::get_registration_args() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * @testdox Should create an attribute with the defaults of the wc/v3 attribute create.
	 */
	public function test_creates_with_defaults(): void {
		$result = $this->sut->execute( array( 'name' => 'Color' ) );

		$this->assertIsArray( $result );
		$attribute = $result['attribute'];
		$this->assertGreaterThan( 0, $attribute['id'] );
		$this->assertSame( 'Color', $attribute['name'] );
		$this->assertSame( 'pa_color', $attribute['slug'] );
		$this->assertSame( 'select', $attribute['type'] );
		$this->assertSame( 'menu_order', $attribute['order_by'] );
		$this->assertFalse( $attribute['has_archives'] );
		$this->assertSame( array(), $attribute['terms'] );
		$this->assertSame( $attribute['id'], wc_attribute_taxonomy_id_by_name( 'color' ) );
	}

	/**
	 * @testdox Should store the slug, sort order and archives it is given, without a pa_ prefix on the slug.
	 */
	public function test_creates_with_given_fields(): void {
		$result = $this->sut->execute(
			array(
				'name'         => 'Shoe size',
				'slug'         => 'pa_shoe-size',
				'order_by'     => 'name_num',
				'has_archives' => true,
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'pa_shoe-size', $result['attribute']['slug'] );
		$this->assertSame( 'name_num', $result['attribute']['order_by'] );
		$this->assertTrue( $result['attribute']['has_archives'] );
	}

	/**
	 * @testdox Should refuse an input it cannot store, with an ability error code.
	 * @testWith ["Other", "test-colour", "woocommerce_ability_product_attribute_slug_exists"]
	 *           ["Other", "pa_test-colour", "woocommerce_ability_product_attribute_slug_exists"]
	 *           ["TEST COLOR", "other-color", "woocommerce_ability_product_attribute_name_exists"]
	 *           ["Shade", "test-color", "woocommerce_ability_product_attribute_name_ambiguous"]
	 *           ["Test Colour", "shade", "woocommerce_ability_product_attribute_name_ambiguous"]
	 *           ["Type", "type", "woocommerce_ability_product_attribute_slug_reserved"]
	 *           ["Long", "abcdefghijklmnopqrstuvwxyz0123", "woocommerce_ability_product_attribute_slug_too_long"]
	 *           ["<b></b>", "blank", "woocommerce_ability_product_attribute_name_empty"]
	 * @param string $name Attribute name.
	 * @param string $slug Attribute slug.
	 * @param string $code Expected error code.
	 */
	public function test_refuses_invalid_attribute( string $name, string $slug, string $code ): void {
		$this->assertIsInt(
			wc_create_attribute(
				array(
					'name' => 'Test Color',
					'slug' => 'test-colour',
				)
			)
		);
		$before = count( wc_get_attribute_taxonomies() );

		$result = $this->sut->execute(
			array(
				'name' => $name,
				'slug' => $slug,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertCount( $before, wc_get_attribute_taxonomies(), 'Nothing is created.' );
	}

	/**
	 * @testdox Should refuse a type that the store does not offer.
	 */
	public function test_refuses_unknown_type(): void {
		$result = $this->sut->execute(
			array(
				'name' => 'Color',
				'type' => 'swatch',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse a customer.
	 */
	public function test_refuses_customer(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$result = $this->sut->execute( array( 'name' => 'Color' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		$this->assertSame( 0, wc_attribute_taxonomy_id_by_name( 'color' ) );
	}

	/**
	 * @testdox Should let product-update turn a simple product into a variable one with two new global attributes and four priced variations.
	 */
	public function test_product_update_uses_created_attributes(): void {
		$color = $this->sut->execute( array( 'name' => 'Test Color' ) )['attribute'];
		$size  = $this->sut->execute( array( 'name' => 'Test Size' ) )['attribute'];
		foreach ( array( $color, $size ) as $attribute ) {
			// WooCommerce registers attribute taxonomies on init, which already ran.
			register_taxonomy( $attribute['slug'], array( 'product' ), array( 'hierarchical' => false ) );
		}
		$product    = WC_Helper_Product::create_simple_product();
		$prices     = array(
			'red-s'  => '10.00',
			'red-m'  => '11.00',
			'blue-s' => '12.00',
			'blue-m' => '13.00',
		);
		$variations = array();
		foreach ( $prices as $key => $price ) {
			list( $color_option, $size_option ) = explode( '-', $key );
			$variations[]                       = array(
				'attributes'    => array(
					array(
						'id'     => $color['id'],
						'option' => $color_option,
					),
					array(
						'id'     => $size['id'],
						'option' => $size_option,
					),
				),
				'regular_price' => $price,
			);
		}
		$update = new ProductUpdate( ProductUpdate::ABILITY_NAME, ProductUpdate::get_registration_args() );

		$result = $update->execute(
			array(
				'id'         => $product->get_id(),
				'type'       => 'variable',
				'attributes' => array(
					array(
						'id'      => $color['id'],
						'options' => array( 'Red', 'Blue' ),
					),
					array(
						'name'    => 'Test Size',
						'global'  => true,
						'options' => array( 'S', 'M' ),
					),
				),
				'variations' => $variations,
			)
		);

		$this->assertNotWPError( $result );
		$saved = wc_get_product( $product->get_id() );
		$this->assertInstanceOf( \WC_Product_Variable::class, $saved );
		$this->assertCount( 4, $saved->get_children() );
		$this->assertSame( array( 'red', 'blue' ), wc_get_product_terms( $saved->get_id(), $color['slug'], array( 'fields' => 'slugs' ) ) );
		$saved_prices = array();
		foreach ( $saved->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			$key       = $variation->get_attribute( $color['slug'] ) . '-' . $variation->get_attribute( $size['slug'] );
			$saved_prices[ strtolower( (string) $key ) ] = $variation->get_regular_price();
		}
		ksort( $prices );
		ksort( $saved_prices );
		$this->assertSame( $prices, $saved_prices );

		$query = new Ability( ProductAttributesQuery::get_name(), ProductAttributesQuery::get_registration_args() );
		$read  = $query->execute( array( 'include' => array( $color['id'] ) ) );
		$this->assertSame( 2, $read['attributes'][0]['term_count'], 'The product write created the terms.' );
	}

	/**
	 * @testdox Should refuse a product write that names a global attribute that does not exist.
	 */
	public function test_product_update_refuses_unknown_global_attribute(): void {
		$product = WC_Helper_Product::create_simple_product();
		$update  = new ProductUpdate( ProductUpdate::ABILITY_NAME, ProductUpdate::get_registration_args() );

		$result = $update->execute(
			array(
				'id'         => $product->get_id(),
				'type'       => 'variable',
				'attributes' => array(
					array(
						'name'    => 'Missing',
						'global'  => true,
						'options' => array( 'A' ),
					),
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_attributes_invalid', $result->get_error_code() );
	}
}
