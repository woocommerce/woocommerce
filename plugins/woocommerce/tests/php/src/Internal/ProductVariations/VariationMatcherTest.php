<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductVariations;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\ProductVariations\VariationMatcher;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Unit_Test_Case;

/**
 * Tests for VariationMatcher.
 */
class VariationMatcherTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var VariationMatcher
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new VariationMatcher();
	}

	/**
	 * @testdox has_earlier_match follows menu order and ignores unavailable earlier variations.
	 *
	 * @dataProvider provide_menu_order_cases
	 *
	 * @param string                $variation_key Variation being checked.
	 * @param array<string, string> $selected      Selected attribute values.
	 * @param bool                  $expected      Whether an earlier variation matches.
	 * @param string                $message       Assertion message.
	 */
	public function test_has_earlier_match_follows_menu_order( string $variation_key, array $selected, bool $expected, string $message ): void {
		$variations = $this->create_color_and_size_variations();

		$this->assertSame( $expected, $this->sut->has_earlier_match( $variations[ $variation_key ], $selected ), $message );
	}

	/**
	 * Menu order and availability cases.
	 *
	 * Variations, in menu order: Red/Any size, unpriced Blue/Large, Red/Large, Any color/Small, Any/Any.
	 *
	 * @return array<string, array{0: string, 1: array<string, string>, 2: bool, 3: string}>
	 */
	public function provide_menu_order_cases(): array {
		$red_large = array(
			'attribute_color' => 'Red',
			'attribute_size'  => 'Large',
		);

		return array(
			'fully specified variation'     => array(
				'red_large',
				$red_large,
				false,
				'A fully specified variation should not match an earlier variation.',
			),
			'unpriced earlier variation'    => array(
				'any',
				array(
					'attribute_color' => 'Blue',
					'attribute_size'  => 'Large',
				),
				false,
				'An unpriced earlier variation should be ignored.',
			),
			'variation ordered first'       => array(
				'red_any_size',
				$red_large,
				false,
				'A variation ordered before a closer match should not report an earlier match.',
			),
			'later any variation'           => array(
				'any',
				$red_large,
				true,
				'A later Any variation should match an earlier available variation.',
			),
			'no earlier variation matches'  => array(
				'any_color_small',
				array(
					'attribute_color' => 'Blue',
					'attribute_size'  => 'Small',
				),
				false,
				'A variation should be allowed when no earlier variation matches.',
			),
			'same number of Any attributes' => array(
				'any_color_small',
				array(
					'attribute_color' => 'Red',
					'attribute_size'  => 'Small',
				),
				true,
				'A later variation should match an earlier one with the same number of Any attributes.',
			),
		);
	}

	/**
	 * @testdox An out-of-stock earlier variation is ignored when out-of-stock products are hidden.
	 */
	public function test_ignores_out_of_stock_earlier_variation_when_hidden_from_catalog(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$product = $this->create_variable_product(
			array(
				array(
					'name'    => 'Color',
					'options' => array( 'Red', 'Blue' ),
				),
			)
		);
		$red     = $this->create_variation( $product, array( 'color' => 'Red' ), '10', 0 );
		$red->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$red->save();
		$any = $this->create_variation( $product, array( 'color' => '' ), '5', 1 );

		$this->assertFalse(
			$this->sut->has_earlier_match(
				$any,
				array(
					'attribute_color' => 'Red',
				)
			),
			'An out-of-stock earlier variation should be ignored when hidden from the catalog.'
		);
	}

	/**
	 * @testdox An earlier variation hidden by woocommerce_available_variation is ignored.
	 */
	public function test_ignores_earlier_variation_hidden_by_available_variation_filter(): void {
		$product = $this->create_variable_product(
			array(
				array(
					'name'    => 'Color',
					'options' => array( 'Blue' ),
				),
			)
		);
		$blue    = $this->create_variation( $product, array( 'color' => 'Blue' ), '50', 0 );
		$any     = $this->create_variation( $product, array( 'color' => '' ), '40', 1 );

		add_filter(
			'woocommerce_available_variation',
			static function ( $data, $variable_product, $variation ) use ( $blue ) {
				unset( $variable_product );
				if ( $variation instanceof WC_Product_Variation && $blue->get_id() === $variation->get_id() ) {
					return false;
				}
				return $data;
			},
			10,
			3
		);

		$this->assertFalse(
			$this->sut->has_earlier_match(
				$any,
				array(
					'attribute_color' => 'Blue',
				)
			),
			'An earlier variation hidden by woocommerce_available_variation should be ignored.'
		);
	}

	/**
	 * Create the color and size variations used by the menu-order cases.
	 *
	 * @return array<string, WC_Product_Variation>
	 */
	private function create_color_and_size_variations(): array {
		$product = $this->create_variable_product(
			array(
				array(
					'name'    => 'Color',
					'options' => array( 'Red', 'Blue' ),
				),
				array(
					'name'    => 'Size',
					'options' => array( 'Large', 'Small' ),
				),
			)
		);

		return array(
			'red_any_size'    => $this->create_variation(
				$product,
				array(
					'color' => 'Red',
					'size'  => '',
				),
				'5',
				0
			),
			'blue_large'      => $this->create_variation(
				$product,
				array(
					'color' => 'Blue',
					'size'  => 'Large',
				),
				'',
				1
			),
			'red_large'       => $this->create_variation(
				$product,
				array(
					'color' => 'Red',
					'size'  => 'Large',
				),
				'8',
				2
			),
			'any_color_small' => $this->create_variation(
				$product,
				array(
					'color' => '',
					'size'  => 'Small',
				),
				'6',
				3
			),
			'any'             => $this->create_variation(
				$product,
				array(
					'color' => '',
					'size'  => '',
				),
				'10',
				4
			),
		);
	}

	/**
	 * Create a variable product.
	 *
	 * @param array<int, array{name: string, options: array<int, string>}> $attributes Attribute name and options.
	 * @return WC_Product_Variable
	 */
	private function create_variable_product( array $attributes ): WC_Product_Variable {
		$product            = new WC_Product_Variable();
		$product_attributes = array();

		foreach ( $attributes as $attribute ) {
			$product_attribute = new WC_Product_Attribute();
			$product_attribute->set_name( $attribute['name'] );
			$product_attribute->set_options( $attribute['options'] );
			$product_attribute->set_variation( true );
			$product_attributes[] = $product_attribute;
		}

		$product->set_attributes( $product_attributes );
		$product->save();

		return $product;
	}

	/**
	 * Create a variation. An empty attribute value is Any, and an empty price leaves it unpriced.
	 *
	 * @param WC_Product_Variable   $parent_product Parent product.
	 * @param array<string, string> $attributes     Variation attributes.
	 * @param string                $price          Regular price.
	 * @param int                   $menu_order     Menu order.
	 * @return WC_Product_Variation
	 */
	private function create_variation( WC_Product_Variable $parent_product, array $attributes, string $price, int $menu_order ): WC_Product_Variation {
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent_product->get_id() );
		$variation->set_attributes( $attributes );
		if ( '' !== $price ) {
			$variation->set_regular_price( $price );
		}
		$variation->set_menu_order( $menu_order );
		$variation->save();

		return $variation;
	}
}
