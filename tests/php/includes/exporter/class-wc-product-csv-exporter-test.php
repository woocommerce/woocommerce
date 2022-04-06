<?php
/**
 * Unit tests for the WC_Product_CSV_Exporter_Test class.
 *
 * @package WooCommerce\Tests\Exporter.
 */

/**
 * Class WC_Product_CSV_Exporter_Test
 */
class WC_Product_CSV_Exporter_Test extends \WC_Unit_Test_Case {

	/**
	 * Product IDs.
	 *
	 * @var array
	 */
	public $product_ids = array();

	/**
	 * Load up the exporter classes since they aren't loaded by default.
	 */
	public function setUp() {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/export/class-wc-product-csv-exporter.php';
	}

	/**
	 * Helper to set product export query args.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function set_export_product_query_args( $args ) {
		$args['include'] = $this->product_ids;
		return $args;
	}

	/**
	 * @testdox variations should use draft status from parent product
	 */
	public function test_get_column_value_published() {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_status( 'draft' );
		$product->save();

		$reflected_exporter = new ReflectionClass( WC_Product_CSV_Exporter::class );
		$get_data_to_export = $reflected_exporter->getMethod( 'get_data_to_export' );
		$get_data_to_export->setAccessible( true );

		$this->product_ids = array_merge( array( $product->get_id() ), $product->get_children( 'edit' ) );

		add_filter( 'woocommerce_product_export_product_query_args', array( $this, 'set_export_product_query_args' ) );
		$exporter = new WC_Product_CSV_Exporter();
		$exporter->prepare_data_to_export();
		$data = $get_data_to_export->invoke( $exporter );

		foreach ( $data as $row ) {
			$this->assertEquals( -1, $row['published'] );
		}
		remove_filter( 'woocommerce_product_export_product_query_args', array( $this, 'set_export_product_query_args' ) );
	}

}
