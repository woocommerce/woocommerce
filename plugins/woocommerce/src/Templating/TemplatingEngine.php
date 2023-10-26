<?php

namespace Automattic\WooCommerce\Templating;

use \Exception;
use \InvalidArgumentException;
use \OverflowException;
use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\Utilities\TimeUtil;

/**
 * Templating engine class.
 *
 * This class contains methods that allow rendering built-in template files using an arbitrary set of variables.
 * Templates live in the src/Templating/Templates directory, but hooks are in place so that extensions can use
 * the engine to render their own templates.
 *
 * Rendered templates have a random file name and an expiration date. They are stored in a directory
 * whose default route is wp-content/uploads/woocommerce_rendered_templates/yyyy-mm, where "yyyy-mm" is
 * the expiration year and month. The base route (minus the expiration date part) can be changed via dedicated hook.
 *
 * While the rendered templates are stored as files in the filesystem, metadata data about them
 * (creation and expiration dates, reachable via REST API or not) is stored in the wp_wc_rendered_templates
 * and wc_rendered_templates_meta tables.
 */
class TemplatingEngine {

	/**
	 * Default directory where templates are stored.
	 *
	 * @var string
	 */
	private string $default_templates_directory;

	/**
	 * The instance of TimeUtil to use.
	 *
	 * @var TimeUtil
	 */
	private TimeUtil $time_util;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->default_templates_directory = __DIR__ . '/Templates';
	}

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 *
	 * @internal
	 * @param TimeUtil $time_util The instance of TimeUtil to use.
	 */
	final public function init( TimeUtil $time_util ) {
		$this->time_util = $time_util;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Render a template, creating the appropriate rendered file and database entries.
	 *
	 * The template file will be searched by file name in the src/Templating/Templates directory, if the supplied
	 * name has no extension, ".template" is assumed. Subdirectories are allowed, so e.g. the template
	 * "foo/bar" matches the "src/Templating/Templates/foo/bar.template" file.
	 *
	 * The complete path of the file will be passed to the woocommerce_template_file_path filter (null will be passed
	 * if no file exists with the calculated path). This allows extensions to use their own templates.
	 *
	 * If the supplied $metadata is null, the result of the rendering will be returned directly as a string,
	 * no new files will be created and no new entries will be added to the database.
	 *
	 * Otherwise, the result of the rendering will be stored as a file with a random name (customizable via
	 * woocommerce_rendered_template_filename filter) in the directory whose name is the base location
	 * (WordPress' uploads directory plus "woocommerce_rendered_templates", changeable via the
	 * woocommerce_rendered_templates_directory filter) plus the expiration year and month formatted as 'yyyy-mm'.
	 * The name of the file will be the return value of the function.
	 *
	 * $metadata must contain at least one of "expiration_date" (GMT, in the usual Y-m-d H:i:s format) or
	 * "expiration_seconds" (a number that once added the current GMT date gives the expiration date).
	 * The minimum resulting expiration date is "now + 60 seconds", this can be changed via the
	 * woocommerce_rendered_template_minimum_expiration_seconds filter.
	 *
	 * $metadata must also contain an "is_public" key with the value `true` if the rendered files is intended
	 * to be reachable via REST API.
	 *
	 * An entry for the file will be created in the wp_wc_rendered_templates table. Any metadata other than
	 * "expiration_date", "expiration_seconds" and "is_public" will be stored as entries in the
	 * wc_rendered_templates_meta table (these entries won't be used by the templating engine itself).
	 *
	 * @param string     $template_name The name of the template to render.
	 * @param array      $variables Variables to be used for the rendering process, can be referenced in the template.
	 * @param array|null $metadata Null to return the rendering result as a string, array to generate a rendered file with the corresponding database entry.
	 * @return string The contents of the rendered template when rendering directly to a string, the name of the rendered file otherwise.
	 * @throws Exception Filesystem or database error.
	 * @throws InvalidArgumentException Template file not found, missing expiration date in $metadata, or wrong expiration date passed.
	 * @throws OverflowException Template rendering depth of 255 levels reached, possible circular reference when rendering secondary templates.
	 */
	public function render_template( string $template_name, array $variables, ?array $metadata = null ): ?string {
		global $wpdb;
		global $wp_filesystem;

		$render_to_file = ! is_null( $metadata );
		$render_ok      = false;

		// phpcs:disable WordPress.WP.AlternativeFunctions
		if ( $render_to_file ) {

			/**
			 * Filter to alter the metadata that will be stored for a template rendered with TemplatingEngine::render_template.
			 *
			 * @param array $metadata The metadata that was supplied to render_template.
			 * @param string $template_name The name of the template being rendered.
			 * @param array $variables The variables that have been supplied to render_template.
			 * @return array The metadata array that will actually be stored for the rendered template.
			 *
			 * @since 8.4.0
			 */
			$metadata = apply_filters( 'woocommerce_rendered_template_metadata', $metadata, $template_name, $variables );

			if ( array_key_exists( 'expiration_date', $metadata ) ) {
				$expiration_date = $metadata['expiration_date'];
				if ( is_numeric( $expiration_date ) ) {
					$expiration_date = gmdate( 'Y-m-d H:i:s', $expiration_date );
				} elseif ( ! is_string( $expiration_date ) || ! TimeUtil::is_valid_date( $expiration_date ) ) {
					throw new InvalidArgumentException( "$expiration_date is not a valid date, expected format: year-month-day hour:minute:second" );
				}
				unset( $metadata['expiration_date'] );
			} elseif ( array_key_exists( 'expiration_seconds', $metadata ) ) {
				$expiration_seconds = $metadata['expiration_seconds'];

				/**
				 * The minimum allowed lifetime, in seconds, of a template rendered with TemplatingEngine::render_template.
				 *
				 * @param int $lifetime_seconds The default lifetime value.
				 * @return int The actual lifetime to use, an expiration date of less than (now + lifetime) will throw an error.
				 *
				 * @since 8.4.0
				 */
				$minimum_expiration_seconds = apply_filters( 'woocommerce_rendered_template_minimum_expiration_seconds', 60 );

				if ( ! is_numeric( $expiration_seconds ) || (int) $expiration_seconds < $minimum_expiration_seconds ) {
					throw new InvalidArgumentException( 'expiration_seconds must be a number and have a minimum value of 60' );
				}
				$now_gmt         = current_time( 'mysql', true );
				$expiration_date = gmdate( 'Y-m-d H:i:s', strtotime( $now_gmt ) + (int) $expiration_seconds );
				unset( $metadata['expiration_seconds'] );
			} else {
				throw new InvalidArgumentException( 'The metadata array must have either an expiration_date key or an expiration_seconds key' );
			}

			$is_public = (bool) $metadata['is_public'] ?? false;
			unset( $metadata['is_public'] );

			$filename = bin2hex( random_bytes( 16 ) );

			/**
			 * Filer to alter the name of the physical file (NOT including the directory) where the result of TemplatingEngine::render_template will be stored.
			 *
			 * @param string $filename Default randomly generated file name.
			 * @param string $template_name The name of the template being rendered.
			 * @param array $variables The variables that have been supplied to render_template.
			 * @param array $metadata The metadata that will be stored for the rendered file.
			 * @return string The actual name that will be given to the file.
			 *
			 * @since 8.4.0
			 */
			$filename = apply_filters( 'woocommerce_rendered_template_filename', $filename, $template_name, $variables, $metadata );

			$rendered_files_directory  = $this->get_rendered_files_directory();
			$rendered_files_directory .= '/' . substr( $expiration_date, 0, 7 );
			if ( ! is_dir( $rendered_files_directory ) ) {
				mkdir( $rendered_files_directory, 0777, true );
			}
			$filepath = $rendered_files_directory . '/' . $filename;

			$file_handle = fopen( $filepath, 'w' );
			if ( ! $file_handle ) {
				throw new Exception( "Can't create file to render template $template_name" );
			}

			$ob_callback = function( $data, $flags ) use ( $file_handle ) {
				fwrite( $file_handle, $data );
				return null;
			};
		} else {
			$ob_callback = fn( $data, $flags) => null;
		}

		ob_start( $ob_callback );
		try {
			$this->render_template_core( $template_name, $variables, array(), null, false, 0 );
			if ( ! $render_to_file ) {
				$result = ob_get_contents();
				ob_end_flush();
				return $result;
			}

			ob_end_flush();
			fclose( $file_handle );
			$render_ok = true;
		} finally {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			if ( ! $render_ok && $render_to_file ) {
				unlink( $filepath );
			}
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions

		$query_ok = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}wc_rendered_templates (file_name, date_created_gmt, expiration_date_gmt, is_public) VALUES (%s, %s, %s, %d)",
				$filename,
				current_time( 'mysql', true ),
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
					"INSERT INTO {$wpdb->prefix}wc_rendered_templates_meta (rendered_template_id, meta_key, meta_value) VALUES $metadata_args_template_sql",
					$metadata_args
				)
			);

			if ( false === $query_ok ) {
				$db_error = $wpdb->last_error;
				$wpdb->delete( "{$wpdb->prefix}wc_rendered_templates", array( 'id' => $inserted_id ), array( 'id' => '%d' ) );
			}
		}

		if ( false === $query_ok ) {
			unlink( $filepath );
			throw new Exception( "Error inserting rendered template info in the database: $db_error" );
		}

		return $filename;
	}

	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Get the base directory where rendered files are stored.
	 *
	 * The default directory is the WordPress uploads directory plus "woocommerce_rendered_templates". This can
	 * be changed by using the woocommerce_rendered_templates_directory filter.
	 *
	 * @return string Effective base directory where rendered files are stored.
	 * @throws Exception The base directory (possibly changed via filter) doesn't exist.
	 */
	public function get_rendered_files_directory(): string {
		$rendered_templates_directory = untrailingslashit( wp_upload_dir()['basedir'] ) . '/woocommerce_rendered_templates';

		/**
		 * Filters the directory where the physical rendered files are stored.
		 *
		 * Note that this is used for both creating new files (with render_template) and retrieving existing files (with get_rendered_file_by_*).
		 *
		 * @param string $rendered_templates_directory The default directory for rendered files.
		 * @return string The actual directory to use for storing rendered files.
		 *
		 * @since 8.4.0
		 */
		$rendered_templates_directory = apply_filters( 'woocommerce_rendered_templates_directory', $rendered_templates_directory );

		$realpathed_rendered_templates_directory = realpath( $rendered_templates_directory );
		if ( false === $realpathed_rendered_templates_directory ) {
			throw new Exception( "The base rendered templates directory doesn't exist: $rendered_templates_directory" );
		}

		return untrailingslashit( $realpathed_rendered_templates_directory );
	}

	/**
	 * This method does the actual rendering of template, either a "root" one (directly from the render_template method)
	 * or a "secondary" one (via invoking "$this->render" from within the template itself). It takes care of resolving
	 * the template path (including invoking the woocommerce_template_file_path filter), creating the instance of
	 * TemplateRenderingContext that the template will see as "$this", and detecting/throwing unclosed block errors.
	 *
	 * @param string      $template_name The name of the template to render.
	 * @param array       $variables Variables to be used for the rendering process, can be referenced in the template.
	 * @param array       $blocks Blocks defined by the parent template, always empty when rendering a root template.
	 * @param string|null $parent_template_path Full path of the parent template file, null when rendering a root template.
	 * @param bool        $relative True when rendering a secondary template with the $relative parameter passed as true to "$this->render".
	 * @param int         $depth Secondary template rendering depth, used to detect infinite loops.
	 * @throws InvalidArgumentException Template file not found.
	 * @throws Exception Unclosed block found after rendering the template.
	 * @throws OverflowException Template rendering depth of 255 levels reached, possible circular reference when rendering secondary templates.
	 */
	private function render_template_core( string $template_name, array $variables, array $blocks, ?string $parent_template_path, bool $relative, int $depth ): void {
		if ( $depth >= 255 ) {
			throw new OverflowException( 'Template rendering depth of 256 levels reached, possible circular reference when rendering secondary templates.' );
		}

		$template_directory = ( $relative && ! is_null( $parent_template_path ) ) ? dirname( $parent_template_path ) : $this->default_templates_directory;
		$template_path      = $template_directory . '/' . $template_name . ( is_null( pathinfo( $template_name )['extension'] ) ? '.template' : '' );
		$template_path      = realpath( $template_path );
		if ( false === $template_path || strpos( $template_path, $template_directory . DIRECTORY_SEPARATOR ) !== 0 ) {
			$template_path = null;
		}

		/**
		 * Filters the physical path of a template file given the template name.
		 * This can be used by extensions to provide their own templates.
		 *
		 * @param string|null $template_path The default full template file path, null if no template file exists with the given name.
		 * @param string $template_name The name of the template that is being searched.
		 * @param string|null $parent_template_path Path of the parent template file when rendering a relative secondary template via "$this->render" from within the template itself, null otherwise.
		 * @return string|null The actual template file path to use, or null if no template is found with the specified name.
		 *
		 * @since 8.4.0
		 */
		$template_path = apply_filters( 'woocommerce_template_file_path', $template_path, $template_name, $relative ? $parent_template_path : null );

		if ( is_null( $template_path ) ) {
			throw new InvalidArgumentException( "Template not found: $template_name" );
		}

		$render_subtemplate_callbak =
			fn( $sub_template_name, $sub_variables, $blocks, $relative)
			=> $this->render_template_core( $sub_template_name, $sub_variables, $blocks, $template_path, $relative, $depth + 1 );
		$context                    = new TemplateRenderingContext( $render_subtemplate_callbak, $template_path, $variables, $blocks );

		// "Execute" the template file, with the instance of TemplateRenderingContext seen as "$this" from within the template code.
		$include_template_file = ( fn() => include $template_path )->bindTo( $context, $context );
		$include_template_file();

		$unclosed_block_name = $context->get_name_of_block_being_defined();
		if ( ! is_null( $unclosed_block_name ) ) {
			throw new Exception( "Unclosed block: $unclosed_block_name, rendering {$context->get_template_display_name()}" );
		}
	}

	/**
	 * Get information about an existing (as a file and as a database entry) rendered template file.
	 *
	 * If no rendered template exists with the specified id, null will be returned.
	 * Otherwise, an array will be returned with the following keys:
	 *
	 * - id (int)
	 * - file_path (string)
	 * - date_created_gmt (date, Y-m-d H:m:i)
	 * - expiration_date_gmt (date, Y-m-d H:m:i)
	 * - is_public (bool)
	 * - has_expired (bool)
	 * - metadata (array), only if $include_metadata is passed as true
	 *
	 * The metadata array, if present, will be an associative array of key => value.
	 *
	 * IMPORTANT: The physical file pointed by file_path is NOT guaranteed to exist if the rendered template has expired.
	 * This is due to how the cleanup of expired items works, see delete_expired_rendered_files.
	 *
	 * @param int  $id Database id of the rendered template entry.
	 * @param bool $include_metadata True to include the rendered template metadata in the result.
	 * @param bool $delete_if_expired If true, and the rendered template has expired, it will be deleted (with delete_rendered_file_core) and null will be returned.
	 * @return array|null Rendered template information array, or null if no entry exists with the supplied id.
	 * @throws Exception The base directory for rendered files (possibly changed via filter) doesn't exist, or error deleting the file.
	 */
	public function get_rendered_file_by_id( int $id, bool $include_metadata = false, bool $delete_if_expired = false ): ?array {
		global $wpdb;

		$sql_query =
			$wpdb->prepare(
				"SELECT id, file_name as file_path, date_created_gmt, expiration_date_gmt, is_public FROM {$wpdb->prefix}wc_rendered_templates WHERE id=%d",
				$id
			);

		return $this->get_rendered_file_core( $sql_query, $include_metadata, $delete_if_expired );
	}

	/**
	 * Get information about an existing (as a file and as a database entry) rendered template file.
	 *
	 * If no rendered template exists with the specified id, null will be returned.
	 * Otherwise, an array will be returned with the following keys:
	 *
	 * - id (int)
	 * - file_path (string)
	 * - date_created_gmt (date, Y-m-d H:m:i)
	 * - expiration_date_gmt (date, Y-m-d H:m:i)
	 * - is_public (bool)
	 * - has_expired (bool)
	 * - metadata (array), only if $include_metadata is passed as true
	 *
	 * The metadata array, if present, will be an associative array of key => value.
	 *
	 * IMPORTANT: The physical file pointed by file_path is NOT guaranteed to exist if the rendered template has expired.
	 * This is due to how the cleanup of expired items works, see delete_expired_rendered_files.
	 *
	 * The metadata array, if present, will be an associative array of key => value.
	 *
	 * @param string $file_name File name of the rendered template entry.
	 * @param bool   $include_metadata True to include the rendered template metadata in the result.
	 * @param bool   $delete_if_expired If true, and the rendered template has expired, it will be deleted (with delete_rendered_file_core) and null will be returned.
	 * @return array|null Rendered template information array, or null if no entry exists with the supplied id.
	 * @throws Exception The base directory for rendered files (possibly changed via filter) doesn't exist, or error deleting the file.
	 */
	public function get_rendered_file_by_name( string $file_name, bool $include_metadata = false, bool $delete_if_expired = false ): ?array {
		global $wpdb;

		$sql_query =
			$wpdb->prepare(
				"SELECT id, file_name as file_path, date_created_gmt, expiration_date_gmt, is_public FROM {$wpdb->prefix}wc_rendered_templates WHERE file_name=%s",
				$file_name
			);

		return $this->get_rendered_file_core( $sql_query, $include_metadata, $delete_if_expired );
	}

	/**
	 * Core method to get information about a rendered template file.
	 *
	 * @param string $sql_query The SQL query to get the row corresponding to a rendered file id or name.
	 * @param bool   $include_metadata True to include the rendered template metadata in the result.
	 * @param bool   $delete_if_expired If true, and the rendered template has expired, it will be deleted (with delete_rendered_file_core) and null will be returned.
	 * @return array|null Rendered template information array, or null if no entry exists with the supplied id.
	 * @throws Exception The base directory for rendered files (possibly changed via filter) doesn't exist, or error deleting the file.
	 */
	private function get_rendered_file_core( string $sql_query, bool $include_metadata, bool $delete_if_expired ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$data = $wpdb->get_row( $sql_query, ARRAY_A );
		if ( empty( $data ) ) {
			return null;
		}

		$data['file_path']   = $this->get_rendered_files_directory() . '/' . substr( $data['expiration_date_gmt'], 0, 7 ) . '/' . $data['file_path'];
		$data['is_public']   = (bool) $data['is_public'];
		$data['has_expired'] = strtotime( $data['expiration_date_gmt'] ) < time();

		if ( $delete_if_expired && $data['has_expired'] ) {
			$this->delete_rendered_file_core( $data );
			return null;
		}

		if ( ! $include_metadata ) {
			return $data;
		}

		$metadata         = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->prefix}wc_rendered_templates_meta WHERE rendered_template_id=%d",
				$data['id']
			),
			OBJECT_K
		);
		$data['metadata'] = array_map( fn( $value) => $value->meta_value, $metadata );

		return $data;
	}

	/**
	 * Delete a rendered file, both the physical file and the database entries.
	 *
	 * @param int $id Database id of the rendered file entry.
	 * @return bool True if the file was deleted, false if not (the file didn't exist).
	 * @throws Exception Error deleting the database entries.
	 */
	public function delete_rendered_file_by_id( int $id ): bool {
		$file_info = $this->get_rendered_file_by_id( $id );
		return $this->delete_rendered_file_core( $file_info );
	}

	/**
	 * Delete a rendered file, both the physical file and the database entries.
	 *
	 * @param string $name Filename of the rendered file entry.
	 * @return bool True if the file was deleted, false if not (the file didn't exist).
	 * @throws Exception Error deleting the database entries.
	 */
	public function delete_rendered_file_by_name( string $name ): bool {
		$file_info = $this->get_rendered_file_by_name( $name );
		return $this->delete_rendered_file_core( $file_info );
	}

	/**
	 * Core method to delete a rendered file.
	 *
	 * @param array|null $file_info File information as returned by one of the get_rendered_file_by_* methods.
	 * @return bool True if the file was deleted, false if not (the file didn't exist).
	 * @throws Exception Error deleting the database entries.
	 */
	private function delete_rendered_file_core( ?array $file_info ): bool {
		global $wpdb;

		if ( is_null( $file_info ) ) {
			return false;
		}

		$id     = $file_info['id'];
		$result = $wpdb->delete( "{$wpdb->prefix}wc_rendered_templates", array( 'id' => $id ), array( 'id' => '%d' ) );
		if ( false === $result ) {
			throw new Exception( "Error deleting template with id $id: {$wpdb->last_error}" );
		}

		$meta_result = $wpdb->delete( "{$wpdb->prefix}wc_rendered_templates_meta", array( 'rendered_template_id' => $id ), array( 'id' => '%d' ) );
		if ( false === $meta_result ) {
			throw new Exception( "Error deleting metadata for template with id $id: {$wpdb->last_error}" );
		}

		if ( is_file( $file_info['file_path'] ) ) {
			unlink( $file_info['file_path'] );
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
	 * Delete rendered files that expired in the previous month or earlier.
	 *
	 * For performance reasons (deleting pairs of database entry and physical file one by one would be slow)
	 * the following strategy is followed:
	 *
	 * 1. First physical files are deleted, starting with the ones having the earlier expiration month,
	 *    and deleting at most $limit files.
	 * 2. Only if no physical files are left to be deleted, database entries are deleted (again, starting
	 *    entries having the earlier expiration date), up to $limit entries.
	 *
	 * This strategy implies that expired database entries can exist for which the corresponding physical file
	 * doesn't exist anymore, but not the opposite (physical files without corresponding database entry).
	 * Also, it's not possible to delete files that have expired in the current month.
	 *
	 * @param int $limit Maximum number of files to delete.
	 * @return array "files_deleted" with the count of physical files deleted, "rows_deleted" with the count of rows deleted from wp_rendered_templates.
	 * @throws Exception The base directory for rendered templates (possibly changed via filter) doesn't exist, database error.
	 */
	public function delete_expired_rendered_files( int $limit = 1000 ): array {
		$expiration_date_gmt = $this->time_util->first_day_of_month( 'now', true );

		$delete_files_result = $this->delete_expired_rendered_files_from_filesystem( $expiration_date_gmt, $limit );
		$rows_deleted_count  =
			$delete_files_result['files_remain'] ?
			0 :
			$this->delete_expired_rendered_files_from_db( $expiration_date_gmt, $limit );

		return array(
			'files_deleted' => $delete_files_result['deleted_count'],
			'rows_deleted'  => $rows_deleted_count,
		);
	}

	/**
	 * Delete expired rendered files from the filesystem.
	 *
	 * @param string $expiration_date_gmt Cutoff date to consider a file as expired (only year and month will be used).
	 * @param int    $limit Maximum number of files to delete.
	 * @return array "deleted_count" with the number of files actually deleted, "files_remain" that will be true if there are still files left to delete.
	 * @throws Exception The base directory for rendered templates (possibly changed via filter) doesn't exist.
	 */
	private function delete_expired_rendered_files_from_filesystem( string $expiration_date_gmt, int $limit ): array {
		$base_dir = $this->get_rendered_files_directory();
		$subdirs  = glob( $base_dir . '/2[0-9][0-9][0-9]-[0-3][0-9]', GLOB_ONLYDIR );
		if ( false === $subdirs ) {
			throw new Exception( "Error when getting the list of subdirectories of $base_dir" );
		}

		$subdirs                   = array_map( fn( $name) => substr( $name, strlen( $name ) - 7, 7 ), $subdirs );
		$expiration_year_and_month = substr( $expiration_date_gmt, 0, 7 );
		$expired_subdirs           = array_filter( $subdirs, fn( $name) => $name < $expiration_year_and_month );
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
			array_map( 'unlink', $files_to_delete );
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
	 * Delete expired rendered files from the database.
	 *
	 * @param string $expiration_date_gmt Cutoff date to consider a file as expired.
	 * @param int    $limit Maximum number of database entries to delete.
	 * @return int How many entries have been actually deleted.
	 * @throws Exception Database error.
	 */
	private function delete_expired_rendered_files_from_db( string $expiration_date_gmt, int $limit ): int {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM {$wpdb->prefix}wc_rendered_templates WHERE expiration_date_gmt<%s ORDER BY expiration_date_gmt LIMIT $limit",
				$expiration_date_gmt
			)
		);
		if ( empty( $ids ) ) {
			return 0;
		}

		$ids = StringUtil::to_sql_list( $ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_result = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_rendered_templates_meta WHERE rendered_template_id in $ids" );
		if ( false === $meta_result ) {
			throw new Exception( "Error deleting metadata for templates expired as of $expiration_date_gmt GMT: {$wpdb->last_error}" );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_rendered_templates WHERE id in $ids" );
		if ( false === $result ) {
			throw new Exception( "Error deleting templates expired as of $expiration_date_gmt GMT: {$wpdb->last_error}" );
		}

		return $result;
	}

	/**
	 * Get the database schema for the templating engine.
	 *
	 * @return string SQL query to create the required database tables.
	 */
	public function get_database_schema(): string {
		global $wpdb;

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		return "
CREATE TABLE {$wpdb->prefix}wc_rendered_templates(
    id bigint(20) unsigned NOT NULL auto_increment primary key,
	file_name varchar(255) NOT NULL,
	date_created_gmt datetime NOT NULL,
	expiration_date_gmt datetime NOT NULL,
	is_public tinyint NOT NULL default 0,
	KEY file_name_key (file_name)
)
$collate;

CREATE TABLE {$wpdb->prefix}wc_rendered_templates_meta(
    id bigint(20) unsigned NOT NULL auto_increment primary key,
	rendered_template_id bigint(20) unsigned NOT NULL,
	meta_key varchar(255) NOT NULL,
	meta_value text null,
	KEY rendered_template_id_meta_key (rendered_template_id, meta_key)
)
$collate;";
	}
}
