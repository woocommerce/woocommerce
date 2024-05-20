<?php
/**
 * Class WC_Product_CSV_Importer unit tests.
 *
 * @package WooCommerce\Tests\Importer
 */

/**
 * Test class for WC_Product_CSV_Importer.
 */
class WC_Tests_Product_CSV_Importer extends WC_Unit_Test_Case {

	/**
	 * Test CSV file path.
	 *
	 * @var string
	 */
	protected $csv_file = '';

	/**
	 * @var WC_Product_CSV_Importer
	 */
	private $sut;

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/import/class-wc-product-csv-importer.php';
		require_once $bootstrap->plugin_dir . '/includes/admin/importers/class-wc-product-csv-importer-controller.php';

		// Callback used by WP_HTTP_TestCase to decide whether to perform HTTP requests or to provide a mocked response.
		$this->http_responder = array( $this, 'mock_http_responses' );
		$this->csv_file       = dirname( __FILE__ ) . '/sample.csv';
		$this->sut            = new WC_Product_CSV_Importer(
			$this->csv_file,
			array(
				'mapping'          => $this->get_csv_mapped_items(),
				'parse'            => true,
				'prevent_timeouts' => false,
			)
		);
	}

	/**
	 * Get CSV mapped items.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	private function get_csv_mapped_items() {
		return array(
			'Type'                    => 'type',
			'SKU'                     => 'sku',
			'Name'                    => 'name',
			'Published'               => 'published',
			'Is featured?'            => 'featured',
			'Visibility in catalog'   => 'catalog_visibility',
			'Short description'       => 'short_description',
			'Description'             => 'description',
			'Date sale price starts'  => 'date_on_sale_from',
			'Date sale price ends'    => 'date_on_sale_to',
			'Tax status'              => 'tax_status',
			'Tax class'               => 'tax_class',
			'In stock?'               => 'stock_status',
			'Stock'                   => 'stock_quantity',
			'Backorders allowed?'     => 'backorders',
			'Sold individually?'      => 'sold_individually',
			'Weight (kg)'             => 'weight',
			'Length (cm)'             => 'length',
			'Width (cm)'              => 'width',
			'Height (cm)'             => 'height',
			'Allow customer reviews?' => 'reviews_allowed',
			'Purchase note'           => 'purchase_note',
			'Sale price'              => 'sale_price',
			'Regular price'           => 'regular_price',
			'Categories'              => 'category_ids',
			'Tags'                    => 'tag_ids',
			'Shipping class'          => 'shipping_class_id',
			'Images'                  => 'images',
			'Download limit'          => 'download_limit',
			'Download expiry days'    => 'download_expiry',
			'Parent'                  => 'parent_id',
			'Upsells'                 => 'upsell_ids',
			'Cross-sells'             => 'cross_sell_ids',
			'Grouped products'        => 'grouped_products',
			'External URL'            => 'product_url',
			'BUTTON TEXT'             => 'button_text',
			'Position'                => 'menu_order',
			'Attribute 1 Name'        => 'attributes:name1',
			'Attribute 1 Value(s)'    => 'attributes:value2',
			'Attribute 2 name'        => 'attributes:name2',
			'Attribute 2 value(s)'    => 'attributes:value2',
			'Attribute 1 default'     => 'attributes:default1',
			'Attribute 2 default'     => 'attributes:default2',
			'Download 1 ID'           => 'downloads:id1',
			'Download 1 name'         => 'downloads:name1',
			'Download 1 URL'          => 'downloads:url1',
		);
	}

	/**
	 * @testdox Test import as triggered by an admin user.
	 */
	public function test_import_for_admin_users() {
		// In most cases, an admin user will run the import.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$results = $this->sut->import();

		$this->assertEquals( 0, count( $results['failed'] ) );
		$this->assertEquals( 0, count( $results['updated'] ) );
		$this->assertEquals( 0, count( $results['skipped'] ) );
		$this->assertEquals(
			7,
			count( $results['imported'] ) + count( $results['imported_variations'] ),
			'One import item references a downloadable file stored in an unapproved location: if the import is triggered by an admin user, that location will be automatically approved.'
		);
	}

	/**
	 * @testdox Test import as triggered by a shop manager (or other non-admin user).
	 */
	public function test_import_for_shop_managers() {
		// In some cases, a shop manager may run the import.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$results = $this->sut->import();

		$this->assertEquals( 0, count( $results['updated'] ) );
		$this->assertEquals( 0, count( $results['skipped'] ) );
		$this->assertEquals( 6, count( $results['imported'] ) + count( $results['imported_variations'] ) );
		$this->assertEquals(
			1,
			count( $results['failed'] ),
			'One import item references a downloadable file stored in an unapproved location: if the import is triggered by a non-admin, that item cannot be imported.'
		);
	}

	/**
	 * Test import should update product price and skip products with empty SKU
	 * (see https://github.com/woocommerce/woocommerce/issues/23257).
	 */
	public function test_import_should_update_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 15 );
		$product->set_sku( 'wp-pennant' );
		$product->save();

		$args = array(
			'mapping'         => $this->get_csv_mapped_items(),
			'parse'           => true,
			'update_existing' => true,
		);

		$csv_file = dirname( __FILE__ ) . '/sample_update_product.csv';

		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$results  = $importer->import();

		$this->assertEquals( 0, count( $results['imported'] ) );
		$this->assertEquals( 0, count( $results['failed'] ) );
		$this->assertEquals( 1, count( $results['updated'] ) );
		$this->assertEquals( 2, count( $results['skipped'] ) );

		$updated_product = wc_get_product( $product->get_id() );
		$this->assertEquals( 20, $updated_product->get_price() );
	}

	/**
	 * Test importing file located on another location on server.
	 *
	 * @return void
	 */
	public function test_server_file() {
		self::file_copy( $this->csv_file, ABSPATH . '/sample.csv' );
		$_POST['file_url'] = 'sample.csv';
		$import_controller = new WC_Product_CSV_Importer_Controller();
		$this->assertEquals( ABSPATH . 'sample.csv', $import_controller->handle_upload() );
	}

	/**
	 * Test get_raw_keys.
	 * @since 3.1.0
	 */
	public function test_get_raw_keys() {
		$importer = new WC_Product_CSV_Importer( $this->csv_file, array( 'lines' => 1 ) );
		$raw_keys = array_keys( $this->get_csv_mapped_items() );

		$this->assertEquals( $raw_keys, $importer->get_raw_keys() );
	}

	/**
	 * Test get_mapped_keys.
	 * @since 3.1.0
	 */
	public function test_get_mapped_keys() {
		$args = array(
			'mapping' => $this->get_csv_mapped_items(),
			'lines'   => 1,
		);

		$importer = new WC_Product_CSV_Importer( $this->csv_file, $args );

		$this->assertEquals( array_values( $args['mapping'] ), $importer->get_mapped_keys() );
	}

	/**
	 * Test get_raw_data.
	 * @since 3.1.0
	 */
	public function test_get_raw_data() {
		$importer = new WC_Product_CSV_Importer(
			$this->csv_file,
			array(
				'parse' => false,
				'lines' => 2,
			)
		);
		$items    = array(
			array(
				'simple',
				'WOOLOGO',
				'Woo Logo',
				'1',
				'',
				'visible',
				'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'2017-01-01',
				'2030-01-01 0:00:00',
				'taxable',
				'standard',
				'1',
				'5',
				'notify',
				'1',
				'1',
				'1',
				'20',
				'40',
				'1',
				'Lorem ipsum dolor sit amet.',
				'18',
				'20',
				'Clothing, Clothing > T-shirts',
				'',
				'',
				'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_1_front.jpg, http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_1_back.jpg',
				'',
				'',
				'',
				'WOOALBUM',
				'WOOALBUM',
				'',
				'',
				'',
				'0',
				'Color',
				'Red',
				'',
				'',
				'',
				'',
				'',
				'',
				'',
			),
			array(
				'simple, downloadable, virtual',
				'WOOALBUM',
				'Woo Album #1',
				'1',
				'1',
				'visible',
				'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'Jul 8, 2023',
				'1689239400',
				'taxable',
				'standard',
				'1',
				'',
				'',
				'',
				'',
				'',
				'',
				'',
				'1',
				'Lorem ipsum dolor sit amet.',
				'4',
				'5',
				'Music > Albums, Music',
				'Woo',
				'',
				'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_1_angle.jpg, http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_1_flat.jpg',
				'10',
				'90',
				'',
				'WOOLOGO',
				'WOOLOGO',
				'',
				'',
				'',
				'1',
				'Label',
				'WooCommerce',
				'Vinyl',
				'180-Gram',
				'',
				'',
				'4ff604c2-97bd-4869-938b-7798ba6648ab',
				'Album flac',
				'http://woo.dev/albums/album.flac',
			),
		);

		$this->assertEquals( $items, $importer->get_raw_data() );
	}

	/**
	 * Test get_parsed_data.
	 * @since 3.1.0
	 */
	public function test_get_parsed_data() {
		$args = array(
			'mapping' => $this->get_csv_mapped_items(),
			'parse'   => true,
		);

		$importer = new WC_Product_CSV_Importer( $this->csv_file, $args );
		$items    = array(
			array(
				'type'                  => 'simple',
				'sku'                   => 'WOOLOGO',
				'name'                  => 'Woo Logo',
				'featured'              => '',
				'catalog_visibility'    => 'visible',
				'short_description'     => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'description'           => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'     => '2017-01-01',
				'date_on_sale_to'       => '2030-01-01 0:00:00',
				'tax_status'            => 'taxable',
				'tax_class'             => 'standard',
				'stock_status'          => 'instock',
				'stock_quantity'        => 5,
				'backorders'            => 'notify',
				'sold_individually'     => true,
				'weight'                => 1.0,
				'length'                => 1.0,
				'width'                 => 20.0,
				'height'                => 40.0,
				'reviews_allowed'       => true,
				'purchase_note'         => 'Lorem ipsum dolor sit amet.',
				'sale_price'            => '18',
				'regular_price'         => '20',
				'shipping_class_id'     => 0,
				'download_limit'        => 0,
				'download_expiry'       => 0,
				'product_url'           => '',
				'button_text'           => '',
				'status'                => 'publish',
				'raw_image_id'          => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_1_front.jpg',
				'raw_gallery_image_ids' => array( 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_1_back.jpg' ),
				'virtual'               => '',
				'downloadable'          => '',
				'manage_stock'          => true,
				'virtual'               => false,
				'downloadable'          => false,
				'raw_attributes'        => array(
					array(
						'name' => 'Color',
					),
				),
				'menu_order'            => 0,
			),
			array(
				'type'                  => 'simple',
				'sku'                   => 'WOOALBUM',
				'name'                  => 'Woo Album #1',
				'featured'              => true,
				'catalog_visibility'    => 'visible',
				'short_description'     => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'description'           => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'     => 'Jul 8, 2023',
				'date_on_sale_to'       => '2023-07-13T09:10:00Z',
				'tax_status'            => 'taxable',
				'tax_class'             => 'standard',
				'stock_status'          => 'instock',
				'stock_quantity'        => '',
				'backorders'            => 'no',
				'sold_individually'     => '',
				'weight'                => '',
				'length'                => '',
				'width'                 => '',
				'height'                => '',
				'reviews_allowed'       => true,
				'purchase_note'         => 'Lorem ipsum dolor sit amet.',
				'sale_price'            => '4',
				'regular_price'         => '5',
				'shipping_class_id'     => 0,
				'download_limit'        => 10,
				'download_expiry'       => 90,
				'product_url'           => '',
				'button_text'           => '',
				'status'                => 'publish',
				'raw_image_id'          => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_1_angle.jpg',
				'raw_gallery_image_ids' => array( 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_1_flat.jpg' ),
				'virtual'               => true,
				'downloadable'          => true,
				'manage_stock'          => false,
				'raw_attributes'        => array(
					array(
						'name' => 'Label',
					),
					array(
						'value' => array( '180-Gram' ),
						'name'  => 'Vinyl',
					),
				),
				'downloads'             => array(
					array(
						'name'        => 'Album flac',
						'file'        => 'http://woo.dev/albums/album.flac',
						'download_id' => '4ff604c2-97bd-4869-938b-7798ba6648ab',
					),
				),
				'menu_order'            => 1,
			),
			array(
				'type'               => 'external',
				'sku'                => '',
				'name'               => 'WooCommerce Product CSV Suite',
				'featured'           => '',
				'catalog_visibility' => 'visible',
				'short_description'  => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'description'        => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'  => '2023-07-08 05:10:15',
				'date_on_sale_to'    => '2023/07/13',
				'tax_status'         => 'taxable',
				'tax_class'          => 'standard',
				'stock_status'       => 'instock',
				'stock_quantity'     => '',
				'backorders'         => 'no',
				'sold_individually'  => '',
				'weight'             => '',
				'length'             => '',
				'width'              => '',
				'height'             => '',
				'reviews_allowed'    => false,
				'purchase_note'      => 'Lorem ipsum dolor sit amet.',
				'sale_price'         => '180',
				'regular_price'      => '199',
				'shipping_class_id'  => 0,
				'download_limit'     => 0,
				'download_expiry'    => 0,
				'product_url'        => 'https://woocommerce.com/products/product-csv-import-suite/',
				'button_text'        => 'Buy on WooCommerce.com',
				'status'             => 'publish',
				'raw_image_id'       => null,
				'virtual'            => false,
				'downloadable'       => false,
				'manage_stock'       => false,
				'menu_order'         => 2,
			),
			array(
				'type'                  => 'variable',
				'sku'                   => 'WOOIDEA',
				'name'                  => 'Ship Your Idea',
				'featured'              => '',
				'catalog_visibility'    => 'visible',
				'short_description'     => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'description'           => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'     => null,
				'date_on_sale_to'       => null,
				'tax_status'            => '',
				'tax_class'             => '',
				'stock_status'          => 'outofstock',
				'stock_quantity'        => '',
				'backorders'            => 'no',
				'sold_individually'     => '',
				'weight'                => '',
				'length'                => '',
				'width'                 => '',
				'height'                => '',
				'reviews_allowed'       => true,
				'purchase_note'         => 'Lorem ipsum dolor sit amet.',
				'sale_price'            => '',
				'regular_price'         => '',
				'shipping_class_id'     => 0,
				'download_limit'        => 0,
				'download_expiry'       => 0,
				'product_url'           => '',
				'button_text'           => '',
				'status'                => 'publish',
				'raw_image_id'          => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_4_front.jpg',
				'raw_gallery_image_ids' => array(
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_4_back.jpg',
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_3_front.jpg',
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_3_back.jpg',
				),
				'virtual'               => false,
				'downloadable'          => false,
				'manage_stock'          => false,
				'raw_attributes'        => array(
					array(
						'name'    => 'Color',
						'default' => 'Green',
					),
					array(
						'value'   => array( 'M', 'L' ),
						'name'    => 'Size',
						'default' => 'L',
					),
				),
				'menu_order'            => 3,
			),
			array(
				'type'               => 'variation',
				'sku'                => '',
				'name'               => '',
				'featured'           => '',
				'catalog_visibility' => 'visible',
				'short_description'  => '',
				'description'        => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'  => null,
				'date_on_sale_to'    => null,
				'tax_status'         => 'taxable',
				'tax_class'          => 'standard',
				'stock_status'       => 'instock',
				'stock_quantity'     => 6,
				'backorders'         => 'no',
				'sold_individually'  => '',
				'weight'             => 1.0,
				'length'             => 2.0,
				'width'              => 25.0,
				'height'             => 55.0,
				'reviews_allowed'    => '',
				'purchase_note'      => '',
				'sale_price'         => '',
				'regular_price'      => '20',
				'shipping_class_id'  => 0,
				'download_limit'     => 0,
				'download_expiry'    => 0,
				'product_url'        => '',
				'button_text'        => '',
				'status'             => 'publish',
				'raw_image_id'       => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_4_front.jpg',
				'virtual'            => false,
				'downloadable'       => false,
				'manage_stock'       => true,
				'raw_attributes'     => array(
					array(
						'name' => 'Color',
					),
					array(
						'value' => array( 'M' ),
						'name'  => 'Size',
					),
				),
				'menu_order'         => 1,
			),
			array(
				'type'               => 'variation',
				'sku'                => '',
				'name'               => '',
				'featured'           => '',
				'catalog_visibility' => 'visible',
				'short_description'  => '',
				'description'        => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'  => null,
				'date_on_sale_to'    => null,
				'tax_status'         => 'taxable',
				'tax_class'          => 'standard',
				'stock_status'       => 'instock',
				'stock_quantity'     => 10,
				'backorders'         => 'yes',
				'sold_individually'  => '',
				'weight'             => 1.0,
				'length'             => 2.0,
				'width'              => 25.0,
				'height'             => 55.0,
				'reviews_allowed'    => '',
				'purchase_note'      => '',
				'sale_price'         => '17.99',
				'regular_price'      => '20',
				'shipping_class_id'  => 0,
				'download_limit'     => 0,
				'download_expiry'    => 0,
				'product_url'        => '',
				'button_text'        => '',
				'status'             => 'publish',
				'raw_image_id'       => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_3_front.jpg',
				'virtual'            => false,
				'downloadable'       => false,
				'manage_stock'       => true,
				'raw_attributes'     => array(
					array(
						'name' => 'Color',
					),
					array(
						'value' => array( 'L' ),
						'name'  => 'Size',
					),
				),
				'menu_order'         => 2,
			),
			array(
				'type'                  => 'grouped',
				'sku'                   => '',
				'name'                  => 'Best Woo Products',
				'featured'              => true,
				'catalog_visibility'    => 'visible',
				'short_description'     => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'description'           => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'     => null,
				'date_on_sale_to'       => null,
				'tax_status'            => '',
				'tax_class'             => '',
				'stock_status'          => 'instock',
				'stock_quantity'        => '',
				'backorders'            => 'no',
				'sold_individually'     => '',
				'weight'                => '',
				'length'                => '',
				'width'                 => '',
				'height'                => '',
				'reviews_allowed'       => '',
				'purchase_note'         => '',
				'sale_price'            => '',
				'regular_price'         => '',
				'shipping_class_id'     => 0,
				'download_limit'        => 0,
				'download_expiry'       => 0,
				'product_url'           => '',
				'button_text'           => '',
				'status'                => 'publish',
				'raw_image_id'          => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_1_front.jpg',
				'raw_gallery_image_ids' => array( 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_1_angle.jpg' ),
				'virtual'               => false,
				'downloadable'          => false,
				'manage_stock'          => false,
				'menu_order'            => 4,
			),
		);

		$parsed_data = $importer->get_parsed_data();

		// Remove fields that depends on product ID or term ID.
		foreach ( $parsed_data as &$data ) {
			unset( $data['parent_id'], $data['upsell_ids'], $data['cross_sell_ids'], $data['children'], $data['category_ids'], $data['tag_ids'] );
		}

		$this->assertEquals( $items, $parsed_data );
	}

	/**
	 * Provides a mocked response for all images that are imported together with the products.
	 * This way it is not necessary to perform a regular request to an external server which would
	 * significantly slow down the tests.
	 *
	 * This function is called by WP_HTTP_TestCase::http_request_listner().
	 *
	 * @param array  $request Request arguments.
	 * @param string $url URL of the request.
	 *
	 * @return array|false mocked response or false to let WP perform a regular request.
	 */
	protected function mock_http_responses( $request, $url ) {
		$mocked_response = false;

		if ( false !== strpos( $url, 'http://demo.woothemes.com' ) ) {

			if ( ! empty( $request['filename'] ) ) {
				self::file_copy( WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/Dr1Bczxq4q.png', $request['filename'] );
			}

			$mocked_response = array(
				'body'     => 'Mocked response',
				'response' => array( 'code' => 200 ),
			);
		}

		return $mocked_response;
	}

	/**
	 * Test WC_Product_CSV_Importer_Controller::is_file_valid_csv.
	 */
	public function test_is_file_valid_csv() {
		$this->assertTrue( WC_Product_CSV_Importer_Controller::is_file_valid_csv( 'C:/wamp64/www/test.local/wp-content/uploads/2018/10/products_all_gg-1.csv' ) );
		$this->assertTrue( WC_Product_CSV_Importer_Controller::is_file_valid_csv( '/srv/www/woodev/wp-content/uploads/2018/10/1098488_single.csv' ) );
		$this->assertFalse( WC_Product_CSV_Importer_Controller::is_file_valid_csv( '/srv/www/woodev/wp-content/uploads/2018/10/img.jpg' ) );
		$this->assertFalse( WC_Product_CSV_Importer_Controller::is_file_valid_csv( 'file:///srv/www/woodev/wp-content/uploads/2018/10/1098488_single.csv' ) );
	}

	/**
	 * Test that directory traversal is prevented.
	 */
	public function test_server_path_traversal() {
		if ( ! file_exists( ABSPATH . '../sample.csv' ) ) {
			self::file_copy( $this->csv_file, ABSPATH . '../sample.csv' );
		}

		$_POST['file_url'] = '../sample.csv';
		$import_controller = new WC_Product_CSV_Importer_Controller();
		$import_result     = $import_controller->handle_upload();

		$this->assertTrue( is_wp_error( $import_result ) );
		$this->assertEquals( $import_result->get_error_code(), 'woocommerce_product_csv_importer_upload_invalid_file' );
	}
}
