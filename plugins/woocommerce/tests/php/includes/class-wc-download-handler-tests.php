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
	 * @testdox Encoded spaces in a local URL resolve to an existing file with spaces in its name.
	 */
	public function test_parse_file_path_for_encoded_space_in_existing_file(): void {
		$uploads       = wp_upload_dir();
		$filename      = 'wc download ' . wp_generate_uuid4() . '.pdf';
		$absolute_path = trailingslashit( $uploads['basedir'] ) . $filename;
		$file_url      = trailingslashit( $uploads['baseurl'] ) . rawurlencode( $filename );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture in the uploads directory.
		$this->assertNotFalse( file_put_contents( $absolute_path, 'download fixture' ) );

		try {
			$parsed_file_path = WC_Download_Handler::parse_file_path( $file_url );
			$this->assertFalse( $parsed_file_path['remote_file'] );
			$this->assertSame( $absolute_path, $parsed_file_path['file_path'] );
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove test fixtures from the uploads directory.
			unlink( $absolute_path );
		}
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
	 * @testdox resolve_filename_from_response_headers() should tolerate a null filename, as produced by a broken `woocommerce_file_download_filename` filter callback, instead of throwing a TypeError.
	 */
	public function test_resolve_filename_tolerates_null_filename(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename="report.pdf"',
		);
		$this->setExpectedIncorrectUsage( 'WC_Download_Handler::resolve_filename_from_response_headers' );

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, null, true );

		$this->assertSame( 'report.pdf', $resolved, 'A null filename cannot be preserved, so the remote-announced filename should be used.' );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should treat a non-scalar filename as empty instead of throwing a TypeError.
	 */
	public function test_resolve_filename_tolerates_non_scalar_filename(): void {
		$incorrect_usage = array();
		$listener        = function ( $function_name, $message, $version ) use ( &$incorrect_usage ) {
			$incorrect_usage = array(
				'function_name' => $function_name,
				'message'       => $message,
				'version'       => $version,
			);
		};
		add_action( 'doing_it_wrong_run', $listener, 10, 3 );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		$this->setExpectedIncorrectUsage( 'WC_Download_Handler::resolve_filename_from_response_headers' );

		try {
			$resolved = WC_Download_Handler::resolve_filename_from_response_headers( array( 'HTTP/1.1 200 OK' ), array( 'not-a-filename' ) );
		} finally {
			remove_action( 'doing_it_wrong_run', $listener, 10 );
			remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		}

		$this->assertSame( '', $resolved, 'A non-scalar filename with no usable response headers should resolve to an empty string.' );
		$this->assertSame( 'WC_Download_Handler::resolve_filename_from_response_headers', $incorrect_usage['function_name'] );
		$this->assertStringContainsString( 'woocommerce_file_download_filename filter should return a string; array returned.', $incorrect_usage['message'] );
		$this->assertSame( '11.1.0', $incorrect_usage['version'] );
	}

	/**
	 * @testdox resolve_filename_from_response_headers() should render a filename object that declares __toString(), as string concatenation always did.
	 */
	public function test_resolve_filename_renders_stringable_filename(): void {
		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename="Quarterly Report.pdf"',
		);

		$filename = new class() {
			/**
			 * Render the filename.
			 *
			 * @return string
			 */
			public function __toString(): string {
				return 'Seasons-Catalog';
			}
		};
		$this->setExpectedIncorrectUsage( 'WC_Download_Handler::resolve_filename_from_response_headers' );

		$resolved = WC_Download_Handler::resolve_filename_from_response_headers( $headers, $filename, true );

		$this->assertSame( 'Seasons-Catalog.pdf', $resolved, 'A filename object declaring __toString() should be preserved and gain the remote extension, not be discarded.' );
	}

	/**
	 * @testdox download_file_force() should carry a non-string filename from a woocommerce_file_download_filename callback through to the download headers instead of fataling.
	 */
	public function test_download_file_force_tolerates_non_string_filename_from_filter(): void {
		// Mirrors the report in #66635: a callback that falls off the end and returns null.
		$broken_filename_filter = function () {};

		$reached_headers = false;
		// download_headers() derives the content type from the resolved filename via
		// get_allowed_mime_types(), so this fires once the filename is resolved but before any
		// header() call. Throwing here unwinds download_file_force() ahead of its terminating
		// exit(), which would otherwise take the PHPUnit process down with it.
		//
		// That ordering is what this test depends on: should download_headers() ever be reworked so
		// that nothing hooks in ahead of the exit(), this test would take the test run down with it
		// rather than fail. Keep the marker on the earliest hook that follows the filename being
		// resolved.
		$header_stage_marker = function () use ( &$reached_headers ) {
			$reached_headers = true;
			throw new RuntimeException( 'reached-download-headers' );
		};

		add_filter( 'woocommerce_file_download_filename', $broken_filename_filter );
		add_filter( 'upload_mimes', $header_stage_marker );
		$this->setExpectedIncorrectUsage( 'WC_Download_Handler::resolve_filename_from_response_headers' );

		$ob_level = ob_get_level();

		// Stand in for the remote host so the open succeeds and the filename actually reaches
		// resolve_filename_from_response_headers(), which a real unreachable URL never would.
		stream_wrapper_unregister( 'http' );
		stream_wrapper_register( 'http', FakeRemoteStreamWrapper::class );

		try {
			// No $this->fail() for the no-throw case: PHPUnit's own AssertionFailedError descends
			// from RuntimeException, so the catch below would swallow it. The assertion after this
			// block covers that path instead.
			WC_Download_Handler::download( 'http://example.test/uc', 0 );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'reached-download-headers', $e->getMessage(), 'Only the marker exception should escape; a TypeError here is the #66635 regression.' );
		} finally {
			stream_wrapper_restore( 'http' );

			// download_headers() unwinds every output buffer, including the one PHPUnit wraps
			// each test in, so restore the nesting level it expects to find on the way out.
			while ( ob_get_level() < $ob_level ) {
				ob_start();
			}

			// Remove only what this test added: `upload_mimes` is a shared WordPress hook.
			remove_filter( 'woocommerce_file_download_filename', $broken_filename_filter );
			remove_filter( 'upload_mimes', $header_stage_marker );
		}

		$this->assertTrue( $reached_headers, 'A null filename should flow through to the download headers instead of throwing a TypeError.' );
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
	 * @testdox readfile_chunked() should emit binary download bytes unchanged.
	 */
	public function test_readfile_chunked_emits_binary_data_unchanged(): void {
		$binary_content = "\x00\xFF\xFE<script>&\x80";
		$temp_file      = wp_tempnam( 'wc-download-handler-streaming' );

		file_put_contents( $temp_file, $binary_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp directory.

		$output = '';
		ob_start(
			function ( $chunk ) use ( &$output ) {
				$output .= $chunk;
				return '';
			}
		);

		try {
			$served = WC_Download_Handler::readfile_chunked( $temp_file, 0, strlen( $binary_content ) );
		} finally {
			ob_end_clean();
			wp_delete_file( $temp_file );
		}

		$this->assertTrue( $served, 'A complete binary stream should be reported as served.' );
		$this->assertSame( $binary_content, $output, 'Binary download bytes must not be escaped or otherwise transformed.' );
	}

	/**
	 * @testdox readfile_chunked() should stop and report failure when a stream read fails.
	 *
	 * @dataProvider provider_stream_lengths
	 *
	 * @param int $length Requested download length, where zero means until EOF.
	 */
	public function test_readfile_chunked_reports_read_failure( int $length ): void {
		$scheme = 'wc-failing-download';

		FakeRemoteStreamWrapper::$fail_reads = true;
		stream_wrapper_register( $scheme, FakeRemoteStreamWrapper::class );

		ob_start();

		try {
			$served = WC_Download_Handler::readfile_chunked( $scheme . '://fixture', 0, $length );
		} finally {
			$output = ob_get_clean();
			stream_wrapper_unregister( $scheme );
			FakeRemoteStreamWrapper::$fail_reads = false;
		}

		$this->assertFalse( $served, 'A failed fread() call should make the download fail.' );
		$this->assertSame( '', $output, 'A failed read should not append anything to the download response.' );
		$this->assertTrue( FakeRemoteStreamWrapper::$closed, 'The failed stream should be closed immediately.' );
	}

	/**
	 * Download lengths for failed-stream coverage.
	 *
	 * @return array<string, array<int>>
	 */
	public function provider_stream_lengths(): array {
		return array(
			'requested range'       => array( 4 ),
			'read until stream EOF' => array( 0 ),
		);
	}

	/**
	 * @testdox readfile_chunked() should push each chunk through a flushable output buffer instead of holding the whole file in it.
	 *
	 * @dataProvider provider_download_sources
	 *
	 * @param bool $unknown_size Whether to stream from a source with no known size, which reads until EOF.
	 */
	public function test_readfile_chunked_flushes_each_chunk_through_output_buffer( bool $unknown_size ): void {
		$chunk_size = defined( 'WC_CHUNK_SIZE' ) ? (int) WC_CHUNK_SIZE : 1024 * 1024;
		$contents   = str_repeat( 'a', $chunk_size ) . str_repeat( 'b', $chunk_size ) . 'c';
		$source     = $this->create_download_source( $contents, $unknown_size );
		$flushed    = array();

		ob_start(
			function ( $buffer, $phase ) use ( &$flushed ) {
				if ( $phase & PHP_OUTPUT_HANDLER_FLUSH ) {
					$flushed[] = $buffer;
				}
				return '';
			}
		);

		try {
			$served = WC_Download_Handler::readfile_chunked( $source );
		} finally {
			ob_end_clean();
			$this->delete_download_source( $source );
		}

		$this->assertTrue( $served, 'The file should be reported as served.' );
		$this->assertGreaterThan( 1, count( $flushed ), 'Each chunk should be flushed as it is read.' );
		$this->assertTrue( implode( '', $flushed ) === $contents, 'Every byte of the file should pass through the buffer by being flushed.' );
	}

	/**
	 * @testdox readfile_chunked() should not try to flush an output buffer that can't be flushed.
	 *
	 * @dataProvider provider_download_sources
	 *
	 * @param bool $unknown_size Whether to stream from a source with no known size, which reads until EOF.
	 */
	public function test_readfile_chunked_does_not_flush_unflushable_output_buffer( bool $unknown_size ): void {
		$source = $this->create_download_source( 'file-data', $unknown_size );
		$errors = array();

		// WordPress hides notices unless WP_DEBUG is on, and sites that turn it on get them printed into the download.
		$error_reporting = error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
		ob_start( null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Records notices rather than letting the first one abort the download mid-stream.
		set_error_handler(
			function ( $errno, $errstr ) use ( &$errors ) {
				// Leave out errors silenced with @, such as filesize() failing on a stream of unknown size.
				if ( error_reporting() & $errno ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
					$errors[] = $errstr;
				}
				return true;
			}
		);

		try {
			$served   = WC_Download_Handler::readfile_chunked( $source );
			$buffered = ob_get_contents();
		} finally {
			restore_error_handler();
			error_reporting( $error_reporting ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
			ob_end_clean();
			$this->delete_download_source( $source );
		}

		$this->assertSame( array(), $errors, 'Streaming into a buffer that cannot be flushed should not raise notices, which would be written into the download.' );
		$this->assertTrue( $served, 'The file should be reported as served.' );
		$this->assertSame( 'file-data', $buffered, 'The file should be left intact in the buffer.' );
	}

	/**
	 * @testdox readfile_chunked() should not flush until file data has been read, so a failed read can still send an error page.
	 *
	 * @dataProvider provider_download_sources
	 *
	 * @param bool $unknown_size Whether to stream from a source with no known size, which reads until EOF.
	 */
	public function test_readfile_chunked_does_not_flush_before_file_data( bool $unknown_size ): void {
		// An empty source gives one empty read before EOF.
		$source  = $this->create_download_source( '', $unknown_size );
		$flushes = 0;

		ob_start(
			function ( $buffer, $phase ) use ( &$flushes ) {
				if ( $phase & PHP_OUTPUT_HANDLER_FLUSH ) {
					++$flushes;
				}
				return '';
			}
		);

		try {
			WC_Download_Handler::readfile_chunked( $source );
		} finally {
			ob_end_clean();
			$this->delete_download_source( $source );
		}

		$this->assertSame( 0, $flushes, 'Some servers send the headers on flush(), so nothing should be flushed before file data is output.' );
	}

	/**
	 * Download sources for streaming coverage.
	 *
	 * @return array<string, array<bool>>
	 */
	public function provider_download_sources(): array {
		return array(
			'local file of known size' => array( false ),
			'stream of unknown size'   => array( true ),
		);
	}

	/**
	 * @testdox Should send only the file through any output buffers it can remove or clean, raise no errors, and warn about buffers left behind.
	 *
	 * @dataProvider provider_output_buffer_stacks
	 *
	 * @param int[]  $buffer_flags      Flags for each output buffer, from the bottom of the stack up. Each buffer holds "[junk-<level>]".
	 * @param string $expected_body     Response body the client should receive.
	 * @param int    $expected_warnings Number of warnings that should be logged.
	 */
	public function test_download_output_through_output_buffers( array $buffer_flags, string $expected_body, int $expected_warnings ): void {
		// Buffers that can't be removed would outlive this test, so run the download handler in its own process.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Runs the PHP CLI on a fixed script with no user input.
		$process = proc_open(
			array( PHP_BINARY, '-d', 'display_errors=stderr', dirname( __DIR__ ) . '/helpers/download-handler-buffer-runner.php', implode( ',', $buffer_flags ), 'FILE-DATA' ),
			array(
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			),
			$pipes
		);
		$body    = stream_get_contents( $pipes[1] );
		$stderr  = stream_get_contents( $pipes[2] );
		fclose( $pipes[1] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $pipes[2] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		proc_close( $process );

		$report = json_decode( $stderr, true );

		$this->assertIsArray( $report, "The runner script did not complete: $stderr" );
		$this->assertSame( $expected_body, $body, 'The client should receive the file without the content of buffers that could be cleaned.' );
		$this->assertSame( array(), $report['errors'], 'Cleaning and flushing buffers should not raise errors, even silenced ones.' );
		$this->assertCount( $expected_warnings, $report['warnings'], 'A warning should be logged only when buffers left behind can affect the download.' );
		foreach ( $report['warnings'] as $warning ) {
			$this->assertStringContainsString( 'default output handler', $warning, 'The warning should name the buffers left behind.' );
		}
	}

	/**
	 * Output buffer stacks a download can be served through.
	 *
	 * @return array<string, array{int[], string, int}>
	 */
	public function provider_output_buffer_stacks(): array {
		$cleanable_flushable = PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_FLUSHABLE;

		return array(
			'no buffers'                             => array( array(), 'FILE-DATA', 0 ),
			'standard buffers'                       => array( array( PHP_OUTPUT_HANDLER_STDFLAGS, PHP_OUTPUT_HANDLER_STDFLAGS ), 'FILE-DATA', 0 ),
			'cleanable and flushable, not removable' => array( array( $cleanable_flushable ), 'FILE-DATA', 0 ),
			'cleanable only'                         => array( array( PHP_OUTPUT_HANDLER_CLEANABLE ), 'FILE-DATA', 1 ),
			'standard below non-removable'           => array( array( PHP_OUTPUT_HANDLER_STDFLAGS, $cleanable_flushable ), '[junk-0]FILE-DATA', 1 ),
			'buffers from issue 51562'               => array( array( 0, 0, PHP_OUTPUT_HANDLER_CLEANABLE, PHP_OUTPUT_HANDLER_REMOVABLE ), '[junk-0][junk-1]FILE-DATA', 1 ),
		);
	}

	/**
	 * Create a download source holding the given contents.
	 *
	 * @param string $contents     File contents.
	 * @param bool   $unknown_size Whether to use a data: URL, which has no size, instead of a temporary file.
	 * @return string File path or URL.
	 */
	private function create_download_source( string $contents, bool $unknown_size ): string {
		if ( $unknown_size ) {
			return 'data://application/octet-stream;base64,' . base64_encode( $contents ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		$file = wp_tempnam( 'wc-download-handler-streaming' );
		file_put_contents( $file, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp directory.

		return $file;
	}

	/**
	 * Delete a download source created by create_download_source().
	 *
	 * @param string $source File path or URL.
	 */
	private function delete_download_source( string $source ): void {
		if ( is_file( $source ) ) {
			wp_delete_file( $source );
		}
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
	 * @testdox download_product() should treat array query args as an invalid download link.
	 */
	public function test_download_product_rejects_array_query_args(): void {
		$string_args = array(
			'download_file' => '1',
			'order'         => 'wc_order_x',
			'key'           => 'k',
			'email'         => 'a@example.org',
		);

		$product_was_looked_up = false;
		$lookup_watcher        = function ( $type ) use ( &$product_was_looked_up ) {
			$product_was_looked_up = true;
			return $type;
		};

		add_filter( 'woocommerce_product_type_query', $lookup_watcher );

		try {
			foreach ( array( 'download_file', 'order', 'key', 'email', 'uid' ) as $arg ) {
				$_GET = $string_args;

				if ( 'uid' === $arg ) {
					// The UID is only consulted when no email address is supplied.
					unset( $_GET['email'] );
				}

				$_GET[ $arg ]          = array( 'x' );
				$wp_die_message        = '';
				$product_was_looked_up = false;

				// We do not use expectException() here because every argument is checked in turn.
				try {
					WC_Download_Handler::download_product();
				} catch ( WPDieException $e ) {
					$wp_die_message = $e->getMessage();
				}

				$this->assertStringContainsString(
					'Invalid download link',
					$wp_die_message,
					"An array value for the \"$arg\" query argument should render the invalid download link error."
				);

				$this->assertFalse(
					$product_was_looked_up,
					"Array query arguments are rejected before any product lookup, but the \"$arg\" case reached one."
				);
			}
		} finally {
			remove_filter( 'woocommerce_product_type_query', $lookup_watcher );
			$_GET = array();
		}
	}

	/**
	 * @testdox download_product() should reject authorization values that sanitize to empty.
	 *
	 * @dataProvider provider_authorization_values_that_sanitize_to_empty
	 *
	 * @param string $argument Query argument under test.
	 * @param string $value    Query argument value.
	 */
	public function test_download_product_rejects_authorization_values_that_sanitize_to_empty( string $argument, string $value ): void {
		self::remove_download_handlers();

		try {
			list( $product, $order ) = $this->build_downloadable_product_and_order_one(
				array(
					array(
						'name' => 'Protected download',
						'file' => content_url( 'uploads/woocommerce_uploads/protected-download.pdf' ),
					),
				)
			);

			$download_key = current( array_keys( $product->get_downloads() ) );
			$download     = current( WC_Data_Store::load( 'customer-download' )->get_downloads( array( 'product_id' => $product->get_id() ) ) );
			$download->set_downloads_remaining( 5 );
			$download->save();

			$_GET = array(
				'download_file' => $product->get_id(),
				'order'         => $order->get_order_key(),
				'email'         => $order->get_billing_email(),
				'key'           => $download_key,
			);

			$_GET[ $argument ] = $value;

			$wp_die_message = '';

			try {
				WC_Download_Handler::download_product();
			} catch ( WPDieException $e ) {
				$wp_die_message = $e->getMessage();
			}

			$this->assertStringContainsString( 'Invalid download link', $wp_die_message, 'The malformed authorization value should render the invalid download link error.' );

			$download = new WC_Customer_Download( $download->get_id() );
			$this->assertSame( 5, $download->get_downloads_remaining(), 'A rejected request must not consume the customer\'s remaining downloads.' );
		} finally {
			self::restore_download_handlers();
			$_GET = array();
		}
	}

	/**
	 * @testdox download_product() should authorize the current email download URL format.
	 */
	public function test_download_product_accepts_current_email_download_url(): void {
		list( $product, $order ) = $this->build_downloadable_product_and_order_one(
			array(
				array(
					'name' => 'Protected download',
					'file' => content_url( 'uploads/woocommerce_uploads/protected-download.pdf' ),
				),
			)
		);

		$download_key = current( array_keys( $product->get_downloads() ) );
		$order_item   = current( $order->get_items() );
		$download_url = $order_item->get_item_download_url( $download_key );
		$query_args   = array();

		parse_str( wp_parse_url( $download_url, PHP_URL_QUERY ), $query_args );

		$this->assertArrayHasKey( 'email', $query_args, 'The current email download URL should contain an email argument.' );
		$this->assertArrayNotHasKey( 'uid', $query_args, 'The email download URL should exercise the email authorization path.' );
		$this->assert_download_url_is_authorized( $download_url );
	}

	/**
	 * @testdox download_product() should authorize the current UID download URL format.
	 */
	public function test_download_product_accepts_current_uid_download_url(): void {
		list( $product, $order ) = $this->build_downloadable_product_and_order_one(
			array(
				array(
					'name' => 'Protected download',
					'file' => content_url( 'uploads/woocommerce_uploads/protected-download.pdf' ),
				),
			)
		);

		$downloadable_items = $order->get_downloadable_items();
		$download_url       = current( $downloadable_items )['download_url'];
		$query_args         = array();

		parse_str( wp_parse_url( $download_url, PHP_URL_QUERY ), $query_args );

		$this->assertArrayHasKey( 'uid', $query_args, 'The current UID download URL should contain a UID argument.' );
		$this->assertArrayNotHasKey( 'email', $query_args, 'The UID download URL should exercise the UID authorization path.' );
		$this->assert_download_url_is_authorized( $download_url );
	}

	/**
	 * @testdox download_product() should reject a generated UID link when the order billing email is empty.
	 */
	public function test_download_product_rejects_generated_uid_link_when_billing_email_is_empty(): void {
		self::remove_download_handlers();

		try {
			list( $product, $order ) = $this->build_downloadable_product_and_order_one(
				array(
					array(
						'name' => 'Protected download',
						'file' => content_url( 'uploads/woocommerce_uploads/protected-download.pdf' ),
					),
				)
			);

			$download = current( WC_Data_Store::load( 'customer-download' )->get_downloads( array( 'product_id' => $product->get_id() ) ) );
			$download->set_downloads_remaining( 5 );
			$download->save();

			$order->set_customer_id( 0 );
			$order->set_billing_email( '' );
			$order->save();

			$downloadable_items = $order->get_downloadable_items();
			$download_url       = current( $downloadable_items )['download_url'];
			$query_args         = array();

			parse_str( wp_parse_url( $download_url, PHP_URL_QUERY ), $query_args );
			$_GET = $query_args;

			$wp_die_message = '';

			try {
				WC_Download_Handler::download_product();
			} catch ( WPDieException $e ) {
				$wp_die_message = $e->getMessage();
			}

			$this->assertStringContainsString( 'Invalid download link', $wp_die_message, 'A UID derived from an empty billing email should be rejected.' );

			$download = new WC_Customer_Download( $download->get_id() );
			$this->assertSame( 5, $download->get_downloads_remaining(), 'A rejected UID request must not consume the customer\'s remaining downloads.' );
		} finally {
			self::restore_download_handlers();
			$_GET = array();
		}
	}

	/**
	 * Values which are present in the request but empty after sanitization.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_authorization_values_that_sanitize_to_empty(): array {
		return array(
			'order key' => array( 'order', ' ' ),
			'email'     => array( 'email', 'x' ),
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
	 * Assert that a generated download URL passes authorization without serving a file.
	 *
	 * @param string $download_url Generated download URL.
	 */
	private function assert_download_url_is_authorized( string $download_url ): void {
		$downloads_dispatched = 0;
		$download_method      = function () {
			return 'test';
		};
		$download_counter     = function () use ( &$downloads_dispatched ) {
			++$downloads_dispatched;
		};

		add_filter( 'woocommerce_file_download_method', $download_method );
		add_action( 'woocommerce_download_file_test', $download_counter );

		try {
			$query_args = array();
			parse_str( wp_parse_url( $download_url, PHP_URL_QUERY ), $query_args );
			$_GET = $query_args;

			WC_Download_Handler::download_product();

			$this->assertSame( 1, $downloads_dispatched, 'An authorized URL should reach the download dispatch without serving a file.' );
		} finally {
			remove_filter( 'woocommerce_file_download_method', $download_method );
			remove_action( 'woocommerce_download_file_test', $download_counter );
			$_GET = array();
		}
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
