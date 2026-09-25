<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\MetaBoxes;

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\ProductCustoms\ClassicEditorFields;
use WC_Admin_Meta_Boxes;
use WC_Meta_Box_Product_Data;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Unit_Test_Case;

/**
 * Tests customs fields in the classic product editor.
 */
class WC_Meta_Box_Product_Data_Customs_Test extends WC_Unit_Test_Case {
	/**
	 * Original request data.
	 *
	 * @var array
	 */
	private $original_post;

	/**
	 * Original admin errors.
	 *
	 * @var array
	 */
	private $original_errors;

	/**
	 * Sets up request state.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_post                  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Saves test request state.
		$this->original_errors                = WC_Admin_Meta_Boxes::$meta_box_errors;
		WC_Admin_Meta_Boxes::$meta_box_errors = array();
	}

	/**
	 * Restores request state.
	 */
	public function tearDown(): void {
		try {
			$_POST                                = $this->original_post;
			WC_Admin_Meta_Boxes::$meta_box_errors = $this->original_errors;
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Saves normalized customs fields and clears them explicitly.
	 * @testWith [false]
	 *           [true]
	 * @param bool $variation Whether to save a variation.
	 */
	public function test_save_and_clear_customs( bool $variation ): void {
		$product = $this->create_product( $variation );
		$this->save_fields(
			$product,
			array(
				'commodity_code'    => '0012.34',
				'country_of_origin' => ' ro ',
				'description'       => '<b>Cotton shirt</b>',
			)
		);
		$saved = wc_get_product( $product->get_id() );
		$this->assertSame( '001234', $saved->get_customs_commodity_code( 'edit' ), 'Leading zeros must survive normalization.' );
		$this->assertSame( 'RO', $saved->get_customs_country_of_origin( 'edit' ) );
		$this->assertSame( 'Cotton shirt', $saved->get_customs_description( 'edit' ) );

		$this->save_fields(
			$product,
			array(
				'commodity_code'    => '',
				'country_of_origin' => '',
				'description'       => '',
			)
		);
		$saved = wc_get_product( $product->get_id() );
		$this->assertNull( $saved->get_customs_commodity_code( 'edit' ), 'Blank fields must clear the raw value.' );
		$this->assertNull( $saved->get_customs_country_of_origin( 'edit' ) );
		$this->assertNull( $saved->get_customs_description( 'edit' ) );
	}

	/**
	 * @testdox Keeps an invalid customs field unchanged and reports it, while other fields still save.
	 * @testWith [false, "commodity_code", "12AB34"]
	 *           [true, "country_of_origin", "ZZ"]
	 * @param bool   $variation Whether to save a variation.
	 * @param string $field Invalid field.
	 * @param string $value Invalid value.
	 */
	public function test_invalid_customs_field_is_reported( bool $variation, string $field, string $value ): void {
		$original = array(
			'commodity_code'    => '001234',
			'country_of_origin' => 'RO',
			'description'       => 'Original',
		);
		$product  = $this->create_product( $variation );
		foreach ( $original as $key => $original_value ) {
			$product->{"set_customs_$key"}( $original_value );
		}
		$product->save();
		$fields           = array(
			'commodity_code'    => '654321',
			'country_of_origin' => 'US',
			'description'       => 'Changed',
		);
		$fields[ $field ] = $value;
		$this->save_fields( $product, $fields );

		$saved = wc_get_product( $product->get_id() );
		foreach ( $fields as $key => $posted ) {
			$expected = $key === $field ? $original[ $key ] : $posted;
			$this->assertSame( $expected, $saved->{"get_customs_$key"}( 'edit' ), "Only the invalid $field should keep its old value." );
		}
		$this->assertSame( '29', $saved->get_regular_price( 'edit' ), 'Unrelated product fields must still save.' );
		$this->assertCount( 1, WC_Admin_Meta_Boxes::$meta_box_errors, 'The editor must report the validation failure.' );
	}

	/**
	 * @testdox Leaves customs values unchanged when their controls are not submitted.
	 * @testWith [false]
	 *           [true]
	 * @param bool $variation Whether to save a variation.
	 */
	public function test_preserves_missing_fields( bool $variation ): void {
		$product = $this->create_product( $variation );
		$product->set_customs_description( 'Original' );
		$product->save();
		$this->save_fields( $product, array() );
		$this->assertSame( 'Original', wc_get_product( $product->get_id() )->get_customs_description( 'edit' ) );
	}

	/**
	 * @testdox Renders raw variation overrides separately from inherited parent placeholders.
	 */
	public function test_variation_fields_show_inheritance(): void {
		$product = $this->create_product( true );
		$parent  = wc_get_product( $product->get_parent_id() );
		$parent->set_customs_commodity_code( '001234' );
		$parent->set_customs_country_of_origin( 'RO' );
		$parent->set_customs_description( 'Parent description' );
		$parent->save();
		$html = $this->render_customs_fields( $product, $parent );
		$this->assertStringContainsString( 'value="" placeholder="001234"', $html, 'The inherited code must not become an explicit override.' );
		$this->assertStringContainsString( 'value="" placeholder="Parent description"', $html );
		$this->assertStringContainsString( 'Same as parent</option>', $html, 'The inherited country option must reuse the variation wording.' );
		$this->assertDoesNotMatchRegularExpression( '/<select[^>]*placeholder=/', $html, 'The country select must not carry a placeholder attribute.' );
		$this->assertStringContainsString( 'Leave blank to inherit from the product: 001234.', $html, 'The help tip should name the inherited value.' );

		$product->set_customs_commodity_code( '654321' );
		$this->assertStringContainsString( 'value="654321" placeholder="001234"', $this->render_customs_fields( $product, $parent ) );
	}

	/**
	 * @testdox Limits the description input to the server's 35-character maximum.
	 */
	public function test_description_renders_maxlength(): void {
		$html = $this->render_customs_fields( $this->create_product( false ) );
		$this->assertStringContainsString( 'maxlength="35"', $html, 'The description must be limited to 35 characters.' );
	}

	/**
	 * Renders the shared customs controls.
	 *
	 * @param WC_Product      $product Product being edited.
	 * @param WC_Product|null $parent_product Parent product.
	 * @return string
	 */
	private function render_customs_fields( WC_Product $product, ?WC_Product $parent_product = null ): string {
		ob_start();
		wc_get_container()->get( ClassicEditorFields::class )->render_fields( $product, $parent_product, 0 );
		return ob_get_clean();
	}

	/**
	 * Creates a persisted product or variation.
	 *
	 * @param bool $variation Whether to create a variation.
	 * @return WC_Product
	 */
	private function create_product( bool $variation ): WC_Product {
		if ( $variation ) {
			$parent = new WC_Product_Variable();
			$parent->set_name( 'Customs product' );
			$parent->save();
			$product = new WC_Product_Variation();
			$product->set_parent_id( $parent->get_id() );
		} else {
			$product = new WC_Product_Simple();
		}
		$product->set_name( 'Customs product' );
		$product->save();
		return $product;
	}

	/**
	 * Submits customs controls through the product editor save handler.
	 *
	 * @param WC_Product $product Product being edited.
	 * @param array      $fields Submitted customs fields.
	 */
	private function save_fields( WC_Product $product, array $fields ): void {
		$_POST = array();
		if ( $product instanceof WC_Product_Variation ) {
			$_POST['variable_post_id']       = array( $product->get_id() );
			$_POST['variable_regular_price'] = array( '29' );
			$_POST['variable_enabled']       = array( 'yes' );
			foreach ( $fields as $field => $value ) {
				$_POST[ 'variable_customs_' . $field ] = array( wp_slash( $value ) );
			}
			WC_Meta_Box_Product_Data::save_variations( $product->get_parent_id(), get_post( $product->get_parent_id() ) );
		} else {
			$_POST['product-type']   = ProductType::SIMPLE;
			$_POST['_regular_price'] = '29';
			foreach ( $fields as $field => $value ) {
				$_POST[ '_customs_' . $field ] = wp_slash( $value );
			}
			WC_Meta_Box_Product_Data::save( $product->get_id(), get_post( $product->get_id() ) );
		}
	}
}
