<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporter;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsImporterRestController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\ImportSession;
use Automattic\WooCommerce\Internal\Admin\ImportExport\CSVUploadHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Data_Store;
use WC_Order;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Integration tests for the importer REST routes.
 */
class FulfillmentsImporterRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Use a fresh REST server per test, since some tests register a controller with a mocked upload helper.
	 *
	 * @var bool
	 */
	protected static $reuse_rest_server = false;

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
	 * Product shared by the orders of one test; created with the first order.
	 *
	 * @var \WC_Product|null
	 */
	private ?\WC_Product $product = null;

	/**
	 * Mapping of the three-column CSV the run tests stage.
	 */
	private const RUN_MAPPING = array(
		'0' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
		'1' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
		'2' => FulfillmentsCsvImporter::COL_PROVIDER,
	);

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
	 * Create an order with one line item, which is all the importer reads from it.
	 *
	 * @return int Order ID.
	 */
	private function make_order(): int {
		if ( null === $this->product ) {
			$this->product = \WC_Helper_Product::create_simple_product();
		}
		$order = wc_create_order();
		$order->add_product( $this->product, 1 );
		return $order->save();
	}

	/**
	 * Stage a CSV with one row per new order and open a session over it.
	 *
	 * @param int  $rows            Number of orders and data rows.
	 * @param bool $with_attachment Whether the staged file gets an attachment post.
	 * @param bool $notify          Value of notify_customer stored by the prepare step.
	 * @param bool $update          Value of update_existing stored by the prepare step.
	 * @return array{0: ImportSession, 1: array<int, int>} The session and the order IDs in file order.
	 */
	private function make_run_session( int $rows, bool $with_attachment = false, bool $notify = false, bool $update = true ): array {
		$order_ids = array();
		$csv       = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 1; $i <= $rows; $i++ ) {
			$order_ids[] = $this->make_order();
			$csv        .= end( $order_ids ) . ",RUN-{$i},ups\n";
		}
		$file    = $this->make_staged_file( $csv );
		$session = ImportSession::create(
			get_current_user_id(),
			$file,
			',',
			array( 'order_number', 'tracking_number', 'shipment_provider' ),
			$rows,
			$notify,
			$update,
			$with_attachment ? $this->make_attachment( $file ) : 0
		);
		$this->assertTrue( $session->persisted() );
		return array( $session, $order_ids );
	}

	/**
	 * Call handle_run with the three-column mapping.
	 *
	 * @param string               $token  Session token.
	 * @param int                  $offset Offset to send.
	 * @param int|null             $limit  Limit to send; null leaves it out.
	 * @param array<string, mixed> $params Parameters that replace or add to the defaults.
	 * @return mixed
	 */
	private function run_chunk( string $token, int $offset, ?int $limit = null, array $params = array() ) {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_param( 'token', $token );
		$request->set_param( 'offset', $offset );
		$request->set_param( 'mapping', self::RUN_MAPPING );
		if ( null !== $limit ) {
			$request->set_param( 'limit', $limit );
		}
		foreach ( $params as $name => $value ) {
			$request->set_param( $name, $value );
		}
		return $this->invoke( 'handle_run', $request );
	}

	/**
	 * Read the fulfillments of an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	private function fulfillments_of( int $order_id ): array {
		return WC_Data_Store::load( 'order-fulfillment' )->read_fulfillments( WC_Order::class, (string) $order_id );
	}

	/**
	 * Read the value of the current user's run lock row.
	 *
	 * @return string|null Null when no request holds the lock.
	 */
	private function run_lock_value(): ?string {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'woocommerce_fulfillments_import_lock_' . get_current_user_id() )
		);
	}

	/**
	 * Write the current user's run lock row, as another request holding the lock would.
	 *
	 * @param int $expires Expiry timestamp stored as the lock value.
	 */
	private function set_run_lock( int $expires ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %d, 'no') ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
				'woocommerce_fulfillments_import_lock_' . get_current_user_id(),
				$expires
			)
		);
	}

	/**
	 * Assert a handler result is the expected error.
	 *
	 * @param string $code   Expected error code.
	 * @param int    $status Expected HTTP status.
	 * @param mixed  $result Handler result.
	 */
	private function assert_run_error( string $code, int $status, $result ): void {
		$this->assertInstanceOf( \WP_Error::class, $result, "The request should be refused with {$code}" );
		$this->assertSame( $code, $result->get_error_code(), 'The error code tells the client what to do next' );
		$this->assertSame( $status, $result->get_error_data()['status'], "{$code} should answer with HTTP {$status}" );
	}

	/**
	 * @testdox register() exposes the prepare and run routes on the REST server through the WooCommerce namespace filter.
	 */
	public function test_register_exposes_the_import_routes(): void {
		$this->assertSame( 10, has_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this->sut, 'handle_woocommerce_rest_api_get_rest_namespaces' ) ) );
		$this->assertArrayHasKey( '/wc/v3/fulfillments/import/prepare', rest_get_server()->get_routes( 'wc/v3' ) );
		$this->assertArrayHasKey( '/wc/v3/fulfillments/import/run', rest_get_server()->get_routes( 'wc/v3' ) );
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

			/**
			 * Error wp_handle_sideload() returned, empty when the file was stored.
			 *
			 * @var string
			 */
			public string $sideload_error = '';

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
				// The type check is off: finfo reports this content as text/plain or text/csv
				// depending on the PHP version, and core refuses a .txt detected as text/csv.
				$upload = wp_handle_sideload(
					$file,
					array(
						'test_form' => false,
						'test_type' => false,
					)
				);
				remove_filter( 'upload_dir', array( $this, 'override_upload_dir' ) );

				$this->sideload_error = (string) ( $upload['error'] ?? '' );
				$this->stored_file    = (string) ( $upload['file'] ?? '' );
				$this->stored_id      = (int) wp_insert_attachment(
					array(
						'post_title'     => 'fulfillment-sample.txt',
						'post_mime_type' => 'text/plain',
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
		$this->assertSame( '', $helper->sideload_error, 'The helper stored the fixture' );
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

	/**
	 * @testdox handle_run imports the rows of one chunk, records the progress and releases the lock.
	 */
	public function test_run_imports_a_chunk_and_records_the_progress(): void {
		list( $session, $order_ids ) = $this->make_run_session( 3 );

		$response = $this->run_chunk( $session->token(), 0, 2 );

		$this->assertIsArray( $response );
		$this->assertSame( 2, $response['processed'] );
		$this->assertSame( 3, $response['total'] );
		$this->assertFalse( $response['done'] );
		$this->assertArrayNotHasKey( 'summary', $response, 'The summary comes with the last chunk only' );
		$this->assertSame( 2, $response['counts']['created'] );
		$this->assertSame( array( 2, 3 ), array_column( $response['rows'], 'row' ), 'Row numbers count the header' );
		$this->assertSame( array( $order_ids[0], $order_ids[1] ), array_column( $response['rows'], 'order_id' ) );
		$this->assertSame( OrderUtil::get_order_admin_edit_url( $order_ids[0] ), $response['rows'][0]['order_edit_url'] );
		$this->assertCount( 1, $this->fulfillments_of( $order_ids[0] ) );
		$stored = $this->fulfillments_of( $order_ids[1] );
		$this->assertCount( 1, $stored );
		$this->assertSame( 'RUN-2', $stored[0]->get_tracking_number(), 'Each cell lands in the field the mapping names' );
		$this->assertSame( 'ups', $stored[0]->get_shipment_provider() );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[2] ), 'A row past the limit is not imported' );
		$stored_session = ImportSession::load( get_current_user_id(), $session->token() );
		$this->assertSame( 2, $stored_session->processed() );
		$this->assertTrue( $stored_session->update_existing(), 'A request without options keeps update_existing as the prepare step stored it' );
		$this->assertNull( $this->run_lock_value(), 'The lock is released when the request ends' );
	}

	/**
	 * @testdox handle_run completes the import with the last chunk: summary, staged file released, action fired once.
	 */
	public function test_run_completes_the_import_with_the_last_chunk(): void {
		list( $session, ) = $this->make_run_session( 2, true );
		$file             = $session->file();
		$attachment_id    = $session->attachment_id();
		$fired            = array();
		add_action(
			'woocommerce_fulfillments_csv_import_completed',
			function ( $counts, $user_id ) use ( &$fired ) {
				$fired[] = array( $counts, $user_id );
			},
			10,
			2
		);

		$first = $this->run_chunk( $session->token(), 0, 1 );
		$this->assertCount( 0, $fired, 'The action does not fire before the last chunk' );
		$last = $this->run_chunk( $session->token(), $first['processed'], 1 );

		$this->assertTrue( $last['done'] );
		$this->assertSame( 2, $last['processed'] );
		$this->assertSame( 2, $last['summary']['created'], 'The summary has the counts of every chunk' );
		$this->assertSame( array(), $last['summary']['rows'], 'The summary carries no rows' );
		$this->assertSame( array( array( $last['counts'], get_current_user_id() ) ), $fired, 'The action fires once with the counts and the user' );
		$this->assertFileDoesNotExist( $file );
		$this->assertNull( get_post( $attachment_id ), 'The attachment of the staged file is deleted' );
		$this->assertFalse(
			as_has_scheduled_action( ImportSession::CLEANUP_HOOK, array( get_current_user_id(), $session->token(), $file, $attachment_id ), 'woocommerce-fulfillments-importer' ),
			'The cleanup of the released file is unscheduled'
		);
		$this->assertTrue( ImportSession::load( get_current_user_id(), $session->token() )->completed(), 'The record of a completed import is kept' );
	}

	/**
	 * @testdox handle_run answers a repeated chunk from its record: same rows and counts, no second write or notification.
	 */
	public function test_run_answers_a_repeated_chunk_without_importing_it_again(): void {
		list( $session, $order_ids ) = $this->make_run_session( 2 );
		$options                     = array( 'options' => array( 'notify_customer' => true ) );

		$first    = $this->run_chunk( $session->token(), 0, 1, $options );
		$notified = did_action( 'woocommerce_fulfillment_created_notification' );
		$repeated = $this->run_chunk( $session->token(), 0, 1, $options );

		$this->assertSame( 1, $first['counts']['notified'], 'The option of the first chunk turns notifications on' );
		$this->assertSame( $first['processed'], $repeated['processed'], 'The repeated request reports the same progress' );
		$this->assertSame( $first['counts'], $repeated['counts'], 'The repeated request does not count the rows again' );
		$this->assertSame( $first['rows'], $repeated['rows'], 'The repeated request gets the row results of the first one' );
		$this->assertSame( OrderUtil::get_order_admin_edit_url( $order_ids[0] ), $repeated['rows'][0]['order_edit_url'] );
		$this->assertCount( 1, $this->fulfillments_of( $order_ids[0] ), 'The row is not written a second time' );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[1] ), 'The repeated request does not import the next chunk' );
		$this->assertSame( $notified, did_action( 'woocommerce_fulfillment_created_notification' ), 'The customer is not notified a second time' );
	}

	/**
	 * @testdox handle_run answers a repeated last chunk after the import completed, without firing the completed action again.
	 */
	public function test_run_answers_a_repeated_last_chunk_after_completion(): void {
		list( $session, ) = $this->make_run_session( 1 );

		$last     = $this->run_chunk( $session->token(), 0 );
		$repeated = $this->run_chunk( $session->token(), 0 );

		$this->assertTrue( $last['done'] );
		$this->assertTrue( $repeated['done'], 'The record of the completed import answers the repeated request' );
		$this->assertSame( $last['counts'], $repeated['counts'], 'The repeated request does not count the rows again' );
		$this->assertSame( $last['rows'], $repeated['rows'], 'The repeated request gets the row results of the last chunk' );
		$this->assertSame( $last['summary'], $repeated['summary'] );
		$this->assertSame( 1, did_action( 'woocommerce_fulfillments_csv_import_completed' ), 'The repeated request does not complete the import again' );
	}

	/**
	 * @testdox handle_run still answers the last chunk when a callback of the completed action throws.
	 */
	public function test_run_answers_the_last_chunk_when_a_completed_callback_throws(): void {
		list( $session, $order_ids ) = $this->make_run_session( 1 );
		add_action(
			'woocommerce_fulfillments_csv_import_completed',
			function () {
				throw new \Error( 'A listener failed after the import.' );
			}
		);

		$last = $this->run_chunk( $session->token(), 0 );

		$this->assertIsArray( $last, 'The rows are imported and recorded, so the request succeeds' );
		$this->assertTrue( $last['done'] );
		$this->assertCount( 1, $this->fulfillments_of( $order_ids[0] ) );
		$this->assertFalse( doing_action( 'woocommerce_fulfillments_csv_import_completed' ), 'The hook the callback threw from is taken off the stack' );
		$this->assertTrue( ImportSession::load( get_current_user_id(), $session->token() )->completed() );
	}

	/**
	 * @testdox handle_run loads the orders of a chunk in one query for their edit URLs, not one by one.
	 */
	public function test_run_response_does_not_query_each_order_of_the_chunk(): void {
		global $wpdb;
		list( $small, ) = $this->make_run_session( 2 );
		$this->run_chunk( $small->token(), 0 );
		wp_cache_flush();
		$before       = $wpdb->num_queries;
		$two_rows     = $this->run_chunk( $small->token(), 0 );
		$for_two_rows = $wpdb->num_queries - $before;

		list( $large, ) = $this->make_run_session( 8 );
		$this->run_chunk( $large->token(), 0 );
		wp_cache_flush();
		$before         = $wpdb->num_queries;
		$eight_rows     = $this->run_chunk( $large->token(), 0 );
		$for_eight_rows = $wpdb->num_queries - $before;

		$this->assertCount( 2, array_column( $two_rows['rows'], 'order_edit_url' ), 'The first measured request answers its two rows with edit URLs' );
		$this->assertCount( 8, array_column( $eight_rows['rows'], 'order_edit_url' ), 'The second measured request answers its eight rows with edit URLs' );
		$this->assertLessThan( 6, $for_eight_rows - $for_two_rows, 'Six more rows in the answered chunk must cost fewer than six more queries' );
	}

	/**
	 * @testdox handle_run answers 500 when the completed flag cannot be stored, and the repeated request completes the import.
	 */
	public function test_run_completes_on_a_repeated_request_when_the_first_could_not(): void {
		list( $session, $order_ids ) = $this->make_run_session( 1 );
		$file                        = $session->file();
		$lose_completed_write        = function ( $value, $old ) {
			return ! empty( $value['completed'] ) ? $old : $value;
		};
		$transient_filter            = 'pre_update_option__transient_wc_fulfillment_import_' . get_current_user_id() . '_' . $session->token();
		add_filter( $transient_filter, $lose_completed_write, 10, 2 );

		$last = $this->run_chunk( $session->token(), 0 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_progress_failed', 500, $last );
		$this->assertSame( 0, did_action( 'woocommerce_fulfillments_csv_import_completed' ), 'An import that is not marked completed does not fire the action' );
		$this->assertFileExists( $file, 'The file stays with its scheduled cleanup until the session is completed' );
		$this->assertCount( 1, $this->fulfillments_of( $order_ids[0] ), 'The row of the chunk is imported and recorded' );

		remove_filter( $transient_filter, $lose_completed_write, 10 );
		$repeated = $this->run_chunk( $session->token(), 0 );

		$this->assertTrue( $repeated['done'], 'The repeated request reports the end from the recorded chunk' );
		$this->assertSame( 1, $repeated['counts']['created'], 'The repeated request does not import the row again' );
		$this->assertSame( 1, did_action( 'woocommerce_fulfillments_csv_import_completed' ), 'The repeated request completes the import' );
		$this->assertFileDoesNotExist( $file );
	}

	/**
	 * @testdox handle_run imports nothing from a chunk when the file ends before the rows the prepare step counted.
	 */
	public function test_run_imports_nothing_when_the_file_ends_early(): void {
		$first_order  = $this->make_order();
		$second_order = $this->make_order();
		$file         = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n{$first_order},SHORT-1,ups\n{$second_order},SHORT-2,ups\n" );
		// A reader that stops early looks like a file with fewer rows than the session counted.
		$session = ImportSession::create( get_current_user_id(), $file, ',', array( 'order_number', 'tracking_number', 'shipment_provider' ), 3, false, true );

		$first  = $this->run_chunk( $session->token(), 0, 1 );
		$second = $this->run_chunk( $session->token(), 1, 1 );

		$this->assertIsArray( $first, 'A chunk with a row after it is a normal chunk' );
		$this->assertFalse( $first['done'] );
		$this->assert_run_error( 'woocommerce_fulfillments_import_chunk_failed', 500, $second );
		$this->assertSame( 'unexpected_eof', $second->get_error_data()['reason'] );
		$this->assertCount( 0, $this->fulfillments_of( $second_order ), 'The rows of a chunk that ended early are not written' );
		$this->assertSame( 1, ImportSession::load( get_current_user_id(), $session->token() )->processed(), 'The chunk that ended early is not recorded' );
		$this->assertFileExists( $file, 'The staged file is kept while rows are still expected' );
		$this->assertSame( 0, did_action( 'woocommerce_fulfillments_csv_import_completed' ) );
	}

	/**
	 * @testdox handle_run refuses an offset that is neither the recorded progress nor the last recorded chunk.
	 */
	public function test_run_rejects_an_offset_that_does_not_match_the_progress(): void {
		list( $session, $order_ids ) = $this->make_run_session( 3 );
		$this->run_chunk( $session->token(), 0, 1 );
		$this->run_chunk( $session->token(), 1, 1 );

		$ahead = $this->run_chunk( $session->token(), 3, 1 );
		$older = $this->run_chunk( $session->token(), 0, 1 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_offset_mismatch', 409, $ahead );
		$this->assert_run_error( 'woocommerce_fulfillments_import_offset_mismatch', 409, $older );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[2] ), 'A refused request imports nothing' );
		$this->assertSame( 2, ImportSession::load( get_current_user_id(), $session->token() )->processed() );

		$this->run_chunk( $session->token(), 2, 1 );
		$after_the_end = $this->run_chunk( $session->token(), 3, 1 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_offset_mismatch', 409, $after_the_end );
	}

	/**
	 * @testdox handle_run refuses a second request for the session while the first one is importing, and the first completes.
	 */
	public function test_run_refuses_a_second_request_while_the_first_is_importing(): void {
		list( $session, $order_ids ) = $this->make_run_session( 2 );
		$second                      = null;
		$sent                        = false;
		$lock_after_refusal          = null;
		add_filter(
			'woocommerce_fulfillments_csv_importer_resolve_order',
			function ( $order ) use ( &$second, &$sent, &$lock_after_refusal, $session ) {
				// Runs while the first request imports its rows under the lock.
				if ( ! $sent ) {
					$sent               = true;
					$second             = $this->run_chunk( $session->token(), 0 );
					$lock_after_refusal = $this->run_lock_value();
				}
				return $order;
			}
		);

		$first = $this->run_chunk( $session->token(), 0 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_chunk_in_progress', 409, $second );
		$this->assertTrue( $first['done'] );
		$this->assertSame( 2, $first['counts']['created'] );
		$this->assertCount( 1, $this->fulfillments_of( $order_ids[0] ), 'The refused request wrote nothing' );
		$this->assertCount( 1, $this->fulfillments_of( $order_ids[1] ) );
		$this->assertNotNull( $lock_after_refusal, 'The refused request does not release the lock of the first' );
		$this->assertNull( $this->run_lock_value(), 'The first request releases its lock at the end' );
	}

	/**
	 * @testdox handle_prepare keeps a cleanup scheduled for a replaced session's file it could not delete.
	 */
	public function test_prepare_keeps_a_cleanup_for_a_replaced_file_it_could_not_delete(): void {
		$old_file = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,OLD-1,ups\n" );
		$prior    = $this->make_session( $old_file );
		$upload   = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,NEW-1,ups\n" );
		// An empty path from this filter makes wp_delete_file() leave the file in place.
		add_filter( 'wp_delete_file', '__return_empty_string' );

		$response = $this->prepare_with_staged_file( $upload );
		// Removed before tearDown() deletes the fixtures through the same function.
		remove_filter( 'wp_delete_file', '__return_empty_string' );

		$this->assertIsArray( $response );
		$this->assertFileExists( $old_file, 'The file of the replaced session could not be deleted' );
		$this->assertIsInt(
			as_next_scheduled_action( ImportSession::CLEANUP_HOOK, array( get_current_user_id(), $prior->token(), $old_file, 0 ), 'woocommerce-fulfillments-importer' ),
			'A cleanup stays scheduled for the file that is left'
		);
	}

	/**
	 * @testdox handle_prepare is refused while a chunk is being imported, and keeps the session that is importing.
	 */
	public function test_prepare_is_refused_while_a_chunk_is_being_imported(): void {
		$importing = $this->make_session( $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,OLD-1,ups\n" ) );
		$upload    = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,NEW-1,ups\n" );
		$held      = time() + MINUTE_IN_SECONDS;
		$this->set_run_lock( $held );

		$response = $this->prepare_with_staged_file( $upload, $this->make_attachment( $upload ) );

		$this->assert_run_error( 'woocommerce_fulfillments_import_chunk_in_progress', 409, $response );
		$this->assertInstanceOf( ImportSession::class, ImportSession::load( get_current_user_id(), $importing->token() ) );
		$this->assertFileExists( $importing->file() );
		$this->assertFileDoesNotExist( $upload, 'The refused upload is not left behind' );
		$this->assertSame( (string) $held, $this->run_lock_value(), 'The lock of the importing request is left alone' );
	}

	/**
	 * @testdox handle_run takes over a lock that expired and releases it at the end.
	 */
	public function test_run_takes_over_an_expired_lock(): void {
		list( $session, ) = $this->make_run_session( 1 );
		$expired          = time() - 1;
		$this->set_run_lock( $expired );
		$this->assertSame( (string) $expired, $this->run_lock_value(), 'The expired lock is in place before the request' );

		$response = $this->run_chunk( $session->token(), 0 );

		$this->assertIsArray( $response );
		$this->assertTrue( $response['done'] );
		$this->assertNull( $this->run_lock_value(), 'The lock that was taken over is released like any other' );
	}

	/**
	 * @testdox handle_run does not record a chunk, or release the lock, after another request took the lock over.
	 */
	public function test_run_does_not_record_a_chunk_that_lost_its_lock(): void {
		list( $session, ) = $this->make_run_session( 1 );
		$taken_over       = time() + HOUR_IN_SECONDS;
		add_filter(
			'woocommerce_fulfillments_csv_importer_resolve_order',
			function ( $order ) use ( $taken_over ) {
				// The lock value changes under the importing request, as a takeover would do.
				$this->set_run_lock( $taken_over );
				return $order;
			}
		);

		$response = $this->run_chunk( $session->token(), 0 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_lock_lost', 500, $response );
		$this->assertSame( 0, ImportSession::load( get_current_user_id(), $session->token() )->processed(), 'The request that took the lock over records the chunk' );
		$this->assertSame( (string) $taken_over, $this->run_lock_value(), 'The lock of the other request is left alone' );
	}

	/**
	 * @testdox handle_run releases the lock when the request ends in an error or an exception.
	 */
	public function test_run_releases_the_lock_when_the_request_fails(): void {
		$this->assert_run_error( 'woocommerce_fulfillments_import_token_invalid', 400, $this->run_chunk( str_repeat( 'a', 32 ), 0 ) );
		$this->assertNull( $this->run_lock_value(), 'An error response releases the lock' );

		list( $session, ) = $this->make_run_session( 1 );
		$thrown           = new \RuntimeException( 'A callback failed during the import.' );
		add_filter(
			'woocommerce_fulfillments_csv_importer_chunk_size',
			function () use ( $thrown ) {
				throw $thrown;
			}
		);

		try {
			$this->run_chunk( $session->token(), 0 );
			$this->fail( 'The exception of the callback should reach the caller.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( $thrown, $e, 'The request ended in the exception of the callback' );
			$this->assertNull( $this->run_lock_value(), 'An exception releases the lock' );
		}
	}

	/**
	 * @testdox handle_run refuses the token of another user's session.
	 */
	public function test_run_rejects_the_token_of_another_user(): void {
		list( $session, $order_ids ) = $this->make_run_session( 1 );
		$other_admin                 = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $other_admin );

		$response = $this->run_chunk( $session->token(), 0 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_token_invalid', 400, $response );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[0] ) );
	}

	/**
	 * @testdox handle_run ends the session and removes the staged file when the file is no longer the one that was prepared.
	 *
	 * @testWith ["grown"]
	 *           ["touched"]
	 *           ["rewritten"]
	 *           ["deleted"]
	 *
	 * @param string $change What happens to the staged file before the request.
	 */
	public function test_run_ends_the_session_when_the_staged_file_changed( string $change ): void {
		// Larger than the hashed head of the file, so each case is caught by one check only.
		$file    = $this->make_staged_file( $this->csv_with_rows( 400 ) );
		$session = $this->make_session( $file, $this->make_attachment( $file ) );
		$this->assertGreaterThan( 4096, $session->file_size() );
		if ( 'grown' === $change ) {
			// Same head and modification time, one more row at the end.
			file_put_contents( $file, "1,EXTRA-1,ups\n", FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
			touch( $file, $session->file_mtime() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test fixture write.
		} elseif ( 'touched' === $change ) {
			touch( $file, $session->file_mtime() + 10 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test fixture write.
		} elseif ( 'rewritten' === $change ) {
			// Same size and modification time, different head.
			file_put_contents( $file, strtoupper( (string) file_get_contents( $file ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Test fixture write.
			touch( $file, $session->file_mtime() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test fixture write.
		} else {
			wp_delete_file( $file );
		}

		$response = $this->run_chunk( $session->token(), 0 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_file_changed', 409, $response );
		$this->assertNull( ImportSession::load( get_current_user_id(), $session->token() ), 'The session of a changed file is ended' );
		$this->assertFileDoesNotExist( $file );
		$this->assertNull( get_post( $session->attachment_id() ), 'The attachment of the staged file is deleted' );
	}

	/**
	 * @testdox handle_run refuses a mapping the import cannot run with and stores nothing.
	 *
	 * @testWith [{"0": "order_number", "1": "tracking_number"}, "shipment_provider"]
	 *           [{"0": "order_number", "1": "tracking_number", "2": "shipment_provider", "3": "tracking_url"}, "column 3"]
	 *           [{"0": "order_number", "1": "order_number", "2": "shipment_provider"}, "order_number to more than one"]
	 *           [{"0": "order_number", "1": "tracking_number", "01": "tracking_url", "2": "shipment_provider"}, "column 01"]
	 *
	 * @param array<string, string> $mapping Mapping to send.
	 * @param string                $reason  Text the error message names.
	 */
	public function test_run_rejects_a_mapping_it_cannot_import_with( array $mapping, string $reason ): void {
		list( $session, $order_ids ) = $this->make_run_session( 1 );

		$response = $this->run_chunk( $session->token(), 0, null, array( 'mapping' => $mapping ) );

		$this->assert_run_error( 'woocommerce_fulfillments_import_mapping_invalid', 400, $response );
		$this->assertStringContainsString( $reason, $response->get_error_message() );
		$this->assertNull( ImportSession::load( get_current_user_id(), $session->token() )->frozen_mapping() );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[0] ) );
	}

	/**
	 * @testdox handle_run imports every chunk under the mapping and options of the first one.
	 */
	public function test_run_keeps_the_settings_of_the_first_chunk(): void {
		list( $session, $order_ids ) = $this->make_run_session( 2 );

		$this->run_chunk( $session->token(), 0, 1, array( 'options' => array( 'update_existing' => false ) ) );
		$second = $this->run_chunk(
			$session->token(),
			1,
			1,
			array(
				'mapping' => array(
					'0' => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
					'1' => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				),
				'options' => array(
					'update_existing' => true,
					'notify_customer' => true,
				),
			)
		);

		$stored = ImportSession::load( get_current_user_id(), $session->token() );
		$this->assertIsArray( $second, 'A mapping that would be refused on the first chunk is not read on a later one' );
		$this->assertSame( FulfillmentsCsvImporter::STATUS_CREATED, $second['rows'][0]['status'], 'The swapped mapping of the second request is ignored' );
		$this->assertSame( $order_ids[1], $second['rows'][0]['order_id'] );
		$this->assertFalse( $stored->update_existing(), 'The option of the first chunk is kept' );
		$this->assertFalse( $stored->notify_customer(), 'The option of a later chunk is not read' );
		$this->assertSame( 0, $second['counts']['notified'] );
	}

	/**
	 * @testdox handle_run keeps the options of the prepare step when the first chunk sends none.
	 */
	public function test_run_keeps_the_prepare_options_the_first_chunk_leaves_out(): void {
		list( $session, ) = $this->make_run_session( 1, false, true, false );

		$response = $this->run_chunk( $session->token(), 0 );

		$stored = ImportSession::load( get_current_user_id(), $session->token() );
		$this->assertTrue( $stored->notify_customer(), 'notify_customer keeps the value of the prepare step' );
		$this->assertFalse( $stored->update_existing(), 'update_existing keeps the value of the prepare step' );
		$this->assertSame( 1, $response['counts']['notified'], 'The row is imported under the kept options' );
	}

	/**
	 * @testdox handle_run uses the store's chunk size when the limit is left out and a smaller limit when one is sent.
	 */
	public function test_run_passes_the_limit_to_the_importer(): void {
		list( $session, ) = $this->make_run_session( 4 );
		add_filter(
			'woocommerce_fulfillments_csv_importer_chunk_size',
			function () {
				return 2;
			}
		);

		$without_limit = $this->run_chunk( $session->token(), 0 );
		$with_limit    = $this->run_chunk( $session->token(), 2, 1 );

		$this->assertSame( 2, $without_limit['processed'] );
		$this->assertSame( 3, $with_limit['processed'] );
	}

	/**
	 * @testdox handle_run and handle_prepare answer 500 and change nothing when the lock cannot be taken because of a database error.
	 */
	public function test_run_and_prepare_fail_when_the_lock_query_fails(): void {
		global $wpdb;
		list( $session, $order_ids ) = $this->make_run_session( 1 );
		$upload                      = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,NEW-1,ups\n" );
		add_filter(
			'query',
			function ( $query ) {
				return false !== strpos( $query, 'woocommerce_fulfillments_import_lock_' ) ? 'SELECT 1 FROM wc_no_such_table' : $query;
			}
		);
		$suppressed = $wpdb->suppress_errors( true );

		$run     = $this->run_chunk( $session->token(), 0 );
		$prepare = $this->prepare_with_staged_file( $upload );

		$wpdb->suppress_errors( $suppressed );
		$this->assert_run_error( 'woocommerce_fulfillments_import_lock_failed', 500, $run );
		$this->assert_run_error( 'woocommerce_fulfillments_import_lock_failed', 500, $prepare );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[0] ), 'A request without the lock imports nothing' );
		$this->assertInstanceOf( ImportSession::class, ImportSession::load( get_current_user_id(), $session->token() ), 'The session is not replaced without the lock' );
		$this->assertFileDoesNotExist( $upload, 'The refused upload is not left behind' );
	}

	/**
	 * @testdox handle_run imports nothing when the settings of the first chunk cannot be stored.
	 */
	public function test_run_imports_nothing_when_the_settings_cannot_be_saved(): void {
		list( $session, $order_ids ) = $this->make_run_session( 1 );
		add_filter(
			'pre_update_option__transient_wc_fulfillment_import_' . get_current_user_id() . '_' . $session->token(),
			function ( $value, $old ) {
				unset( $value );
				return $old;
			},
			10,
			2
		);

		$response = $this->run_chunk( $session->token(), 0 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_progress_failed', 500, $response );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[0] ), 'No row is imported under settings that are not stored' );
		$this->assertNull( ImportSession::load( get_current_user_id(), $session->token() )->frozen_mapping() );
		$this->assertNull( $this->run_lock_value(), 'The lock is released after the failure' );
	}

	/**
	 * @testdox handle_run imports nothing when the session expired right after it was loaded.
	 */
	public function test_run_imports_nothing_when_the_session_expired_after_it_was_loaded(): void {
		list( $session, $order_ids ) = $this->make_run_session( 2 );
		$this->run_chunk( $session->token(), 0, 1 );
		$transient = 'wc_fulfillment_import_' . get_current_user_id() . '_' . $session->token();
		$expired   = false;
		// The first read is the one that loads the session; the record is gone for the next one.
		add_filter(
			'transient_' . $transient,
			function ( $value ) use ( &$expired, $transient ) {
				if ( ! $expired ) {
					$expired = true;
					delete_transient( $transient );
				}
				return $value;
			}
		);

		$response = $this->run_chunk( $session->token(), 1, 1 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_token_invalid', 400, $response );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[1] ), 'No row is written for a session that cannot record it' );
	}

	/**
	 * @testdox handle_run checks the offset against the progress it imports from, not against a copy loaded earlier.
	 */
	public function test_run_does_not_import_from_progress_another_request_recorded(): void {
		list( $session, $order_ids ) = $this->make_run_session( 2 );
		$transient                   = 'wc_fulfillment_import_' . get_current_user_id() . '_' . $session->token();
		$recorded                    = false;
		// While this request holds its copy, another one stores the settings and records the first row.
		add_filter(
			'transient_' . $transient,
			function ( $value ) use ( &$recorded, $session ) {
				if ( ! $recorded ) {
					$recorded = true;
					$other    = ImportSession::load( get_current_user_id(), $session->token() );
					$other->freeze_run_settings( array_values( self::RUN_MAPPING ), false, true );
					$other->record_chunk(
						0,
						array(
							'consumed'    => 1,
							'byte_offset' => 60,
							'rows'        => array(
								array(
									'row'    => 2,
									'status' => 'created',
								),
							),
						)
					);
				}
				return $value;
			}
		);

		$response = $this->run_chunk( $session->token(), 0, 1 );

		$this->assertIsArray( $response, 'The offset is the one of the chunk the other request recorded, so its record answers' );
		$this->assertSame( 1, $response['processed'] );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[0] ), 'This request imports nothing for an offset that is already recorded' );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[1] ), 'This request does not import the next row under the old offset' );
	}

	/**
	 * @testdox handle_run keeps a cleanup scheduled for a changed staged file it could not delete.
	 */
	public function test_run_keeps_a_cleanup_for_a_changed_file_it_could_not_delete(): void {
		$file    = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n1,CHG-1,ups\n" );
		$session = $this->make_session( $file );
		touch( $file, $session->file_mtime() + 10 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test fixture write.
		// An empty path from this filter makes wp_delete_file() leave the file in place.
		add_filter( 'wp_delete_file', '__return_empty_string' );

		$response = $this->run_chunk( $session->token(), 0 );
		// Removed before tearDown() deletes the fixture through the same function.
		remove_filter( 'wp_delete_file', '__return_empty_string' );

		$this->assert_run_error( 'woocommerce_fulfillments_import_file_changed', 409, $response );
		$this->assertNull( ImportSession::load( get_current_user_id(), $session->token() ), 'The session is ended either way' );
		$this->assertFileExists( $file );
		$this->assertIsInt(
			as_next_scheduled_action( ImportSession::CLEANUP_HOOK, array( get_current_user_id(), $session->token(), $file, 0 ), 'woocommerce-fulfillments-importer' ),
			'A cleanup stays scheduled for the file that is left'
		);
	}

	/**
	 * @testdox handle_run releases its lock in the options table it took it from, whatever a callback leaves behind.
	 */
	public function test_run_releases_the_lock_in_the_table_it_was_taken_from(): void {
		global $wpdb;
		list( $session, ) = $this->make_run_session( 1 );
		$options_table    = $wpdb->options;
		// A callback that switched to another site and did not switch back leaves another options table.
		add_action(
			'woocommerce_fulfillments_csv_import_completed',
			function () use ( $wpdb ) {
				$wpdb->options = $wpdb->prefix . 'options_of_another_site';
			}
		);
		$suppressed = $wpdb->suppress_errors( true );

		$this->run_chunk( $session->token(), 0 );

		$wpdb->options = $options_table;
		$wpdb->suppress_errors( $suppressed );
		$this->assertNull( $this->run_lock_value(), 'The lock is released on the site that holds it' );
	}

	/**
	 * @testdox handle_run keeps the session when the importer cannot read the chunk.
	 */
	public function test_run_keeps_the_session_when_the_importer_reports_an_error(): void {
		// The prepare step refuses CR-only line endings, so only a session opened by hand has such a file.
		$session = $this->make_session( $this->make_staged_file( "order_number,tracking_number,shipment_provider\r1,CR-1,ups\r" ) );

		$response = $this->run_chunk( $session->token(), 0 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_chunk_failed', 500, $response );
		$this->assertSame( 'cr_line_endings', $response->get_error_data()['reason'] );
		$this->assertInstanceOf( ImportSession::class, ImportSession::load( get_current_user_id(), $session->token() ) );
		$this->assertFileExists( $session->file() );
	}

	/**
	 * @testdox handle_run reports progress it could not save, and the repeated chunk skips the row it already wrote.
	 */
	public function test_run_reports_progress_that_could_not_be_saved(): void {
		list( $session, $order_ids ) = $this->make_run_session( 2 );
		$this->run_chunk( $session->token(), 0, 1 );
		$lose_write       = function ( $value, $old ) {
			unset( $value );
			return $old;
		};
		$transient_filter = 'pre_update_option__transient_wc_fulfillment_import_' . get_current_user_id() . '_' . $session->token();
		add_filter( $transient_filter, $lose_write, 10, 2 );

		$failed = $this->run_chunk( $session->token(), 1, 1 );

		$this->assert_run_error( 'woocommerce_fulfillments_import_progress_failed', 500, $failed );
		$this->assertCount( 1, $this->fulfillments_of( $order_ids[1] ), 'The row was written before its progress could not be saved' );

		remove_filter( $transient_filter, $lose_write, 10 );
		$repeated = $this->run_chunk( $session->token(), 1, 1 );

		$this->assertSame( FulfillmentsCsvImporter::STATUS_SKIPPED, $repeated['rows'][0]['status'] );
		$this->assertCount( 1, $this->fulfillments_of( $order_ids[1] ), 'The repeated chunk does not write the row again' );
		$this->assertTrue( $repeated['done'] );
	}

	/**
	 * @testdox The run route imports a chunk when dispatched through the REST server, with a mapping that skips a column.
	 */
	public function test_rest_run_route_imports_a_chunk(): void {
		$order_id = $this->make_order();
		$file     = $this->make_staged_file( "order_number,note,tracking_number,shipment_provider\n{$order_id},fragile,REST-1,ups\n" );
		$session  = ImportSession::create( get_current_user_id(), $file, ',', array( 'order_number', 'note', 'tracking_number', 'shipment_provider' ), 1, false, true );
		$request  = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			'{"token":"' . $session->token() . '","offset":0,"mapping":{"0":"order_number","2":"tracking_number","3":"shipment_provider"},"options":{"notify_customer":false}}'
		);

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['done'] );
		$stored = $this->fulfillments_of( $order_id );
		$this->assertCount( 1, $stored );
		$this->assertSame( 'REST-1', $stored[0]->get_tracking_number(), 'The skipped column does not shift the mapping' );
	}

	/**
	 * @testdox The run route rejects arguments outside its schema with a 400.
	 *
	 * @testWith [{"token": "not-a-token"}]
	 *           [{"offset": -1}]
	 *           [{"limit": 0}]
	 *           [{"limit": 1001}]
	 *           [{"mapping": {"first": "order_number"}}]
	 *           [{"mapping": {"0": "order_id"}}]
	 *           [{"options": {"dry_run": true}}]
	 *           [{"options": {"notify_customer": "sometimes"}}]
	 *
	 * @param array<string, mixed> $invalid Argument that replaces a valid one.
	 */
	public function test_rest_run_route_rejects_invalid_arguments( array $invalid ): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			(string) wp_json_encode(
				array_merge(
					array(
						'token'   => str_repeat( 'a', 32 ),
						'mapping' => self::RUN_MAPPING,
					),
					$invalid
				)
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertSame( array( key( $invalid ) ), array_keys( $response->get_data()['data']['params'] ) );
	}

	/**
	 * @testdox The run route returns 403 for a user without manage_woocommerce, even for that user's own session.
	 */
	public function test_rest_run_route_requires_manage_woocommerce(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		list( $session, $order_ids ) = $this->make_run_session( 1 );
		$request                     = new WP_REST_Request( 'POST', '/wc/v3/fulfillments/import/run' );
		$request->set_body_params(
			array(
				'token'   => $session->token(),
				'mapping' => self::RUN_MAPPING,
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertCount( 0, $this->fulfillments_of( $order_ids[0] ), 'Only the capability check stands between this user and the import' );
	}

	/**
	 * @testdox A file goes from handle_prepare through handle_run to the end, and a new prepare then replaces the completed session.
	 */
	public function test_prepare_then_run_imports_the_file_and_the_next_prepare_replaces_it(): void {
		$first_order  = $this->make_order();
		$second_order = $this->make_order();
		$file         = $this->make_staged_file( "Order ID,Tracking Number,Carrier\n{$first_order},JOINT-1,ups\n\n{$second_order},JOINT-2,ups\n" );

		$prepared = $this->prepare_with_staged_file( $file, $this->make_attachment( $file ) );
		$offset   = 0;
		$requests = 0;
		do {
			$chunk  = $this->run_chunk( $prepared['token'], $offset, 1, array( 'mapping' => (array) $prepared['detected_mapping'] ) );
			$offset = $chunk['processed'];
		} while ( ! $chunk['done'] && ++$requests < 5 );

		$this->assertTrue( $chunk['done'], 'The import ends within the expected number of requests' );
		$this->assertSame( $prepared['total'], $chunk['processed'], 'The run processes exactly the rows the prepare step counted' );
		$this->assertSame( 2, $chunk['counts']['created'] );
		$this->assertSame( 'JOINT-2', $this->fulfillments_of( $second_order )[0]->get_tracking_number() );
		$this->assertNull( $this->run_lock_value(), 'No lock is left after the import' );

		$next_file = $this->make_staged_file( "order_number,tracking_number,shipment_provider\n{$first_order},NEXT-1,ups\n" );
		$next      = $this->prepare_with_staged_file( $next_file );

		$this->assertIsArray( $next, 'A prepare after a completed import is accepted although the old staged file is already gone' );
		$this->assertSame( $next['token'], ImportSession::active_for_user( get_current_user_id() )->token() );
		$this->assert_run_error( 'woocommerce_fulfillments_import_token_invalid', 400, $this->run_chunk( $prepared['token'], 1, 1 ) );
	}
}
