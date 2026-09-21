<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Enums\ProductStatus;

/**
 * Integration tests for manufacturer part numbers.
 */
class WC_Product_Mpn_Test extends WC_Unit_Test_Case {
	/**
	 * Load migration functions, which are normally loaded only during updates.
	 */
	public function setUp(): void {
		parent::setUp();
		require_once WC_ABSPATH . 'includes/wc-update-functions.php';
	}

	/**
	 * Reset the REST server created for endpoint tests.
	 */
	public function tearDown(): void {
		$this->clear_rest_server();
		parent::tearDown();
	}

	/**
	 * @testdox MPNs survive product and variation saves, updates, and explicit clearing.
	 * @testWith ["simple"]
	 *           ["variable"]
	 *           ["variation"]
	 *
	 * @param string $type Product type.
	 */
	public function test_mpn_persistence( string $type ): void {
		global $wpdb;

		$sut = $this->create_product( $type );
		foreach ( array( 'AB-001 / Blue', '0', '' ) as $mpn ) {
			$sut->set_mpn( $mpn );
			$sut->save();
			$sut = wc_get_product( $sut->get_id() );
			$this->assertSame( $mpn, $sut->get_mpn( 'edit' ), 'Reloading must preserve the MPN, including zero and an explicit empty value.' );
			$this->assertSame( $mpn, $wpdb->get_row( $wpdb->prepare( "SELECT mpn FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $sut->get_id() ) )->mpn, 'The lookup value must follow CRUD updates.' );
		}
	}

	/**
	 * @testdox A variation can share an MPN with another product without inheriting its parent's MPN.
	 */
	public function test_mpn_is_not_unique_or_inherited(): void {
		$sut    = $this->create_product( ProductType::VARIATION );
		$parent = wc_get_product( $sut->get_parent_id() );
		$parent->set_mpn( 'SHARED-PART' );
		$parent->save();
		$this->assertSame( '', wc_get_product( $sut->get_id() )->get_mpn(), 'An unspecified variation MPN must remain empty.' );

		$sut->set_mpn( 'SHARED-PART' );
		$sut->save();
		$this->assertSame( 'SHARED-PART', wc_get_product( $sut->get_id() )->get_mpn() );
	}

	/**
	 * @testdox Lookup regeneration and migration preserve full metadata and consistently truncate long multibyte lookup values.
	 */
	public function test_mpn_lookup_regeneration_and_migration(): void {
		global $wpdb;

		$sut = $this->create_product( ProductType::SIMPLE );
		$mpn = str_repeat( '部', 101 );
		$sut->set_mpn( $mpn );
		$sut->save();
		$empty = $this->create_product( ProductType::SIMPLE );

		foreach ( array( null, 'wc_update_product_lookup_tables_column', 'wc_update_11301_add_mpn_to_product_lookup_table', 'wc_update_11301_add_mpn_to_product_lookup_table' ) as $callback ) {
			if ( $callback ) {
				call_user_func( $callback, 'mpn' );
			}
			$this->assertSame( $mpn, wc_get_product( $sut->get_id() )->get_mpn(), 'The lookup limit must not truncate the source value.' );
			$this->assertSame( str_repeat( '部', 100 ), $wpdb->get_row( $wpdb->prepare( "SELECT mpn FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $sut->get_id() ) )->mpn );
			$this->assertSame( '', $wpdb->get_row( $wpdb->prepare( "SELECT mpn FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $empty->get_id() ) )->mpn, 'Absent metadata must produce an empty lookup value, never NULL.' );
		}
	}

	/**
	 * @testdox An older schema can still save products before the MPN lookup column is available.
	 */
	public function test_mpn_save_before_schema_upgrade(): void {
		global $wpdb;

		$sut = $this->create_product( ProductType::SIMPLE );
		update_option( 'woocommerce_schema_version', '920' );
		$sut->set_mpn( 'PRE-UPGRADE' );
		$sut->save();
		$this->assertSame( 'PRE-UPGRADE', wc_get_product( $sut->get_id() )->get_mpn() );
		$this->assertSame( '', $wpdb->get_row( $wpdb->prepare( "SELECT mpn FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $sut->get_id() ) )->mpn );

		update_option( 'woocommerce_schema_version', '1130' );
		wc_update_11301_add_mpn_to_product_lookup_table();
		$this->assertSame( 'PRE-UPGRADE', $wpdb->get_row( $wpdb->prepare( "SELECT mpn FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $sut->get_id() ) )->mpn );
	}

	/**
	 * @testdox REST product and variation endpoints persist MPNs and distinguish omission from explicit clearing.
	 * @testWith ["v2", "simple"]
	 *           ["v3", "simple"]
	 *           ["v3", "variation"]
	 *
	 * @param string $version REST API version.
	 * @param string $type Product type.
	 */
	public function test_mpn_rest_round_trip( string $version, string $type ): void {
		$sut    = $this->create_product( $type );
		$route  = ProductType::VARIATION === $type ? "/wc/$version/products/{$sut->get_parent_id()}/variations/{$sut->get_id()}" : "/wc/$version/products/{$sut->get_id()}";
		$server = $this->create_product_rest_server();
		foreach ( array( 'PART-123', '0', null, '' ) as $mpn ) {
			$request = new WP_REST_Request( 'PUT', $route );
			if ( null !== $mpn ) {
				$request->set_param( 'mpn', $mpn );
			}
			$this->assertSame( 200, $server->dispatch( $request )->get_status() );
			$response = $server->dispatch( new WP_REST_Request( 'GET', $route ) );
			$this->assertSame( $mpn ?? '0', $response->get_data()['mpn'], 'A subsequent REST read must return the persisted MPN.' );
			$this->assertSame( $mpn ?? '0', wc_get_product( $sut->get_id() )->get_mpn( 'edit' ) );
		}
	}

	/**
	 * @testdox MPN filters handle zero, escaped wildcards, combined searches, variations, and repeated requests.
	 */
	public function test_mpn_rest_search(): void {
		$server = $this->create_product_rest_server();
		$sut    = $this->create_product( ProductType::SIMPLE );
		$sut->set_name( 'MPN target' );
		$sut->set_sku( 'mpn-target-sku' );
		$sut->set_mpn( 'PART_%_42' );
		$sut->save();
		$other = $this->create_product( ProductType::SIMPLE );
		$other->set_name( 'MPN target other' );
		$other->set_mpn( 'PART-XX42' );
		$other->save();
		$variation = $this->create_product( ProductType::VARIATION );
		$variation->set_mpn( '0' );
		$variation->save();

		$cases = array(
			array( array( 'mpn' => 'PART_%_42' ), array( $sut->get_id() ) ),
			array( array( 'search_mpn' => '_%' ), array( $sut->get_id() ) ),
			array(
				array(
					'search_mpn' => '_%',
					'search'     => 'target',
				),
				array( $sut->get_id() ),
			),
			array(
				array(
					'search_mpn' => '_%',
					'search_sku' => 'mpn-target',
				),
				array( $sut->get_id() ),
			),
			array(
				array(
					'search_mpn' => 'missing',
					'search_sku' => 'mpn-target',
				),
				array(),
			),
			array(
				array(
					'search_mpn'         => 'missing',
					'search_name_or_sku' => 'target',
				),
				array(),
			),
			array(
				array(
					'search_mpn' => '_%',
					'mpn'        => 'missing',
				),
				array( $sut->get_id() ),
			),
			array( array( 'search_mpn' => '0' ), array( $variation->get_id() ) ),
			array( array( 'mpn' => '0' ), array( $variation->get_id() ) ),
			array(
				array(
					'search'        => '_%',
					'search_fields' => array( 'mpn' ),
					'search_mpn'    => 'missing',
				),
				array( $sut->get_id() ),
			),
			array( array( 'include' => array( $other->get_id() ) ), array( $other->get_id() ) ),
		);

		foreach ( $cases as list( $params, $expected ) ) {
			$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
			$request->set_query_params( $params );
			$response = $server->dispatch( $request );
			$this->assertSame( 200, $response->get_status(), wp_json_encode( $params ) );
			$this->assertSame( $expected, wp_list_pluck( $response->get_data(), 'id' ), wp_json_encode( $params ) );
		}

		$request = new WP_REST_Request( 'GET', "/wc/v3/products/{$variation->get_parent_id()}/variations" );
		$request->set_param( 'mpn', '0' );
		$this->assertSame( array( $variation->get_id() ), wp_list_pluck( $server->dispatch( $request )->get_data(), 'id' ) );
	}

	/**
	 * @testdox Product schema includes zero MPNs, omits empty values, and preserves extension filters.
	 * @testWith ["0"]
	 *           [""]
	 *
	 * @param string $mpn Manufacturer part number.
	 */
	public function test_mpn_structured_data( string $mpn ): void {
		$product = $this->create_product( ProductType::SIMPLE );
		$product->set_mpn( $mpn );
		$sut = new WC_Structured_Data();
		$sut->generate_product_data( $product );
		$this->assertSame( '' === $mpn ? null : $mpn, $sut->get_data()[0]['mpn'] ?? null );

		add_filter(
			'woocommerce_structured_data_product',
			static function ( $markup ) {
				$markup['mpn'] = 'EXTENSION-MPN';
				return $markup;
			}
		);
		$sut = new WC_Structured_Data();
		$sut->generate_product_data( $product );
		$this->assertSame( 'EXTENSION-MPN', $sut->get_data()[0]['mpn'] );
	}

	/**
	 * @testdox CSV exports and imports round-trip MPNs, including variations and explicit clearing.
	 * @testWith ["simple"]
	 *           ["variation"]
	 *
	 * @param string $type Product type.
	 */
	public function test_mpn_csv_round_trip( string $type ): void {
		require_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php';
		require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
		require_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';

		$product = $this->create_product( $type );
		$product->set_mpn( 'PART-CSV' );
		$product->save();
		$sut = new WC_Product_CSV_Exporter();
		$sut->set_product_ids_to_export( array( $product->get_id() ) );
		$sut->set_column_names(
			array(
				'id'  => 'ID',
				'mpn' => 'MPN',
			)
		);
		$sut->set_columns_to_export( array( 'id', 'mpn' ) );
		add_filter(
			'woocommerce_product_export_product_query_args',
			static function ( $args ) use ( $type ) {
				$args['type'] = $type;
				return $args;
			}
		);
		$sut->prepare_data_to_export();
		$method = new ReflectionMethod( $sut, 'get_csv_data' );
		$method->setAccessible( true );
		$csv    = $method->invoke( $sut );
		$method = new ReflectionMethod( $sut, 'export_column_headers' );
		$method->setAccessible( true );
		$csv = $method->invoke( $sut ) . $csv;
		$this->assertStringContainsString( 'MPN', $csv );
		$this->assertStringContainsString( 'PART-CSV', $csv );

		$temporary_file = wp_tempnam( 'mpn' );
		$file           = $temporary_file . '.csv';
		rename( $temporary_file, $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- The importer requires a CSV extension on the temporary fixture.
		try {
			file_put_contents( $file, str_replace( 'PART-CSV', 'PART-IMPORTED', $csv ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write an isolated temporary CSV fixture.
			$controller = new WC_Product_CSV_Importer_Controller();
			$method     = new ReflectionMethod( $controller, 'auto_map_columns' );
			$method->setAccessible( true );
			$mapping = $method->invoke( $controller, array( 'ID', 'MPN' ), false );
			$this->assertSame( 'mpn', $mapping['MPN'] );
			$args   = array(
				'mapping'         => $mapping,
				'update_existing' => true,
				'parse'           => true,
			);
			$sut    = new WC_Product_CSV_Importer( $file, $args );
			$result = $sut->import();
			$this->assertSame( array( $product->get_id() ), $result['updated'], wp_json_encode( $result ) );
			$this->assertSame( 'PART-IMPORTED', wc_get_product( $product->get_id() )->get_mpn() );
			file_put_contents( $file, str_replace( 'PART-CSV', '', $csv ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Exercise an explicit empty CSV cell.
			$sut    = new WC_Product_CSV_Importer( $file, $args );
			$result = $sut->import();
			$this->assertSame( array( $product->get_id() ), $result['updated'], wp_json_encode( $result ) );
			$this->assertSame( '', wc_get_product( $product->get_id() )->get_mpn() );
		} finally {
			wp_delete_file( $file );
		}
	}

	/**
	 * Create a product with only the fixtures needed by MPN tests.
	 *
	 * @param string $type Product type.
	 * @return WC_Product
	 */
	private function create_product( string $type ): WC_Product {
		$class   = WC_Product_Factory::get_product_classname( 0, $type );
		$product = new $class();
		$product->set_name( 'MPN test product' );
		$product->set_status( ProductStatus::PUBLISH );
		$product->set_regular_price( '10' );
		if ( ProductType::VARIATION === $type ) {
			$product->set_parent_id( $this->create_product( ProductType::VARIABLE )->get_id() );
		}
		$product->save();
		return $product;
	}

	/**
	 * Register product routes and authenticate the test administrator.
	 *
	 * @return WP_REST_Server
	 */
	private function create_product_rest_server(): WP_REST_Server {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		return $this->create_rest_server_with_routes(
			array(
				array( new WC_REST_Products_V2_Controller(), 'register_routes' ),
				array( new WC_REST_Products_Controller(), 'register_routes' ),
				array( new WC_REST_Product_Variations_Controller(), 'register_routes' ),
			),
			true
		);
	}
}
