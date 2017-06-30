<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Download handler.
 *
 * Handle digital downloads.
 *
 * @class 		WC_Download_Handler
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Download_Handler {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		if ( isset( $_GET['download_file'], $_GET['order'], $_GET['email'] ) ) {
			add_action( 'init', array( __CLASS__, 'download_product' ) );
		}
		add_action( 'woocommerce_download_file_redirect', array( __CLASS__, 'download_file_redirect' ), 10, 2 );
		add_action( 'woocommerce_download_file_xsendfile', array( __CLASS__, 'download_file_xsendfile' ), 10, 2 );
		add_action( 'woocommerce_download_file_force', array( __CLASS__, 'download_file_force' ), 10, 2 );
	}

	/**
	 * Check if we need to download a file and check validity.
	 */
	public static function download_product() {
		$product_id   = absint( $_GET['download_file'] );
		$product      = wc_get_product( $product_id );
		$data_store   = WC_Data_Store::load( 'customer-download' );

		if ( ! $product || ! isset( $_GET['key'], $_GET['order'] ) ) {
			self::download_error( __( 'Invalid download link.', 'woocommerce' ) );
		}

		$download_ids = $data_store->get_downloads( array(
			'user_email'  => sanitize_email( str_replace( ' ', '+', $_GET['email'] ) ),
			'order_key'   => wc_clean( $_GET['order'] ),
			'product_id'  => $product_id,
			'download_id' => wc_clean( preg_replace( '/\s+/', ' ', $_GET['key'] ) ),
			'orderby'     => 'downloads_remaining',
			'order'       => 'DESC',
			'limit'       => 1,
			'return'      => 'ids',
		) );

		if ( empty( $download_ids ) ) {
			self::download_error( __( 'Invalid download link.', 'woocommerce' ) );
		}

		$download = new WC_Customer_Download( current( $download_ids ) );

		self::check_order_is_valid( $download );
		self::check_downloads_remaining( $download );
		self::check_download_expiry( $download );
		self::check_download_login_required( $download );

		do_action(
			'woocommerce_download_product',
			$download->get_user_email(),
			$download->get_order_key(),
			$download->get_product_id(),
			$download->get_user_id(),
			$download->get_download_id(),
			$download->get_order_id()
		);
		$count     = $download->get_download_count();
		$remaining = $download->get_downloads_remaining();
		$download->set_download_count( $count + 1 );
		if ( '' !== $remaining ) {
			$download->set_downloads_remaining( $remaining - 1 );
		}
		$download->save();

		self::download( $product->get_file_download_path( $download->get_download_id() ), $download->get_product_id() );
	}

	/**
	 * Check if an order is valid for downloading from.
	 * @param  WC_Customer_Download $download
	 * @access private
	 */
	private static function check_order_is_valid( $download ) {
		if ( $download->get_order_id() && ( $order = wc_get_order( $download->get_order_id() ) ) && ! $order->is_download_permitted() ) {
			self::download_error( __( 'Invalid order.', 'woocommerce' ), '', 403 );
		}
	}

	/**
	 * Check if there are downloads remaining.
	 * @param  WC_Customer_Download $download
	 * @access private
	 */
	private static function check_downloads_remaining( $download ) {
		if ( '' !== $download->get_downloads_remaining() && 0 >= $download->get_downloads_remaining() ) {
			self::download_error( __( 'Sorry, you have reached your download limit for this file', 'woocommerce' ), '', 403 );
		}
	}

	/**
	 * Check if the download has expired.
	 * @param  WC_Customer_Download $download
	 * @access private
	 */
	private static function check_download_expiry( $download ) {
		if ( ! is_null( $download->get_access_expires() ) && $download->get_access_expires()->getTimestamp() < strtotime( 'midnight', current_time( 'timestamp', true ) ) ) {
			self::download_error( __( 'Sorry, this download has expired', 'woocommerce' ), '', 403 );
		}
	}

	/**
	 * Check if a download requires the user to login first.
	 * @param  WC_Customer_Download $download
	 * @access private
	 */
	private static function check_download_login_required( $download ) {
		if ( $download->get_user_id() && 'yes' === get_option( 'woocommerce_downloads_require_login' ) ) {
			if ( ! is_user_logged_in() ) {
				if ( wc_get_page_id( 'myaccount' ) ) {
					wp_safe_redirect( add_query_arg( 'wc_error', urlencode( __( 'You must be logged in to download files.', 'woocommerce' ) ), wc_get_page_permalink( 'myaccount' ) ) );
					exit;
				} else {
					self::download_error( __( 'You must be logged in to download files.', 'woocommerce' ) . ' <a href="' . esc_url( wp_login_url( wc_get_page_permalink( 'myaccount' ) ) ) . '" class="wc-forward">' . __( 'Login', 'woocommerce' ) . '</a>', __( 'Log in to Download Files', 'woocommerce' ), 403 );
				}
			} elseif ( ! current_user_can( 'download_file', $download ) ) {
				self::download_error( __( 'This is not your download link.', 'woocommerce' ), '', 403 );
			}
		}
	}

	/**
	 * @deprecated
	 *
	 * @param $download_data
	 */
	public static function count_download( $download_data ) {}

	/**
	 * Download a file - hook into init function.
	 * @param string $file_path URL to file
	 * @param integer $product_id of the product being downloaded
	 */
	public static function download( $file_path, $product_id ) {
		if ( ! $file_path ) {
			self::download_error( __( 'No file defined', 'woocommerce' ) );
		}

		$filename = basename( $file_path );

		if ( strstr( $filename, '?' ) ) {
			$filename = current( explode( '?', $filename ) );
		}

		$filename             = apply_filters( 'woocommerce_file_download_filename', $filename, $product_id );
		$file_download_method = apply_filters( 'woocommerce_file_download_method', get_option( 'woocommerce_file_download_method', 'force' ), $product_id );

		// Add action to prevent issues in IE
		add_action( 'nocache_headers', array( __CLASS__, 'ie_nocache_headers_fix' ) );

		// Trigger download via one of the methods
		do_action( 'woocommerce_download_file_' . $file_download_method, $file_path, $filename );
	}

	/**
	 * Redirect to a file to start the download.
	 * @param  string $file_path
	 * @param  string $filename
	 */
	public static function download_file_redirect( $file_path, $filename = '' ) {
		header( 'Location: ' . $file_path );
		exit;
	}

	/**
	 * Parse file path and see if its remote or local.
	 * @param  string $file_path
	 * @return array
	 */
	public static function parse_file_path( $file_path ) {
		$wp_uploads     = wp_upload_dir();
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		// Replace uploads dir, site url etc with absolute counterparts if we can
		$replacements = array(
			$wp_uploads_url                  => $wp_uploads_dir,
			network_site_url( '/', 'https' ) => ABSPATH,
			network_site_url( '/', 'http' )  => ABSPATH,
			site_url( '/', 'https' )         => ABSPATH,
			site_url( '/', 'http' )          => ABSPATH,
		);

		$file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path );
		$parsed_file_path = parse_url( $file_path );
		$remote_file      = true;

		// See if path needs an abspath prepended to work
		if ( file_exists( ABSPATH . $file_path ) ) {
			$remote_file = false;
			$file_path   = ABSPATH . $file_path;

		} elseif ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
			$remote_file = false;
			$file_path   = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );

		// Check if we have an absolute path
		} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ) ) ) && isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] ) ) {
			$remote_file = false;
			$file_path   = $parsed_file_path['path'];
		}

		return array(
			'remote_file' => $remote_file,
			'file_path'   => $file_path,
		);
	}

	/**
	 * Download a file using X-Sendfile, X-Lighttpd-Sendfile, or X-Accel-Redirect if available.
	 * @param  string $file_path
	 * @param  string $filename
	 */
	public static function download_file_xsendfile( $file_path, $filename ) {
		$parsed_file_path = self::parse_file_path( $file_path );

		if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules() ) ) {
			self::download_headers( $parsed_file_path['file_path'], $filename );
			header( "X-Sendfile: " . $parsed_file_path['file_path'] );
			exit;
		} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {
			self::download_headers( $parsed_file_path['file_path'], $filename );
			header( "X-Lighttpd-Sendfile: " . $parsed_file_path['file_path'] );
			exit;
		} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {
			self::download_headers( $parsed_file_path['file_path'], $filename );
			$xsendfile_path = trim( preg_replace( '`^' . str_replace( '\\', '/', getcwd() ) . '`', '', $parsed_file_path['file_path'] ), '/' );
			header( "X-Accel-Redirect: /$xsendfile_path" );
			exit;
		}

		// Fallback
		self::download_file_force( $file_path, $filename );
	}

	/**
	 * Force download - this is the default method.
	 * @param  string $file_path
	 * @param  string $filename
	 */
	public static function download_file_force( $file_path, $filename ) {
		$parsed_file_path = self::parse_file_path( $file_path );

		self::download_headers( $parsed_file_path['file_path'], $filename );

		if ( ! self::readfile_chunked( $parsed_file_path['file_path'] ) ) {
			if ( $parsed_file_path['remote_file'] ) {
				self::download_file_redirect( $file_path );
			} else {
				self::download_error( __( 'File not found', 'woocommerce' ) );
			}
		}

		exit;
	}

	/**
	 * Get content type of a download.
	 * @param  string $file_path
	 * @return string
	 * @access private
	 */
	private static function get_download_content_type( $file_path ) {
		$file_extension  = strtolower( substr( strrchr( $file_path, "." ), 1 ) );
		$ctype           = "application/force-download";

		foreach ( get_allowed_mime_types() as $mime => $type ) {
			$mimes = explode( '|', $mime );
			if ( in_array( $file_extension, $mimes ) ) {
				$ctype = $type;
				break;
			}
		}

		return $ctype;
	}

	/**
	 * Set headers for the download.
	 * @param  string $file_path
	 * @param  string $filename
	 * @access private
	 */
	private static function download_headers( $file_path, $filename ) {
		self::check_server_config();
		self::clean_buffers();
		nocache_headers();

		header( "X-Robots-Tag: noindex, nofollow", true );
		header( "Content-Type: " . self::get_download_content_type( $file_path ) );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=\"" . $filename . "\";" );
		header( "Content-Transfer-Encoding: binary" );

		if ( $size = @filesize( $file_path ) ) {
			header( "Content-Length: " . $size );
		}
	}

	/**
	 * Check and set certain server config variables to ensure downloads work as intended.
	 */
	private static function check_server_config() {
		wc_set_time_limit( 0 );
		if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() && version_compare( phpversion(), '5.4', '<' ) ) {
			set_magic_quotes_runtime( 0 );
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}
		@ini_set( 'zlib.output_compression', 'Off' );
		@session_write_close();
	}

	/**
	 * Clean all output buffers.
	 *
	 * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
	 *
	 * @access private
	 */
	private static function clean_buffers() {
		if ( ob_get_level() ) {
			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean();
			}
		} else {
			@ob_end_clean();
		}
	}

	/**
	 * readfile_chunked.
	 *
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/.
	 *
	 * @param   string $file
	 * @return 	bool Success or fail
	 */
	public static function readfile_chunked( $file ) {
		$chunksize = 1024 * 1024;
		$handle    = @fopen( $file, 'r' );

		if ( false === $handle ) {
			return false;
		}

		while ( ! @feof( $handle ) ) {
			echo @fread( $handle, $chunksize );

			if ( ob_get_length() ) {
				ob_flush();
				flush();
			}
		}

		return @fclose( $handle );
	}

	/**
	 * Filter headers for IE to fix issues over SSL.
	 *
	 * IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
	 *
	 * @param array $headers
	 * @return array
	 */
	public static function ie_nocache_headers_fix( $headers ) {
		if ( is_ssl() && ! empty( $GLOBALS['is_IE'] ) ) {
			$headers['Cache-Control'] = 'private';
			unset( $headers['Pragma'] );
		}
		return $headers;
	}

	/**
	 * Die with an error message if the download fails.
	 * @param  string $message
	 * @param  string  $title
	 * @param  integer $status
	 * @access private
	 */
	private static function download_error( $message, $title = '', $status = 404 ) {
		if ( ! strstr( $message, '<a ' ) ) {
			$message .= ' <a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="wc-forward">' . esc_html__( 'Go to shop', 'woocommerce' ) . '</a>';
		}
		wp_die( $message, $title, array( 'response' => $status ) );
	}
}

WC_Download_Handler::init();
