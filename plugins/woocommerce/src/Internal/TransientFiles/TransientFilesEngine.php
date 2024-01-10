<?php

namespace Automattic\WooCommerce\Internal\TransientFiles;

use \DateTime;
use \Exception;
use \InvalidArgumentException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Utilities\TimeUtil;

/**
 * Transient files engine class.
 *
 * This class contains methods that allow creating files that have an expiration date.
 *
 * A transient file is created by invoking the create_transient_file method, which accepts the file contents
 * and the expiration date as arguments. Transient file names are composed by concatenating the expiration date
 * encoded in hexadecimal (3 digits for the year, 1 for the month and 2 for the day) and a random string
 * of hexadecimal digits.
 *
 * Transient files are stored in a directory whose default route is
 * wp-content/uploads/woocommerce_transient_files/yyyy-mm-dd, where "yyyy-mm-dd" is the expiration date
 * (year, month and day). The base route (minus the expiration date part) can be changed via a dedicated hook.
 *
 * Transient files that haven't expired (the expiration date is today or in the future) can be obtained remotely
 * via a dedicated URL, <server root>/wc/file/transient/<file name>. This URL is public (no authentication is required).
 * The content type of the response will always be "text/html".
 *
 * Cleanup of expired files is handled by the delete_expired_files method, which can be invoked manually
 * but there's a dedicated scheduled action that will invoke it that can be started and stopped via a dedicated tool
 * available in the WooCommerce tools page. The action runs once per day but this can be customized
 * via a dedicated hook.
 */
class TransientFilesEngine implements RegisterHooksInterface {

	use AccessiblePrivateMethods;

	private const CLEANUP_ACTION_NAME  = 'woocommerce_expired_transient_files_cleanup';
	private const CLEANUP_ACTION_GROUP = 'wc_batch_processes';

	/**
	 * The instance of LegacyProxy to use.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Register hooks.
	 */
	public function register() {
		self::add_action( self::CLEANUP_ACTION_NAME, array( $this, 'handle_expired_files_cleanup_action' ) );
		self::add_filter( 'woocommerce_debug_tools', array( $this, 'add_debug_tools_entries' ), 999, 1 );

		self::add_action( 'init', array( $this, 'handle_init' ), 0 );
		self::add_filter( 'query_vars', array( $this, 'handle_query_vars' ), 0 );
		self::add_action( 'parse_request', array( $this, 'handle_parse_request' ), 0 );
	}

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy The instance of LegacyProxy to use.
	 */
	final public function init( LegacyProxy $legacy_proxy ) {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Get the base directory where transient files are stored.
	 *
	 * The default base directory is the WordPress uploads directory plus "woocommerce_transient_files". This can
	 * be changed by using the woocommerce_transient_files_directory filter.
	 *
	 * If the woocommerce_transient_files_directory filter is not used and the default base directory
	 * doesn't exist, it will be created. If the filter is used it's the responsibility of the caller
	 * to ensure that the custom directory exists, otherwise an exception will be thrown.
	 *
	 * The actual directory for each existing file will be the base directory plus the expiration date
	 * of the file formatted as 'yyyy-mm-dd'.
	 *
	 * @return string Effective base directory where transient files are stored.
	 * @throws Exception The custom base directory (as specified via filter) doesn't exist, or the default base directory can't be created.
	 */
	public function get_transient_files_directory(): string {
		$upload_dir_info                   = $this->legacy_proxy->call_function( 'wp_upload_dir' );
		$default_transient_files_directory = untrailingslashit( $upload_dir_info['basedir'] ) . '/woocommerce_transient_files';

		/**
		 * Filters the directory where transient files are stored.
		 *
		 * Note that this is used for both creating new files (with create_file_by_rendering_template)
		 * and retrieving existing files (with get_file_by_*).
		 *
		 * @param string $transient_files_directory The default directory for transient files.
		 * @return string The actual directory to use for storing transient files.
		 *
		 * @since 8.5.0
		 */
		$transient_files_directory = apply_filters( 'woocommerce_transient_files_directory', $default_transient_files_directory );

		$realpathed_transient_files_directory = $this->legacy_proxy->call_function( 'realpath', $transient_files_directory );
		if ( false === $realpathed_transient_files_directory ) {
			if ( $transient_files_directory === $default_transient_files_directory ) {
				if ( ! $this->legacy_proxy->call_function( 'wp_mkdir_p', $transient_files_directory ) ) {
					throw new Exception( "Can't create directory: $transient_files_directory" );
				}
				$realpathed_transient_files_directory = $this->legacy_proxy->call_function( 'realpath', $transient_files_directory );
			} else {
				throw new Exception( "The base transient files directory doesn't exist: $transient_files_directory" );
			}
		}

		return untrailingslashit( $realpathed_transient_files_directory );
	}

	/**
	 * Create a transient file.
	 *
	 * @param string     $file_contents The contents of the file.
	 * @param string|int $expiration_date A string representing the expiration date formatted as "yyyy-mm-dd", or a number representing the expiration date as a timestamp (the time of day part will be ignored).
	 * @return string The name of the transient file created (without path information).
	 * @throws \InvalidArgumentException Invalid expiration date (wrongly formatted, or it's a date in the past).
	 * @throws \Exception The directory to store the file doesn't exist and can't be created.
	 */
	public function create_transient_file( string $file_contents, $expiration_date ): string {
		if ( is_numeric( $expiration_date ) ) {
			$expiration_date = gmdate( 'Y-m-d', $expiration_date );
		} elseif ( ! is_string( $expiration_date ) || ! TimeUtil::is_valid_date( $expiration_date, 'Y-m-d' ) ) {
			$expiration_date = is_scalar( $expiration_date ) ? $expiration_date : gettype( $expiration_date );
			throw new InvalidArgumentException( "$expiration_date is not a valid date, expected format: YYYY-MM-DD" );
		}

		$expiration_date_object = DateTime::createFromFormat( 'Y-m-d', $expiration_date, TimeUtil::get_utc_date_time_zone() );
		$today_date_object      = new DateTime( $this->legacy_proxy->call_function( 'gmdate', 'Y-m-d' ), TimeUtil::get_utc_date_time_zone() );

		if ( $expiration_date_object < $today_date_object ) {
			throw new InvalidArgumentException( "The supplied expiration date, $expiration_date, is in the past" );
		}

		$filename = bin2hex( $this->legacy_proxy->call_function( 'random_bytes', 16 ) );

		$transient_files_directory  = $this->get_transient_files_directory();
		$transient_files_directory .= '/' . $expiration_date_object->format( 'Y-m-d' );
		if ( ! $this->legacy_proxy->call_function( 'is_dir', $transient_files_directory ) ) {
			if ( ! $this->legacy_proxy->call_function( 'wp_mkdir_p', $transient_files_directory ) ) {
				throw new Exception( "Can't create directory: $transient_files_directory" );
			}
		}
		$filepath = $transient_files_directory . '/' . $filename;

		WP_Filesystem();
		$wp_filesystem = $this->legacy_proxy->get_global( 'wp_filesystem' );
		if ( false === $wp_filesystem->put_contents( $filepath, $file_contents ) ) {
			throw new Exception( "Can't create file: $filepath" );
		}

		return sprintf(
			'%03x%01x%02x%s',
			$expiration_date_object->format( 'Y' ),
			$expiration_date_object->format( 'm' ),
			$expiration_date_object->format( 'd' ),
			$filename
		);
	}

	/**
	 * Get the full physical path of a transient file given its name.
	 *
	 * @param string $filename The name of the transient file to locate.
	 * @return string|null The full physical path of the file, or null if the files doesn't exist.
	 */
	public function get_transient_file_path( string $filename ): ?string {
		if ( strlen( $filename ) < 7 || ! ctype_xdigit( $filename ) ) {
			return null;
		}

		$expiration_date = sprintf(
			'%04d-%02d-%02d',
			hexdec( substr( $filename, 0, 3 ) ),
			hexdec( substr( $filename, 3, 1 ) ),
			hexdec( substr( $filename, 4, 2 ) )
		);
		if ( ! TimeUtil::is_valid_date( $expiration_date, 'Y-m-d' ) ) {
			return null;
		}

		$file_path = $this->get_transient_files_directory() . '/' . $expiration_date . '/' . substr( $filename, 6 );

		return is_file( $file_path ) ? $file_path : null;
	}

	/**
	 * Verify if a file has expired, given its full physical file path.
	 *
	 * Given a file name returned by 'create_transient_file', the procedure to check if it has expired is as follows:
	 *
	 * 1. Use 'get_transient_file_path' to obtain the full file path.
	 * 2. If the above returns null, the file doesn't exist anymore (likely it expired and was deleted by the cleanup process).
	 * 3. Otherwise, use 'file_has_expired' passing the obtained full file path.
	 *
	 * @param string $file_path The full file path to check.
	 * @return bool True if the file has expired, false otherwise.
	 * @throws \Exception Thrown by DateTime if a wrong file path is passed.
	 */
	public function file_has_expired( string $file_path ): bool {
		$dirname                = dirname( $file_path );
		$expiration_date        = basename( $dirname );
		$expiration_date_object = new DateTime( $expiration_date, TimeUtil::get_utc_date_time_zone() );
		$today_date_object      = new DateTime( $this->legacy_proxy->call_function( 'gmdate', 'Y-m-d' ), TimeUtil::get_utc_date_time_zone() );
		return $expiration_date_object < $today_date_object;
	}

	/**
	 * Delete an existing transient file.
	 *
	 * @param string $filename The name of the file to delete.
	 * @return bool True if the file has been deleted, false otherwise (the file didn't exist).
	 */
	public function delete_transient_file( string $filename ): bool {
		$file_path = $this->get_transient_file_path( $filename );
		if ( is_null( $file_path ) ) {
			return false;
		}

		$dirname = dirname( $file_path );
		wp_delete_file( $file_path );
		$this->delete_directory_if_not_empty( $dirname );

		return true;
	}

	/**
	 * Delete expired transient files from the filesystem.
	 *
	 * @param int $limit Maximum number of files to delete.
	 * @return array "deleted_count" with the number of files actually deleted, "files_remain" that will be true if there are still files left to delete.
	 * @throws Exception The base directory for transient files (possibly changed via filter) doesn't exist.
	 */
	public function delete_expired_files( int $limit = 1000 ): array {
		$expiration_date_gmt = $this->legacy_proxy->call_function( 'gmdate', 'Y-m-d' );
		$base_dir            = $this->get_transient_files_directory();
		$subdirs             = glob( $base_dir . '/[2-9][0-9][0-9][0-9]-[01][0-9]-[0-3][0-9]', GLOB_ONLYDIR );
		if ( false === $subdirs ) {
			throw new Exception( "Error when getting the list of subdirectories of $base_dir" );
		}

		$subdirs         = array_map( fn( $name ) => substr( $name, strlen( $name ) - 10, 10 ), $subdirs );
		$expired_subdirs = array_filter( $subdirs, fn( $name ) => $name < $expiration_date_gmt );
		asort( $subdirs ); // We want to delete files starting with the oldest expiration month.

		$remaining_limit = $limit;
		$limit_reached   = false;
		foreach ( $expired_subdirs as $subdir ) {
			$full_dir_path   = $base_dir . '/' . $subdir;
			$files_to_delete = glob( $full_dir_path . '/*' );
			if ( count( $files_to_delete ) > $remaining_limit ) {
				$limit_reached   = true;
				$files_to_delete = array_slice( $files_to_delete, 0, $remaining_limit );
			}
			array_map( 'wp_delete_file', $files_to_delete );
			$remaining_limit -= count( $files_to_delete );
			$this->delete_directory_if_not_empty( $full_dir_path );

			if ( $limit_reached ) {
				break;
			}
		}

		return array(
			'deleted_count' => $limit - $remaining_limit,
			'files_remain'  => $limit_reached,
		);
	}

	/**
	 * Is the expired files cleanup action currently scheduled?
	 *
	 * @return bool True if the expired files cleanup action is currently scheduled, false otherwise.
	 */
	public function expired_files_cleanup_is_scheduled(): bool {
		return as_has_scheduled_action( self::CLEANUP_ACTION_NAME, array(), self::CLEANUP_ACTION_GROUP );
	}

	/**
	 * Schedule an action that will do one round of expired files cleanup.
	 * The action is scheduled to run immediately. If a previous pending action exists, it's unscheduled first.
	 */
	public function schedule_expired_files_cleanup(): void {
		$this->unschedule_expired_files_cleanup();
		as_schedule_single_action( time() + 1, self::CLEANUP_ACTION_NAME, array(), self::CLEANUP_ACTION_GROUP );
	}

	/**
	 * Remove the scheduled action that does the expired files cleanup, if it's scheduled.
	 */
	public function unschedule_expired_files_cleanup(): void {
		if ( $this->expired_files_cleanup_is_scheduled() ) {
			as_unschedule_action( self::CLEANUP_ACTION_NAME, array(), self::CLEANUP_ACTION_GROUP );
		}
	}

	/**
	 * Run the expired files cleanup action and schedule a new one.
	 *
	 * If files are actually deleted then we assume that more files are pending deletion and schedule the next
	 * action to run immediately. Otherwise (nothing was deleted) we schedule the next action for one day later
	 * (but this can be changed via the 'woocommerce_delete_expired_transient_files_interval' filter).
	 *
	 * If the actual deletion process fails the next action is scheduled anyway for one day later
	 * or for the interval given by the filter.
	 *
	 * NOTE: If the default interval is changed to something different from DAY_IN_SECONDS, please adjust the
	 * "every 24h" text in add_debug_tools_entries too.
	 */
	private function handle_expired_files_cleanup_action(): void {
		$new_interval = null;

		try {
			$result = $this->delete_expired_files();
			if ( $result['deleted_count'] > 0 ) {
				$new_interval = 1;
			}
		} finally {
			if ( is_null( $new_interval ) ) {

				/**
				 * Filter to alter the interval between the actions that delete expired transient files.
				 *
				 * @param int $interval The default time before the next action run, in seconds.
				 * @return int The time to actually wait before the next action run, in seconds.
				 *
				 * @since 8.5.0
				 */
				$new_interval = apply_filters( 'woocommerce_delete_expired_transient_files_interval', DAY_IN_SECONDS );
			}

			$next_time = $this->legacy_proxy->call_function( 'time' ) + $new_interval;
			$this->legacy_proxy->call_function( 'as_schedule_single_action', $next_time, self::CLEANUP_ACTION_NAME, array(), self::CLEANUP_ACTION_GROUP );
		}
	}

	/**
	 * Add the tools to (re)schedule and un-schedule the expired files cleanup actions in the WooCommerce debug tools page.
	 *
	 * @param array $tools_array Original debug tools array.
	 * @return array Updated debug tools array
	 */
	private function add_debug_tools_entries( array $tools_array ): array {
		$cleanup_is_scheduled = $this->expired_files_cleanup_is_scheduled();

		$tools_array['schedule_expired_transient_files_cleanup'] = array(
			'name'             => $cleanup_is_scheduled ?
				__( 'Re-schedule expired transient files cleanup', 'woocommerce' ) :
				__( 'Schedule expired transient files cleanup', 'woocommerce' ),
			'desc'             => $cleanup_is_scheduled ?
				__( 'Remove the currently scheduled action to delete expired transient files, then schedule it again for running immediately. Subsequent actions will run once every 24h.', 'woocommerce' ) :
				__( 'Schedule the action to delete expired transient files for running immediately. Subsequent actions will run once every 24h.', 'woocommerce' ),
			'button'           => $cleanup_is_scheduled ?
				__( 'Re-schedule', 'woocommerce' ) :
				__( 'Schedule', 'woocommerce' ),
			'requires_refresh' => true,
			'callback'         => array( $this, 'schedule_expired_files_cleanup' ),
		);

		if ( $cleanup_is_scheduled ) {
			$tools_array['unschedule_expired_transient_files_cleanup'] = array(
				'name'             => __( 'Un-schedule expired transient files cleanup', 'woocommerce' ),
				'desc'             => __( "Remove the currently scheduled action to delete expired transient files. Expired files won't be automatically deleted until the 'Schedule expired transient files cleanup' tool is run again.", 'woocommerce' ),
				'button'           => __( 'Un-schedule', 'woocommerce' ),
				'requires_refresh' => true,
				'callback'         => array( $this, 'unschedule_expired_files_cleanup' ),
			);
		}

		return $tools_array;
	}

	/**
	 * Delete a directory if it isn't empty.
	 *
	 * @param string $directory Full directory path.
	 */
	private function delete_directory_if_not_empty( string $directory ) {
		if ( ! ( new \FilesystemIterator( $directory ) )->valid() ) {
			rmdir( $directory );
		}
	}

	/**
	 * Handle the "init" action, add rewrite rules for the "wc/file" endpoint.
	 */
	private function handle_init() {
		add_rewrite_rule( '^wc/file/transient/?$', 'index.php?wc-transient-file-name=', 'top' );
		add_rewrite_rule( '^wc/file/transient/(.+)$', 'index.php?wc-transient-file-name=$matches[1]', 'top' );
		add_rewrite_endpoint( 'wc/file/transient', EP_ALL );
	}

	/**
	 * Handle the "query_vars" action, add the "wc-transient-file-name" variable for the "wc/file/transient" endpoint.
	 *
	 * @param array $vars The original query variables.
	 * @return array The updated query variables.
	 */
	private function handle_query_vars( $vars ) {
		$vars[] = 'wc-transient-file-name';
		return $vars;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.Missing, WordPress.WP.AlternativeFunctions

	/**
	 * Handle the "parse_request" action for the "wc/file/transient" endpoint.
	 *
	 * If the request is not for "/wc/file/transient/<filename>" or "index.php?wc-transient-file-name=filename",
	 * it returns without doing anything. Otherwise, it will serve the contents of the file with the provided name
	 * if it exists, is public and has not expired; or will return a "Not found" status otherwise.
	 *
	 * The file will be served with a content type header of "text/html".
	 */
	private function handle_parse_request() {
		global $wp;

		// phpcs:ignore WordPress.Security
		$query_arg = wp_unslash( $_GET['wc-transient-file-name'] ?? null );
		if ( ! is_null( $query_arg ) ) {
			$wp->query_vars['wc-transient-file-name'] = $query_arg;
		}

		if ( is_null( $wp->query_vars['wc-transient-file-name'] ?? null ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? null ) ) {
			status_header( 405 );
			exit();
		}

		$this->serve_file_contents( $wp->query_vars['wc-transient-file-name'] );
	}

	/**
	 * Core method to serve the contents of a transient file.
	 *
	 * @param string $file_name Transient file id or filename.
	 */
	private function serve_file_contents( string $file_name ) {
		$legacy_proxy = wc_get_container()->get( LegacyProxy::class );

		try {
			$file_path = $this->get_transient_file_path( $file_name );
			if ( is_null( $file_path ) ) {
				$legacy_proxy->call_function( 'status_header', 404 );
				$legacy_proxy->exit();
			}

			if ( $this->file_has_expired( $file_path ) ) {
				$legacy_proxy->call_function( 'status_header', 404 );
				$legacy_proxy->exit();
			}

			$file_length = filesize( $file_path );
			if ( false === $file_length ) {
				throw new Exception( "Can't retrieve file size: $file_path" );
			}

			$file_handle = fopen( $file_path, 'r' );
		} catch ( Exception $ex ) {
			$error_message = "Error serving transient file $file_name: {$ex->getMessage()}";
			wc_get_logger()->error( $error_message );

			$legacy_proxy->call_function( 'status_header', 500 );
			$legacy_proxy->exit();
		}

		$legacy_proxy->call_function( 'status_header', 200 );
		$legacy_proxy->call_function( 'header', 'Content-Type: text/html' );
		$legacy_proxy->call_function( 'header', "Content-Length: $file_length" );

		try {
			while ( ! feof( $file_handle ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo fread( $file_handle, 1024 );
			}

			/**
			 * Action that fires after a transient file has been successfully served, right before terminating the request.
			 *
			 * @param array $transient_file_info Information about the served file, as returned by get_file_by_name.
			 * @param bool $is_json_rest_api_request True if the request came from the JSON API endpoint, false if it came from the authenticated endpoint.
			 *
			 * @since 8.5.0
			 */
			do_action( 'woocommerce_transient_file_contents_served', $file_name );
		} catch ( Exception $e ) {
			wc_get_logger()->error( "Error serving transient file $file_name: {$e->getMessage()}" );
			// We can't change the response status code at this point.
		} finally {
			fclose( $file_handle );
			$legacy_proxy->exit();
		}
	}
}
