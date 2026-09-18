<?php
/**
 * Tests for the classic product data meta box.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

/**
 * Class WC_Meta_Box_Product_Data_Test.
 */
class WC_Meta_Box_Product_Data_Test extends WC_Unit_Test_Case {

	/**
	 * Original meta-box errors.
	 *
	 * @var string[]
	 */
	private $original_meta_box_errors;

	/**
	 * Original product-related globals and their presence.
	 *
	 * @var array<string, array{present: bool, value: mixed}>
	 */
	private $original_globals = array();

	/**
	 * Approved download directory register.
	 *
	 * @var Download_Directories
	 */
	private $download_directories;

	/**
	 * Set up an isolated classic admin request.
	 *
	 * The base class already empties the request globals, rolls back every
	 * database write and resets the current user, so only the two things it
	 * does not own are captured here: the static meta-box error list and the
	 * post and product globals the classic save and render seams set.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_meta_box_errors = WC_Admin_Meta_Boxes::$meta_box_errors;
		$this->download_directories     = wc_get_container()->get( Download_Directories::class );

		foreach ( array( 'post', 'product', 'product_object', 'thepostid' ) as $global_name ) {
			$this->original_globals[ $global_name ] = array(
				'present' => array_key_exists( $global_name, $GLOBALS ),
				'value'   => $GLOBALS[ $global_name ] ?? null,
			);
		}

		WC_Admin_Meta_Boxes::$meta_box_errors = array();
		$this->download_directories->set_mode( Download_Directories::MODE_DISABLED );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Restore the meta-box error list and the post and product globals.
	 */
	public function tearDown(): void {
		try {
			WC_Admin_Meta_Boxes::$meta_box_errors = $this->original_meta_box_errors;

			foreach ( $this->original_globals as $global_name => $global ) {
				if ( $global['present'] ) {
					$GLOBALS[ $global_name ] = $global['value']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test product global.
				} else {
					unset( $GLOBALS[ $global_name ] );
				}
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox The classic product save persists the simple product field matrix.
	 * @dataProvider simple_product_type_provider
	 *
	 * @param bool $virtual Whether the submitted simple product is virtual.
	 * @param bool $downloadable Whether the submitted simple product is downloadable.
	 */
	public function test_save_persists_simple_product_field_matrix( bool $virtual, bool $downloadable ): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Field Matrix Simple Product',
				'status' => 'publish',
			)
		);

		$sku         = 'sku-' . wp_generate_uuid4();
		$download_id = 'download-' . wp_generate_uuid4();
		$download    = array(
			'id'   => $download_id,
			'name' => 'Field matrix download',
			'file' => 'https://downloads.example.com/field-matrix/product.pdf',
		);

		$this->save_product_data( $product, $sku, $virtual, $downloadable, $download );

		$fresh_product = wc_get_product( $product->get_id() );
		$this->assertInstanceOf( WC_Product::class, $fresh_product, 'The saved product should reload from the data store.' );
		$this->assertSame( '100.05', $fresh_product->get_regular_price( 'edit' ), 'The regular price should retain the submitted value.' );
		$this->assertSame( $sku, $fresh_product->get_sku( 'edit' ), 'The unique SKU should retain the submitted value.' );
		$this->assertSame( 'Field matrix purchase note', $fresh_product->get_purchase_note( 'edit' ), 'The purchase note should retain the submitted value.' );
		$this->assertSame( $virtual, $fresh_product->get_virtual( 'edit' ), 'The virtual flag should retain the submitted value.' );
		$this->assertSame( $downloadable, $fresh_product->get_downloadable( 'edit' ), 'The downloadable flag should retain the submitted value.' );
		$this->assertSame( $downloadable ? 365 : -1, $fresh_product->get_download_expiry( 'edit' ), 'The download expiry should retain the submitted value.' );
		$this->assertSame( array(), WC_Admin_Meta_Boxes::$meta_box_errors, 'The classic save should not add meta-box errors.' );

		$this->assert_custom_attribute( $fresh_product );

		if ( ! $virtual && ! $downloadable ) {
			$this->assertSame( '2', $fresh_product->get_weight( 'edit' ), 'The physical product weight should retain the submitted value.' );
			$this->assertSame( '20', $fresh_product->get_length( 'edit' ), 'The physical product length should retain the submitted value.' );
			$this->assertSame( '10', $fresh_product->get_width( 'edit' ), 'The physical product width should retain the submitted value.' );
			$this->assertSame( '30', $fresh_product->get_height( 'edit' ), 'The physical product height should retain the submitted value.' );
		}

		if ( $downloadable ) {
			$downloads = $fresh_product->get_downloads( 'edit' );
			$this->assertCount( 1, $downloads, 'The downloadable product should contain exactly one saved download.' );
			$saved_download = current( $downloads );
			$this->assertInstanceOf( WC_Product_Download::class, $saved_download, 'The saved download should be normalized to a WC_Product_Download object.' );
			$this->assertSame(
				$download,
				array(
					'id'   => $saved_download->get_id(),
					'name' => $saved_download->get_name(),
					'file' => $saved_download->get_file(),
				),
				'The saved download should retain its posted ID, name, and URL.'
			);
		} else {
			$this->assertSame( array(), $fresh_product->get_downloads( 'edit' ), 'A non-downloadable product should not retain download records.' );
		}
	}

	/**
	 * Provide named simple product type rows.
	 *
	 * @return array<string, array{bool, bool}>
	 */
	public static function simple_product_type_provider(): array {
		return array(
			'physical'     => array( false, false ),
			'virtual'      => array( true, false ),
			'downloadable' => array( false, true ),
		);
	}

	/**
	 * @testdox The product data panel renders the Virtual and Downloadable checkboxes with the stored product state.
	 * @dataProvider product_type_option_provider
	 *
	 * @param bool $virtual Whether the rendered product is virtual.
	 * @param bool $downloadable Whether the rendered product is downloadable.
	 */
	public function test_output_renders_the_virtual_and_downloadable_checkboxes( bool $virtual, bool $downloadable ): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'         => 'Product Type Option Product',
				'status'       => 'publish',
				'virtual'      => $virtual,
				'downloadable' => $downloadable,
			)
		);

		$panel = $this->render_product_data_panel( $product );

		$this->assert_product_type_checkbox( $panel, '_virtual', $virtual );
		$this->assert_product_type_checkbox( $panel, '_downloadable', $downloadable );
	}

	/**
	 * Provide named product type option rows.
	 *
	 * @return array<string, array{bool, bool}>
	 */
	public static function product_type_option_provider(): array {
		return array(
			'virtual only'      => array( true, false ),
			'downloadable only' => array( false, true ),
		);
	}

	/**
	 * Render the classic product data panel for a product.
	 *
	 * @param WC_Product $product Product to render.
	 * @return string
	 */
	private function render_product_data_panel( WC_Product $product ): string {
		$post = get_post( $product->get_id() );
		$this->assertInstanceOf( WP_Post::class, $post, 'The product fixture should have a persisted post.' );

		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The panel views read the edited post from the global; tearDown restores it.

		ob_start();
		try {
			WC_Meta_Box_Product_Data::output( $post );
		} finally {
			$panel = (string) ob_get_clean();
		}

		return $panel;
	}

	/**
	 * Assert one product type option checkbox and its checked state.
	 *
	 * @param string $panel Rendered product data panel.
	 * @param string $checkbox_id Value of the checkbox `id` and `name` attributes.
	 * @param bool   $expected_checked Whether the checkbox should carry the `checked` attribute.
	 */
	private function assert_product_type_checkbox( string $panel, string $checkbox_id, bool $expected_checked ): void {
		$tags       = new WP_HTML_Tag_Processor( $panel );
		$attributes = null;

		while ( $tags->next_tag( array( 'tag_name' => 'INPUT' ) ) ) {
			if ( $checkbox_id === $tags->get_attribute( 'id' ) ) {
				$attributes = array(
					'type'    => $tags->get_attribute( 'type' ),
					'name'    => $tags->get_attribute( 'name' ),
					'checked' => $tags->get_attribute( 'checked' ),
				);
				break;
			}
		}

		$this->assertIsArray( $attributes, "The product data panel should render the {$checkbox_id} checkbox." );
		$this->assertSame( 'checkbox', $attributes['type'], "The {$checkbox_id} field should render as a checkbox." );
		$this->assertSame( $checkbox_id, $attributes['name'], "The {$checkbox_id} checkbox should post under its own name." );

		if ( $expected_checked ) {
			$this->assertSame( 'checked', $attributes['checked'], "The {$checkbox_id} checkbox should be checked." );
		} else {
			$this->assertNull( $attributes['checked'], "The {$checkbox_id} checkbox should not be checked." );
		}
	}

	/**
	 * Save a product through the public classic product data meta-box seam.
	 *
	 * @param WC_Product                                    $product Product to save.
	 * @param string                                        $sku Unique SKU to submit.
	 * @param bool                                          $virtual Whether to submit the virtual flag.
	 * @param bool                                          $downloadable Whether to submit the downloadable flag.
	 * @param array{id: string, name: string, file: string} $download Download data to submit.
	 */
	private function save_product_data( WC_Product $product, string $sku, bool $virtual, bool $downloadable, array $download ): void {
		$_POST = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the complete classic admin request.
			'product-type'           => 'simple',
			'_sku'                   => $sku,
			'_global_unique_id'      => '',
			'_purchase_note'         => 'Field matrix purchase note',
			'_visibility'            => 'visible',
			'_tax_status'            => 'taxable',
			'_tax_class'             => '',
			'_weight'                => '2',
			'_length'                => '20',
			'_width'                 => '10',
			'_height'                => '30',
			'product_shipping_class' => '0',
			'upsell_ids'             => array(),
			'crosssell_ids'          => array(),
			'_regular_price'         => '100.05',
			'_sale_price'            => '',
			'_sale_price_dates_from' => '',
			'_sale_price_dates_to'   => '',
			'_manage_stock'          => '',
			'_stock'                 => '',
			'_low_stock_amount'      => '',
			'_backorders'            => 'no',
			'_stock_status'          => 'instock',
			'_download_limit'        => '',
			'_download_expiry'       => $downloadable ? '365' : '',
			'_wc_file_names'         => $downloadable ? array( $download['name'] ) : array(),
			'_wc_file_urls'          => $downloadable ? array( $download['file'] ) : array(),
			'_wc_file_hashes'        => $downloadable ? array( $download['id'] ) : array(),
			'_product_url'           => '',
			'_button_text'           => '',
			'comment_status'         => 'open',
			'attribute_names'        => array( 'Field matrix attribute' ),
			'attribute_values'       => array( 'Field matrix value' ),
			'attribute_position'     => array( 0 ),
			'attribute_visibility'   => array( 0 => '1' ),
			'attribute_variation'    => array(),
		);

		if ( $virtual ) {
			$_POST['_virtual'] = 'yes'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the classic admin request.
		}

		if ( $downloadable ) {
			$_POST['_downloadable'] = 'yes'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the classic admin request.
		}

		$post = get_post( $product->get_id() );
		$this->assertInstanceOf( WP_Post::class, $post, 'The product fixture should have a persisted post.' );
		WC_Meta_Box_Product_Data::save( $product->get_id(), $post );
	}

	/**
	 * Assert the one submitted custom attribute.
	 *
	 * @param WC_Product $product Freshly saved product.
	 */
	private function assert_custom_attribute( WC_Product $product ): void {
		$attributes = $product->get_attributes( 'edit' );
		$this->assertCount( 1, $attributes, 'The saved product should contain exactly one custom attribute.' );
		$attribute = current( $attributes );
		$this->assertInstanceOf( WC_Product_Attribute::class, $attribute, 'The saved custom attribute should be normalized to a WC_Product_Attribute object.' );
		$this->assertSame( 'Field matrix attribute', $attribute->get_name(), 'The custom attribute name should retain the submitted value.' );
		$this->assertSame( array( 'Field matrix value' ), $attribute->get_options(), 'The custom attribute value should retain the submitted value.' );
	}
}
