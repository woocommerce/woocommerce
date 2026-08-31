<?php
/**
 * SelectedVariationName tests.
 *
 * @package WooCommerce\Tests\Internal\ProductVariations
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductVariations;

use Automattic\WooCommerce\Internal\ProductVariations\SelectedVariationName;
use WC_Data_Store;
use WC_Helper_Product;
use WC_Product_Variation;
use WC_Unit_Test_Case;

/**
 * Tests for SelectedVariationName.
 */
class SelectedVariationNameTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var SelectedVariationName
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new SelectedVariationName();
	}

	/**
	 * @testdox Selected Any attributes are included contextually without changing the stored variation name.
	 */
	public function test_get_product_name_includes_selected_any_attributes(): void {
		list( $product, $variation ) = WC_Helper_Product::create_variation_product_with_global_attributes(
			'Selected Any Product',
			array(
				'pa_size'   => 'huge',
				'pa_number' => '',
			)
		);
		$filter_calls                = 0;
		$option_filter               = function ( $value ) use ( &$filter_calls ) {
			++$filter_calls;

			return 'Filtered ' . $value;
		};
		add_filter( 'woocommerce_variation_option_name', $option_filter );

		try {
			$stored_name = $variation->get_name();
			$name        = $this->sut->get_product_name(
				$variation,
				array(
					'attribute_pa_size'   => 'huge',
					'attribute_pa_number' => '1',
				)
			);

			$this->assertSame( 'Selected Any Product - huge, 1', $name );
			$this->assertSame( 0, $filter_calls, 'Canonical order values must not use cart option-label filters.' );
			$this->assertSame( $stored_name, $variation->get_name(), 'The contextual name must not mutate the variation object.' );
			$this->assertSame( $stored_name, wc_get_product( $variation->get_id() )->get_name(), 'The contextual name must not change the persisted variation.' );
		} finally {
			remove_filter( 'woocommerce_variation_option_name', $option_filter );
			$variation->delete( true );
			$product->delete( true );
		}
	}

	/**
	 * @testdox Data stores without the shared title policy keep the stored variation name.
	 */
	public function test_get_product_name_keeps_stored_name_without_data_store_title_policy(): void {
		$data_store = $this->getMockBuilder( WC_Data_Store::class )->disableOriginalConstructor()->onlyMethods( array( 'has_callable' ) )->getMock();
		$data_store->method( 'has_callable' )->with( 'should_include_attributes_in_title' )->willReturn( false );

		$variation = $this->getMockBuilder( WC_Product_Variation::class )->onlyMethods( array( 'get_data_store' ) )->getMock();
		$variation->method( 'get_data_store' )->willReturn( $data_store );
		$variation->set_name( 'Legacy Store Product' );
		$variation->set_attributes( array( 'finish' => '' ) );

		$this->assertSame( 'Legacy Store Product', $this->sut->get_product_name( $variation, array( 'attribute_finish' => 'gloss' ) ) );
	}

	/**
	 * @testdox Fixed variation names return without evaluating contextual title policy.
	 */
	public function test_get_product_name_returns_early_for_fixed_variations(): void {
		$variation = new WC_Product_Variation();
		$variation->set_name( 'Fixed Product - huge, 1' );
		$variation->set_attributes(
			array(
				'pa_size'   => 'huge',
				'pa_number' => '1',
			)
		);

		$title_policy_calls  = 0;
		$title_policy_filter = function ( $should_include ) use ( &$title_policy_calls ) {
			++$title_policy_calls;

			return $should_include;
		};
		add_filter( 'woocommerce_product_variation_title_include_attributes', $title_policy_filter );

		$name = $this->sut->get_product_name(
			$variation,
			array(
				'attribute_pa_size'   => 'huge',
				'attribute_pa_number' => '1',
			)
		);

		$this->assertSame( 'Fixed Product - huge, 1', $name );
		$this->assertSame( 0, $title_policy_calls );
	}

	/**
	 * @testdox Contextual variation names retain parent-only titles when title policy omits attributes.
	 *
	 * @dataProvider title_policy_provider
	 *
	 * @param string                $product_name Product name.
	 * @param array<string, string> $stored_attributes Stored variation attributes.
	 * @param array<string, string> $selected_attributes Selected cart attributes.
	 * @param bool                  $use_exclude_filter Whether to force exclusion via the include-attributes filter.
	 */
	public function test_get_product_name_respects_title_policy( string $product_name, array $stored_attributes, array $selected_attributes, bool $use_exclude_filter = false ): void {
		$variation = new WC_Product_Variation();
		$variation->set_name( $product_name );
		$variation->set_attributes( $stored_attributes );

		if ( $use_exclude_filter ) {
			add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );
		}

		$this->assertSame( $product_name, $this->sut->get_product_name( $variation, $selected_attributes ) );
	}

	/**
	 * Provides variation-title policy cases.
	 *
	 * @return array<string, array{0: string, 1: array<string, string>, 2: array<string, string>, 3?: bool}>
	 */
	public static function title_policy_provider(): array {
		return array(
			'three attributes'             => array(
				'Three Attribute Product',
				array(
					'pa_size'   => 'huge',
					'pa_colour' => 'blue',
					'pa_number' => '',
				),
				array(
					'attribute_pa_size'   => 'huge',
					'attribute_pa_colour' => 'blue',
					'attribute_pa_number' => '1',
				),
			),
			'multi-word attribute'         => array(
				'Multi Word Attribute Product',
				array(
					'pa_mount-colour' => '',
					'pa_size'         => 'large',
				),
				array(
					'attribute_pa_mount-colour' => 'black',
					'attribute_pa_size'         => 'large',
				),
			),
			'include filter returns false' => array(
				'Filtered Include Product',
				array(
					'pa_size'   => 'huge',
					'pa_number' => '',
				),
				array(
					'attribute_pa_size'   => 'huge',
					'attribute_pa_number' => '1',
				),
				true,
			),
		);
	}

	/**
	 * @testdox Filtered Any variation labels that cannot be displayed are omitted.
	 * @dataProvider filtered_any_variation_label_provider
	 *
	 * @param mixed $filtered_value Filtered variation option label.
	 */
	public function test_get_product_name_omits_filtered_any_variation_labels_that_cannot_be_displayed( $filtered_value ): void {
		$variation = new WC_Product_Variation();
		$variation->set_name( 'Filtered Any Product' );
		$variation->set_attributes( array( 'finish' => '' ) );

		$option_filter          = static function () use ( $filtered_value ) {
			return $filtered_value;
		};
		$separator_filter_calls = 0;
		$separator_filter       = static function ( $separator ) use ( &$separator_filter_calls ) {
			++$separator_filter_calls;

			return $separator;
		};
		add_filter( 'woocommerce_variation_option_name', $option_filter );
		add_filter( 'woocommerce_product_variation_title_attributes_separator', $separator_filter );

		$name = $this->sut->get_product_name( $variation, array( 'attribute_finish' => 'gloss' ), true );

		$this->assertSame( 'Filtered Any Product', $name );
		$this->assertSame( 1, $separator_filter_calls, 'The title separator filter must retain its existing timing.' );
	}

	/**
	 * Provides filtered Any variation labels that cannot be displayed.
	 *
	 * @return array<string, array{mixed}>
	 */
	public static function filtered_any_variation_label_provider(): array {
		return array(
			'false'            => array( false ),
			'empty string'     => array( '' ),
			'non-scalar array' => array( array( 'unexpected' ) ),
		);
	}

	/**
	 * @testdox Non-string separator filter values fall back to the default separator.
	 */
	public function test_get_product_name_tolerates_non_string_separator_filter_values(): void {
		$variation = new WC_Product_Variation();
		$variation->set_name( 'Guarded Separator Product' );
		$variation->set_attributes( array( 'finish' => '' ) );

		add_filter(
			'woocommerce_product_variation_title_attributes_separator',
			static function () {
				return array( 'not', 'a', 'string' );
			}
		);

		$this->assertSame( 'Guarded Separator Product - gloss', $this->sut->get_product_name( $variation, array( 'attribute_finish' => 'gloss' ) ) );
	}
}
