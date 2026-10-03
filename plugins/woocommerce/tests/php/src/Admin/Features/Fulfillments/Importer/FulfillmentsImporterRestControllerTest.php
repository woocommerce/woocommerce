<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporter;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsImporterRestController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\ImportSession;
use Automattic\WooCommerce\Internal\Admin\ImportExport\CSVUploadHelper;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Integration tests for the importer REST routes.
 */
class FulfillmentsImporterRestControllerTest extends WC_REST_Unit_Test_Case {

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
	 * Attachment posts created by tests; removed in tearDown.
	 *
	 * @var array<int, int>
	 */
	private array $attachments = array();

	/**
	 * Sessions created via ImportSession that need explicit cleanup.
	 *
	 * @var array<int, ImportSession>
	 */
	private array $sessions = array();

	/**
	 * The System Under Test.
	 *
	 * @var FulfillmentsImporterRestController
	 */
	private FulfillmentsImporterRestController $sut;

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
	 * Sign in as admin and register the controller for this test.
	 *
	 * The hook snapshot the WP test case restores after every test predates this class, so
	 * hooks added in setUpBeforeClass() only survive the first test. Registering here keeps
	 * the namespace filter and the cleanup action in place for each test.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( (int) self::$admin_id );
		$this->sut = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$this->sut->register();
	}

	/**
	 * Clean up files, attachments, sessions and container replacements created during the test.
	 */
	public function tearDown(): void {
		foreach ( $this->sessions as $session ) {
			$session->delete();
		}
		$this->sessions = array();
		foreach ( $this->attachments as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
		$this->attachments = array();
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->temp_files = array();
		$this->reset_container_replacements();
		$this->reset_container_resolutions();
		parent::tearDown();
	}

	/**
	 * Write a file inside the uploads directory and track it for cleanup.
	 *
	 * @param string $content   File content.
	 * @param string $extension File extension without the dot.
	 * @return string
	 */
	private function make_staged_file( string $content, string $extension = 'csv' ): string {
		// Stage inside the uploads directory, like CSVUploadHelper does in production,
		// so the controller's staged-path containment checks hold in tests.
		$upload_dir = wp_upload_dir();
		$path       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-rest-' . wp_generate_uuid4() . '.' . $extension;
		file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * Create an attachment post for a staged file and track it for cleanup.
	 *
	 * @param string $file Absolute path of the staged file.
	 * @return int
	 */
	private function make_attachment( string $file ): int {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'Fulfillments import test CSV',
				'post_mime_type' => 'text/csv',
			),
			$file
		);
		$this->assertIsInt( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );
		$this->attachments[] = $attachment_id;
		return $attachment_id;
	}

	/**
	 * Open a session for the admin user over a staged file and track it for cleanup.
	 *
	 * @param string $file          Absolute path of the staged file.
	 * @param int    $attachment_id Attachment post for the file.
	 * @return ImportSession
	 */
	private function make_session( string $file, int $attachment_id = 0 ): ImportSession {
		$session = ImportSession::create(
			get_current_user_id(),
			$file,
			',',
			array( 'order_number', 'tracking_number', 'shipment_provider' ),
			1,
			false,
			true,
			$attachment_id
		);
		$this->assertTrue( $session->persisted() );
		$this->sessions[] = $session;
		return $session;
	}

	/**
	 * Build a controller whose staging step returns a canned path instead of a real upload.
	 *
	 * is_uploaded_file() can never pass for files created inside a test process, so this
	 * stubs the staging seam and exercises everything handle_prepare does after it.
	 *
	 * @param string $file          Path to return as the staged file.
	 * @param int    $attachment_id Attachment ID to return with it.
	 * @return FulfillmentsImporterRestController
	 */
	private function make_controller_with_staged_file( string $file, int $attachment_id = 0 ): FulfillmentsImporterRestController {
		$sut = new class() extends FulfillmentsImporterRestController {
			/**
			 * Path returned instead of staging a real upload.
			 *
			 * @var string
			 */
			public string $staged = '';

			/**
			 * Attachment ID returned with the staged path.
			 *
			 * @var int
			 */
			public int $staged_attachment = 0;

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
					'id'   => $this->staged_attachment,
				);
			}
		};

		$sut->staged            = $file;
		$sut->staged_attachment = $attachment_id;
		return $sut;
	}

	/**
	 * Invoke a protected handler on a controller.
	 *
	 * @param string                                  $method  Handler method name.
	 * @param WP_REST_Request                         $request Built request.
	 * @param FulfillmentsImporterRestController|null $sut     Controller to invoke; the container's one by default.
	 * @return mixed
	 */
	private function invoke( string $method, WP_REST_Request $request, ?FulfillmentsImporterRestController $sut = null ) {
		$sut        = $sut ?? $this->sut;
		$reflection = new \ReflectionClass( $sut );
		$handler    = $reflection->getMethod( $method );
		$handler->setAccessible( true );
		return $handler->invoke( $sut, $request );
	}

	/**
	 * Build a prepare request with the default parameters.
	 *
	 * @return WP_REST_Request
	 */
	private function make_prepare_request(): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_param( 'delimiter', ',' );
		$request->set_param( 'notify_customer', false );
		$request->set_param( 'update_existing', true );
		return $request;
	}

	/**
	 * Run handle_prepare against a staged file and return the handler result.
	 *
	 * @param string $file          Staged file path.
	 * @param int    $attachment_id Attachment ID for the staged file.
	 * @return mixed
	 */
	private function prepare_with_staged_file( string $file, int $attachment_id = 0 ) {
		$sut      = $this->make_controller_with_staged_file( $file, $attachment_id );
		$response = $this->invoke( 'handle_prepare', $this->make_prepare_request(), $sut );
		if ( is_array( $response ) && isset( $response['token'] ) ) {
			$session = ImportSession::load( get_current_user_id(), (string) $response['token'] );
			if ( $session instanceof ImportSession ) {
				$this->sessions[] = $session;
			}
		}
		return $response;
	}

	/**
	 * Build a CSV with the given number of data rows.
	 *
	 * @param int $rows Number of data rows.
	 * @return string
	 */
	private function csv_with_rows( int $rows ): string {
		$csv = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 1; $i <= $rows; $i++ ) {
			$csv .= "1,CAP-{$i},ups\n";
		}
		return $csv;
	}

	/**
	 * @testdox register() exposes the prepare route on the REST server through the WooCommerce namespace filter.
	 */
	public function test_register_exposes_prepare_route(): void {
		$this->assertSame( 10, has_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this->sut, 'handle_woocommerce_rest_api_get_rest_namespaces' ) ) );
		$this->assertArrayHasKey( '/wc/v3/fulfillments/import/prepare', rest_get_server()->get_routes( 'wc/v3' ) );
	}

	/**
	 * @testdox register() attaches the session cleanup handler to the Action Scheduler hook.
	 */
	public function test_register_attaches_cleanup_handler(): void {
		remove_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ), 10 );
		$this->assertFalse( has_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ) ) );

		$this->sut->register();

		$this->assertSame( 10, has_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ) ) );
	}

	/**
	 * @testdox handle_prepare parses the staged CSV and opens a session bound to the current user.
	 */
	public function test_prepare_stages_csv_and_opens_session(): void {
		$file = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1001,TRK-1,ups\n" );

		$response = $this->prepare_with_staged_file( $file );

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'token', $response );
		$this->assertSame( 1, $response['total'] );
		$this->assertSame( array( '1001', 'TRK-1', 'ups' ), $response['sample'] );
		$this->assertSame( ',', $response['delimiter'] );

		$session = ImportSession::load( get_current_user_id(), (string) $response['token'] );
		$this->assertNotNull( $session );
		$this->assertSame( $file, $session->file() );
		$this->assertFileExists( $file, 'The staged file must stay in place for the import run' );

		// Contiguous 0-based column indexes would encode as a JSON array without the cast.
		$this->assertJsonStringEqualsJsonString(
			'{"0":"order_number","1":"tracking_number","2":"shipment_provider"}',
			(string) wp_json_encode( $response['detected_mapping'] )
		);
	}

	/**
	 * @testdox handle_prepare accepts a CSV with exactly the maximum number of rows.
	 */
	public function test_prepare_accepts_csv_at_row_cap(): void {
		$file = $this->make_staged_file( $this->csv_with_rows( FulfillmentsCsvImporter::MAX_IMPORT_ROWS ) );

		$response = $this->prepare_with_staged_file( $file );

		$this->assertIsArray( $response );
		$this->assertSame( FulfillmentsCsvImporter::MAX_IMPORT_ROWS, $response['total'] );
	}

	/**
	 * @testdox handle_prepare rejects a CSV with one row more than the importer supports.
	 */
	public function test_prepare_rejects_csv_over_row_cap(): void {
		$file = $this->make_staged_file( $this->csv_with_rows( FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 1 ) );

		$response = $this->prepare_with_staged_file( $file );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_too_many_rows', $response->get_error_code() );
		$this->assertSame( 413, $response->get_error_data()['status'] );
		$this->assertFileDoesNotExist( $file, 'The staged file must be cleaned up when the row cap rejects it' );
		$this->assertNull( ImportSession::active_for_user( get_current_user_id() ) );
	}

	/**
	 * @testdox handle_prepare rejects a header-only CSV and removes the staged file.
	 */
	public function test_prepare_rejects_header_only_csv(): void {
		$file          = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n" );
		$attachment_id = $this->make_attachment( $file );

		$response = $this->prepare_with_staged_file( $file, $attachment_id );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_csv_parse_error', $response->get_error_code() );
		$this->assertSame( 'The CSV file has no data rows.', $response->get_error_message() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertFileDoesNotExist( $file );
		$this->assertNull( get_post( $attachment_id ), 'The attachment post must go with the staged file' );
		$this->assertNull( ImportSession::active_for_user( get_current_user_id() ) );
	}

	/**
	 * @testdox handle_prepare removes the staged file when the header cannot be parsed.
	 */
	public function test_prepare_removes_staged_file_on_parse_error(): void {
		$file          = $this->make_staged_file( '' );
		$attachment_id = $this->make_attachment( $file );

		$response = $this->prepare_with_staged_file( $file, $attachment_id );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_csv_parse_error', $response->get_error_code() );
		$this->assertSame( 'CSV file is empty.', $response->get_error_message() );
		$this->assertFileDoesNotExist( $file );
		$this->assertNull( get_post( $attachment_id ) );
	}

	/**
	 * @testdox A successful upload replaces the prior session and removes its staged file and attachment.
	 */
	public function test_prepare_replaces_prior_session_after_successful_upload(): void {
		$prior_file       = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,OLD-1,ups\n" );
		$prior_attachment = $this->make_attachment( $prior_file );
		$prior            = $this->make_session( $prior_file, $prior_attachment );
		$new_file         = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n2,NEW-1,ups\n" );

		$response = $this->prepare_with_staged_file( $new_file );

		$this->assertIsArray( $response );
		$this->assertNotSame( $prior->token(), $response['token'] );
		$active = ImportSession::active_for_user( get_current_user_id() );
		$this->assertNotNull( $active );
		$this->assertSame( $response['token'], $active->token() );
		$this->assertNull( ImportSession::load( get_current_user_id(), $prior->token() ), 'The prior session record must be gone' );
		$this->assertFileDoesNotExist( $prior_file, 'The prior staged file must be removed' );
		$this->assertNull( get_post( $prior_attachment ), 'The prior attachment post must be removed' );
		$this->assertFileExists( $new_file );
	}

	/**
	 * @testdox A rejected upload leaves the prior session, its staged file and its attachment intact.
	 */
	public function test_prepare_keeps_prior_session_after_rejected_upload(): void {
		$prior_file       = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,OLD-1,ups\n" );
		$prior_attachment = $this->make_attachment( $prior_file );
		$prior            = $this->make_session( $prior_file, $prior_attachment );
		$header_only      = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n" );

		$response = $this->prepare_with_staged_file( $header_only );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$active = ImportSession::active_for_user( get_current_user_id() );
		$this->assertNotNull( $active );
		$this->assertSame( $prior->token(), $active->token() );
		$this->assertFileExists( $prior_file );
		$this->assertNotNull( get_post( $prior_attachment ) );
		$this->assertFileDoesNotExist( $header_only );
	}

	/**
	 * @testdox A staged file that is not a CSV is rejected and removed together with its attachment.
	 */
	public function test_prepare_rejects_non_csv_upload_and_discards_it(): void {
		$staged        = $this->make_staged_file( "<?php echo 'nope';\n", 'php' );
		$attachment_id = $this->make_attachment( $staged );

		$helper = $this->getMockBuilder( CSVUploadHelper::class )
			->onlyMethods( array( 'handle_csv_upload' ) )
			->getMock();
		$helper->method( 'handle_csv_upload' )->willReturn(
			array(
				'id'   => $attachment_id,
				'file' => $staged,
			)
		);
		wc_get_container()->replace( CSVUploadHelper::class, $helper );

		$request = $this->make_prepare_request();
		$request->set_file_params(
			array(
				'file' => array(
					'name'     => 'nope.php',
					'type'     => 'text/csv',
					'tmp_name' => $staged,
					'error'    => 0,
					'size'     => 20,
				),
			)
		);

		$response = $this->invoke( 'handle_prepare', $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_upload_failed', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertFileDoesNotExist( $staged );
		$this->assertNull( get_post( $attachment_id ) );
		$this->assertArrayNotHasKey( 'fulfillment_import_file', $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Asserting the superglobal was restored, not reading input.
	}

	/**
	 * @testdox An upload the helper rejects returns a fixed message instead of the helper's own.
	 */
	public function test_prepare_hides_upload_helper_error_message(): void {
		$helper = $this->getMockBuilder( CSVUploadHelper::class )
			->onlyMethods( array( 'handle_csv_upload' ) )
			->getMock();
		$helper->method( 'handle_csv_upload' )->willThrowException( new \Exception( 'Unable to write to /var/www/html/wp-content/uploads/wc-imports' ) );
		wc_get_container()->replace( CSVUploadHelper::class, $helper );

		$request = $this->make_prepare_request();
		$request->set_file_params(
			array(
				'file' => array(
					'name'     => 'sample.csv',
					'type'     => 'text/csv',
					'tmp_name' => '/tmp/does-not-matter.csv',
					'error'    => 0,
					'size'     => 20,
				),
			)
		);

		$response = $this->invoke( 'handle_prepare', $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_upload_failed', $response->get_error_code() );
		$this->assertSame( 'The file could not be uploaded.', $response->get_error_message() );
		$this->assertStringNotContainsString( '/var/www', $response->get_error_message() );
		$this->assertNull( ImportSession::active_for_user( get_current_user_id() ) );
	}

	/**
	 * @testdox handle_prepare rejects an empty multipart request with a 400.
	 */
	public function test_prepare_rejects_missing_file(): void {
		$response = $this->invoke( 'handle_prepare', $this->make_prepare_request() );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_no_file', $response->get_error_code() );
	}

	/**
	 * @testdox The permission check returns 403 for a user without manage_woocommerce.
	 */
	public function test_permission_check_returns_403_for_subscriber(): void {
		$subscriber = wp_insert_user(
			array(
				'user_login' => 'fulfill_subscriber_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password( 12, false ),
				'role'       => 'subscriber',
			)
		);
		$this->assertIsInt( $subscriber );
		wp_set_current_user( $subscriber );

		$result = $this->invoke( 'check_permission_for_fulfillments_import', new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox The permission check returns 401 for an unauthenticated request.
	 */
	public function test_permission_check_returns_401_for_logged_out_user(): void {
		wp_set_current_user( 0 );

		$result = $this->invoke( 'check_permission_for_fulfillments_import', new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 401, $result->get_error_data()['status'] );
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
	 * @testdox The delimiter argument is rejected with a 400 when it is not a single character or a tab spelling.
	 *
	 * @testWith ["ab"]
	 *           [";;"]
	 *           [["a"]]
	 *
	 * @param mixed $delimiter Delimiter value to send.
	 */
	public function test_rest_prepare_route_rejects_invalid_delimiter( $delimiter ): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_body_params( array( 'delimiter' => $delimiter ) );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertArrayHasKey( 'delimiter', $response->get_data()['data']['params'] );
	}

	/**
	 * @testdox The delimiter argument accepts a tab spelling and reaches the handler.
	 *
	 * @testWith ["\\t"]
	 *           ["tab"]
	 *           [";"]
	 *
	 * @param string $delimiter Delimiter value to send.
	 */
	public function test_rest_prepare_route_accepts_valid_delimiter( string $delimiter ): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_body_params( array( 'delimiter' => $delimiter ) );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_fulfillments_import_no_file', $response->get_data()['code'], 'A valid delimiter must pass validation and fail only on the missing file' );
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
			$request = $this->make_prepare_request();
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
