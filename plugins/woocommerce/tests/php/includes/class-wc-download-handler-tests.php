<?php

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

		$product = WC_Helper_Product::create_downloadable_product(
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

		$customer = WC_Helper_Customer::create_customer();
		$email    = 'admin@example.org';
		$order    = WC_Helper_Order::create_order( $customer->get_id(), $product );
		$order->set_status( 'completed' );
		$order->save();

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
