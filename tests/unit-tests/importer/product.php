<?php

/**
 * Meta
 * @package WooCommerce\Tests\Importer
 */
class WC_Tests_Product_CSV_Importer extends WC_Unit_Test_Case {

	/**
	 * Test CSV file path.
	 *
	 * @var string
	 */
	protected $csv_file = string;

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp() {
		$this->csv_file = dirname( __FILE__ ) . '/sample.csv';

		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/import/class-wc-product-csv-importer.php';
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
			'Button text'             => 'button_text',
			'Attribute 1 name'        => 'attributes:name1',
			'Attribute 1 value(s)'    => 'attributes:value2',
			'Attribute 2 name'        => 'attributes:name2',
			'Attribute 2 value(s)'    => 'attributes:value2',
			'Attribute 1 default'     => 'attributes:default1',
			'Attribute 2 default'     => 'attributes:default2',
			'Download 1 name'         => 'downloads:name1',
			'Download 1 URL'          => 'downloads:url1',
		);
	}

	/**
	 * Test import.
	 * @since 3.1.0
	 */
	public function test_import() {
		$args = array(
			'mapping' => $this->get_csv_mapped_items(),
			'parse'   => true,
		);

		$importer = new WC_Product_CSV_Importer( $this->csv_file, $args );
		$results  = $importer->import();

		$this->assertEquals( 7, count( $results['imported'] ) );
		$this->assertEquals( 0, count( $results['failed'] ) );
		$this->assertEquals( 0, count( $results['updated'] ) );
		$this->assertEquals( 0, count( $results['skipped'] ) );

		// Exclude imported products.
		foreach ( $results['imported'] as $id ) {
			wp_delete_post( $id, true );
		}
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
		$importer = new WC_Product_CSV_Importer( $this->csv_file, array( 'parse' => false, 'lines' => 2 ) );
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
				'2030-01-01',
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
				'Color',
				'Red',
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
				'',
				'',
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
				'',
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
				'Label',
				'WooCommerce',
				'Vinyl',
				'180-Gram',
				'',
				'',
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
			'parse'   => true
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
				'date_on_sale_to'       => '2030-01-01',
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
			),
			array(
				'type'                  => 'simple',
				'sku'                   => 'WOOALBUM',
				'name'                  => 'Woo Album #1',
				'featured'              => true,
				'catalog_visibility'    => 'visible',
				'short_description'     => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'description'           => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'     => null,
				'date_on_sale_to'       => null,
				'tax_status'            => 'taxable',
				'tax_class'             => 'standard',
				'stock_status'          => 'instock',
				'stock_quantity'        => 0,
				'backorders'            => '',
				'sold_individually'     => '',
				'weight'                => '',
				'length'                => '',
				'width'                 => '',
				'height'                => '',
				'reviews_allowed'       => true,
				'purchase_note'         => 'Lorem ipsum dolor sit amet.',
				'sale_price'            => '',
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
						'name' => 'Album flac',
						'file' => 'http://woo.dev/albums/album.flac',
					),
				),
			),
			array(
				'type'               => 'external',
				'sku'                => '',
				'name'               => 'WooCommerce Product CSV Suite',
				'featured'           => '',
				'catalog_visibility' => 'visible',
				'short_description'  => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'description'        => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'  => null,
				'date_on_sale_to'    => null,
				'tax_status'         => 'taxable',
				'tax_class'          => 'standard',
				'stock_status'       => 'instock',
				'stock_quantity'     => 0,
				'backorders'         => '',
				'sold_individually'  => '',
				'weight'             => '',
				'length'             => '',
				'width'              => '',
				'height'             => '',
				'reviews_allowed'    => false,
				'purchase_note'      => 'Lorem ipsum dolor sit amet.',
				'sale_price'         => '',
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
				'stock_quantity'        => 0,
				'backorders'            => '',
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
						'default' => 'L'
					),
				),
			),
			array(
				'type'                  => 'variation',
				'sku'                   => '',
				'name'                  => '',
				'featured'              => '',
				'catalog_visibility'    => 'visible',
				'short_description'     => '',
				'description'           => 'Lorem ipsum dolor sit amet, at exerci civibus appetere sit, iuvaret hendrerit mea no. Eam integre feugait liberavisse an.',
				'date_on_sale_from'     => null,
				'date_on_sale_to'       => null,
				'tax_status'            => 'taxable',
				'tax_class'             => 'standard',
				'stock_status'          => 'instock',
				'stock_quantity'        => 6,
				'backorders'            => '',
				'sold_individually'     => '',
				'weight'                => 1.0,
				'length'                => 2.0,
				'width'                 => 25.0,
				'height'                => 55.0,
				'reviews_allowed'       => '',
				'purchase_note'         => '',
				'sale_price'            => '',
				'regular_price'         => '20',
				'shipping_class_id'     => 0,
				'download_limit'        => 0,
				'download_expiry'       => 0,
				'product_url'           => '',
				'button_text'           => '',
				'status'                => 'publish',
				'raw_image_id'          => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_4_front.jpg',
				'virtual'               => false,
				'downloadable'          => false,
				'manage_stock'          => true,
				'raw_attributes'        => array(
					array(
						'name' => 'Color',
					),
					array(
						'value' => array( 'M' ),
						'name'  => 'Size',
					),
				),
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
						'name'  => 'Size'
					)
				),
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
				'stock_quantity'        => 0,
				'backorders'            => '',
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
			),
		);

		$parsed_data = $importer->get_parsed_data();

		// Remove fields that depends on product ID or term ID.
		foreach ( $parsed_data as &$data ) {
			unset( $data['parent_id'], $data['upsell_ids'], $data['cross_sell_ids'], $data['children'], $data['category_ids'], $data['tag_ids'] );
		}

		$this->assertEquals( $items, $parsed_data );

		// Remove temporary products.
		$temp_products = get_posts( array(
			'post_status' => 'importing',
			'post_type'   => 'product',
			'fields'      => 'ids',
		) );
		foreach ( $temp_products as $id ) {
			wp_delete_post( $id, true );
		}
	}
}
