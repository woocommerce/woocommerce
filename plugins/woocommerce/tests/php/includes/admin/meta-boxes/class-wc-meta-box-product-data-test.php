<?php
/**
 * Tests for the classic product data meta box.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductStatus;
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
	 * three product globals the classic save seam sets.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_meta_box_errors = WC_Admin_Meta_Boxes::$meta_box_errors;
		$this->download_directories     = wc_get_container()->get( Download_Directories::class );

		foreach ( array( 'product', 'product_object', 'thepostid' ) as $global_name ) {
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
	 * Restore the meta-box error list and the product globals.
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
	 * @testdox The classic editor tracks the URL rendered for product and variation downloads.
	 */
	public function test_download_rows_track_the_rendered_url(): void {
		$product_download_row   = $this->render_download_row( 'html-product-download.php' );
		$variation_download_row = $this->render_download_row( 'html-product-variation-download.php', 123 );

		$this->assertStringContainsString(
			'name="_wc_file_rendered_urls[]" value="https://temporary.example.com/file.zip"',
			$product_download_row,
			'The product download row should retain the URL shown to the user.'
		);
		$this->assertStringContainsString(
			'name="_wc_variation_file_rendered_urls[123][]" value="https://temporary.example.com/file.zip"',
			$variation_download_row,
			'The variation download row should retain the URL shown to the user.'
		);
	}

	/**
	 * @testdox The classic product save only persists a filtered download URL when it was deliberately edited.
	 * @testWith [false, "https://license.example.com/download/file.zip"]
	 *           [true, "https://downloads.example.com/replacement/file.zip"]
	 *
	 * @param bool   $edit_url Whether to edit the rendered URL before saving.
	 * @param string $expected_stored_url Expected stored URL after saving.
	 */
	public function test_save_only_persists_deliberately_edited_filtered_download_url( bool $edit_url, string $expected_stored_url ): void {
		$original_file_url = 'https://license.example.com/download/file.zip';
		$edited_file_url   = 'https://downloads.example.com/replacement/file.zip';
		$download_id       = 'download-id';
		$product           = $this->create_downloadable_product( $download_id, $original_file_url );

		$filter_calls = 0;
		$this->add_incrementing_download_path_filter( 'https://temporary.example.com/file.zip', $filter_calls );
		$editor_downloads   = ( new WC_Product_Simple( $product->get_id() ) )->get_downloads( 'edit' );
		$rendered_file_url  = $editor_downloads[ $download_id ]->get_file();
		$submitted_file_url = $edit_url ? $edited_file_url : $rendered_file_url;

		$this->save_product_data(
			$product,
			'sku-' . wp_generate_uuid4(),
			false,
			true,
			array(
				'id'   => $download_id,
				'name' => 'Updated download name',
				'file' => $submitted_file_url,
			),
			$rendered_file_url
		);

		$stored_downloads = get_post_meta( $product->get_id(), '_downloadable_files', true );
		$this->assertGreaterThanOrEqual( 2, $filter_calls, 'The filter should produce a different URL while handling the save request.' );
		$this->assertSame( $expected_stored_url, $stored_downloads[ $download_id ]['file'], 'The save should distinguish an untouched filtered URL from a deliberate edit.' );
		$this->assertSame( 'Updated download name', $stored_downloads[ $download_id ]['name'], 'A name-only edit should still be saved.' );
	}

	/**
	 * @testdox The classic variation save preserves the stored URL when the filtered URL is untouched.
	 */
	public function test_variation_save_preserves_stored_url_for_untouched_filtered_download(): void {
		$download_id                = 'variation-download-id';
		list( $parent, $variation ) = $this->create_downloadable_variation( $download_id, 'https://license.example.com/download/variation.zip' );

		$filter_calls = 0;
		$this->add_incrementing_download_path_filter( 'https://temporary.example.com/variation.zip', $filter_calls );
		$editor_downloads  = ( new WC_Product_Variation( $variation->get_id() ) )->get_downloads( 'edit' );
		$rendered_file_url = $editor_downloads[ $download_id ]->get_file();
		$this->save_variation_download( $parent, $variation, $download_id, $rendered_file_url );

		$stored_downloads = get_post_meta( $variation->get_id(), '_downloadable_files', true );
		$this->assertGreaterThanOrEqual( 2, $filter_calls, 'The filter should produce a different URL while handling the variation save request.' );
		$this->assertSame( 'https://license.example.com/download/variation.zip', $stored_downloads[ $download_id ]['file'], 'The variation should retain its stored download URL.' );
		$this->assertSame( 'Updated variation download', $stored_downloads[ $download_id ]['name'], 'The variation should still save a name-only edit.' );
	}

	/**
	 * Render a product download row.
	 *
	 * @param string $template Download row template.
	 * @param int    $variation_id Variation ID used by the variation template.
	 * @return string
	 */
	private function render_download_row( string $template, int $variation_id = 0 ): string {
		$file              = array(
			'name' => 'Filtered download',
			'file' => 'https://temporary.example.com/file.zip',
		);
		$key               = 'download-id';
		$disabled_download = false;

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/' . $template;
		return (string) ob_get_clean();
	}

	/**
	 * Create a downloadable product fixture.
	 *
	 * @param string $download_id Download ID.
	 * @param string $file_url Stored file URL.
	 * @return WC_Product_Simple
	 */
	private function create_downloadable_product( string $download_id, string $file_url ): WC_Product_Simple {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_downloadable( true );
		$product->set_downloads(
			array(
				array(
					'download_id' => $download_id,
					'name'        => 'Original download',
					'file'        => $file_url,
				),
			)
		);
		$product->save();

		return $product;
	}

	/**
	 * Create a downloadable variation fixture.
	 *
	 * @param string $download_id Download ID.
	 * @param string $file_url Stored file URL.
	 * @return array{WC_Product_Variable, WC_Product_Variation}
	 */
	private function create_downloadable_variation( string $download_id, string $file_url ): array {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Downloadable variable product' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_status( ProductStatus::PUBLISH );
		$variation->set_regular_price( '10' );
		$variation->set_downloadable( true );
		$variation->set_downloads(
			array(
				array(
					'download_id' => $download_id,
					'name'        => 'Variation download',
					'file'        => $file_url,
				),
			)
		);
		$variation->save();

		return array( $parent, $variation );
	}

	/**
	 * Add a download path filter that returns a different URL on each call.
	 *
	 * @param string $file_url Filtered file URL.
	 * @param int    $filter_calls Number of filter calls.
	 */
	private function add_incrementing_download_path_filter( string $file_url, int &$filter_calls ): void {
		add_filter(
			'woocommerce_file_download_path',
			static function () use ( $file_url, &$filter_calls ): string {
				++$filter_calls;
				return $file_url . '?signature=' . $filter_calls;
			}
		);
	}

	/**
	 * Save a single variation download through the classic meta box.
	 *
	 * @param WC_Product_Variable  $parent_product Parent product.
	 * @param WC_Product_Variation $variation Variation fixture.
	 * @param string               $download_id Download ID.
	 * @param string               $rendered_file_url URL rendered in the editor.
	 */
	private function save_variation_download( WC_Product_Variable $parent_product, WC_Product_Variation $variation, string $download_id, string $rendered_file_url ): void {
		$variation_id = $variation->get_id();
		$_POST        = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the classic variation request.
			'variable_post_id'                 => array( 0 => $variation_id ),
			'variable_enabled'                 => array( 0 => '1' ),
			'variable_regular_price'           => array( 0 => '10' ),
			'variable_is_downloadable'         => array( 0 => 'yes' ),
			'_wc_variation_file_names'         => array( $variation_id => array( 'Updated variation download' ) ),
			'_wc_variation_file_urls'          => array( $variation_id => array( $rendered_file_url ) ),
			'_wc_variation_file_hashes'        => array( $variation_id => array( $download_id ) ),
			'_wc_variation_file_rendered_urls' => array( $variation_id => array( $rendered_file_url ) ),
		);

		WC_Meta_Box_Product_Data::save_variations( $parent_product->get_id(), get_post( $parent_product->get_id() ) );
	}

	/**
	 * Save a product through the public classic product data meta-box seam.
	 *
	 * @param WC_Product                                    $product Product to save.
	 * @param string                                        $sku Unique SKU to submit.
	 * @param bool                                          $virtual Whether to submit the virtual flag.
	 * @param bool                                          $downloadable Whether to submit the downloadable flag.
	 * @param array{id: string, name: string, file: string} $download Download data to submit.
	 * @param string|null                                   $rendered_file_url File URL rendered in the editor.
	 */
	private function save_product_data( WC_Product $product, string $sku, bool $virtual, bool $downloadable, array $download, ?string $rendered_file_url = null ): void {
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

			if ( null !== $rendered_file_url ) {
				$_POST['_wc_file_rendered_urls'] = array( $rendered_file_url ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the classic admin request.
			}
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
