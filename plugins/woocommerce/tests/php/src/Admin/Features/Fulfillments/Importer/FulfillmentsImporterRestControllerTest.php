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
		$sut        = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$reflection = new \ReflectionClass( $sut );
		$handler    = $reflection->getMethod( $method );
		$handler->setAccessible( true );
		return $handler->invoke( $sut, $request );
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
		$this->assertSame( 'woocommerce_fulfillments_import_token_invalid', $response->get_error_code() );
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
		$this->assertSame( 'woocommerce_fulfillments_import_mapping_invalid', $response->get_error_code() );
	}

	/**
	 * @testdox handle_run rejects an offset ahead of the recorded progress.
	 */
	public function test_run_rejects_offset_ahead_of_progress(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},T-AHEAD,ups\n{$order->get_id()},T-AHEAD-2,ups\n";
		$file  = $this->make_csv( $csv );

		$session = $this->open_session_for( $file );

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_param( 'token', $session->token() );
		$request->set_param( 'offset', 2 );
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

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_offset_mismatch', $response->get_error_code() );
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
		$this->assertSame( 'woocommerce_fulfillments_import_token_invalid', $res3->get_error_code() );
	}

	/**
	 * @testdox The completion action fires once, on the final chunk, with the summary counts.
	 */
	public function test_import_completed_action_fires_once_on_the_final_chunk(): void {
		$csv = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 0; $i < 4; $i++ ) {
			/** @var WC_Order $order */
			$order = OrderHelper::create_order();
			$csv  .= "{$order->get_id()},DONE-{$i},ups\n";
		}
		$session = $this->open_session_for( $this->make_csv( $csv ) );
		$token   = $session->token();

		$mapping = array(
			'0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
			'1' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
			'2' => FulfillmentsCsvImporter::COL_PROVIDER,
		);

		$fired    = array();
		$listener = function ( $summary ) use ( &$fired ) {
			$fired[] = $summary;
		};
		add_action( 'woocommerce_fulfillments_csv_import_completed', $listener );

		try {
			foreach ( array( 0, 2 ) as $offset ) {
				$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
				$request->set_param( 'token', $token );
				$request->set_param( 'offset', $offset );
				$request->set_param( 'limit', 2 );
				$request->set_param( 'mapping', $mapping );

				$response = $this->invoke( 'handle_run', $request );
				$this->assertIsArray( $response );
				$this->assertCount( 0 === $offset ? 0 : 1, $fired, 'The action must only fire on the final chunk' );
			}
		} finally {
			remove_action( 'woocommerce_fulfillments_csv_import_completed', $listener );
		}

		$this->assertCount( 1, $fired );
		$this->assertSame( 4, $fired[0]['created'] );
		$this->assertSame( 0, $fired[0]['failed'] );
		$this->assertSame( array(), $fired[0]['rows'] );
	}

	/**
	 * @testdox handle_run freezes the first chunk's mapping and ignores later mapping changes.
	 */
	public function test_run_freezes_mapping_after_first_chunk(): void {
		$orders = array();
		$csv    = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 0; $i < 4; $i++ ) {
			/** @var WC_Order $order */
			$order    = OrderHelper::create_order();
			$orders[] = $order;
			$csv     .= "{$order->get_id()},FRZ-{$i},ups\n";
		}
		$file    = $this->make_csv( $csv );
		$session = $this->open_session_for( $file );
		$token   = $session->token();

		$mapping = array(
			'0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
			'1' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
			'2' => FulfillmentsCsvImporter::COL_PROVIDER,
		);

		$req1 = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$req1->set_param( 'token', $token );
		$req1->set_param( 'offset', 0 );
		$req1->set_param( 'limit', 2 );
		$req1->set_param( 'mapping', $mapping );

		$res1 = $this->invoke( 'handle_run', $req1 );
		$this->assertIsArray( $res1 );
		$this->assertSame( 2, $res1['processed'] );

		// A different (even invalid) mapping on the next chunk is ignored; the
		// frozen mapping from the first chunk drives the rest of the session.
		$req2 = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$req2->set_param( 'token', $token );
		$req2->set_param( 'offset', 2 );
		$req2->set_param( 'limit', 2 );
		$req2->set_param( 'mapping', array( '0' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER ) );

		$res2 = $this->invoke( 'handle_run', $req2 );
		$this->assertIsArray( $res2, 'The second chunk must not fail mapping validation once the mapping is frozen' );
		$this->assertTrue( $res2['done'] );
		$this->assertSame( 4, $res2['counts']['created'] );
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
		$this->assertSame( '99999999', $response['rows'][0]['order_number'] );
		$this->assertSame( 'order_not_found', $response['rows'][0]['code'] );
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

		$sut        = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$reflection = new \ReflectionClass( $sut );
		$method     = $reflection->getMethod( 'check_permission_for_fulfillments_import' );
		$method->setAccessible( true );

		$result = $method->invoke( $sut, new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' ) );

		$this->assertInstanceOf( \WP_Error::class, $result );

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
		file_put_contents( $file, $csv . "\n# trailing change\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Deliberate fixture mutation.
		touch( $file, time() + 60 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Deliberate mtime change to trip the integrity guard.

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
		$this->assertSame( 'woocommerce_fulfillments_import_file_changed', $response->get_error_code() );
		$this->assertFileDoesNotExist( $file, 'The stale staged file must be cleaned up with the session' );
	}

	/**
	 * @testdox handle_run keeps the session and staged file when a chunk aborts on an unreadable file.
	 */
	public function test_run_keeps_session_when_chunk_aborts(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRK-ABORT,ups\n";
		$file  = $this->make_csv( $csv );

		$session = $this->open_session_for( $file );

		chmod( $file, 0200 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Deliberately making the fixture unreadable.
		if ( is_readable( $file ) ) {
			chmod( $file, 0644 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Restoring fixture permissions.
			$this->markTestSkipped( 'Cannot make the staged file unreadable in this environment.' );
		}

		try {
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
		} finally {
			chmod( $file, 0644 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Restoring fixture permissions.
		}

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_chunk_failed', $response->get_error_code() );

		$reloaded = ImportSession::load( get_current_user_id(), $session->token() );
		$this->assertNotNull( $reloaded, 'The session must survive an aborted chunk so the client can retry' );
		$this->assertFileExists( $file, 'The staged CSV must not be deleted on an aborted chunk' );
	}

	/**
	 * Build a run request for a session with the standard three-column mapping.
	 *
	 * @param string $token  Session token.
	 * @param int    $offset Chunk offset.
	 * @param int    $limit  Chunk limit.
	 * @return WP_REST_Request
	 */
	private function make_run_request( string $token, int $offset, int $limit ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_param( 'token', $token );
		$request->set_param( 'offset', $offset );
		$request->set_param( 'limit', $limit );
		$request->set_param(
			'mapping',
			array(
				'0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				'1' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
				'2' => FulfillmentsCsvImporter::COL_PROVIDER,
			)
		);
		return $request;
	}

	/**
	 * @testdox handle_run caps the chunk size when customer notifications are enabled.
	 */
	public function test_run_clamps_chunk_size_when_notifying(): void {
		$csv = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 0; $i < FulfillmentsCsvImporter::NOTIFY_CHUNK_SIZE + 5; $i++ ) {
			$csv .= sprintf( "99%06d,NTF-%d,ups\n", $i, $i );
		}
		$file    = $this->make_csv( $csv );
		$session = $this->open_session_for( $file );

		$request = $this->make_run_request( $session->token(), 0, FulfillmentsCsvImporter::MAX_CHUNK_SIZE );
		$request->set_param( 'options', array( 'notify_customer' => true ) );

		$response = $this->invoke( 'handle_run', $request );

		$this->assertIsArray( $response );
		$this->assertSame(
			FulfillmentsCsvImporter::NOTIFY_CHUNK_SIZE,
			$response['processed'],
			'A notifying chunk must consume at most NOTIFY_CHUNK_SIZE rows per request'
		);
		$this->assertFalse( $response['done'] );
	}

	/**
	 * @testdox Retrying an already-processed offset returns progress without importing the rows again.
	 */
	public function test_run_is_idempotent_for_replayed_offsets(): void {
		$o1  = OrderHelper::create_order();
		$o2  = OrderHelper::create_order();
		$csv = "order_number,tracking_number,shipment_provider\n"
			. "{$o1->get_id()},RPL-1,ups\n"
			. "{$o2->get_id()},RPL-2,ups\n";

		$file    = $this->make_csv( $csv );
		$session = $this->open_session_for( $file );
		$token   = $session->token();

		$first = $this->invoke( 'handle_run', $this->make_run_request( $token, 0, 1 ) );
		$this->assertIsArray( $first );
		$this->assertSame( 1, $first['processed'] );
		$this->assertSame( 1, $first['counts']['created'] );

		// Same offset again, as a client retry would send it after a lost response.
		$replay = $this->invoke( 'handle_run', $this->make_run_request( $token, 0, 1 ) );
		$this->assertIsArray( $replay );
		$this->assertSame( 1, $replay['processed'] );
		$this->assertSame( 1, $replay['counts']['created'], 'A replayed chunk must not import rows again' );
		$this->assertSame( array(), $replay['rows'] );

		$store = \WC_Data_Store::load( 'order-fulfillment' );
		$this->assertCount(
			1,
			$store->read_fulfillments( \WC_Order::class, (string) $o1->get_id() ),
			'The replayed chunk must not create a duplicate fulfillment'
		);

		$final = $this->invoke( 'handle_run', $this->make_run_request( $token, 1, 5 ) );
		$this->assertIsArray( $final );
		$this->assertTrue( $final['done'] );
		$this->assertSame( 2, $final['counts']['created'] );
	}

	/**
	 * @testdox A concurrent run for the same session token is rejected with a 409.
	 */
	public function test_run_rejects_concurrent_chunk_for_same_token(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},LCK-1,ups\n";
		$file  = $this->make_csv( $csv );

		$session  = $this->open_session_for( $file );
		$token    = $session->token();
		$lock_key = 'wc_fulfillment_import_lock_' . get_current_user_id() . '_' . $token;

		add_option( $lock_key, (string) time(), '', false );
		try {
			$response = $this->invoke( 'handle_run', $this->make_run_request( $token, 0, 5 ) );
		} finally {
			delete_option( $lock_key );
		}

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_chunk_in_progress', $response->get_error_code() );

		$store = \WC_Data_Store::load( 'order-fulfillment' );
		$this->assertCount( 0, $store->read_fulfillments( \WC_Order::class, (string) $order->get_id() ) );
	}

	/**
	 * Dispatch a run request through the REST server so routes, arg schema, and
	 * permission callbacks are exercised, not just the handler.
	 *
	 * @param array<string, mixed> $params Body params.
	 * @return \WP_REST_Response
	 */
	private function dispatch_run( array $params ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_body_params( $params );
		return rest_do_request( $request );
	}

	/**
	 * @testdox The run route processes a chunk end to end when dispatched through the REST server.
	 */
	public function test_rest_run_route_end_to_end(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},REST-1,ups\n";
		$file  = $this->make_csv( $csv );

		$session = $this->open_session_for( $file );

		$response = $this->dispatch_run(
			array(
				'token'   => $session->token(),
				'offset'  => 0,
				'limit'   => 5,
				'mapping' => array(
					'0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
					'1' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
					'2' => FulfillmentsCsvImporter::COL_PROVIDER,
				),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['done'] );
		$this->assertSame( 1, $data['counts']['created'] );
	}

	/**
	 * @testdox The run route rejects a malformed token via its validate callback.
	 */
	public function test_rest_run_route_rejects_malformed_token(): void {
		$response = $this->dispatch_run(
			array(
				'token'   => 'not-a-token!',
				'mapping' => array( '0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER ),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox The run route rejects a limit above the schema maximum.
	 */
	public function test_rest_run_route_rejects_out_of_range_limit(): void {
		$response = $this->dispatch_run(
			array(
				'token'   => str_repeat( 'a', 32 ),
				'limit'   => FulfillmentsCsvImporter::MAX_CHUNK_SIZE + 1,
				'mapping' => array( '0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER ),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox The run route refuses users without manage_woocommerce when dispatched through the REST server.
	 */
	public function test_rest_run_route_requires_capability(): void {
		$subscriber = wp_insert_user(
			array(
				'user_login' => 'fulfill_rest_sub_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password( 12, false ),
				'role'       => 'subscriber',
			)
		);
		$this->assertIsInt( $subscriber );
		wp_set_current_user( (int) $subscriber );

		try {
			$response = $this->dispatch_run(
				array(
					'token'   => str_repeat( 'a', 32 ),
					'mapping' => array( '0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER ),
				)
			);
			$this->assertGreaterThanOrEqual( 401, $response->get_status() );
			$this->assertLessThanOrEqual( 403, $response->get_status() );
		} finally {
			wp_set_current_user( (int) self::$admin_id );
			wp_delete_user( (int) $subscriber );
		}
	}

	/**
	 * @testdox The prepare route rejects a request without a file when dispatched through the REST server.
	 */
	public function test_rest_prepare_route_rejects_missing_file(): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_body_params( array( 'delimiter' => ',' ) );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_fulfillments_import_no_file', $response->get_data()['code'] );
	}

	/**
	 * @testdox Duplicate order and tracking pairs are skipped across chunk boundaries.
	 */
	public function test_run_deduplicates_pairs_across_chunks(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n"
			. "{$order->get_id()},XCHUNK-1,ups\n"
			. "{$order->get_id()},XCHUNK-1,fedex\n";
		$file  = $this->make_csv( $csv );

		$session = $this->open_session_for( $file );
		$token   = $session->token();

		$first = $this->invoke( 'handle_run', $this->make_run_request( $token, 0, 1 ) );
		$this->assertIsArray( $first );
		$this->assertSame( 1, $first['counts']['created'] );

		$second = $this->invoke( 'handle_run', $this->make_run_request( $token, 1, 1 ) );
		$this->assertIsArray( $second );
		$this->assertSame( 1, $second['counts']['skipped'], 'The repeated pair in the second chunk must be skipped via persisted dedupe state' );
		$this->assertSame( 1, $second['counts']['created'] );

		$store = \WC_Data_Store::load( 'order-fulfillment' );
		$this->assertCount( 1, $store->read_fulfillments( \WC_Order::class, (string) $order->get_id() ) );
	}

	/**
	 * @testdox handle_run rejects a session whose staged file was deleted.
	 */
	public function test_run_rejects_when_staged_file_was_deleted(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},DEL-1,ups\n";
		$file  = $this->make_csv( $csv );

		$session = $this->open_session_for( $file );
		wp_delete_file( $file );

		$response = $this->invoke( 'handle_run', $this->make_run_request( $session->token(), 0, 5 ) );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_file_changed', $response->get_error_code() );
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

	/**
	 * @testdox A stale run lock is taken over instead of wedging the import.
	 */
	public function test_run_takes_over_stale_lock(): void {
		$order = OrderHelper::create_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},STL-1,ups\n";
		$file  = $this->make_csv( $csv );

		$session  = $this->open_session_for( $file );
		$token    = $session->token();
		$lock_key = 'wc_fulfillment_import_lock_' . get_current_user_id() . '_' . $token;

		add_option( $lock_key, (string) ( time() - 2 * MINUTE_IN_SECONDS ), '', false );
		try {
			$response = $this->invoke( 'handle_run', $this->make_run_request( $token, 0, 5 ) );
		} finally {
			delete_option( $lock_key );
		}

		$this->assertIsArray( $response, 'A lock older than the takeover threshold must not block the chunk' );
		$this->assertTrue( $response['done'] );
		$this->assertSame( 1, $response['counts']['created'] );
	}
}
