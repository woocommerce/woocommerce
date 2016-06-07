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
		if ( isset( $_GET['download_file'] ) && isset( $_GET['order'] ) && isset( $_GET['email'] ) ) {
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
		$product_id    = absint( $_GET['download_file'] );
		$_product      = wc_get_product( $product_id );
		$download_data = self::get_download_data( array(
			'product_id'  => $product_id,
			'order_key'   => wc_clean( $_GET['order'] ),
			'email'       => sanitize_email( str_replace( ' ', '+', $_GET['email'] ) ),
			'download_id' => wc_clean( isset( $_GET['key'] ) ? preg_replace( '/\s+/', ' ', $_GET['key'] ) : '' )
		) );

		if ( $_product && $download_data ) {
			self::check_current_user_can_download( $download_data );

			do_action( 'woocommerce_download_product', $download_data->user_email, $download_data->order_key, $download_data->product_id, $download_data->user_id, $download_data->download_id, $download_data->order_id );

			self::count_download( $download_data );
			self::download( $_product->get_file_download_path( $download_data->download_id ), $download_data->product_id );
		} else {
			self::download_error( __( 'Invalid download link.', 'woocommerce' ) );
		}
	}

	/**
	 * Get a download from the database.
	 *
	 * @param  array  $args Contains email, order key, product id and download id
	 * @return object
	 * @access private
	 */
	private static function get_download_data( $args = array() ) {
		global $wpdb;

		$query = "SELECT * FROM " . $wpdb->prefix . "woocommerce_downloadable_product_permissions ";
		$query .= "WHERE user_email = %s ";
		$query .= "AND order_key = %s ";
		$query .= "AND product_id = %s ";

		if ( $args['download_id'] ) {
			$query .= "AND download_id = %s ";
		}

		$query .= "ORDER BY downloads_remaining DESC";

		return $wpdb->get_row( $wpdb->prepare( $query, array( $args['email'], $args['order_key'], $args['product_id'], $args['download_id'] ) ) );
	}

	/**
	 * Perform checks to see if the current user can download the file.
	 * @param  object $download_data
	 * @access private
	 */
	private static function check_current_user_can_download( $download_data ) {
		self::check_order_is_valid( $download_data );
		self::check_downloads_remaining( $download_data );
		self::check_download_expiry( $download_data );
		self::check_download_login_required( $download_data );
	}

	/**
	 * Check if an order is valid for downloading from.
	 * @param  array $download_data
	 * @access private
	 */
	private static function check_order_is_valid( $download_data ) {
		if ( $download_data->order_id && ( $order = wc_get_order( $download_data->order_id ) ) && ! $order->is_download_permitted() ) {
			self::download_error( __( 'Invalid order.', 'woocommerce' ), '', 403 );
		}
	}

	/**
	 * Check if there are downloads remaining.
	 * @param  array $download_data
	 * @access private
	 */
	private static function check_downloads_remaining( $download_data ) {
		if ( '0' == $download_data->downloads_remaining  ) {
			self::download_error( __( 'Sorry, you have reached your download limit for this file', 'woocommerce' ), '', 403 );
		}
	}

	/**
	 * Check if the download has expired.
	 * @param  array $download_data
	 * @access private
	 */
	private static function check_download_expiry( $download_data ) {
		if ( $download_data->access_expires > 0 && strtotime( $download_data->access_expires ) < strtotime( 'midnight', current_time( 'timestamp' ) ) ) {
			self::download_error( __( 'Sorry, this download has expired', 'woocommerce' ), '', 403 );
		}
	}

	/**
	 * Check if a download requires the user to login first.
	 * @param  array $download_data
	 * @access private
	 */
	private static function check_download_login_required( $download_data ) {
		if ( $download_data->user_id && 'yes' === get_option( 'woocommerce_downloads_require_login' ) ) {
			if ( ! is_user_logged_in() ) {
				if ( wc_get_page_id( 'myaccount' ) ) {
					wp_safe_redirect( add_query_arg( 'wc_error', urlencode( __( 'You must be logged in to download files.', 'woocommerce' ) ), wc_get_page_permalink( 'myaccount' ) ) );
					exit;
				} else {
					self::download_error( __( 'You must be logged in to download files.', 'woocommerce' ) . ' <a href="' . esc_url( wp_login_url( wc_get_page_permalink( 'myaccount' ) ) ) . '" class="wc-forward">' . __( 'Login', 'woocommerce' ) . '</a>', __( 'Log in to Download Files', 'woocommerce' ), 403 );
				}
			} elseif ( ! current_user_can( 'download_file', $download_data ) ) {
				self::download_error( __( 'This is not your download link.', 'woocommerce' ), '', 403 );
			}
		}
	}

	/**
	 * Log the download + increase counts.
	 * @param object $download_data
	 */
	public static function count_download( $download_data ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_downloadable_product_permissions',
			array(
				'download_count'      => $download_data->download_count + 1,
				'downloads_remaining' => $download_data->downloads_remaining > 0 ? $download_data->downloads_remaining - 1 : $download_data->downloads_remaining,
			),
			array(
				'permission_id' => absint( $download_data->permission_id ),
			),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

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
			site_url( '/', 'http' )          => ABSPATH
		);

		$file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path );
		$parsed_file_path = parse_url( $file_path );
		$remote_file      = true;

		// See if path needs an abspath prepended to work
		if ( file_exists( ABSPATH . $file_path ) ) {
			$remote_file = false;
			$file_path   = ABSPATH . $file_path;

		// Check if we have an absolute path
		} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ) ) ) && isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] ) ) {
			$remote_file = false;
			$file_path   = $parsed_file_path['path'];
		}

		return array(
			'remote_file' => $remote_file,
			'file_path'   => $file_path
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
			$message .= ' <a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="wc-forward">' . __( 'Go to shop', 'woocommerce' ) . '</a>';
		}
		wp_die( $message, $title, array( 'response' => $status ) );
	}
}

WC_Download_Handler::init();
