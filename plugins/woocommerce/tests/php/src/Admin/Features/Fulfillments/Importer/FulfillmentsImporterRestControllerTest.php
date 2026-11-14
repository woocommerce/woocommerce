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
	 * the namespace filter in place for each test.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( (int) self::$admin_id );
		$this->sut = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$this->sut->register();
	}

	/**
	 * Clean up files and container replacements created during the test.
	 *
	 * Sessions, attachments and scheduled actions are database rows the test
	 * transaction rolls back; files and container state are not.
	 */
	public function tearDown(): void {
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
		// Stage inside wc-imports, like CSVUploadHelper does in production, so the
		// controller's staged-path containment checks hold in tests.
		$dir  = wc_get_container()->get( CSVUploadHelper::class )->get_import_dir();
		$path = trailingslashit( $dir ) . 'wc-fulfillments-rest-' . wp_generate_uuid4() . '.' . $extension;
		file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * Create an attachment post for a staged file.
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
		return $attachment_id;
	}

	/**
	 * Open a session for the admin user over a staged file.
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
		return $session;
	}

	/**
	 * Build a controller whose staging step returns a canned path instead of a real upload.
	 *
	 * is_uploaded_file() can never pass for files created inside a test process, so this
	 * stubs the staging seam and exercises everything handle_prepare does after it.
	 *
	 * @param string        $file          Path to return as the staged file.
	 * @param int           $attachment_id Attachment ID to return with it.
	 * @param callable|null $on_stage      Runs while the upload is being staged, to simulate another request landing in between.
	 * @return FulfillmentsImporterRestController
	 */
	private function make_controller_with_staged_file( string $file, int $attachment_id = 0, ?callable $on_stage = null ): FulfillmentsImporterRestController {
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
			 * Callback run while staging, before the canned path is returned.
			 *
			 * @var callable|null
			 */
			public $on_stage = null;

			/**
			 * Return the canned staged path.
			 *
			 * @param WP_REST_Request $request Unused.
			 * @return array{file:string, id:int}
			 */
			protected function stage_uploaded_csv( WP_REST_Request $request ) {
				unset( $request );
				if ( is_callable( $this->on_stage ) ) {
					call_user_func( $this->on_stage );
				}
				return array(
					'file' => $this->staged,
					'id'   => $this->staged_attachment,
				);
			}
		};

		$sut->init( wc_get_container()->get( CSVUploadHelper::class ) );
		$sut->staged            = $file;
		$sut->staged_attachment = $attachment_id;
		$sut->on_stage          = $on_stage;
		return $sut;
	}

	/**
	 * Build a controller whose upload helper is replaced by a mock.
	 *
	 * The helper is injected through init() when the container resolves the controller, so the
	 * replacement has to be registered before a fresh controller is resolved.
	 *
	 * @param CSVUploadHelper $helper Mocked helper.
	 * @return FulfillmentsImporterRestController
	 */
	private function controller_with_helper( CSVUploadHelper $helper ): FulfillmentsImporterRestController {
		wc_get_container()->replace( CSVUploadHelper::class, $helper );
		$this->reset_container_resolutions();
		return wc_get_container()->get( FulfillmentsImporterRestController::class );
	}

	/**
	 * Build a multipart file entry the way PHP populates $_FILES.
	 *
	 * @param string $name  Client file name.
	 * @param int    $error PHP upload error code.
	 * @param int    $size  Reported size in bytes.
	 * @return array<string, array<string, mixed>>
	 */
	private function file_params( string $name = 'sample.csv', int $error = UPLOAD_ERR_OK, int $size = 20 ): array {
		return array(
			'file' => array(
				'name'     => $name,
				'type'     => 'text/csv',
				'tmp_name' => UPLOAD_ERR_OK === $error ? '/tmp/does-not-matter.csv' : '',
				'error'    => $error,
				'size'     => $size,
			),
		);
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
	 * @param string        $file          Staged file path.
	 * @param int           $attachment_id Attachment ID for the staged file.
	 * @param callable|null $on_stage      Runs while the upload is being staged.
	 * @return mixed
	 */
	private function prepare_with_staged_file( string $file, int $attachment_id = 0, ?callable $on_stage = null ) {
		$sut = $this->make_controller_with_staged_file( $file, $attachment_id, $on_stage );
		return $this->invoke( 'handle_prepare', $this->make_prepare_request(), $sut );
	}

	/**
	 * Count the callbacks registered on a hook.
	 *
	 * @param string $hook_name Hook name.
	 * @return int
	 */
	private function callback_count( string $hook_name ): int {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook_name ] ) ) {
			return 0;
		}
		return array_sum( array_map( 'count', $wp_filter[ $hook_name ]->callbacks ) );
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
	 * @testdox handle_prepare parses the staged CSV and opens a session bound to the current user.
	 */
	public function test_prepare_stages_csv_and_opens_session(): void {
		$file = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1001,TRK-1,ups\n" );

		$response = $this->prepare_with_staged_file( $file );

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'token', $response );
		$this->assertSame( 1, $response['total'] );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $response['headers'] );
		$this->assertSame( array( '1001', 'TRK-1', 'ups' ), $response['sample'] );
		$this->assertSame( ',', $response['delimiter'] );

		$session = ImportSession::load( get_current_user_id(), (string) $response['token'] );
		$this->assertNotNull( $session );
		$this->assertSame( $file, $session->file() );
		$this->assertSame( $response['headers'], $session->headers() );
		$this->assertSame( 1, $session->total() );
		$this->assertSame( ',', $session->delimiter() );
		$this->assertFalse( $session->notify_customer(), 'The default notify_customer must be stored' );
		$this->assertTrue( $session->update_existing(), 'The default update_existing must be stored' );
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
	 * @testdox handle_prepare rejects a file far over the cap, where the importer reports a capped total, and removes its attachment.
	 */
	public function test_prepare_rejects_capped_total_and_removes_attachment(): void {
		$file          = $this->make_staged_file( $this->csv_with_rows( FulfillmentsCsvImporter::MAX_IMPORT_ROWS + 2 ) );
		$attachment_id = $this->make_attachment( $file );

		$response = $this->prepare_with_staged_file( $file, $attachment_id );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_too_many_rows', $response->get_error_code() );
		$this->assertSame( 413, $response->get_error_data()['status'] );
		$this->assertStringContainsString( number_format_i18n( FulfillmentsCsvImporter::MAX_IMPORT_ROWS ), $response->get_error_message(), 'The message names the cap, never the capped total' );
		$this->assertFileDoesNotExist( $file );
		$this->assertNull( get_post( $attachment_id ), 'The attachment must go with the staged file on the 413 path' );
	}

	/**
	 * @testdox handle_prepare stores the notify_customer and update_existing values on the session.
	 */
	public function test_prepare_stores_notify_and_update_flags_on_the_session(): void {
		$file    = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1001,TRK-1,ups\n" );
		$sut     = $this->make_controller_with_staged_file( $file );
		$request = $this->make_prepare_request();
		$request->set_param( 'notify_customer', true );
		$request->set_param( 'update_existing', false );

		$response = $this->invoke( 'handle_prepare', $request, $sut );

		$this->assertIsArray( $response );
		$session = ImportSession::load( get_current_user_id(), (string) $response['token'] );
		$this->assertNotNull( $session );
		$this->assertTrue( $session->notify_customer() );
		$this->assertFalse( $session->update_existing() );
	}

	/**
	 * @testdox A replaced session whose file sits outside wc-imports keeps that file and its attachment.
	 */
	public function test_prepare_keeps_a_replaced_sessions_file_outside_the_import_directory(): void {
		$media = trailingslashit( wp_upload_dir()['basedir'] ) . 'wc-fulfillments-rest-' . wp_generate_uuid4() . '.jpg';
		file_put_contents( $media, 'not a csv' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		$this->temp_files[] = $media;
		$media_attachment   = $this->make_attachment( $media );
		$prior              = $this->make_session( $media, $media_attachment );
		$new_file           = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n2,NEW-1,ups\n" );

		$response = $this->prepare_with_staged_file( $new_file );

		$this->assertIsArray( $response );
		$this->assertNull( ImportSession::load( get_current_user_id(), $prior->token() ), 'The prior session record is still replaced' );
		$this->assertFileExists( $media, 'A session path outside wc-imports must not be deleted' );
		$this->assertNotNull( get_post( $media_attachment ), 'Its attachment must not be deleted' );

		wp_delete_file( $media );
		$this->assertNotNull( get_post( $media_attachment ) );
		$again = $this->make_session( $media, $media_attachment );
		$this->prepare_with_staged_file( $this->make_staged_file( "order_number,tracking_number,shipment_provider\n3,NEW-2,ups\n" ) );
		$this->assertNull( ImportSession::load( get_current_user_id(), $again->token() ) );
		$this->assertNotNull( get_post( $media_attachment ), 'The containment check must run for a missing file too' );
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
	 * @testdox Two overlapping prepares by the same user each remove the staged file of the session they replaced.
	 */
	public function test_prepare_deletes_file_of_session_replaced_at_create_time(): void {
		$prior_file = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,OLD-1,ups\n" );
		$prior      = $this->make_session( $prior_file );
		$a_file     = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n2,A-1,ups\n" );
		$a_attach   = $this->make_attachment( $a_file );
		$b_file     = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n3,B-1,ups\n" );
		$a_session  = null;

		// Request A finishes while request B is still staging its upload: it replaces the
		// prior session and removes the prior staged file, as handle_prepare would.
		$request_a = function () use ( $a_file, $a_attach, $prior_file, &$a_session ) {
			$a_session = $this->make_session( $a_file, $a_attach );
			wp_delete_file( $prior_file );
		};

		$response = $this->prepare_with_staged_file( $b_file, 0, $request_a );

		$this->assertIsArray( $response );
		$this->assertInstanceOf( ImportSession::class, $a_session );
		$this->assertNull( ImportSession::load( get_current_user_id(), $prior->token() ) );
		$this->assertNull( ImportSession::load( get_current_user_id(), $a_session->token() ), 'Request B retired request A\'s session' );
		$this->assertFileDoesNotExist( $a_file, 'Request B must remove the staged file of the session it retired' );
		$this->assertNull( get_post( $a_attach ), 'Request B must remove the attachment of the session it retired' );
		$this->assertFileExists( $b_file );
		$active = ImportSession::active_for_user( get_current_user_id() );
		$this->assertNotNull( $active );
		$this->assertSame( $response['token'], $active->token() );
	}

	/**
	 * @testdox handle_prepare fails with a 500 and removes the staged file when the cleanup cannot be scheduled.
	 */
	public function test_prepare_fails_when_cleanup_cannot_be_scheduled(): void {
		$file          = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1001,TRK-1,ups\n" );
		$attachment_id = $this->make_attachment( $file );

		$fail_schedule = static function () {
			return 0;
		};
		add_filter( 'pre_as_schedule_single_action', $fail_schedule );
		try {
			$response = $this->prepare_with_staged_file( $file, $attachment_id );
		} finally {
			remove_filter( 'pre_as_schedule_single_action', $fail_schedule );
		}

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_session_failed', $response->get_error_code() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
		$this->assertFileDoesNotExist( $file, 'A staged file with no cleanup owner must not be left behind' );
		$this->assertNull( get_post( $attachment_id ) );
		$this->assertNull( ImportSession::active_for_user( get_current_user_id() ) );
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
	 * @testdox A staged file the upload handler wrote outside wc-imports is rejected and removed together with its attachment.
	 */
	public function test_prepare_discards_a_staged_file_outside_the_import_directory(): void {
		$staged = trailingslashit( wp_upload_dir()['basedir'] ) . 'wc-fulfillments-rest-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $staged, "order_number,tracking_number,shipment_provider\n1,T-1,ups\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		$this->temp_files[] = $staged;
		$attachment_id      = $this->make_attachment( $staged );

		$helper = $this->getMockBuilder( CSVUploadHelper::class )
			->onlyMethods( array( 'handle_csv_upload' ) )
			->getMock();
		$helper->method( 'handle_csv_upload' )->willReturn(
			array(
				'id'   => $attachment_id,
				'file' => $staged,
			)
		);
		$sut = $this->controller_with_helper( $helper );

		$request = $this->make_prepare_request();
		$request->set_file_params( $this->file_params() );

		$response = $this->invoke( 'handle_prepare', $request, $sut );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_upload_failed', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertFileDoesNotExist( $staged, 'A misplaced upload is still removed' );
		$this->assertNull( get_post( $attachment_id ) );
		$this->assertNull( ImportSession::active_for_user( get_current_user_id() ) );
		$this->assertArrayNotHasKey( 'fulfillment_import_file', $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Asserting the superglobal was restored, not reading input.
	}

	/**
	 * @testdox A staged file passes the real pipeline when the helper stages it inside wc-imports.
	 */
	public function test_prepare_succeeds_over_the_rest_server_with_a_mocked_upload(): void {
		$staged        = $this->make_staged_file( "order_number\ttracking_number\tshipment_provider\n1001\tTRK-1\tups\n" );
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
		$this->controller_with_helper( $helper )->register();

		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/prepare' );
		$request->set_body_params( array( 'delimiter' => 'tab' ) );
		$request->set_file_params( $this->file_params( 'sample.txt' ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( "\t", $data['delimiter'], 'The tab spelling must reach the importer as a tab' );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $data['headers'] );
		$this->assertSame( 1, $data['total'] );
		$this->assertJsonStringEqualsJsonString(
			'{"0":"order_number","1":"tracking_number","2":"shipment_provider"}',
			(string) wp_json_encode( $data['detected_mapping'] )
		);
		$session = ImportSession::load( get_current_user_id(), (string) $data['token'] );
		$this->assertNotNull( $session );
		$this->assertSame( "\t", $session->delimiter() );
		$this->assertFileExists( $staged );
	}

	/**
	 * @testdox PHP upload errors are mapped to the matching error code before the helper runs.
	 *
	 * @testWith [1, "woocommerce_fulfillments_import_file_too_large", 413]
	 *           [2, "woocommerce_fulfillments_import_file_too_large", 413]
	 *           [4, "woocommerce_fulfillments_import_no_file", 400]
	 *           [3, "woocommerce_fulfillments_import_upload_failed", 400]
	 *
	 * @param int    $error  PHP upload error code.
	 * @param string $code   Expected error code.
	 * @param int    $status Expected status.
	 */
	public function test_prepare_maps_php_upload_errors( int $error, string $code, int $status ): void {
		$helper = $this->getMockBuilder( CSVUploadHelper::class )
			->onlyMethods( array( 'handle_csv_upload' ) )
			->getMock();
		$helper->expects( $this->never() )->method( 'handle_csv_upload' );
		$sut = $this->controller_with_helper( $helper );

		$request = $this->make_prepare_request();
		$request->set_file_params( $this->file_params( 'big.csv', $error, 0 ) );

		$response = $this->invoke( 'handle_prepare', $request, $sut );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( $code, $response->get_error_code() );
		$this->assertSame( $status, $response->get_error_data()['status'] );
		$this->assertNull( ImportSession::active_for_user( get_current_user_id() ) );
	}

	/**
	 * @testdox A zero-byte upload gets the importer's empty-file message before the helper runs.
	 */
	public function test_prepare_rejects_a_zero_byte_upload_as_an_empty_csv(): void {
		$helper = $this->getMockBuilder( CSVUploadHelper::class )
			->onlyMethods( array( 'handle_csv_upload' ) )
			->getMock();
		$helper->expects( $this->never() )->method( 'handle_csv_upload' );
		$sut = $this->controller_with_helper( $helper );

		$request = $this->make_prepare_request();
		$request->set_file_params( $this->file_params( 'empty.csv', UPLOAD_ERR_OK, 0 ) );

		$response = $this->invoke( 'handle_prepare', $request, $sut );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_csv_parse_error', $response->get_error_code() );
		$this->assertSame( 'CSV file is empty.', $response->get_error_message() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
	}

	/**
	 * @testdox An error thrown by the upload helper returns a 500 with the fixed message.
	 */
	public function test_prepare_returns_500_when_the_upload_helper_throws_an_error(): void {
		$helper = $this->getMockBuilder( CSVUploadHelper::class )
			->onlyMethods( array( 'handle_csv_upload' ) )
			->getMock();
		$helper->method( 'handle_csv_upload' )->willThrowException( new \TypeError( 'wp_import_handle_upload(): Argument #1 must be of type array' ) );
		$sut = $this->controller_with_helper( $helper );

		$request = $this->make_prepare_request();
		$request->set_file_params( $this->file_params() );

		$response = $this->invoke( 'handle_prepare', $request, $sut );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_upload_failed', $response->get_error_code() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
		$this->assertStringNotContainsString( 'wp_import_handle_upload', $response->get_error_message() );
		$this->assertArrayNotHasKey( 'fulfillment_import_file', $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Asserting the superglobal was restored, not reading input.
	}

	/**
	 * @testdox A throw from the upload helper after it stored the file removes the file and its attachment.
	 */
	public function test_prepare_removes_the_stored_upload_when_the_helper_throws_after_storing_it(): void {
		// CSVUploadHelper throws after storing when wc_is_file_valid_csv() rejects the stored
		// file. is_uploaded_file() cannot pass in a test process, so this helper stores the file
		// with wp_handle_sideload() (which fires the same hooks as wp_import_handle_upload()) and
		// then throws the way the real helper does.
		$helper = new class() extends CSVUploadHelper {
			/**
			 * Absolute path of the file stored before the throw.
			 *
			 * @var string
			 */
			public string $stored_file = '';

			/**
			 * Attachment created for the stored file before the throw.
			 *
			 * @var int
			 */
			public int $stored_id = 0;

			// The stub always throws; the array return type is never reached.
			// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
			/**
			 * Store the upload under wc-imports with its attachment, then fail the CSV check.
			 *
			 * @param string     $import_type        Unused.
			 * @param string     $files_index        Unused.
			 * @param array|null $allowed_mime_types Unused.
			 * @return array
			 * @throws \Exception Always, after the file and attachment exist.
			 */
			public function handle_csv_upload( string $import_type, string $files_index = 'import', ?array $allowed_mime_types = null ): array {
				$tmp = wp_tempnam( 'fulfillment-sample.txt' );
				file_put_contents( $tmp, "order_number,tracking_number,shipment_provider\n1,T-1,ups\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

				$file = array(
					'name'     => 'fulfillment-sample.txt',
					'type'     => 'text/plain',
					'tmp_name' => $tmp,
					'error'    => UPLOAD_ERR_OK,
					'size'     => (int) filesize( $tmp ),
				);

				$this->get_import_dir();
				add_filter( 'upload_dir', array( $this, 'override_upload_dir' ) );
				$upload = wp_handle_sideload( $file, array( 'test_form' => false ) );
				remove_filter( 'upload_dir', array( $this, 'override_upload_dir' ) );

				$this->stored_file = (string) ( $upload['file'] ?? '' );
				$this->stored_id   = (int) wp_insert_attachment(
					array(
						'post_title'     => 'fulfillment-sample.txt',
						'post_mime_type' => (string) ( $upload['type'] ?? 'text/plain' ),
					),
					$this->stored_file
				);

				throw new \Exception( 'Invalid file type for a CSV import.' );
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
		};
		$sut    = $this->controller_with_helper( $helper );

		$request = $this->make_prepare_request();
		$request->set_file_params( $this->file_params( 'sample.txt' ) );

		$hooks_before = array( $this->callback_count( 'wp_handle_upload' ), $this->callback_count( 'add_attachment' ) );
		$response     = $this->invoke( 'handle_prepare', $request, $sut );

		$this->temp_files[] = $helper->stored_file;
		$this->assertSame( $hooks_before, array( $this->callback_count( 'wp_handle_upload' ), $this->callback_count( 'add_attachment' ) ), 'The recording hooks are removed' );
		$this->assertTrue( ImportSession::is_staged_path( $helper->stored_file ), 'The helper stored the file under wc-imports' );
		$this->assertGreaterThan( 0, $helper->stored_id, 'The helper created an attachment' );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_upload_failed', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertFileDoesNotExist( $helper->stored_file, 'The stored file is removed' );
		$this->assertNull( get_post( $helper->stored_id ), 'The attachment is removed' );
		$this->assertNull( ImportSession::active_for_user( get_current_user_id() ) );
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
		$sut = $this->controller_with_helper( $helper );

		$request = $this->make_prepare_request();
		$request->set_file_params( $this->file_params() );

		$response = $this->invoke( 'handle_prepare', $request, $sut );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_upload_failed', $response->get_error_code() );
		$this->assertSame( 'The file could not be uploaded.', $response->get_error_message() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
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
		$this->assertSame( 400, $response->get_error_data()['status'] );
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
	 * @testdox The delimiter argument is rejected with a 400 when it is not a single ASCII character or a tab spelling.
	 *
	 * @testWith ["ab"]
	 *           [";;"]
	 *           [["a"]]
	 *           ["\""]
	 *           ["\n"]
	 *           ["\u00a7"]
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
	 * @testdox The delimiter argument accepts a tab spelling, a single ASCII character or an empty value and reaches the handler.
	 *
	 * @testWith ["\\t"]
	 *           ["\t"]
	 *           ["tab"]
	 *           ["TAB"]
	 *           [";"]
	 *           [","]
	 *           [""]
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
	 * @testdox A zero upload limit, which multisite reports when the site is out of upload space, rejects the upload with a 413.
	 */
	public function test_prepare_rejects_upload_when_no_upload_space_is_left(): void {
		$no_space = static function () {
			return 0;
		};
		add_filter( 'import_upload_size_limit', $no_space );

		try {
			$request = $this->make_prepare_request();
			$request->set_file_params( $this->file_params( 'small.csv', UPLOAD_ERR_OK, 20 ) );

			$response = $this->invoke( 'handle_prepare', $request );
		} finally {
			remove_filter( 'import_upload_size_limit', $no_space );
		}

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_file_too_large', $response->get_error_code() );
		$this->assertSame( 413, $response->get_error_data()['status'] );
		$this->assertStringContainsString( 'no upload space', $response->get_error_message() );
	}

	/**
	 * @testdox A site with no upload space left gets the no-space 413 even for a file PHP refused for its size.
	 */
	public function test_prepare_reports_no_upload_space_before_a_php_size_error(): void {
		$no_space = static function () {
			return 0;
		};
		add_filter( 'import_upload_size_limit', $no_space );

		try {
			$request = $this->make_prepare_request();
			$request->set_file_params( $this->file_params( 'large.csv', UPLOAD_ERR_INI_SIZE, 0 ) );

			$response = $this->invoke( 'handle_prepare', $request );
		} finally {
			remove_filter( 'import_upload_size_limit', $no_space );
		}

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'woocommerce_fulfillments_import_file_too_large', $response->get_error_code() );
		$this->assertSame( 413, $response->get_error_data()['status'] );
		$this->assertSame( 'This site has no upload space left for the file.', $response->get_error_message() );
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
		$this->assertSame( 413, $response->get_error_data()['status'] );
		$this->assertNull( ImportSession::active_for_user( get_current_user_id() ) );
		$this->assertArrayNotHasKey( 'fulfillment_import_file', $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Asserting the superglobal was never written, not reading input.
	}
}
