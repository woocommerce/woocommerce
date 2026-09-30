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

		foreach ( array( null, 'wc_update_product_lookup_tables_column', 'wc_update_1130_add_mpn_to_product_lookup_table', 'wc_update_1130_add_mpn_to_product_lookup_table' ) as $callback ) {
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
		wc_update_1130_add_mpn_to_product_lookup_table();
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
		$server = $this->create_product_rest_server();
		$cases  = array(
			'update'  => array( array( 'mpn' => 'PART-123' ), 'PART-123' ),
			'zero'    => array( array( 'mpn' => '0' ), '0' ),
			'omitted' => array( array(), 'ORIGINAL' ),
			'clear'   => array( array( 'mpn' => '' ), '' ),
		);

		foreach ( $cases as $case => list( $params, $expected_mpn ) ) {
			$sut = $this->create_product( $type );
			$sut->set_mpn( 'ORIGINAL' );
			$sut->save();

			$route   = ProductType::VARIATION === $type ? "/wc/$version/products/{$sut->get_parent_id()}/variations/{$sut->get_id()}" : "/wc/$version/products/{$sut->get_id()}";
			$request = new WP_REST_Request( 'PUT', $route );
			$request->set_body_params( $params );
			$this->assertSame( 200, $server->dispatch( $request )->get_status(), $case );
			$response = $server->dispatch( new WP_REST_Request( 'GET', $route ) );
			$this->assertSame( $expected_mpn, $response->get_data()['mpn'], $case );
			$this->assertSame( $expected_mpn, wc_get_product( $sut->get_id() )->get_mpn( 'edit' ), $case );
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
			'exact MPN'                         => array( array( 'mpn' => 'PART_%_42' ), array( 'target' ) ),
			'literal wildcard symbols'          => array( array( 'search_mpn' => '_%' ), array( 'target' ) ),
			'partial MPN and name'              => array(
				array(
					'search_mpn' => '_%',
					'search'     => 'target',
				),
				array( 'target' ),
			),
			'partial MPN and SKU'               => array(
				array(
					'search_mpn' => '_%',
					'search_sku' => 'mpn-target',
				),
				array( 'target' ),
			),
			'MPN must also match SKU'           => array(
				array(
					'search_mpn' => 'missing',
					'search_sku' => 'mpn-target',
				),
				array(),
			),
			'MPN must match name/SKU'           => array(
				array(
					'search_mpn'         => 'missing',
					'search_name_or_sku' => 'target',
				),
				array(),
			),
			'partial supersedes exact'          => array(
				array(
					'search_mpn' => '_%',
					'mpn'        => 'missing',
				),
				array( 'target' ),
			),
			'partial zero MPN'                  => array( array( 'search_mpn' => '0' ), array( 'variation' ) ),
			'exact zero MPN'                    => array( array( 'mpn' => '0' ), array( 'variation' ) ),
			'explicit search fields'            => array(
				array(
					'search'        => '_%',
					'search_fields' => array( 'mpn' ),
					'search_mpn'    => 'missing',
				),
				array( 'target' ),
			),
			'search fields supersede exact MPN' => array(
				array(
					'search'        => '_%',
					'search_fields' => array( 'mpn' ),
					'mpn'           => 'missing',
				),
				array( 'target' ),
			),
			'name search supersedes exact MPN'  => array(
				array(
					'search'        => 'other',
					'search_fields' => array( 'name' ),
					'mpn'           => 'PART_%_42',
				),
				array( 'other' ),
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
