<?php

namespace Automattic\WooCommerce\TransientFiles;

use WP_HTTP_Response;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_Error;
use \Exception;
use \InvalidArgumentException;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * REST API controller for the transient files engine. This class handles:
 *
 * 1. The JSON REST API endpoints for creating, deleting and retrieving information about transient files.
 * 2. The unauthenticated endpoint used to retrieve the contents of a transient file given its file name.
 *
 * Transient files that are public and haven't expired can be obtained remotely via the unauthenticated endpoint,
 * which is "/wc/file/transient/<filename>". The default content type will be "text/html", this can be changed if a
 * "content-type" metadata key is passed to the corresponding file creation method in TransientFilesEngine.
 * Once a transient file is successfully served, the woocommerce_transient_file_contents_served action is fired.
 *
 * The "/wp-json/wc/v3/files/transient/<filename>" endpoint is equivalent to the unauthenticated one, being the differences that
 * it requires authentication (and more precisely, the 'read_transient_file' capability) and will always
 * return the file contents, even for files that haven't been created as public.
 */
class TransientFilesRestController {

	use AccessiblePrivateMethods;

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	private string $route_namespace = 'wc/v3';

	/**
	 * The instance of TransientFilesEngine to use.
	 *
	 * @var TransientFilesEngine
	 */
	private TransientFilesEngine $transient_files_engine;

	/**
	 * Holds authentication error messages for each HTTP verb.
	 *
	 * @var array
	 */
	private array $authentication_errors_by_method;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->authentication_errors_by_method = array(
			'GET'    => array(
				'code'    => 'woocommerce_rest_cannot_view',
				'message' => __( 'Sorry, you cannot view resources.', 'woocommerce' ),
			),
			'POST'   => array(
				'code'    => 'woocommerce_rest_cannot_create',
				'message' => __( 'Sorry, you cannot create resources.', 'woocommerce' ),
			),
			'DELETE' => array(
				'code'    => 'woocommerce_rest_cannot_delete',
				'message' => __( 'Sorry, you cannot delete resources.', 'woocommerce' ),
			),
		);

		self::add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this, 'handle_woocommerce_rest_api_get_rest_namespaces' ) );

		self::add_action( 'init', array( $this, 'handle_init' ), 0 );
		self::add_filter( 'query_vars', array( $this, 'handle_query_vars' ), 0 );
		self::add_action( 'parse_request', array( $this, 'handle_parse_request' ), 0 );
	}

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 *
	 * @param TransientFilesEngine $transient_files_engine The instance of TransientFilesEngine to use.
	 * @internal
	 */
	final public function init( TransientFilesEngine $transient_files_engine ) {
		$this->transient_files_engine = $transient_files_engine;
	}

	/**
	 * Handle the woocommerce_rest_api_get_rest_namespaces filter
	 * to add ourselves to the list of REST API controllers registered by WooCommerce.
	 *
	 * @param array $namespaces The original list of WooCommerce REST API namespaces/controllers.
	 * @return array The updated list of WooCommerce REST API namespaces/controllers.
	 */
	private function handle_woocommerce_rest_api_get_rest_namespaces( array $namespaces ): array {
		$namespaces['wc/v3']['files/transient'] = self::class;
		return $namespaces;
	}

	/**
	 * Register the JSON REST API endpoints handled by this controller.
	 */
	public function register_routes() {
		self::mark_method_as_accessible( 'run' );

		register_rest_route(
			$this->route_namespace,
			'/files/transient/(?P<id_or_name>[^/]+)',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => fn( $request ) => $this->run( 'get_file_contents', $request ),
					'args'     => $this->get_args_for_get_file_contents(),
					// No permission callback, the get_file_contents method handles authentication by itself
					// because it's different for the REST API endpoint and for the unauthenticated endpoint.
					// Also no schema, since this endpoint doesn't (necessarily) return JSON.
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/files/transient/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => fn( $request ) => $this->run( 'delete_file', $request ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'delete_transient_file' ),
					'args'                => $this->get_args_for_delete_file(),
					'schema'              => $this->get_schema_for_delete_file(),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/files/transient/render',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( 'create_file_by_rendering_template', $request ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'create_transient_file' ),
					'args'                => $this->get_args_for_create_file_by_rendering_template(),
					'schema'              => $this->get_schema_for_create_file(),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/files/transient/(?P<id>[\d]+)/info',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( 'get_file_info', $request ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'read_transient_file' ),
					'args'                => $this->get_args_for_get_file_info(),
					'schema'              => $this->get_schema_for_get_file_info(),
				),
			)
		);
	}

	/**
	 * Handle a request for one of the provided JSON REST API endpoints.
	 *
	 * If an exception is thrown, the exception message will be returned as part of the response
	 * if the user has the 'manage_woocommerce' capability.
	 *
	 * @param string          $method_name The name of the class method to execute.
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response The response to send back to the client.
	 */
	private function run( string $method_name, WP_REST_Request $request ) {
		try {
			return rest_ensure_response( $this->$method_name( $request ) );
		} catch ( Exception $ex ) {
			wc_get_logger()->error( "TransientFilesRestController: when executing method $method_name: {$ex->getMessage()}" );
			return $this->internal_wp_error( $ex );
		}
	}

	/**
	 * Return an WP_Error object for an internal server error, with exception information if the current user is an admin.
	 *
	 * @param Exception $exception The exception to maybe include information from.
	 * @return WP_Error
	 */
	private function internal_wp_error( Exception $exception ) {
		$data = array( 'status' => 500 );
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$data['exception_message'] = $exception->getMessage();
		}

		return new WP_Error( 'woocommerce_rest_internal_error', __( 'Internal server error', 'woocommerce' ), $data );
	}

	/**
	 * Permission check for JSON REST API endpoints.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @param string          $required_capability_name The name of the required capability.
	 * @return bool|WP_Error True if the current user has the capability, an "Unauthorized" error otherwise.
	 */
	private function check_permission( WP_REST_Request $request, string $required_capability_name ) {
		return $this->check_permission_by_method( $request->get_method(), $required_capability_name );
	}

	/**
	 * Permission check for JSON REST API endpoints, given the request method.
	 *
	 * @param string $method The HTTP method of the request.
	 * @param string $required_capability_name The name of the required capability.
	 * @return bool|WP_Error True if the current user has the capability, an "Unauthorized" error otherwise.
	 */
	private function check_permission_by_method( string $method, string $required_capability_name ) {
		if ( current_user_can( $required_capability_name ) ) {
			return true;
		}

		$error_information = $this->authentication_errors_by_method[ $method ] ?? null;
		if ( is_null( $error_information ) ) {
			return false;
		}

		return new WP_Error(
			$error_information['code'],
			$error_information['message'],
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Get the details of a transient file by id.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @return WP_Error|array The response to send back to the client.
	 */
	private function get_file_info( WP_REST_Request $request ) {
		$transient_file_info = $this->transient_files_engine->get_file_by_id( $request->get_param( 'id' ), $request->get_param( 'include_metadata' ) );
		if ( is_null( $transient_file_info ) ) {
			return new WP_Error( 'woocommerce_rest_not_found', __( 'File not found', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $this->adjust_transient_file_info_for_response( $transient_file_info );
	}

	/**
	 * Create a transient file by rendering a template.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @return WP_Error|WP_REST_Response The response to send back to the client.
	 * @throws Exception The template can't be found right after having been rendered.
	 */
	private function create_file_by_rendering_template( WP_REST_Request $request ) {
		$metadata                       = $request->get_param( 'metadata' );
		$metadata['expiration_date']    = $request->get_param( 'expiration_date' );
		$metadata['expiration_seconds'] = $request->get_param( 'expiration_seconds' );
		$metadata['is_public']          = $request->get_param( 'is_public' );

		try {
			$file_name = $this->transient_files_engine->create_file_by_rendering_template( $request->get_param( 'template_name' ), $request->get_param( 'variables' ), $metadata );
		} catch ( InvalidArgumentException $ex ) {
			return new WP_Error( 'woocommerce_rest_invalid_arguments', $ex->getMessage(), array( 'status' => 400 ) );
		}

		$transient_file_info = $this->transient_files_engine->get_file_by_name( $file_name );
		if ( is_null( $transient_file_info ) ) {
			throw new Exception( "Template {$request->get_param('template_name')} has just been rendered as $file_name, but now somehow it can't be found" );
		}

		return new WP_REST_Response( $this->adjust_transient_file_info_for_response( $transient_file_info ), 201 );
	}

	/**
	 * Deletes an existing transient file.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @return array The response to send back to the client.
	 */
	private function delete_file( WP_REST_Request $request ): array {
		$deleted = $this->transient_files_engine->delete_file_by_id( $request->get_param( 'id' ) );
		return array( 'deleted' => $deleted );
	}

	/**
	 * Adjust an array of data representing a transient file so that it's suitable to be returned to the client.
	 *
	 * @param array $transient_file_info The array to adjust.
	 * @return array The adjusted array.
	 */
	private function adjust_transient_file_info_for_response( array $transient_file_info ): array {
		unset( $transient_file_info['file_path'] );
		if ( $transient_file_info['is_public'] ) {
			$transient_file_info['public_url'] = get_site_url( null, "/wc/file/transient/{$transient_file_info['file_name']}" );
		}
		return $transient_file_info;
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
	 * The file will be served with a content type header taken from the "content-type" metadata key of the file,
	 * or "text/html" if that key isn't present.
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

		$this->serve_file_contents_core( $wp->query_vars['wc-transient-file-name'], true, false );
	}

	/**
	 * Serve the contents of a transient file, exactly in the same way as 'handle_parse_request',
	 * with the difference that this endpoint requires an authenticated user with the proper capability,
	 * and works even for files that don't have 'is_public' set to true.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 */
	private function get_file_contents( WP_REST_Request $request ) {
		return $this->serve_file_contents_core( $request->get_param( 'id_or_name' ), 'true' === $request->get_param( 'get_by_name' ), true );
	}

	/**
	 * Core method to serve the contents of a transient file.
	 *
	 * @param string $file_id_or_name Transient file id or filename.
	 * @param bool   $id_is_name True if $file_id_or_name is a filename, false if it's a database id.
	 * @param bool   $is_json_rest_api_request True if the request comes from the REST API endpoint, false if it comes from the unauthenticated endpoint.
	 */
	private function serve_file_contents_core( string $file_id_or_name, bool $id_is_name, bool $is_json_rest_api_request ) {
		try {
			if ( ! $id_is_name && ! is_numeric( $file_id_or_name ) ) {
				if ( $is_json_rest_api_request ) {
					return new WP_Error( 'woocommerce_rest_invalid_arguments', __( 'Invalid file id', 'woocommerce' ), array( 'status' => 400 ) );
				} else {
					status_header( 400 );
					exit();
				}
			}

			$transient_file_info =
				$id_is_name ?
					$this->transient_files_engine->get_file_by_name( $file_id_or_name, true ) :
					$this->transient_files_engine->get_file_by_id( (int) $file_id_or_name, true );

			if ( $is_json_rest_api_request ) {
				$permission_error = $this->check_permission_by_method( 'GET', 'read_transient_file' );
				if ( is_wp_error( $permission_error ) ) {
					return $permission_error;
				}
			}

			if ( is_null( $transient_file_info ) || $transient_file_info['has_expired'] ) {
				if ( $is_json_rest_api_request ) {
					return new WP_Error( 'woocommerce_rest_not_found', __( 'File not found', 'woocommerce' ), array( 'status' => 404 ) );
				} else {
					status_header( 404 );
					exit();
				}
			}

			if ( ! $is_json_rest_api_request && ! $transient_file_info['is_public'] ) {
				status_header( 404 );
				exit();
			}

			$file_path = $transient_file_info['file_path'];
			if ( ! is_file( $file_path ) ) {
				throw new Exception( "File not found: $file_path" );
			}

			$file_length = filesize( $file_path );
			if ( false === $file_length ) {
				throw new Exception( "Can't retrieve file size: $file_path" );
			}

			$file_handle = fopen( $file_path, 'r' );
		} catch ( Exception $ex ) {
			$error_message = "Error serving transient file $file_id_or_name: {$ex->getMessage()}";
			wc_get_logger()->error( $error_message );
			if ( $is_json_rest_api_request ) {
				return $this->internal_wp_error( $ex );
			} else {
				status_header( 500 );
				exit();
			}
		}

		$content_type = $transient_file_info['metadata']['content-type'] ?? 'text/html';
		header( "Content-Type: $content_type" );
		header( "Content-Length: $file_length" );

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
			 * @since 8.4.0
			 */
			do_action( 'woocommerce_transient_file_contents_served', $transient_file_info, $is_json_rest_api_request );
		} catch ( Exception $e ) {
			wc_get_logger()->error( "Error serving transient file {$transient_file_info['file_name']}: {$e->getMessage()}" );
			// We can't change the response status code at this point.
		} finally {
			fclose( $file_handle );
			exit;
		}
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.Missing, WordPress.WP.AlternativeFunctions

	/**
	 * Get a description of the arguments accepted by the GET /files/transient/<id>/info endpoint.
	 *
	 * @return array A description of the arguments accepted by the GET /files/transient/<id> endpoint.
	 */
	private function get_args_for_get_file_info(): array {
		return array(
			'id'               => array(
				'description' => __( 'Unique identifier of the transient file.', 'woocommerce' ),
				'type'        => 'integer',
			),
			'include_metadata' => array(
				'required'    => false,
				'default'     => false,
				'type'        => 'boolean',
				'description' => __( 'True to include the file metadata in the response.', 'woocommerce' ),
			),
		);
	}

	/**
	 * Get a description of the arguments accepted by the GET /files/transient/<id> endpoint.
	 *
	 * @return array A description of the arguments accepted by the GET /files/transient/<id> endpoint.
	 */
	private function get_args_for_get_file_contents(): array {
		return array(
			'id_or_name'  => array(
				'description' => __( 'Unique identifier or name of the file.', 'woocommerce' ),
				'type'        => 'string',
			),
			'get_by_name' => array(
				'description' => __( 'True if the value of id_or_name is a file name, false if i\'s a database id.', 'woocommerce' ),
				'type'        => 'bool',
				'default'     => false,
			),
		);
	}

	/**
	 * Get a description of the arguments accepted by the DELETE /files/transient<id> endpoint.
	 *
	 * @return array A description of the arguments accepted by the DELETE /files/transient/<id> endpoints.
	 */
	private function get_args_for_delete_file(): array {
		return array(
			'id' => array(
				'description' => __( 'Unique identifier of the file.', 'woocommerce' ),
				'type'        => 'integer',
			),
		);
	}

	/**
	 * Get a description of the arguments accepted by the POST /files/transient endpoint.
	 *
	 * @return array A description of the arguments accepted by the POST /files/transient endpoint.
	 */
	private function get_args_for_create_file_by_rendering_template(): array {
		return array(
			'template_name'      => array(
				'required'    => true,
				'type'        => 'string',
				'description' => __( 'Name of the template to render.', 'woocommerce' ),
			),
			'expiration_date'    => array(
				'required'    => false,
				'default'     => null,
				'type'        => 'string',
				'description' => __( 'Expiration date (UTC), formatted as Y-m-d H:i:s.', 'woocommerce' ),
			),
			'expiration_seconds' => array(
				'required'    => false,
				'default'     => null,
				'type'        => 'int',
				'description' => __( 'Expiration seconds, the expiration date will be the current date (UTC) plus this value.', 'woocommerce' ),
			),
			'is_public'          => array(
				'required'    => false,
				'default'     => false,
				'type'        => 'boolean',
				'description' => __( 'Whether the created transient file will get a public URL.', 'woocommerce' ),
			),
			'variables'          => array(
				'required'    => false,
				'default'     => array(),
				'type'        => 'object',
				'description' => __( 'Variables to pass to the template being rendered.', 'woocommerce' ),
			),
			'metadata'           => array(
				'required'    => false,
				'default'     => array(),
				'type'        => 'object',
				'description' => __( 'Optional metadata object.', 'woocommerce' ),
			),
		);
	}

	/**
	 * Get the schema for the render file endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_create_file(): array {
		$schema               = $this->get_base_schema();
		$schema['properties'] = array(
			'id'                  => array(
				'description' => __( 'Unique identifier of the file.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'file_name'           => array(
				'description' => __( 'Unique name of the file.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_created_gmt'    => array(
				'description' => __( 'The creation date of the file, in UTC.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'expiration_date_gmt' => array(
				'description' => __( 'The expiration date of the file, in UTC.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_public'           => array(
				'description' => __( 'Whether the file can be reached publicly via the authenticated URL or not.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'has_expired'         => array(
				'description' => __( 'Whether the file is past its expiration date or not.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'public_url'          => array(
				'description' => __( "The URL of the unauthenticated endpoint to get the file contents. This field is present only when 'is_public' is returned as 'true'.", 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
		return $schema;
	}

	/**
	 * Get the schema for the get file information endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_get_file_info(): array {
		$schema                           = $this->get_schema_for_create_file();
		$schema['properties']['metadata'] = array(
			array(
				'description' => __( "File metadata. This field is present only when the 'include_metadata' parameter is sent as 'true'. The fields of the object are the meta keys.", 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
		return $schema;
	}

	/**
	 * Get the schema for the delete file endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_delete_file(): array {
		$schema               = $this->get_base_schema();
		$schema['properties'] = array(
			'deleted' => array(
				'description' => __( "Whether the file has been deleted or not (because it didn't exist).", 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			),
		);
		return $schema;
	}

	/**
	 * Get the base schema for the REST API endpoints.
	 *
	 * @return array
	 */
	private function get_base_schema(): array {
		return array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'transient files',
			'type'    => 'object',
		);
	}
}
