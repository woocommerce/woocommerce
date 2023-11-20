<?php

namespace Automattic\WooCommerce\TransientFiles;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use \Exception;
use \InvalidArgumentException;
use \OverflowException;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\Utilities\TimeUtil;

/**
 * Transient files engine class.
 *
 * This class contains methods that allow creating files that have an expiration date.
 *
 * Although the engine is file contents agnostic, as of now the only way to create transient files is
 * by rendering them from text (and code)-based templates using the create_file_by_rendering_template method,
 * which accepts a template file name and a set of variables to be referred to by the code in the template itself.
 * Templates live in the src/TransientFiles/Templates directory, but hooks are in place so that extensions can use
 * the engine to create transient files by rendering their own templates.
 *
 * Transient files have a random file name and an expiration date. Files are stored in a directory
 * whose default route is wp-content/uploads/woocommerce_transient_files/yyyy-mm-dd, where "yyyy-mm-dd" is
 * the expiration date (year, month and day). The base route (minus the expiration date part) can be changed
 * via a dedicated hook.
 *
 * While the transient files are stored as actual files in the filesystem, metadata data about them
 * (creation and expiration dates, reachable via REST API or not) is stored in the wp_wc_transient_files
 * and wc_transient_files_meta tables.
 *
 * Transient files that are public and haven't expired can be obtained remotely via a dedicated URL,
 * this is handled by the TransientFilesRestController class.
 *
 * Cleanup of expired files is handled by the delete_expired_files method, which can be invoked manually
 * but there's a dedicated scheduled action that will invoke it that can be started and stopped via a dedicated tool
 * available in the WooCommerce tools page. The action runs once per day but this can be customized
 * via a dedicated hook.
 */
class TransientFilesEngine {

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
	 * Class constructor.
	 */
	public function __construct() {
		self::add_action( self::CLEANUP_ACTION_NAME, array( $this, 'handle_expired_files_cleanup_action' ) );
		self::add_filter( 'woocommerce_debug_tools', array( $this, 'add_debug_tools_entries' ), 999, 1 );
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

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Create a transient file (and the appropriate database entries) by rendering a template.
	 *
	 * The template file will be searched by file name in the src/TransientFiles/Templates directory. If the supplied
	 * name has no extension, ".template" is assumed. Subdirectories are allowed, so e.g. the template
	 * "foo/bar" matches the "src/TransientFiles/Templates/foo/bar.template" file.
	 *
	 * The complete path of the file will be passed to the woocommerce_transient_file_creation_template_file_path filter
	 * (null will be passed if no file exists with the calculated path). This allows extensions to use their own templates.
	 *
	 * If the supplied $metadata is null, the result of the rendering will be returned directly as a string,
	 * no new files will be created and no new entries will be added to the database.
	 *
	 * Otherwise, the result of the rendering will be stored as a file with a random name (customizable via
	 * woocommerce_transient_file_creation_filename filter) in the directory whose name is the base location
	 * (WordPress' uploads directory plus "woocommerce_transient_files", changeable via the
	 * woocommerce_transient_files_directory filter) plus the expiration date formatted as 'yyyy-mm-dd'.
	 * The name of the file will become the return value of the function.
	 *
	 * $metadata must contain at least one of "expiration_date" (GMT, in the usual Y-m-d H:i:s format) or
	 * "expiration_seconds" (a number that once added the current GMT date gives the expiration date).
	 * The minimum resulting expiration date is "now + 60 seconds", this can be changed via the
	 * woocommerce_transient_files_minimum_expiration_seconds filter.
	 *
	 * $metadata must also contain an "is_public" key with the value `true` if the transient files is intended
	 * to be reachable via unauthenticated URL. This is handled by the TransientFilesRestController class.
	 *
	 * An entry for the file will be created in the wp_wc_transient_files table. Any metadata other than
	 * "expiration_date", "expiration_seconds" and "is_public" will be stored as entries in the
	 * wc_transient_files_meta table (these entries won't be used by the transient files engine itself).
	 *
	 * @param string     $template_name The name of the template to render.
	 * @param array      $variables Variables to be used for the rendering process, can be referenced in the template.
	 * @param array|null $metadata Null to return the rendering result as a string, array to generate a transient file and the corresponding database entries.
	 * @return string The result of the rendering process when rendering directly to a string, the name of the created transient file otherwise.
	 * @throws Exception Filesystem or database error.
	 * @throws InvalidArgumentException Template file not found, missing expiration date in $metadata, or wrong expiration date passed.
	 * @throws OverflowException Template rendering depth of 255 levels reached, possible circular reference when rendering secondary templates.
	 */
	public function create_file_by_rendering_template( string $template_name, array $variables, ?array $metadata = null ): ?string {
		global $wpdb;

		$render_to_file = ! is_null( $metadata );
		$render_ok      = false;

		// phpcs:disable WordPress.WP.AlternativeFunctions
		if ( $render_to_file ) {

			$transient_file_creation_data = array(
				'created_via'   => 'template',
				'template_name' => $template_name,
				'variables'     => $variables,
			);

			/**
			 * Filter to alter the metadata that will be stored for a transient file created with the TransientFilesEngine class.
			 *
			 * @param array $metadata The metadata that was supplied when creating the transient file.
			 * @param array $transient_file_creation_data An array with the following keys: 'created_via' = 'template', 'template_name', 'variables.
			 * @return array The metadata array that will actually be stored for the transient file.
			 *
			 * @since 8.5.0
			 */
			$metadata = apply_filters( 'woocommerce_transient_file_creation_metadata', $metadata, $transient_file_creation_data );

			if ( ! is_null( $metadata['expiration_date'] ?? null ) ) {
				$expiration_date = $metadata['expiration_date'];
				if ( is_numeric( $expiration_date ) ) {
					$expiration_date = gmdate( 'Y-m-d H:i:s', $expiration_date );
				} elseif ( ! is_string( $expiration_date ) || ! TimeUtil::is_valid_date( $expiration_date ) ) {
					$expiration_date = is_scalar( $expiration_date ) ? $expiration_date : gettype( $expiration_date );
					throw new InvalidArgumentException( "$expiration_date is not a valid date, expected format: year-month-day hour:minute:second" );
				}

				$minimum_expiration_seconds = $this->get_minimum_expiration_seconds();
				$expiration_date_timestamp  = strtotime( $metadata['expiration_date'] );
				$now_timestamp              = $this->legacy_proxy->call_function( 'current_time', 'timestamp', true );
				if ( $expiration_date_timestamp < $now_timestamp + $minimum_expiration_seconds ) {
					throw new InvalidArgumentException( "expiration_date must be higher than the current time plus $minimum_expiration_seconds seconds, got {$metadata['expiration_date']}" );
				}
			} elseif ( ! is_null( $metadata['expiration_seconds'] ?? null ) ) {
				$expiration_seconds         = $metadata['expiration_seconds'];
				$minimum_expiration_seconds = $this->get_minimum_expiration_seconds();

				if ( ! is_numeric( $expiration_seconds ) || (int) $expiration_seconds < $minimum_expiration_seconds ) {
					$expiration_seconds = is_scalar( $expiration_seconds ) ? $expiration_seconds : gettype( $expiration_seconds );
					throw new InvalidArgumentException( "expiration_seconds must be a number and have a minimum value of $minimum_expiration_seconds, got $expiration_seconds" );
				}
				$now_gmt         = $this->legacy_proxy->call_function( 'current_time', 'mysql', true );
				$expiration_date = gmdate( 'Y-m-d H:i:s', strtotime( $now_gmt ) + (int) $expiration_seconds );
			} else {
				throw new InvalidArgumentException( 'The metadata array must have either an expiration_date key or an expiration_seconds key' );
			}

			unset( $metadata['expiration_date'] );
			unset( $metadata['expiration_seconds'] );

			$is_public = (bool) ( $metadata['is_public'] ?? false );
			unset( $metadata['is_public'] );

			$filename = bin2hex( $this->legacy_proxy->call_function( 'random_bytes', 16 ) );

			/**
			 * Filer to alter the name of the physical file (NOT including the directory) of a newly created transient file.
			 *
			 * @param string $filename Default randomly generated file name.
			 * @param array $transient_file_creation_data An array with the following keys: 'created_via' = 'template', 'template_name', 'variables.
			 * @param array $metadata The metadata that will be stored for the transient file (not including expiration date and 'is_public').
			 * @return string The actual name that will be given to the file.
			 *
			 * @since 8.5.0
			 */
			$filename = apply_filters( 'woocommerce_transient_file_creation_filename', $filename, $transient_file_creation_data, $metadata );

			$transient_files_directory  = $this->get_transient_files_directory();
			$transient_files_directory .= '/' . substr( $expiration_date, 0, 10 );
			if ( ! $this->legacy_proxy->call_function( 'is_dir', $transient_files_directory ) ) {
				if ( ! $this->legacy_proxy->call_function( 'wp_mkdir_p', $transient_files_directory ) ) {
					throw new Exception( "Can't create directory: $transient_files_directory" );
				}
			}
			$filepath = $transient_files_directory . '/' . $filename;

			$file_handle = $this->legacy_proxy->call_function( 'fopen', $filepath, 'w' );
			if ( ! $file_handle ) {
				throw new Exception( "Can't create transient file, rendering template '$template_name'" );
			}

			$ob_callback = function( $data, $flags ) use ( $file_handle ) {
				fwrite( $file_handle, $data );
				return null;
			};
		} else {
			$ob_callback = fn( $data, $flags ) => null;
		}

		$ob_initial_level = ob_get_level();
		ob_start( $ob_callback );
		try {
			$this->create_file_by_rendering_template_core( $template_name, $variables, null, false, 0 );
			if ( ! $render_to_file ) {
				$result = ob_get_contents();
				ob_end_flush();
				return $result;
			}

			ob_end_flush();
			fclose( $file_handle );
			$render_ok = true;
		} finally {
			while ( ob_get_level() > $ob_initial_level ) {
				// This will happen only in case of unhandled error.
				ob_end_clean();
			}
			if ( ! $render_ok && $render_to_file ) {
				wp_delete_file( $filepath );
			}
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions

		$query_ok = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}wc_transient_files (file_name, date_created_gmt, expiration_date_gmt, is_public) VALUES (%s, %s, %s, %d)",
				$filename,
				$this->legacy_proxy->call_function( 'current_time', 'mysql', true ),
				$expiration_date,
				$is_public
			)
		);

		$db_error = $wpdb->last_error;
		if ( false !== $query_ok && ! empty( $metadata ) ) {
			$metadata_args_template = array();
			$metadata_args          = array();
			$inserted_id            = $wpdb->insert_id;

			foreach ( $metadata as $metadata_key => $metadata_value ) {
				$metadata_args_template[] = '(%d, %s, %s)';
				$metadata_args[]          = $inserted_id;
				$metadata_args[]          = $metadata_key;
				$metadata_args[]          = $metadata_value;
			}

			$metadata_args_template_sql = join( ',', $metadata_args_template );

			$query_ok = $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"INSERT INTO {$wpdb->prefix}wc_transient_files_meta (transient_file_id, meta_key, meta_value) VALUES $metadata_args_template_sql",
					$metadata_args
				)
			);

			if ( false === $query_ok ) {
				$db_error = $wpdb->last_error;
				$wpdb->delete( "{$wpdb->prefix}wc_transient_files", array( 'id' => $inserted_id ), array( 'id' => '%d' ) );
			}
		}

		if ( false === $query_ok ) {
			wp_delete_file( $filepath );
			throw new Exception( "Error inserting transient file info in the database: $db_error" );
		}

		return $filename;
	}

	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Get the minimum allowed value for the expiration of new transient files, in seconds.
	 *
	 * @return int
	 */
	private function get_minimum_expiration_seconds() {
		/**
		 * The minimum allowed lifetime, in seconds, for a newly created transient file.
		 *
		 * @param int $lifetime_seconds The default lifetime value.
		 * @return int The actual lifetime to use, an expiration date of less than (now + lifetime) will throw an error.
		 *
		 * @since 8.5.0
		 */
		return apply_filters( 'woocommerce_transient_files_minimum_expiration_seconds', 60 );
	}

	/**
	 * Get the base directory where transient files are stored.
	 *
	 * The default directory is the WordPress uploads directory plus "woocommerce_transient_files". This can
	 * be changed by using the woocommerce_transient_files_directory filter.
	 *
	 * @return string Effective base directory where transient files are stored.
	 * @throws Exception The base directory (possibly changed via filter) doesn't exist.
	 */
	public function get_transient_files_directory(): string {
		$upload_dir_info           = $this->legacy_proxy->call_function( 'wp_upload_dir' );
		$transient_files_directory = untrailingslashit( $upload_dir_info['basedir'] ) . '/woocommerce_transient_files';

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
		$transient_files_directory = apply_filters( 'woocommerce_transient_files_directory', $transient_files_directory );

		$realpathed_transient_files_directory = $this->legacy_proxy->call_function( 'realpath', $transient_files_directory );
		if ( false === $realpathed_transient_files_directory ) {
			throw new Exception( "The base transient files directory doesn't exist: $transient_files_directory" );
		}

		return untrailingslashit( $realpathed_transient_files_directory );
	}

	/**
	 * This method does the actual rendering of a template, either a "root" one (directly from the create_file_by_rendering_template method)
	 * or a "secondary" one (via invoking "$this->render" from within the template itself). It takes care of resolving
	 * the template path (including invoking the woocommerce_transient_file_creation_template_file_path filter) and creating the instance of
	 * TemplateRenderingContext that the template will see as "$this".
	 *
	 * @param string      $template_name The name of the template to render.
	 * @param array       $variables Variables to be used for the rendering process, can be referenced in the template.
	 * @param string|null $parent_template_path Full path of the parent template file, null when rendering a root template.
	 * @param bool        $relative True when rendering a secondary template with the $relative parameter passed as true to "$this->render".
	 * @param int         $depth Secondary template rendering depth, used to detect infinite loops.
	 * @throws InvalidArgumentException Template file not found.
	 * @throws OverflowException Template rendering depth of 255 levels reached, possible circular reference when rendering secondary templates.
	 */
	private function create_file_by_rendering_template_core( string $template_name, array $variables, ?string $parent_template_path, bool $relative, int $depth ): void {
		if ( $depth >= 255 ) {
			throw new OverflowException( 'Template rendering depth of 256 levels reached, possible circular reference when rendering secondary templates.' );
		}

		$template_directory = ( $relative && ! is_null( $parent_template_path ) ) ? dirname( $parent_template_path ) : $this->get_default_templates_directory();
		$template_path      = $template_directory . '/' . $template_name . ( is_null( pathinfo( $template_name )['extension'] ?? null ) ? '.template' : '' );
		$template_path      = $this->legacy_proxy->call_function( 'realpath', $template_path );

		if ( false === $template_path || strpos( $template_path, $template_directory . DIRECTORY_SEPARATOR ) !== 0 ) {
			$template_path = null;
		}

		/**
		 * Filters the physical path of a template file used to create a transient file, given the template name.
		 * This can be used by extensions to provide their own templates.
		 *
		 * @param string|null $template_path The default full template file path, null if no template file exists with the given name.
		 * @param string $template_name The name of the template that is being searched.
		 * @param string|null $parent_template_path Path of the parent template file when rendering a relative secondary template via "$this->render" from within the template itself, null otherwise.
		 * @return string|null The actual template file path to use, or null if no template is found with the specified name.
		 *
		 * @since 8.5.0
		 */
		$template_path = apply_filters( 'woocommerce_transient_file_creation_template_file_path', $template_path, $template_name, $relative ? $parent_template_path : null );

		if ( is_null( $template_path ) ) {
			throw new InvalidArgumentException( "Template not found: $template_name" );
		}

		$render_subtemplate_callbak =
			fn( $sub_template_name, $sub_variables, $relative)
			=> $this->create_file_by_rendering_template_core( $sub_template_name, $sub_variables, $template_path, $relative, $depth + 1 );
		$context                    = new TemplateRenderingContext( $render_subtemplate_callbak, $template_path, $variables );

		// "Execute" the template file, with the instance of TemplateRenderingContext seen as "$this" from within the template code.
		$include_template_file = ( fn() => include $template_path )->bindTo( $context, $context );
		$include_template_file();
	}

	/**
	 * Get the default base directory where renderizable templates are stored.
	 *
	 * @return string
	 */
	private function get_default_templates_directory(): string {
		return $this->legacy_proxy->call_function( 'dirname', __FILE__ ) . '/Templates';
	}

	/**
	 * Get information about an existing transient file, identifying it by its database id.
	 *
	 * If no transient file exists with the specified id, null will be returned.
	 * Otherwise, an array will be returned with the following keys:
	 *
	 * - id (int)
	 * - file_name (string)
	 * - file_path (string)
	 * - date_created_gmt (date, Y-m-d H:m:i)
	 * - expiration_date_gmt (date, Y-m-d H:m:i)
	 * - is_public (bool)
	 * - has_expired (bool)
	 * - metadata (array), only if $include_metadata is passed as true
	 *
	 * The metadata array, if present, will be an associative array of key => value.
	 *
	 * IMPORTANT: The physical file pointed by file_path is NOT guaranteed to exist if the transient file has expired.
	 * This is due to how the cleanup of expired items works, see delete_expired_files.
	 *
	 * @param int  $id Database id of the transient file entry.
	 * @param bool $include_metadata True to include the transient file metadata in the result.
	 * @param bool $delete_if_expired If true, and the transient file has expired, it will be deleted (with delete_file_core) and null will be returned.
	 * @return array|null Transient file information array, or null if no entry exists with the supplied id.
	 * @throws Exception The base directory for transient files (possibly changed via filter) doesn't exist, or error deleting the file.
	 */
	public function get_file_by_id( int $id, bool $include_metadata = false, bool $delete_if_expired = false ): ?array {
		global $wpdb;

		$sql_query =
			$wpdb->prepare(
				"SELECT id, file_name, date_created_gmt, expiration_date_gmt, is_public FROM {$wpdb->prefix}wc_transient_files WHERE id=%d",
				$id
			);

		return $this->get_file_core( $sql_query, $include_metadata, $delete_if_expired );
	}

	/**
	 * Get information about an existing transient file, identifying it by its file name.
	 *
	 * If no transient file exists with the specified file name, null will be returned.
	 * Otherwise, an array will be returned with the following keys:
	 *
	 * - id (int)
	 * - file_name (string)
	 * - file_path (string)
	 * - date_created_gmt (date, Y-m-d H:m:i)
	 * - expiration_date_gmt (date, Y-m-d H:m:i)
	 * - is_public (bool)
	 * - has_expired (bool)
	 * - metadata (array), only if $include_metadata is passed as true
	 *
	 * The metadata array, if present, will be an associative array of key => value.
	 *
	 * IMPORTANT: The physical file pointed by file_path is NOT guaranteed to exist if the transient file has expired.
	 * This is due to how the cleanup of expired items works, see delete_expired_files.
	 *
	 * @param string $file_name File name of the transient file.
	 * @param bool   $include_metadata True to include the transient file metadata in the result.
	 * @param bool   $delete_if_expired If true, and the transient file has expired, it will be deleted (with delete_file_core) and null will be returned.
	 * @return array|null Transient file information array, or null if no entry exists with the supplied file name.
	 * @throws Exception The base directory for transient files (possibly changed via filter) doesn't exist, or error deleting the file.
	 */
	public function get_file_by_name( string $file_name, bool $include_metadata = false, bool $delete_if_expired = false ): ?array {
		global $wpdb;

		$sql_query =
			$wpdb->prepare(
				"SELECT id, file_name, date_created_gmt, expiration_date_gmt, is_public FROM {$wpdb->prefix}wc_transient_files WHERE file_name=%s",
				$file_name
			);

		return $this->get_file_core( $sql_query, $include_metadata, $delete_if_expired );
	}

	/**
	 * Core method to get information about a transient file.
	 *
	 * @param string $sql_query The SQL query to get the row corresponding to a transient file id or name.
	 * @param bool   $include_metadata True to include the transient file metadata in the result.
	 * @param bool   $delete_if_expired If true, and the transient file has expired, it will be deleted (with delete_file_core) and null will be returned.
	 * @return array|null Transient file information array, or null if no file exists with the supplied id.
	 * @throws Exception The base directory for transient files (possibly changed via filter) doesn't exist, or error deleting the file.
	 */
	private function get_file_core( string $sql_query, bool $include_metadata, bool $delete_if_expired ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$data = $wpdb->get_row( $sql_query, ARRAY_A );
		if ( empty( $data ) ) {
			return null;
		}

		$data['file_path']   = $this->get_transient_files_directory() . '/' . substr( $data['expiration_date_gmt'], 0, 10 ) . '/' . $data['file_name'];
		$data['is_public']   = (bool) $data['is_public'];
		$data['has_expired'] = strtotime( $data['expiration_date_gmt'] ) < time();

		if ( $delete_if_expired && $data['has_expired'] ) {
			$this->delete_file_core( $data );
			return null;
		}

		if ( ! $include_metadata ) {
			return $data;
		}

		$metadata         = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->prefix}wc_transient_files_meta WHERE transient_file_id=%d",
				$data['id']
			),
			OBJECT_K
		);
		$data['metadata'] = array_map( fn( $value) => $value->meta_value, $metadata );

		return $data;
	}

	/**
	 * Delete a transient file, both the physical file and the database entries.
	 *
	 * @param int $id Database id of the transient file entry.
	 * @return bool True if the file was deleted, false if not (the file didn't exist).
	 * @throws Exception Error deleting the file or the database entries.
	 */
	public function delete_file_by_id( int $id ): bool {
		$file_info = $this->get_file_by_id( $id );
		return $this->delete_file_core( $file_info );
	}

	/**
	 * Delete a transient file, both the physical file and the database entries.
	 *
	 * @param string $name Filename of the transient file entry.
	 * @return bool True if the file was deleted, false if not (the file didn't exist).
	 * @throws Exception Error deleting the file or the database entries.
	 */
	public function delete_file_by_name( string $name ): bool {
		$file_info = $this->get_file_by_name( $name );
		return $this->delete_file_core( $file_info );
	}

	/**
	 * Core method to delete a transient file.
	 *
	 * @param array|null $file_info File information as returned by one of the get_file_by_* methods.
	 * @return bool True if the file was deleted, false if not (the file didn't exist).
	 * @throws Exception Error deleting the database entries.
	 */
	private function delete_file_core( ?array $file_info ): bool {
		global $wpdb;

		if ( is_null( $file_info ) ) {
			return false;
		}

		$id     = $file_info['id'];
		$result = $wpdb->delete( "{$wpdb->prefix}wc_transient_files", array( 'id' => $id ), array( 'id' => '%d' ) );
		if ( false === $result ) {
			throw new Exception( "Error deleting transient file with id $id: {$wpdb->last_error}" );
		}

		$meta_result = $wpdb->delete( "{$wpdb->prefix}wc_transient_files_meta", array( 'transient_file_id' => $id ), array( 'id' => '%d' ) );
		if ( false === $meta_result ) {
			throw new Exception( "Error deleting metadata for transient file with id $id: {$wpdb->last_error}" );
		}

		if ( is_file( $file_info['file_path'] ) ) {
			wp_delete_file( $file_info['file_path'] );
		}

		try {
			$this->delete_directory_if_not_empty( dirname( $file_info['file_path'] ) );
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( Exception $e ) {
			// The directory doesn't exist.
			// Could happen because the cleanup process may delete files (and empty directories)
			// while still keeping the corresponding database entries.
		}

		return $result > 0;
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
	 * Delete transient files that expired in the previous day or earlier.
	 *
	 * For performance reasons (deleting pairs of database entry and physical file one by one would be slow)
	 * the following strategy is followed:
	 *
	 * 1. First physical files are deleted, starting with the ones having the earlier expiration day,
	 *    and deleting at most $limit files.
	 * 2. Only if no physical files are left to be deleted, database entries are deleted (again, starting
	 *    entries having the earlier expiration date), up to $limit entries.
	 *
	 * This strategy implies that expired database entries can exist for which the corresponding physical file
	 * doesn't exist anymore, but not the opposite (physical files without corresponding database entry).
	 * Also, it's not possible to delete files that have expired in the current day.
	 *
	 * @param int $limit Maximum number of files to delete.
	 * @return array "files_deleted" with the count of physical files deleted, "rows_deleted" with the count of rows deleted from the main database table.
	 * @throws Exception The base directory for transient files (possibly changed via filter) doesn't exist, database error.
	 */
	public function delete_expired_files( int $limit = 1000 ): array {
		$expiration_date_gmt = $this->legacy_proxy->call_function( 'current_time', 'mysql', true );
		$expiration_date_gmt = substr( $expiration_date_gmt, 0, 10 ) . ' 00:00:00';

		$delete_files_result = $this->delete_expired_files_from_filesystem( $expiration_date_gmt, $limit );
		$rows_deleted_count  =
			$delete_files_result['files_remain'] ?
			0 :
			$this->delete_expired_files_from_db( $expiration_date_gmt, $limit );

		return array(
			'files_deleted' => $delete_files_result['deleted_count'],
			'rows_deleted'  => $rows_deleted_count,
		);
	}

	/**
	 * Delete expired transient files from the filesystem.
	 *
	 * @param string $expiration_date_gmt Cutoff date to consider a file as expired (time of day will not be used).
	 * @param int    $limit Maximum number of files to delete.
	 * @return array "deleted_count" with the number of files actually deleted, "files_remain" that will be true if there are still files left to delete.
	 * @throws Exception The base directory for transient files (possibly changed via filter) doesn't exist.
	 */
	private function delete_expired_files_from_filesystem( string $expiration_date_gmt, int $limit ): array {
		$base_dir = $this->get_transient_files_directory();
		$subdirs  = glob( $base_dir . '/[2-9][0-9][0-9][0-9]-[01][0-9]-[0-3][0-9]', GLOB_ONLYDIR );
		if ( false === $subdirs ) {
			throw new Exception( "Error when getting the list of subdirectories of $base_dir" );
		}

		$subdirs                   = array_map( fn( $name ) => substr( $name, strlen( $name ) - 10, 10 ), $subdirs );
		$expiration_year_month_day = substr( $expiration_date_gmt, 0, 10 );
		$expired_subdirs           = array_filter( $subdirs, fn( $name ) => $name < $expiration_year_month_day );
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
	 * Delete expired transient files from the database.
	 *
	 * @param string $expiration_date_gmt Cutoff date to consider a file as expired.
	 * @param int    $limit Maximum number of database entries to delete.
	 * @return int How many entries have been actually deleted.
	 * @throws Exception Database error.
	 */
	private function delete_expired_files_from_db( string $expiration_date_gmt, int $limit ): int {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM {$wpdb->prefix}wc_transient_files WHERE expiration_date_gmt<%s ORDER BY expiration_date_gmt LIMIT $limit",
				$expiration_date_gmt
			)
		);
		if ( empty( $ids ) ) {
			return 0;
		}

		$ids = StringUtil::to_sql_list( $ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_result = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_transient_files_meta WHERE transient_file_id in $ids" );
		if ( false === $meta_result ) {
			throw new Exception( "Error deleting metadata for transient files expired as of $expiration_date_gmt GMT: {$wpdb->last_error}" );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_transient_files WHERE id in $ids" );
		if ( false === $result ) {
			throw new Exception( "Error deleting transient files expired as of $expiration_date_gmt GMT: {$wpdb->last_error}" );
		}

		return $result;
	}

	/**
	 * Get the database schema for the transient files engine.
	 *
	 * @return string SQL query to create the required database tables.
	 */
	public function get_database_schema(): string {
		global $wpdb;

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		return "
CREATE TABLE {$wpdb->prefix}wc_transient_files (
    id bigint(20) unsigned NOT NULL auto_increment primary key,
	file_name varchar(255) NOT NULL,
	date_created_gmt datetime NOT NULL,
	expiration_date_gmt datetime NOT NULL,
	is_public tinyint NOT NULL default 0,
	KEY file_name_key (file_name)
)
$collate;

CREATE TABLE {$wpdb->prefix}wc_transient_files_meta (
    id bigint(20) unsigned NOT NULL auto_increment primary key,
	transient_file_id bigint(20) unsigned NOT NULL,
	meta_key varchar(255) NOT NULL,
	meta_value text null,
	KEY transient_file_id_meta_key (transient_file_id, meta_key)
)
$collate;";
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
			if ( $result['files_deleted'] + $result['rows_deleted'] > 0 ) {
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
}
