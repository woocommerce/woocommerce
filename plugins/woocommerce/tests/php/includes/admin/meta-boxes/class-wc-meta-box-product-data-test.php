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
	 * Product IDs created by the current test.
	 *
	 * @var int[]
	 */
	private $product_ids = array();

	/**
	 * Original POST data.
	 *
	 * @var array<string, mixed>
	 */
	private $original_post_data;

	/**
	 * Original current user ID.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Administrator user created for the current test.
	 *
	 * @var int
	 */
	private $test_user_id;

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
	 * Original approved download directory mode.
	 *
	 * @var string
	 */
	private $original_download_directory_mode;

	/**
	 * Set up an isolated classic admin request.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_post_data               = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test snapshots request state before exercising the real admin save seam.
		$this->original_user_id                 = get_current_user_id();
		$this->original_meta_box_errors         = WC_Admin_Meta_Boxes::$meta_box_errors;
		$this->download_directories             = wc_get_container()->get( Download_Directories::class );
		$this->original_download_directory_mode = $this->download_directories->get_mode();

		foreach ( array( 'post', 'product', 'product_object', 'thepostid' ) as $global_name ) {
			$this->original_globals[ $global_name ] = array(
				'present' => array_key_exists( $global_name, $GLOBALS ),
				'value'   => $GLOBALS[ $global_name ] ?? null,
			);
		}

		$_POST                                = array();
		WC_Admin_Meta_Boxes::$meta_box_errors = array();
		$this->download_directories->set_mode( Download_Directories::MODE_DISABLED );
		$this->test_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->test_user_id );
	}

	/**
	 * Restore request, user, meta-box, download-directory, and global state.
	 */
	public function tearDown(): void {
		try {
			foreach ( array_reverse( $this->product_ids ) as $product_id ) {
				WC_Helper_Product::delete_product( $product_id );
			}
		} finally {
			$_POST                                = $this->original_post_data;
			WC_Admin_Meta_Boxes::$meta_box_errors = $this->original_meta_box_errors;
			$this->download_directories->set_mode( $this->original_download_directory_mode );
			wp_set_current_user( $this->original_user_id );
			wp_delete_user( $this->test_user_id );

			foreach ( $this->original_globals as $global_name => $global ) {
				if ( $global['present'] ) {
					$GLOBALS[ $global_name ] = $global['value']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test product global.
				} else {
					unset( $GLOBALS[ $global_name ] );
				}
			}

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
		$product             = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Slice 078 Simple Product',
				'status' => 'publish',
			)
		);
		$this->product_ids[] = $product->get_id();

		$sku         = 'slice078-' . wp_generate_uuid4();
		$download_id = 'slice078-' . wp_generate_uuid4();
		$download    = array(
			'id'   => $download_id,
			'name' => 'Slice 078 download',
			'file' => 'https://downloads.example.com/slice-078/product.pdf',
		);

		$this->save_product_data( $product, $sku, $virtual, $downloadable, $download );

		$fresh_product = wc_get_product( $product->get_id() );
		$this->assertInstanceOf( WC_Product::class, $fresh_product, 'The saved product should reload from the data store.' );
		$this->assertSame( '100.05', $fresh_product->get_regular_price( 'edit' ), 'The regular price should retain the submitted value.' );
		$this->assertSame( $sku, $fresh_product->get_sku( 'edit' ), 'The unique SKU should retain the submitted value.' );
		$this->assertSame( 'Slice 078 purchase note', $fresh_product->get_purchase_note( 'edit' ), 'The purchase note should retain the submitted value.' );
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
			'_purchase_note'         => 'Slice 078 purchase note',
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
			'attribute_names'        => array( 'Slice 078 attribute' ),
			'attribute_values'       => array( 'Slice 078 value' ),
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
		$this->assertSame( 'Slice 078 attribute', $attribute->get_name(), 'The custom attribute name should retain the submitted value.' );
		$this->assertSame( array( 'Slice 078 value' ), $attribute->get_options(), 'The custom attribute value should retain the submitted value.' );
	}
}
