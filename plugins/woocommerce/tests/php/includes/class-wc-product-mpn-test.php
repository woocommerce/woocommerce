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
		$sut = $this->create_product( $type );
		foreach ( array( 'AB-001 / Blue', '0', '' ) as $mpn ) {
			$sut->set_mpn( $mpn );
			$sut->save();
			$sut = wc_get_product( $sut->get_id() );
			$this->assertSame( $mpn, $sut->get_mpn( 'edit' ), 'Reloading must preserve the MPN, including zero and an explicit empty value.' );
			$this->assertSame( $mpn, $this->get_lookup_mpn( $sut->get_id() ), 'The lookup value must follow CRUD updates.' );
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
			$this->assertSame( str_repeat( '部', 100 ), $this->get_lookup_mpn( $sut->get_id() ) );
			$this->assertSame( '', $this->get_lookup_mpn( $empty->get_id() ), 'Absent metadata must produce an empty lookup value, never NULL.' );
		}
	}

	/**
	 * @testdox An older schema can still save products before the MPN lookup column is available.
	 */
	public function test_mpn_save_before_schema_upgrade(): void {
		$sut = $this->create_product( ProductType::SIMPLE );
		update_option( 'woocommerce_schema_version', '920' );
		$sut->set_mpn( 'PRE-UPGRADE' );
		$sut->save();
		$this->assertSame( 'PRE-UPGRADE', wc_get_product( $sut->get_id() )->get_mpn() );
		$this->assertSame( '', $this->get_lookup_mpn( $sut->get_id() ) );

		update_option( 'woocommerce_schema_version', '1130' );
		wc_update_11301_add_mpn_to_product_lookup_table();
		$this->assertSame( 'PRE-UPGRADE', $this->get_lookup_mpn( $sut->get_id() ) );
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
	 * @testdox MPN search returns the expected products and variations.
	 * @dataProvider mpn_search_provider
	 *
	 * @param array $params Search parameters.
	 * @param array $expected_keys Keys of the expected search fixtures.
	 */
	public function test_mpn_rest_search( array $params, array $expected_keys ): void {
		$server   = $this->create_product_rest_server();
		$products = $this->create_search_products();
		$expected = array_map(
			static function ( $key ) use ( $products ) {
				return $products[ $key ]->get_id();
			},
			$expected_keys
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params( $params );
		$response = $server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $expected, wp_list_pluck( $response->get_data(), 'id' ) );
	}

	/**
	 * Provide search parameters and expected fixture keys.
	 *
	 * @return array
	 */
	public static function mpn_search_provider(): array {
		return array(
			'exact MPN'                => array( array( 'mpn' => 'PART_%_42' ), array( 'target' ) ),
			'literal wildcard symbols' => array( array( 'search_mpn' => '_%' ), array( 'target' ) ),
			'partial MPN and name'     => array(
				array(
					'search_mpn' => '_%',
					'search'     => 'target',
				),
				array( 'target' ),
			),
			'partial MPN and SKU'      => array(
				array(
					'search_mpn' => '_%',
					'search_sku' => 'mpn-target',
				),
				array( 'target' ),
			),
			'MPN must also match SKU'  => array(
				array(
					'search_mpn' => 'missing',
					'search_sku' => 'mpn-target',
				),
				array(),
			),
			'MPN must match name/SKU'  => array(
				array(
					'search_mpn'         => 'missing',
					'search_name_or_sku' => 'target',
				),
				array(),
			),
			'partial supersedes exact' => array(
				array(
					'search_mpn' => '_%',
					'mpn'        => 'missing',
				),
				array( 'target' ),
			),
			'partial zero MPN'         => array( array( 'search_mpn' => '0' ), array( 'variation' ) ),
			'exact zero MPN'           => array( array( 'mpn' => '0' ), array( 'variation' ) ),
			'explicit search fields'   => array(
				array(
					'search'        => '_%',
					'search_fields' => array( 'mpn' ),
					'search_mpn'    => 'missing',
				),
				array( 'target' ),
			),
		);
	}

	/**
	 * @testdox MPN search state does not affect a later request on the same REST server.
	 * @testWith [{"search_mpn": "_%"}]
	 *           [{"search": "_%", "search_fields": ["mpn"]}]
	 *
	 * @param array $params First request's search parameters.
	 */
	public function test_mpn_search_does_not_affect_later_requests( array $params ): void {
		$server   = $this->create_product_rest_server();
		$products = $this->create_search_products();
		$request  = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params( $params );
		$response = $server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $products['target']->get_id() ), wp_list_pluck( $response->get_data(), 'id' ) );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include', array( $products['other']->get_id() ) );
		$response = $server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $products['other']->get_id() ), wp_list_pluck( $response->get_data(), 'id' ) );
	}

	/**
	 * @testdox The variation endpoint accepts zero as an exact MPN filter.
	 */
	public function test_variation_mpn_rest_search(): void {
		$server = $this->create_product_rest_server();
		$sut    = $this->create_product( ProductType::VARIATION );
		$sut->set_mpn( '0' );
		$sut->save();

		$request = new WP_REST_Request( 'GET', "/wc/v3/products/{$sut->get_parent_id()}/variations" );
		$request->set_param( 'mpn', '0' );
		$response = $server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $sut->get_id() ), wp_list_pluck( $response->get_data(), 'id' ) );
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
	 * @testdox CSV exports include the MPN header and saved product or variation value.
	 * @testWith ["simple"]
	 *           ["variation"]
	 *
	 * @param string $type Product type.
	 */
	public function test_mpn_csv_export( string $type ): void {
		$product = $this->create_product( $type );
		$product->set_mpn( 'PART-CSV' );
		$product->save();

		$csv  = $this->export_mpn_csv( $product );
		$rows = array_map( 'str_getcsv', explode( "\n", trim( $csv ) ) );

		$this->assertSame( array( 'ID', 'MPN' ), $rows[0] );
		$this->assertSame( array( (string) $product->get_id(), 'PART-CSV' ), $rows[1] );
	}

	/**
	 * @testdox CSV imports update MPNs and allow zero and explicit clearing.
	 * @testWith ["simple", "PART-IMPORTED"]
	 *           ["simple", "0"]
	 *           ["simple", ""]
	 *           ["variation", "PART-IMPORTED"]
	 *           ["variation", "0"]
	 *           ["variation", ""]
	 *
	 * @param string $type Product type.
	 * @param string $mpn MPN to import.
	 */
	public function test_mpn_csv_import( string $type, string $mpn ): void {
		require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';

		$product = $this->create_product( $type );
		$product->set_mpn( 'ORIGINAL' );
		$product->save();
		$csv = str_replace( 'ORIGINAL', $mpn, $this->export_mpn_csv( $product ) );

		$temporary_file = wp_tempnam( 'mpn' );
		$file           = $temporary_file . '.csv';
		rename( $temporary_file, $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- The importer requires a CSV extension on the temporary fixture.
		try {
			file_put_contents( $file, $csv ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write an isolated temporary CSV fixture.
			$sut    = new WC_Product_CSV_Importer(
				$file,
				array(
					'mapping'         => array(
						'ID'  => 'id',
						'MPN' => 'mpn',
					),
					'update_existing' => true,
					'parse'           => true,
				)
			);
			$result = $sut->import();

			$this->assertSame( array( $product->get_id() ), $result['updated'], wp_json_encode( $result ) );
			$this->assertSame( $mpn, wc_get_product( $product->get_id() )->get_mpn() );
		} finally {
			wp_delete_file( $file );
		}
	}

	/**
	 * @testdox The CSV importer automatically maps the MPN column.
	 */
	public function test_mpn_csv_header_mapping(): void {
		require_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';

		$sut    = new WC_Product_CSV_Importer_Controller();
		$method = new ReflectionMethod( $sut, 'auto_map_columns' );
		$method->setAccessible( true );

		$mapping = $method->invoke( $sut, array( 'ID', 'MPN' ), false );

		$this->assertSame( 'mpn', $mapping['MPN'] );
	}

	/**
	 * Read the lookup copy independently of the product getter.
	 *
	 * @param int $product_id Product ID.
	 * @return string|null
	 */
	private function get_lookup_mpn( int $product_id ): ?string {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT mpn FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d",
				$product_id
			)
		);

		return $row->mpn ?? null;
	}

	/**
	 * Create products that distinguish literal MPN matches from wildcard matches.
	 *
	 * @return array<string, WC_Product>
	 */
	private function create_search_products(): array {
		$target = $this->create_product( ProductType::SIMPLE );
		$target->set_name( 'MPN target' );
		$target->set_sku( 'mpn-target-sku' );
		$target->set_mpn( 'PART_%_42' );
		$target->save();
		$other = $this->create_product( ProductType::SIMPLE );
		$other->set_name( 'MPN target other' );
		$other->set_mpn( 'PART-XX42' );
		$other->save();
		$variation = $this->create_product( ProductType::VARIATION );
		$variation->set_mpn( '0' );
		$variation->save();

		return array(
			'target'    => $target,
			'other'     => $other,
			'variation' => $variation,
		);
	}

	/**
	 * Export the ID and MPN columns for a single product or variation.
	 *
	 * @param WC_Product $product Product to export.
	 * @return string
	 */
	private function export_mpn_csv( WC_Product $product ): string {
		require_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php';

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
			static function ( $args ) use ( $product ) {
				$args['type'] = $product->get_type();
				return $args;
			}
		);
		$sut->prepare_data_to_export();

		$headers = new ReflectionMethod( $sut, 'export_column_headers' );
		$headers->setAccessible( true );
		$data = new ReflectionMethod( $sut, 'get_csv_data' );
		$data->setAccessible( true );

		return $headers->invoke( $sut ) . $data->invoke( $sut );
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
