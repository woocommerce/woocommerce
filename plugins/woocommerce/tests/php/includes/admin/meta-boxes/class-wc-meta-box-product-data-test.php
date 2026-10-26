<?php
/**
 * Tests for the classic product data meta box.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;
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
	 * Set up an isolated classic admin request.
	 *
	 * The base class restores request, database and user state. Capture the
	 * additional globals and static errors used by the classic meta box.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_meta_box_errors = WC_Admin_Meta_Boxes::$meta_box_errors;

		foreach ( array( 'product', 'product_object', 'thepostid' ) as $global_name ) {
			$this->original_globals[ $global_name ] = array(
				'present' => array_key_exists( $global_name, $GLOBALS ),
				'value'   => $GLOBALS[ $global_name ] ?? null,
			);
		}

		WC_Admin_Meta_Boxes::$meta_box_errors = array();
		wc_get_container()->get( Download_Directories::class )->set_mode( Download_Directories::MODE_DISABLED );
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
	 * @testdox Each download row submits the same URL in its visible field and hidden baseline.
	 * @testWith ["html-product-download.php", 0, "_wc_file_urls[]", "_wc_file_rendered_urls[]"]
	 *           ["html-product-variation-download.php", 123, "_wc_variation_file_urls[123][]", "_wc_variation_file_rendered_urls[123][]"]
	 *
	 * @param string $template Download row template.
	 * @param int    $variation_id Variation ID used by the variation template.
	 * @param string $visible_field Visible URL field name.
	 * @param string $hidden_field Hidden baseline field name.
	 */
	public function test_download_rows_track_the_rendered_url( string $template, int $variation_id, string $visible_field, string $hidden_field ): void {
		$file              = array(
			'name' => 'Filtered download',
			'file' => 'https://temporary.example.com/file.zip?signature=abc&expires=123',
		);
		$key               = 'download-id';
		$disabled_download = false;

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/' . $template;
		$processor = new WP_HTML_Tag_Processor( (string) ob_get_clean() );
		$fields    = array();
		while ( $processor->next_tag( 'input' ) ) {
			$fields[ $processor->get_attribute( 'name' ) ] = array(
				'type'  => $processor->get_attribute( 'type' ),
				'value' => $processor->get_attribute( 'value' ),
			);
		}

		$this->assertSame(
			array(
				'type'  => 'text',
				'value' => $file['file'],
			),
			$fields[ $visible_field ] ?? null,
			'The visible field should contain the filtered URL.'
		);
		$this->assertSame(
			array(
				'type'  => 'hidden',
				'value' => $file['file'],
			),
			$fields[ $hidden_field ] ?? null,
			'The hidden field should retain the same URL, including query parameters.'
		);
	}

	/**
	 * @testdox Saves retain untouched URLs while allowing URL and name edits in the same form.
	 * @testWith [false, null, "https://license.example.com/first.zip"]
	 *           [true, null, "https://license.example.com/first.zip"]
	 *           [false, "https://downloads.example.com/replacement.zip", "https://downloads.example.com/replacement.zip"]
	 *           [true, "https://downloads.example.com/replacement.zip", "https://downloads.example.com/replacement.zip"]
	 *
	 * @param bool        $is_variation Whether to save a variation.
	 * @param string|null $edited_url Replacement URL, or null to leave the field untouched.
	 * @param string      $expected_url Expected stored URL for the first download.
	 */
	public function test_save_distinguishes_untouched_urls_from_edits( bool $is_variation, ?string $edited_url, string $expected_url ): void {
		list( $product, $rows ) = $this->create_download_editor( $is_variation );
		$product_class          = get_class( $product );
		$next_downloads         = ( new $product_class( $product->get_id() ) )->get_downloads( 'edit' );
		$this->assertNotSame( $rows[0]['rendered_url'], $next_downloads['first-download']->get_file(), 'The filter must change between reads so a fresh read cannot replace the rendered baseline.' );

		if ( null !== $edited_url ) {
			$rows[0]['file'] = $edited_url;
		}
		$this->save_download_rows( $product, $rows );

		$this->assertSame(
			array(
				'first-download'  => array(
					'name' => 'First renamed',
					'file' => $expected_url,
				),
				'second-download' => array(
					'name' => 'Second renamed',
					'file' => 'https://license.example.com/second.zip',
				),
			),
			$this->get_stored_downloads( $product ),
			'Only a deliberately edited URL should replace its stored path; both name edits should persist.'
		);
	}

	/**
	 * @testdox Forms without the new baseline retain the previous behavior of saving submitted URLs.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $is_variation Whether to save a variation.
	 */
	public function test_save_without_rendered_url_baseline( bool $is_variation ): void {
		list( $product, $rows ) = $this->create_download_editor( $is_variation );

		$this->save_download_rows( $product, $rows, false );

		$this->assertSame( array_column( $rows, 'file' ), array_column( $this->get_stored_downloads( $product ), 'file' ), 'A form without the baseline should keep its previous save behavior.' );
	}

	/**
	 * @testdox Adding a row with an empty ID creates a new download and preserves the existing files.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $is_variation Whether to save a variation.
	 */
	public function test_save_adds_a_download( bool $is_variation ): void {
		list( $product, $rows ) = $this->create_download_editor( $is_variation );
		array_unshift(
			$rows,
			array(
				'id'           => '',
				'name'         => 'New',
				'file'         => 'https://downloads.example.com/new.zip',
				'rendered_url' => '',
			)
		);

		$this->save_download_rows( $product, $rows );

		$stored_downloads = $this->get_stored_downloads( $product );
		$this->assertSame(
			array(
				'New'            => 'https://downloads.example.com/new.zip',
				'First renamed'  => 'https://license.example.com/first.zip',
				'Second renamed' => 'https://license.example.com/second.zip',
			),
			array_column( $stored_downloads, 'file', 'name' ),
			'Adding a file should retain all three names, paths and their submitted order.'
		);
		$download_ids = array_keys( $stored_downloads );
		$this->assertNotEmpty( $download_ids[0], 'The new file needs a generated download ID.' );
		$this->assertSame( array( 'first-download', 'second-download' ), array_slice( $download_ids, 1 ), 'Adding a row must not change existing download IDs.' );
	}

	/**
	 * @testdox Removing a row or clearing its URL removes that file and preserves the remaining file.
	 * @testWith [false, false]
	 *           [true, false]
	 *           [false, true]
	 *           [true, true]
	 *
	 * @param bool $is_variation Whether to save a variation.
	 * @param bool $clear_url Whether to clear the URL instead of removing the row.
	 */
	public function test_save_removes_a_download( bool $is_variation, bool $clear_url ): void {
		list( $product, $rows ) = $this->create_download_editor( $is_variation );
		if ( $clear_url ) {
			$rows[0]['file'] = '';
		} else {
			array_shift( $rows );
		}

		$this->save_download_rows( $product, $rows );

		$this->assertSame(
			array(
				'second-download' => array(
					'name' => 'Second renamed',
					'file' => 'https://license.example.com/second.zip',
				),
			),
			$this->get_stored_downloads( $product ),
			'Removing the first file must retain the second file under its original ID.'
		);
	}

	/**
	 * @testdox Reordering rows preserves the correct stored URL for each download ID.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $is_variation Whether to save a variation.
	 */
	public function test_save_reorders_downloads( bool $is_variation ): void {
		list( $product, $rows ) = $this->create_download_editor( $is_variation );

		$this->save_download_rows( $product, array_reverse( $rows ) );

		$this->assertSame(
			array(
				'second-download' => array(
					'name' => 'Second renamed',
					'file' => 'https://license.example.com/second.zip',
				),
				'first-download'  => array(
					'name' => 'First renamed',
					'file' => 'https://license.example.com/first.zip',
				),
			),
			$this->get_stored_downloads( $product ),
			'Reordering rows should change their order without swapping or replacing stored URLs.'
		);
	}

	/**
	 * @testdox Removing every row removes every stored download.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $is_variation Whether to save a variation.
	 */
	public function test_save_removes_all_downloads( bool $is_variation ): void {
		list( $product ) = $this->create_download_editor( $is_variation );

		$this->save_download_rows( $product, array() );

		$this->assertSame( array(), $this->get_stored_downloads( $product ), 'An empty download list must not restore old files.' );
	}

	/**
	 * @testdox A malformed download ID is skipped without losing the other download.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $is_variation Whether to save a variation.
	 */
	public function test_save_skips_an_array_download_id( bool $is_variation ): void {
		list( $product, $rows ) = $this->create_download_editor( $is_variation );
		$rows[0]['id']          = array( 'invalid' );

		$this->save_download_rows( $product, $rows );

		$this->assertSame( array( 'second-download' ), array_keys( $this->get_stored_downloads( $product ) ), 'A malformed ID must not be used as a stored-download lookup key.' );
	}

	/**
	 * Create two stored downloads and the rows shown by a changing URL filter.
	 *
	 * @param bool $is_variation Whether to create a variation.
	 * @return array{WC_Product, array}
	 */
	private function create_download_editor( bool $is_variation ): array {
		if ( $is_variation ) {
			$parent = new WC_Product_Variable();
			$parent->save();
			$product = new WC_Product_Variation();
			$product->set_parent_id( $parent->get_id() );
			$product->set_status( ProductStatus::PUBLISH );
			$product->set_regular_price( '10' );
		} else {
			$product = WC_Helper_Product::create_simple_product( false );
		}
		$product->set_downloadable( true );
		$product->set_downloads(
			array(
				array(
					'download_id' => 'first-download',
					'name'        => 'First',
					'file'        => 'https://license.example.com/first.zip',
				),
				array(
					'download_id' => 'second-download',
					'name'        => 'Second',
					'file'        => 'https://license.example.com/second.zip',
				),
			)
		);
		$product->save();

		add_filter(
			'woocommerce_file_download_path',
			static function ( $file ) {
				static $signature = 0;
				return $file . '?signature=' . ( ++$signature );
			}
		);
		$product_class  = get_class( $product );
		$editor_product = new $product_class( $product->get_id() );
		$rows           = array();
		foreach ( $editor_product->get_downloads( 'edit' ) as $download ) {
			$rows[] = array(
				'id'           => $download->get_id(),
				'name'         => $download->get_name() . ' renamed',
				'file'         => $download->get_file(),
				'rendered_url' => $download->get_file(),
			);
		}

		return array( $product, $rows );
	}

	/**
	 * Read saved names and URLs directly, without applying the download URL filter.
	 *
	 * @param WC_Product $product Product or variation to read.
	 * @return array<string, array{name: string, file: string}>
	 */
	private function get_stored_downloads( WC_Product $product ): array {
		$stored_downloads = get_post_meta( $product->get_id(), '_downloadable_files', true );
		return array_map(
			static function ( $download ) {
				return array(
					'name' => $download['name'],
					'file' => $download['file'],
				);
			},
			is_array( $stored_downloads ) ? $stored_downloads : array()
		);
	}

	/**
	 * Save rows through the public product or variation meta-box method.
	 *
	 * @param WC_Product $product Product or variation to save.
	 * @param array      $rows Submitted rows, each with its original rendered URL.
	 * @param bool       $include_baseline Whether to include the new hidden fields.
	 */
	private function save_download_rows( WC_Product $product, array $rows, bool $include_baseline = true ): void {
		$rendered_file_urls = $include_baseline ? array_column( $rows, 'rendered_url' ) : null;
		if ( $product instanceof WC_Product_Variation ) {
			$variation_id = $product->get_id();
			$_POST        = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the classic variation request.
				'variable_post_id'          => array( 0 => $variation_id ),
				'variable_enabled'          => array( 0 => '1' ),
				'variable_regular_price'    => array( 0 => '10' ),
				'variable_is_downloadable'  => array( 0 => 'yes' ),
				'_wc_variation_file_names'  => array( $variation_id => array_column( $rows, 'name' ) ),
				'_wc_variation_file_urls'   => array( $variation_id => array_column( $rows, 'file' ) ),
				'_wc_variation_file_hashes' => array( $variation_id => array_column( $rows, 'id' ) ),
			);
			if ( $include_baseline ) {
				$_POST['_wc_variation_file_rendered_urls'] = array( $variation_id => $rendered_file_urls ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test supplies the URLs rendered by the editor.
			}
			WC_Meta_Box_Product_Data::save_variations( $product->get_parent_id(), get_post( $product->get_parent_id() ) );
		} else {
			$_POST = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the classic product request.
				'product-type'    => ProductType::SIMPLE,
				'_downloadable'   => 'yes',
				'_wc_file_names'  => array_column( $rows, 'name' ),
				'_wc_file_urls'   => array_column( $rows, 'file' ),
				'_wc_file_hashes' => array_column( $rows, 'id' ),
			);
			if ( $include_baseline ) {
				$_POST['_wc_file_rendered_urls'] = $rendered_file_urls; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test supplies the URLs rendered by the editor.
			}
			WC_Meta_Box_Product_Data::save( $product->get_id(), get_post( $product->get_id() ) );
		}

		$this->assertSame( array(), WC_Admin_Meta_Boxes::$meta_box_errors, 'Saving the download rows should not add meta-box errors.' );
	}

	/**
	 * Save a product through the public classic product data meta-box seam.
	 *
	 * @param WC_Product $product Product to save.
	 * @param string     $sku Unique SKU to submit.
	 * @param bool       $virtual Whether to submit the virtual flag.
	 * @param bool       $downloadable Whether to submit the downloadable flag.
	 * @param array      $download Submitted download row.
	 */
	private function save_product_data( WC_Product $product, string $sku, bool $virtual, bool $downloadable, array $download ): void {
		$_POST = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the complete classic admin request.
			'product-type'           => ProductType::SIMPLE,
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
