<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporter;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsImporterRestController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\ImportSession;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WP_REST_Request;

/**
 * Integration tests for the importer REST routes.
 */
class FulfillmentsImporterRestControllerTest extends \WC_Unit_Test_Case {

	/**
	 * Original fulfillments feature flag value.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Admin user ID for permission checks.
	 *
	 * @var int
	 */
	private static int $admin_id;

	/**
	 * Temporary CSV files created by tests; removed in tearDown.
	 *
	 * @var array<int, string>
	 */
	private array $temp_files = array();

	/**
	 * Tokens created via ImportSession that need explicit cleanup.
	 *
	 * @var array<int, ImportSession>
	 */
	private array $sessions = array();

	/**
	 * Bootstrap the fulfillments feature and create an admin user.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$sut = wc_get_container()->get( FulfillmentsController::class );
		$sut->register();
		$sut->initialize_fulfillments();

		$result = wp_insert_user(
			array(
				'user_login' => 'fulfill_admin_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password( 12, false ),
				'role'       => 'administrator',
			)
		);
		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( 'Failed to create admin user: ' . esc_html( $result->get_error_message() ) );
		}
		self::$admin_id = (int) $result;
	}

	/**
	 * Tear down the feature flag and the admin user.
	 */
	public static function tearDownAfterClass(): void {
		if ( self::$admin_id > 0 ) {
			wp_delete_user( self::$admin_id );
		}
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Sign in as admin and reset request globals.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( (int) self::$admin_id );
	}

	/**
	 * Clean up temp files and any sessions created during the test.
	 */
	public function tearDown(): void {
		foreach ( $this->sessions as $session ) {
			$session->delete();
		}
		$this->sessions = array();
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->temp_files = array();
		parent::tearDown();
	}

	/**
	 * Write a CSV to a temp file and track it for cleanup.
	 *
	 * @param string $content CSV content.
	 * @return string
	 */
	private function make_csv( string $content ): string {
		// Stage inside the uploads directory, like CSVUploadHelper does in production,
		// so the controller's staged-path containment checks hold in tests.
		$upload_dir = wp_upload_dir();
		$path       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-rest-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * Invoke a protected handler on the controller.
	 *
	 * @param string          $method  Handler method name.
	 * @param WP_REST_Request $request Built request.
	 * @return mixed
	 */
	private function invoke( string $method, WP_REST_Request $request ) {
		$sut        = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$reflection = new \ReflectionClass( $sut );
		$handler    = $reflection->getMethod( $method );
		$handler->setAccessible( true );
		return $handler->invoke( $sut, $request );
	}

	/**
	 * @testdox handle_prepare parses the staged CSV and opens a session bound to the current user.
	 */
	public function test_prepare_stages_csv_and_opens_session(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRK-1,ups\n";
		$file  = $this->make_csv( $csv );

		// is_uploaded_file() can never pass for files created inside a test process, so
		// stub the staging seam and exercise everything handle_prepare does after it.
		$sut         = new class() extends FulfillmentsImporterRestController {
			/**
			 * Path returned instead of staging a real upload.
			 *
			 * @var string
			 */
			public string $staged = '';

			/**
			 * Return the canned staged path.
			 *
			 * @param WP_REST_Request $request Unused.
			 * @return array{file:string, id:int}
			 */
			protected function stage_uploaded_csv( WP_REST_Request $request ) {
				unset( $request );
				return array(
					'file' => $this->staged,
					'id'   => 0,
				);
			}
		};
		$sut->staged = $file;

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_param( 'delimiter', ',' );
		$request->set_param( 'notify_customer', false );
		$request->set_param( 'update_existing', true );

		$reflection = new \ReflectionClass( $sut );
		$handler    = $reflection->getMethod( 'handle_prepare' );
		$handler->setAccessible( true );
		$response = $handler->invoke( $sut, $request );

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'token', $response );
		$this->assertSame( 1, $response['total'] );
		$this->assertSame( ',', $response['delimiter'] );

		$session = ImportSession::load( get_current_user_id(), (string) $response['token'] );
		$this->assertNotNull( $session );
		$this->sessions[] = $session;

		// Contiguous 0-based column indexes would encode as a JSON array without the cast.
		$this->assertJsonStringEqualsJsonString(
			'{"0":"order_number","1":"tracking_number","2":"shipment_provider"}',
			(string) wp_json_encode( $response['detected_mapping'] )
		);
	}

	/**
	 * @testdox handle_prepare rejects a CSV with more rows than the importer supports.
	 */
	public function test_prepare_rejects_csv_over_row_cap(): void {
		$csv = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 0; $i <= FulfillmentsCsvImporter::MAX_IMPORT_ROWS; $i++ ) {
			$csv .= "1,CAP-{$i},ups\n";
		}
		$file = $this->make_csv( $csv );

		$sut         = new class() extends FulfillmentsImporterRestController {
			/**
			 * Path returned instead of staging a real upload.
			 *
			 * @var string
			 */
			public string $staged = '';

			/**
			 * Return the canned staged path.
			 *
			 * @param WP_REST_Request $request Unused.
			 * @return array{file:string, id:int}
			 */
			protected function stage_uploaded_csv( WP_REST_Request $request ) {
				unset( $request );
				return array(
					'file' => $this->staged,
					'id'   => 0,
				);
			}
		};
		$sut->staged = $file;

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_param( 'delimiter', ',' );

		$reflection = new \ReflectionClass( $sut );
		$handler    = $reflection->getMethod( 'handle_prepare' );
		$handler->setAccessible( true );
		$response = $handler->invoke( $sut, $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_too_many_rows', $response->get_error_code() );
		$this->assertFileDoesNotExist( $file, 'The staged file must be cleaned up when the row cap rejects it' );
	}

	/**
	 * @testdox handle_prepare rejects an empty multipart request with a 400.
	 */
	public function test_prepare_rejects_missing_file(): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_param( 'delimiter', ',' );

		$response = $this->invoke( 'handle_prepare', $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_no_file', $response->get_error_code() );
	}

	/**
	 * @testdox The import route refuses callers that lack manage_woocommerce.
	 */
	public function test_permission_check_requires_manage_woocommerce(): void {
		$subscriber = wp_insert_user(
			array(
				'user_login' => 'fulfill_subscriber_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password( 12, false ),
				'role'       => 'subscriber',
			)
		);
		$this->assertIsInt( $subscriber );
		wp_set_current_user( (int) $subscriber );

		$sut        = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$reflection = new \ReflectionClass( $sut );
		$method     = $reflection->getMethod( 'check_permission_for_fulfillments_import' );
		$method->setAccessible( true );

		$result = $method->invoke( $sut, new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' ) );

		$this->assertInstanceOf( \WP_Error::class, $result );

		wp_delete_user( (int) $subscriber );
	}

	/**
	 * @testdox The prepare route rejects a request without a file when dispatched through the REST server.
	 */
	public function test_rest_prepare_route_rejects_missing_file(): void {
		// setUpBeforeClass() registers the controller through FulfillmentsController, but when
		// the whole Fulfillments suite runs the route is missing from the REST server built
		// here. Register the controller directly and start from a fresh server so this test
		// does not depend on test order.
		wc_get_container()->get( FulfillmentsImporterRestController::class )->register();
		$GLOBALS['wp_rest_server'] = null;

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_body_params( array( 'delimiter' => ',' ) );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_fulfillments_import_no_file', $response->get_data()['code'] );
	}

	/**
	 * @testdox handle_prepare rejects uploads above the filtered size limit.
	 */
	public function test_prepare_rejects_file_above_size_limit(): void {
		$limit_filter = static function () {
			return 10;
		};
		add_filter( 'import_upload_size_limit', $limit_filter );

		try {
			$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
			$request->set_param( 'delimiter', ',' );
			$request->set_file_params(
				array(
					'file' => array(
						'name'     => 'big.csv',
						'type'     => 'text/csv',
						'tmp_name' => '/tmp/does-not-matter.csv',
						'error'    => 0,
						'size'     => 1000,
					),
				)
			);

			$response = $this->invoke( 'handle_prepare', $request );
		} finally {
			remove_filter( 'import_upload_size_limit', $limit_filter );
		}

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_file_too_large', $response->get_error_code() );
	}
}
