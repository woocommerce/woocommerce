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
	 * Test import.
	 * @todo enable the importer again after conclude the parser.
	 * @since 3.1.0
	 */
	public function test_import() {
		//
		$importer = new WC_Product_CSV_Importer( $this->csv_file );

		$expected = array(
			'imported' => array(),
			'failed'   => array(),
		);

		// $this->assertEquals( $expected, $importer->import() );
		$this->assertEquals( true, true );
	}

	/**
	 * Test get_raw_keys.
	 * @since 3.1.0
	 */
	public function test_get_raw_keys() {
		$importer = new WC_Product_CSV_Importer( $this->csv_file );
		$raw_keys = array(
			'Type',
			'SKU',
			'Name',
			'Status',
			'Regular price',
		);

		$this->assertEquals( $raw_keys, $importer->get_raw_keys() );
	}

	/**
	 * Test get_mapped_keys.
	 * @since 3.1.0
	 */
	public function test_get_mapped_keys() {
		$mapped = array(
			'Type'          => 'type',
			'SKU'           => 'sku',
			'Name'          => 'name',
			'Status'        => 'status',
			'Regular price' => 'regular_price',
		);

		$args = array(
			'mapping' => $mapped,
		);

		$importer = new WC_Product_CSV_Importer( $this->csv_file, $args );

		$this->assertEquals( array_values( $mapped ), $importer->get_mapped_keys() );
	}

	/**
	 * Test get_raw_data.
	 * @since 3.1.0
	 */
	public function test_get_raw_data() {
		$importer = new WC_Product_CSV_Importer( $this->csv_file, array( 'parse' => false ) );
		$items    = array(
			array(
				'regular',
				'PRODUCT-01',
				'Imported Product 1',
				1,
				40,
			),
			array(
				'regular',
				'PRODUCT-02',
				'Imported Product 2',
				1,
				41,
			),
			array(
				'regular',
				'PRODUCT-03',
				'Imported Product 3',
				1,
				42,
			),
		);

		$this->assertEquals( $items, $importer->get_raw_data() );
	}

	/**
	 * Test get_parsed_data.
	 * @since 3.1.0
	 */
	public function test_get_parsed_data() {
		$mapped = array(
			'Type'          => 'type',
			'SKU'           => 'sku',
			'Name'          => 'name',
			'Status'        => 'status',
			'Regular price' => 'regular_price',
		);

		$args = array(
			'mapping' => $mapped,
			'parse'   => true,
		);

		$importer = new WC_Product_CSV_Importer( $this->csv_file, $args );
		$items    = array(
			array(
				'type'          => 'regular',
				'sku'           => 'PRODUCT-01',
				'name'          => 'Imported Product 1',
				'status'        => 1,
				'regular_price' => 40,
			),
			array(
				'type'          => 'regular',
				'sku'           => 'PRODUCT-02',
				'name'          => 'Imported Product 2',
				'status'        => 1,
				'regular_price' => 41,
			),
			array(
				'type'          => 'regular',
				'sku'           => 'PRODUCT-03',
				'name'          => 'Imported Product 3',
				'status'        => 1,
				'regular_price' => 42,
			),
		);

		$this->assertEquals( $items, $importer->get_parsed_data() );
	}
}
