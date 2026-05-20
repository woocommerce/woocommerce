<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporter;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsImporterRestController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\ImportSession;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Order;
use WP_REST_Request;

/**
 * Integration tests for the chunked importer REST routes.
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
		$controller = wc_get_container()->get( FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();

		$result = wp_insert_user(
			array(
				'user_login' => 'fulfill_admin_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password( 12, false ),
				'role'       => 'administrator',
			)
		);
		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( 'Failed to create admin user: ' . $result->get_error_message() );
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
				unlink( $path );
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
		$path = wp_tempnam( 'wc-fulfillments-rest-' );
		file_put_contents( $path, $content );
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * Open an ImportSession for the staged CSV and remember it for cleanup.
	 *
	 * @param string $file Path to staged CSV.
	 * @return ImportSession
	 */
	private function open_session_for( string $file ): ImportSession {
		$importer = new FulfillmentsCsvImporter( $file );
		$parsed   = $importer->parse_headers();
		$this->assertArrayNotHasKey( 'error', $parsed );

		$session          = ImportSession::create(
			get_current_user_id(),
			$file,
			$parsed['delimiter'],
			$parsed['headers'],
			(int) $parsed['total'],
			false,
			true
		);
		$this->sessions[] = $session;
		return $session;
	}

	/**
	 * Invoke a protected handler on the controller.
	 *
	 * @param string          $method  Handler method name.
	 * @param WP_REST_Request $request Built request.
	 * @return mixed
	 */
	private function invoke( string $method, WP_REST_Request $request ) {
		$controller = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$reflection = new \ReflectionClass( $controller );
		$handler    = $reflection->getMethod( $method );
		$handler->setAccessible( true );
		return $handler->invoke( $controller, $request );
	}

	/**
	 * @testdox handle_run rejects an unknown token with a 400.
	 */
	public function test_run_rejects_unknown_token(): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_param( 'token', 'definitely-not-a-real-token' );
		$request->set_param( 'mapping', array() );

		$response = $this->invoke( 'handle_run', $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'fulfillments_import_token_invalid', $response->get_error_code() );
	}

	/**
	 * @testdox handle_run rejects a mapping missing required canonical columns.
	 */
	public function test_run_rejects_invalid_mapping(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},T-1,ups\n";
		$file  = $this->make_csv( $csv );

		$session = $this->open_session_for( $file );

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_param( 'token', $session->token() );
		$request->set_param( 'mapping', array( '0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER ) );

		$response = $this->invoke( 'handle_run', $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'fulfillments_import_mapping_invalid', $response->get_error_code() );
	}

	/**
	 * @testdox handle_run processes a chunk, advances processed, and attaches summary on the final chunk.
	 */
	public function test_run_processes_chunks_and_attaches_summary_on_completion(): void {
		$orders = array();
		$csv    = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 0; $i < 4; $i++ ) {
			/** @var WC_Order $order */
			$order    = OrderHelper::create_order();
			$orders[] = $order;
			$csv     .= "{$order->get_id()},TRK-{$i},ups\n";
		}
		$file    = $this->make_csv( $csv );
		$session = $this->open_session_for( $file );
		$token   = $session->token();

		$mapping = array(
			'0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
			'1' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
			'2' => FulfillmentsCsvImporter::COL_PROVIDER,
		);

		// First chunk: process 2 of 4 rows.
		$req1 = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$req1->set_param( 'token', $token );
		$req1->set_param( 'offset', 0 );
		$req1->set_param( 'limit', 2 );
		$req1->set_param( 'mapping', $mapping );

		$res1 = $this->invoke( 'handle_run', $req1 );
		$this->assertIsArray( $res1 );
		$this->assertFalse( $res1['done'] );
		$this->assertSame( 2, $res1['processed'] );
		$this->assertSame( 4, $res1['total'] );
		$this->assertSame( 2, $res1['counts']['created'] );
		$this->assertArrayNotHasKey( 'summary', $res1 );
		// Each chunk returns its own rows so the wizard can accumulate them
		// without forcing the session transient to hold them all.
		$this->assertArrayHasKey( 'rows', $res1 );
		$this->assertCount( 2, $res1['rows'] );

		// Second chunk: completes the import.
		$req2 = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$req2->set_param( 'token', $token );
		$req2->set_param( 'offset', 2 );
		$req2->set_param( 'limit', 2 );
		$req2->set_param( 'mapping', $mapping );

		$res2 = $this->invoke( 'handle_run', $req2 );
		$this->assertIsArray( $res2 );
		$this->assertTrue( $res2['done'] );
		$this->assertSame( 4, $res2['processed'] );
		$this->assertSame( 4, $res2['counts']['created'] );
		$this->assertArrayHasKey( 'summary', $res2 );
		$this->assertSame( 4, $res2['summary']['created'] );
		$this->assertCount( 2, $res2['rows'] );

		// Session is gone after completion; the same token must not load again.
		$this->assertNull( ImportSession::load( get_current_user_id(), $token ) );

		// Re-running with the (now-deleted) token returns a 400 with the standard code.
		$req3 = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$req3->set_param( 'token', $token );
		$req3->set_param( 'mapping', $mapping );
		$res3 = $this->invoke( 'handle_run', $req3 );
		$this->assertInstanceOf( \WP_Error::class, $res3 );
		$this->assertSame( 'fulfillments_import_token_invalid', $res3->get_error_code() );
	}

	/**
	 * @testdox handle_run surfaces row-level failures via the `errors` array.
	 */
	public function test_run_returns_row_errors(): void {
		$csv  = "order_number,tracking_number,shipment_provider\n99999999,T-MISS,ups\n";
		$file = $this->make_csv( $csv );

		$session = $this->open_session_for( $file );

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_param( 'token', $session->token() );
		$request->set_param( 'offset', 0 );
		$request->set_param( 'limit', 10 );
		$request->set_param(
			'mapping',
			array(
				'0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				'1' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
				'2' => FulfillmentsCsvImporter::COL_PROVIDER,
			)
		);

		$response = $this->invoke( 'handle_run', $request );

		$this->assertIsArray( $response );
		$this->assertTrue( $response['done'] );
		$this->assertNotEmpty( $response['errors'] );
		$this->assertSame( 1, $response['counts']['failed'] );
		$this->assertSame( 'order_not_found', $response['errors'][0]['code'] );
	}

	/**
	 * @testdox handle_prepare stages an uploaded CSV and opens a session bound to the current user.
	 */
	public function test_prepare_stages_csv_and_opens_session(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRK-1,ups\n";
		$file  = $this->make_csv( $csv );

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_param( 'delimiter', ',' );
		$request->set_param( 'notify_customer', false );
		$request->set_param( 'update_existing', true );
		$request->set_file_params(
			array(
				'file' => array(
					'name'     => 'fulfillments.csv',
					'type'     => 'text/csv',
					'tmp_name' => $file,
					'error'    => 0,
					'size'     => filesize( $file ),
				),
			)
		);

		$response = $this->invoke( 'handle_prepare', $request );

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'token', $response );
		$this->assertSame( 1, $response['total'] );
		$this->assertSame( ',', $response['delimiter'] );

		$session = ImportSession::load( get_current_user_id(), (string) $response['token'] );
		$this->assertNotNull( $session );
		$this->sessions[] = $session;
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
	 * @testdox The import routes refuse callers that lack manage_woocommerce.
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

		$controller = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$reflection = new \ReflectionClass( $controller );
		$method     = $reflection->getMethod( 'check_permission_for_fulfillments_import' );
		$method->setAccessible( true );

		$result = $method->invoke( $controller, new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' ) );

		$this->assertNotSame( true, $result );

		wp_delete_user( (int) $subscriber );
	}

	/**
	 * @testdox handle_run rejects a stale session whose staged file has been modified.
	 */
	public function test_run_rejects_when_staged_file_has_changed(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRK-1,ups\n";
		$file  = $this->make_csv( $csv );

		$session = $this->open_session_for( $file );

		// Mutate the staged file after the session was sealed.
		file_put_contents( $file, $csv . "\n# trailing change\n" );
		touch( $file, time() + 60 );

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_param( 'token', $session->token() );
		$request->set_param(
			'mapping',
			array(
				'0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				'1' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
				'2' => FulfillmentsCsvImporter::COL_PROVIDER,
			)
		);

		$response = $this->invoke( 'handle_run', $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'fulfillments_import_file_changed', $response->get_error_code() );
	}
}
