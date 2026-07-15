<?php

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Approved_Directories;

/**
 * Class WC_Download_Handler_Tests.
 */
class WC_Download_Handler_Tests extends \WC_Unit_Test_Case {

	/**
	 * Test for local file path.
	 */
	public function test_parse_file_path_for_local_file() {
		$local_file_path  = trailingslashit( wp_upload_dir()['basedir'] ) . 'dummy_file.jpg';
		$parsed_file_path = WC_Download_Handler::parse_file_path( $local_file_path );
		$this->assertFalse( $parsed_file_path['remote_file'] );
	}

	/**
	 * Test for local URL without protocol.
	 */
	public function test_parse_file_path_for_local_url() {
		$local_file_path  = trailingslashit( wp_upload_dir()['baseurl'] ) . 'dummy_file.jpg';
		$parsed_file_path = WC_Download_Handler::parse_file_path( $local_file_path );
		$this->assertFalse( $parsed_file_path['remote_file'] );
	}

	/**
	 * Test for local file with `file` protocol.
	 */
	public function test_parse_file_path_for_local_file_protocol() {
		$local_file_path  = 'file:/' . trailingslashit( wp_upload_dir()['basedir'] ) . 'dummy_file.jpg';
		$parsed_file_path = WC_Download_Handler::parse_file_path( $local_file_path );
		$this->assertFalse( $parsed_file_path['remote_file'] );
	}

	/**
	 * Test for local file with https protocom.
	 */
	public function test_parse_file_path_for_local_file_https_protocol() {
		$local_file_path  = site_url( '/', 'https' ) . 'dummy_file.jpg';
		$parsed_file_path = WC_Download_Handler::parse_file_path( $local_file_path );
		$this->assertFalse( $parsed_file_path['remote_file'] );
	}

	/**
	 * Test for remote file.
	 */
	public function test_parse_file_path_for_remote_file() {
		$remote_file_path = 'https://dummy.woo.com/dummy_file.jpg';
		$parsed_file_path = WC_Download_Handler::parse_file_path( $remote_file_path );
		$this->assertTrue( $parsed_file_path['remote_file'] );
	}

	/**
	 * @testdox Customers may not use a direct download link to obtain a downloadable file that has been disabled.
	 */
	public function test_inactive_downloads_will_not_be_served() {
		self::remove_download_handlers();
		$downloads_served = 0;

		$download_counter = function () use ( &$downloads_served ) {
			$downloads_served++;
		};

		// Track downloads served.
		add_action( 'woocommerce_download_file_force', $download_counter );

		/**
		 * @var Approved_Directories $approved_directories
		 */
		$approved_directories = wc_get_container()->get( Approved_Directories::class );
		$approved_directories->set_mode( Approved_Directories::MODE_ENABLED );
		$approved_directories->add_approved_directory( 'https://always.trusted' );
		$approved_directory_rule_id = $approved_directories->add_approved_directory( 'https://new.supplier' );

		list( $product, $order ) = $this->build_downloadable_product_and_order_one(
			array(
				array(
					'name' => 'Book 1',
					'file' => 'https://always.trusted/123.pdf',
				),
				array(
					'name' => 'Book 2',
					'file' => 'https://new.supplier/456.pdf',
				),
			)
		);

		$email         = 'admin@example.org';
		$product_id    = $product->get_id();
		$downloads     = $product->get_downloads();
		$download_keys = array_keys( $downloads );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$_GET = array(
			'download_file' => $product_id,
			'order'         => $order->get_order_key(),
			'email'         => $email,
			'uid'           => hash( 'sha256', $email ),
			'key'           => $download_keys[0],
		);

		// With both the corresponding approved directory rules enabled...
		WC_Download_Handler::download_product();
		$this->assertEquals( 1, $downloads_served, 'Can successfully download "Book 1".' );

		$_GET['key'] = $download_keys[1];
		WC_Download_Handler::download_product();
		$this->assertEquals( 2, $downloads_served, 'Can successfully download "Book 2".' );

		// And now with one of the approved directory rules disabled...
		$approved_directories->disable_by_id( $approved_directory_rule_id );

		// Approved Download Directory rule changes don't invalidate the product object cache, so
		// flush to force a fresh read that reflects the updated rules.
		wp_cache_flush();

		$_GET['key']     = $download_keys[1];
		$wp_die_happened = false;

		// We do not use expectException() here because we wish to continue testing after wp_die() has
		// been triggered inside WC_Download_Handler::download_error().
		try {
			WC_Download_Handler::download_product();
		} catch ( WPDieException $e ) {
			$wp_die_happened = true;
		}

		$this->assertTrue( $wp_die_happened );
		$this->assertEquals( 2, $downloads_served, 'Downloading "Book 2" failed after the corresponding approved directory rule was disabled.' );

		$_GET['key'] = $download_keys[0];
		WC_Download_Handler::download_product();
		$this->assertEquals( 3, $downloads_served, 'Continued to be able to download "Book 1" (the corresponding rule never having been disabled.' );

		// Cleanup.
		add_action( 'woocommerce_download_file_force', $download_counter );
		self::restore_download_handlers();
	}

	/**
	 * @testdox The remaining downloads count should iterate accurately.
	 */
	public function test_downloads_remaining_count(): void {
		self::remove_download_handlers();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Ok for unit tests.
		file_put_contents( WP_CONTENT_DIR . '/uploads/woocommerce_uploads/supersheet-123.ods', str_pad( '', 100 ) );

		list( $product, $order ) = $this->build_downloadable_product_and_order_one(
			array(
				array(
					'name' => 'Supersheet 123',
					'file' => content_url( 'uploads/woocommerce_uploads/supersheet-123.ods' ),
				),
			)
		);

		$product_id    = $product->get_id();
		$downloads     = $product->get_downloads();
		$download_keys = array_keys( $downloads );
		$email         = 'admin@example.org';
		$download      = current( WC_Data_Store::load( 'customer-download' )->get_downloads( array( 'product_id' => $product_id ) ) );

		$download->set_downloads_remaining( 10 );
		$download->save();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$_GET = array(
			'download_file' => $product_id,
			'order'         => $order->get_order_key(),
			'email'         => $email,
			'uid'           => hash( 'sha256', $email ),
			'key'           => $download_keys[0],
		);

		WC_Download_Handler::download_product();
		$download = new WC_Customer_Download( $download->get_id() );
		$this->assertEquals(
			9,
			$download->get_downloads_remaining(),
			'In relation to "normal" download requests, we should see a reduction in the downloads remaining count.'
		);

		// Let's simulate a ranged request (partial download).
		$_SERVER['HTTP_RANGE'] = 'bytes=10-50';
		WC_Download_Handler::download_product();
		$download = new WC_Customer_Download( $download->get_id() );
		$this->assertEquals(
			9,
			$download->get_downloads_remaining(),
			'In relation to "ranged" (partial) download requests, we should not see an immediate reduction in the downloads remaining count.'
		);

		// Repeat (HTTP_RANGE is still set).
		WC_Download_Handler::download_product();
		$download = new WC_Customer_Download( $download->get_id() );
		$this->assertEquals(
			9,
			$download->get_downloads_remaining(),
			'In relation to "ranged" (partial) download requests, we should not see an immediate reduction in the downloads remaining count.'
		);

		// Find the deferred download tracking action.
		$deferred_download_tracker = current(
			WC_Queue::instance()->search(
				array( 'hook' => WC_Download_Handler::TRACK_DOWNLOAD_CALLBACK )
			)
		);

		// Let it do it's thing, and confirm that a further decrement happened (of just 1 unit).
		do_action_ref_array( $deferred_download_tracker->get_hook(), $deferred_download_tracker->get_args() );
		$download = new WC_Customer_Download( $download->get_id() );
		$this->assertEquals(
			8,
			$download->get_downloads_remaining(),
			'In relation to "ranged" (partial) download requests, the deferred update to the downloads remaining count functioned as expected.'
		);

		self::restore_download_handlers();
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should keep the URL-derived filename when it already has an extension.
	 */
	public function test_resolve_filename_keeps_filename_with_extension(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename="remote-name.pdf"',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'local-name.zip' );

		$this->assertSame( 'local-name.zip', $resolved, 'A filename that already has an extension should not be overridden.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should use the Content-Disposition filename when the URL-derived filename has no extension.
	 */
	public function test_resolve_filename_uses_content_disposition_filename(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Type: application/pdf',
			'Content-Disposition: attachment; filename="My Report.pdf"',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'uc' );

		$this->assertSame( 'My-Report.pdf', $resolved, 'The sanitized Content-Disposition filename should be used.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should parse the unquoted token form of the filename parameter.
	 */
	public function test_resolve_filename_parses_bare_token_filename(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename=Hello-World-master.zip',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'master' );

		$this->assertSame( 'Hello-World-master.zip', $resolved, 'The unquoted filename token form should be parsed.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should prefer the RFC 5987 filename* parameter and percent-decode it.
	 */
	public function test_resolve_filename_prefers_rfc5987_filename(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename="fallback.pdf"; filename*=UTF-8\'\'My%20Report.pdf',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'uc' );

		$this->assertSame( 'My-Report.pdf', $resolved, 'The RFC 5987 filename* parameter should win over the plain filename parameter.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should handle the non-standard quoted form of the filename* parameter.
	 */
	public function test_resolve_filename_handles_quoted_rfc5987_filename(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename*="UTF-8\'\'My%20Report.pdf"',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'uc' );

		$this->assertSame( 'My-Report.pdf', $resolved, 'The quoted filename* form emitted by some non-conforming servers should be parsed too.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should use the headers of the last response in a redirect chain.
	 */
	public function test_resolve_filename_uses_last_response_of_redirect_chain(): void {
		$headers = array(
			'HTTP/1.1 302 Found',
			'Location: https://cdn.example.com/get',
			'Content-Disposition: attachment; filename="wrong.pdf"',
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename="right.pdf"',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'uc' );

		$this->assertSame( 'right.pdf', $resolved, 'Only the final response of the redirect chain should be considered.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should match header names and disposition parameters case-insensitively.
	 */
	public function test_resolve_filename_is_case_insensitive(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'CONTENT-DISPOSITION: Attachment; FILENAME="Report.PDF"',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'uc' );

		$this->assertSame( 'Report.PDF', $resolved, 'Header and parameter matching should be case-insensitive.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should fall back to an extension derived from the Content-Type header when no Content-Disposition filename is available.
	 */
	public function test_resolve_filename_falls_back_to_content_type_extension(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Type: application/pdf; charset=binary',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'uc' );

		$this->assertSame( 'uc.pdf', $resolved, 'The extension should be derived from the Content-Type header.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should return the original filename when the response headers contain nothing usable.
	 */
	public function test_resolve_filename_returns_original_when_headers_unusable(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Type: application/octet-stream',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'uc' );

		$this->assertSame( 'uc', $resolved, 'The original filename should be kept when the headers provide no better information.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should sanitize characters that are unsafe in a filename or header value.
	 */
	public function test_resolve_filename_sanitizes_unsafe_characters(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename*=UTF-8\'\'..%2F..%2Fevil%22name.pdf',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'uc' );

		$this->assertSame( 'evilname.pdf', $resolved, 'Path traversal sequences and quotes should be stripped from the filename.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should only append the remote extension to a filename marked as preserved.
	 */
	public function test_resolve_filename_preserves_customized_filename(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename="My Report.pdf"',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'Seasons-Catalog', true );

		$this->assertSame( 'Seasons-Catalog.pdf', $resolved, 'A preserved filename should keep its name and only gain the remote extension.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should complete a preserved filename from the Content-Type header when the Content-Disposition filename is absent.
	 */
	public function test_resolve_filename_preserves_customized_filename_with_content_type_fallback(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Type: application/zip',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'Seasons-Catalog', true );

		$this->assertSame( 'Seasons-Catalog.zip', $resolved, 'A preserved filename should gain the extension derived from the Content-Type header.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should leave a preserved filename untouched when the remote filename has no extension either.
	 */
	public function test_resolve_filename_preserved_filename_untouched_without_remote_extension(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename="report"',
		);

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, 'Seasons-Catalog', true );

		$this->assertSame( 'Seasons-Catalog', $resolved, 'An extensionless remote filename provides nothing to complete a preserved filename with.' );
	}

	/**
	 * @testdox download_file_force() should render an error page, not a download, when the remote file cannot be opened.
	 */
	public function test_download_file_force_shows_error_when_remote_file_cannot_be_opened(): void {
		update_option( 'woocommerce_downloads_redirect_fallback_allowed', 'no' );

		$this->expectException( WPDieException::class );
		$this->expectExceptionMessageMatches( '/File not found/' );

		// Port 1 is never listening, so the remote open fails immediately.
		WC_Download_Handler::download_file_force( 'http://127.0.0.1:1/missing-file', 'missing-file' );
	}

	/**
	 * @testdox The Content-Type fallback to the resolved filename should apply to remote files only.
	 */
	public function test_content_type_fallback_applies_only_to_remote_files(): void {
		$method = new ReflectionMethod( WC_Download_Handler::class, 'get_content_type_for_served_download' );
		$method->setAccessible( true );

		$this->assertSame(
			'application/pdf',
			$method->invoke( null, 'https://drive.google.com/uc', 'My-Report.pdf', true ),
			'For remote files the Content-Type should fall back to the resolved filename.'
		);
		$this->assertSame(
			'application/force-download',
			$method->invoke( null, '/some/local/file', 'My-Report.pdf', false ),
			'For local files the Content-Type should be derived from the file path only.'
		);
	}

	/**
	 * @testdox The Content-Type fallback should not serve types browsers may render inline, since the extension can come from the remote server.
	 */
	public function test_content_type_fallback_rejects_renderable_types(): void {
		// Only users with unfiltered_html have text/html in their allowed mime types at all.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$method = new ReflectionMethod( WC_Download_Handler::class, 'get_content_type_for_served_download' );
		$method->setAccessible( true );

		$this->assertSame(
			'application/force-download',
			$method->invoke( null, 'https://evil.example.com/uc', 'payload.html', true ),
			'A remote-derived .html filename should not switch the response to text/html.'
		);
	}

	/**
	 * Creates a downloadable product, and then places (and completes) an order for that
	 * object.
	 *
	 * @param array[] $downloadable_files Array of arrays, with each inner array specifying the 'name' and 'file'.
	 *
	 * @return array {
	 *     WC_Product,
	 *     WC_Order
	 * }
	 */
	private function build_downloadable_product_and_order_one( array $downloadable_files ): array {
		$product  = WC_Helper_Product::create_downloadable_product( $downloadable_files );
		$customer = WC_Helper_Customer::create_customer();
		$order    = WC_Helper_Order::create_order( $customer->get_id(), $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		return array(
			$product,
			$order,
		);
	}

	/**
	 * Unregister download handlers to prevent unwanted output and side-effects.
	 */
	private static function remove_download_handlers() {
		remove_action( 'woocommerce_download_file_xsendfile', array( WC_Download_Handler::class, 'download_file_xsendfile' ) );
		remove_action( 'woocommerce_download_file_redirect', array( WC_Download_Handler::class, 'download_file_redirect' ) );
		remove_action( 'woocommerce_download_file_force', array( WC_Download_Handler::class, 'download_file_force' ) );
	}

	/**
	 * Restores download handlers in case needed by other tests.
	 */
	private static function restore_download_handlers() {
		add_action( 'woocommerce_download_file_redirect', array( WC_Download_Handler::class, 'download_file_redirect' ), 10, 2 );
		add_action( 'woocommerce_download_file_xsendfile', array( WC_Download_Handler::class, 'download_file_xsendfile' ), 10, 2 );
		add_action( 'woocommerce_download_file_force', array( WC_Download_Handler::class, 'download_file_force' ), 10, 2 );
	}
}
