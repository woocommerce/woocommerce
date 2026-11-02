<?php
/**
 * WeightPlaceholderTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Shipping\FlatRate;

use Automattic\WooCommerce\Internal\Shipping\FlatRate\WeightPlaceholder;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for WeightPlaceholder.
 */
class WeightPlaceholderTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WeightPlaceholder
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = $this->get_instance_of( WeightPlaceholder::class );
	}

	/**
	 * @testdox Package weight sums shippable items with a weight, multiplied by quantity.
	 */
	public function test_get_for_package_sums_shippable_weighted_items(): void {
		$package = array(
			'contents' => array(
				'weighted'    => array(
					'data'     => WC_Helper_Product::create_simple_product( false, array( 'weight' => '1.5' ) ),
					'quantity' => 2,
				),
				'no_weight'   => array(
					'data'     => WC_Helper_Product::create_simple_product( false, array( 'weight' => '' ) ),
					'quantity' => 1,
				),
				'virtual'     => array(
					'data'     => WC_Helper_Product::create_simple_product(
						false,
						array(
							'weight'  => '5',
							'virtual' => true,
						)
					),
					'quantity' => 3,
				),
				'no_quantity' => array(
					'data'     => WC_Helper_Product::create_simple_product( false, array( 'weight' => '9' ) ),
					'quantity' => 0,
				),
			),
		);

		$this->assertFloatEquals( 3.0, $this->sut->get_for_package( $package ), null, 'Only shippable items that have a weight should contribute to the package weight.' );
	}

	/**
	 * @testdox Package weight uses the parent weight for variations that have none of their own.
	 */
	public function test_get_for_package_uses_parent_weight_for_variations(): void {
		$variable_product = WC_Helper_Product::create_variation_product();
		$variable_product->set_weight( '2' );
		$variable_product->save();

		$children  = $variable_product->get_children();
		$variation = wc_get_product( $children[0] );

		$package = array(
			'contents' => array(
				'variation' => array(
					'data'     => $variation,
					'quantity' => 2,
				),
			),
		);

		$this->assertFloatEquals( 4.0, $this->sut->get_for_package( $package ), null, 'A variation without its own weight should inherit the parent product weight.' );
	}

	/**
	 * @testdox Package weight is zero when the package has no contents.
	 */
	public function test_get_for_package_returns_zero_for_empty_contents(): void {
		$this->assertSame( 0.0, $this->sut->get_for_package( array( 'contents' => array() ) ), 'An empty package should weigh zero.' );
	}

	/**
	 * @testdox Package weight is zero when the package has no contents key at all.
	 */
	public function test_get_for_package_returns_zero_when_contents_missing(): void {
		$this->assertSame( 0.0, $this->sut->get_for_package( array() ), 'A package without contents should weigh zero.' );
	}

	/**
	 * @testdox Non-array package contents do not cause a type error.
	 *
	 * @testWith [null]
	 *           ["invalid"]
	 *           [false]
	 *           [12]
	 *
	 * @param mixed $contents Invalid package contents.
	 */
	public function test_get_for_package_ignores_invalid_contents( $contents ): void {
		$this->assertSame( 0.0, $this->sut->get_for_package( array( 'contents' => $contents ) ), 'Invalid contents must not contribute weight.' );
	}

	/**
	 * @testdox Malformed items do not prevent valid items from contributing weight.
	 */
	public function test_get_for_items_ignores_malformed_items(): void {
		$items   = $this->single_item( '2.5', 2 );
		$product = $items['item']['data'];

		$items['missing_quantity'] = array( 'data' => $product );
		$items['invalid_quantity'] = array(
			'data'     => $product,
			'quantity' => 'invalid',
		);
		$items['missing_product']  = array( 'quantity' => 2 );
		$items['invalid_product']  = array(
			'data'     => false,
			'quantity' => 2,
		);
		$items['invalid_item']     = 'invalid';
		$items['object_item']      = new \stdClass();

		$this->assertSame( 5.0, $this->sut->get_for_items( $items ), 'Only valid items should contribute to the weight.' );
	}

	/**
	 * @testdox A negative product weight is clamped to zero.
	 */
	public function test_get_for_items_clamps_negative_product_weight(): void {
		$this->assertSame(
			0.0,
			$this->sut->get_for_items( $this->single_item( '-5', 2 ) ),
			'A negative weight should be clamped, the same way wc_get_weight() clamps it.'
		);
	}

	/**
	 * @testdox Negative product weights do not reduce the weight of other items.
	 *
	 * @testWith ["-1"]
	 *           ["-5"]
	 *
	 * @param string $negative_weight Weight of the product that must contribute nothing.
	 */
	public function test_get_for_items_does_not_subtract_negative_product_weights( string $negative_weight ): void {
		$items = array(
			array(
				'data'     => WC_Helper_Product::create_simple_product( false, array( 'weight' => '2.5' ) ),
				'quantity' => 2,
			),
			array(
				'data'     => WC_Helper_Product::create_simple_product( false, array( 'weight' => $negative_weight ) ),
				'quantity' => 2,
			),
		);

		$this->assertSame( 5.0, $this->sut->get_for_items( $items ), 'Negative product weights must not cancel out positive weights.' );
	}

	/**
	 * @testdox The [weight] placeholder is replaced with the weight.
	 *
	 * @dataProvider provider_placeholder_replacement
	 *
	 * @param string $sum      Cost formula.
	 * @param mixed  $weight   Weight to substitute.
	 * @param string $expected Expected formula after replacement.
	 */
	public function test_expand( string $sum, $weight, string $expected ): void {
		$this->assertSame( $expected, $this->sut->expand( $sum, $weight ), "Expected '{$sum}' to expand to '{$expected}'." );
	}

	/**
	 * Placeholder replacement cases.
	 *
	 * Format: [ formula, weight, expected formula ].
	 *
	 * @return array
	 */
	public function provider_placeholder_replacement(): array {
		return array(
			'bare placeholder'                => array( '[weight]', 3, '3' ),
			'placeholder in an expression'    => array( '2 * [weight]', 1.5, '2 * 1.5' ),
			'repeated placeholder'            => array( '[weight] + [weight]', 2, '2 + 2' ),
			'independent placeholder limits'  => array( '[weight min="5"] + [weight max="2"]', 3, '5 + 2' ),
			'longer placeholder name'         => array( '[weightless min="1"]', 3, '[weightless min="1"]' ),
			'hyphenated placeholder name'     => array( '[weight-foo]', 3, '[weight-foo]' ),
			'dotted placeholder name'         => array( '[weight.foo]', 3, '[weight.foo]' ),
			'hyphenated name with limits'     => array( '[weight-foo min="1"]', 3, '[weight-foo min="1"]' ),
			'dotted name with limits'         => array( '[weight.foo max="2"]', 3, '[weight.foo max="2"]' ),
			'unterminated placeholder'        => array( '[weight min="1"', 3, '[weight min="1"' ),
			'no placeholder is untouched'     => array( '10 * [qty]', 5, '10 * [qty]' ),
			'numeric string weight'           => array( '[weight]', '2.5', '2.5' ),

			// A zero weight must not collapse to an empty string, or `10 * [weight]` would become `10 *`.
			'zero weight keeps the operand'   => array( '10 * [weight]', 0, '10 * 0' ),

			// Anything non-numeric or negative counts as zero.
			'non-numeric weight'              => array( '[weight]', 'not-a-number', '0' ),
			'null weight'                     => array( '[weight]', null, '0' ),
			'array weight'                    => array( '[weight]', array( 1 ), '0' ),
			'negative weight clamped'         => array( '[weight]', -5, '0' ),

			// WC_Eval_Math reads `e` as a constant and has no thousands separator.
			'small weight is not an exponent' => array( '[weight]', 0.00001, '0.00001' ),
			'large weight has no separator'   => array( '[weight]', 1000000.0, '1000000' ),
			'float imprecision normalised'    => array( '[weight]', 0.1 + 0.2, '0.3' ),

			// wc_get_rounding_precision() is 6 by default, so anything finer truncates away.
			'weight below rounding precision' => array( '[weight]', 0.0000001, '0' ),

			// Minimum and maximum attributes.
			'min raises a low weight'         => array( '[weight min="1"]', 0, '1' ),
			'min ignored above the floor'     => array( '[weight min="1"]', 4, '4' ),
			'max caps a high weight'          => array( '[weight max="20"]', 50, '20' ),
			'max ignored below the ceiling'   => array( '[weight max="20"]', 5, '5' ),
			'min and max together'            => array( '[weight min="1" max="20"]', 0, '1' ),
			'explicit min zero is honoured'   => array( '[weight min="0"]', 3, '3' ),
			'min keeps a divisor non-zero'    => array( '10 / [weight min="2"]', 0, '10 / 2' ),
			'unknown attributes are ignored'  => array( '[weight foo="bar"]', 3, '3' ),
			'non-numeric min is ignored'      => array( '[weight min="abc"]', 3, '3' ),
		);
	}

	/**
	 * Build a package contents array holding a single simple product.
	 *
	 * @param string $weight   Product weight.
	 * @param int    $quantity Item quantity.
	 * @return array
	 */
	private function single_item( string $weight, int $quantity ): array {
		return array(
			'item' => array(
				'data'     => WC_Helper_Product::create_simple_product( false, array( 'weight' => $weight ) ),
				'quantity' => $quantity,
			),
		);
	}
}
