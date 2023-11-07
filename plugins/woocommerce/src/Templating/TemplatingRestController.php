<?php

namespace Automattic\WooCommerce\Templating;

use WP_HTTP_Response;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_Error;
use \Exception;
use \InvalidArgumentException;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * REST API controller for the templating engine. This class handles:
 *
 * 1. The JSON REST API endpoints for creating, deleting and retrieving information about rendered template files.
 * 2. The endpoint used to retrieve the contents of a rendered file given its file name.
 *
 * Rendered templates that are public and haven't expired can be obtained remotely via the "/wc/file/filename" endpoint
 * (unauthenticated access is allowed). The default content type will be "text/html", this can be changed if a
 * "content-type" metadata key is passed to render_template. Once a rendered file is successfully served,
 * the woocommerce_rendered_template_served action is fired.
 */
class TemplatingRestController {

	use AccessiblePrivateMethods;

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	private string $route_namespace = 'wc/v3';

	/**
	 * The instance of TemplatingEngine to use.
	 *
	 * @var TemplatingEngine
	 */
	private TemplatingEngine $templating_engine;

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
	 * @param TemplatingEngine $templating_engine The instance of TemplatingEngine to use.
	 * @internal
	 */
	final public function init( TemplatingEngine $templating_engine ) {
		$this->templating_engine = $templating_engine;
	}

	/**
	 * Handle the woocommerce_rest_api_get_rest_namespaces filter
	 * to add ourselves to the list of REST API controllers registered by WooCommerce.
	 *
	 * @param array $namespaces The original list of WooCommerce REST API namespaces/controllers.
	 * @return array The updated list of WooCommerce REST API namespaces/controllers.
	 */
	private function handle_woocommerce_rest_api_get_rest_namespaces( array $namespaces ): array {
		$namespaces['wc/v3']['templates'] = self::class;
		return $namespaces;
	}

	/**
	 * Register the JSON REST API endpoints handled by this controller.
	 */
	public function register_routes() {
		self::mark_method_as_accessible( 'run' );

		register_rest_route(
			$this->route_namespace,
			'/templates/(?P<id>[\d]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn( $request ) => $this->run( 'get_template', $request ),
				'permission_callback' => fn( $request ) => $this->check_permission( $request, 'read_rendered_template_info' ),
				'args'                => $this->get_args_for_get_or_delete_template(),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/templates/(?P<id>[\d]+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => fn( $request ) => $this->run( 'delete_template', $request ),
				'permission_callback' => fn( $request ) => $this->check_permission( $request, 'delete_rendered_template' ),
				'args'                => $this->get_args_for_get_or_delete_template(),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/templates/render',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( 'render_template', $request ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'create_rendered_template' ),
					'args'                => $this->get_args_for_render_template(),
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
			$data = array( 'status' => 500 );
			if ( current_user_can( 'manage_woocommerce' ) ) {
				$data['exception_message'] = $ex->getMessage();
			}
			return new WP_Error( 'woocommerce_rest_internal_error', __( 'Internal server error', 'woocommerce' ), $data );
		}
	}

	/**
	 * Permission check for JSON REST API endpoints.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @param string          $required_capability_name The name of the required capability.
	 * @return bool|WP_Error True if the current user has the capability, an "Unauthorized" error otherwise.
	 */
	private function check_permission( WP_REST_Request $request, string $required_capability_name ) {
		if ( current_user_can( $required_capability_name ) ) {
			return true;
		}

		$error_information = $this->authentication_errors_by_method[ $request->get_method() ] ?? null;
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
	 * Get the details of a rendered template by id.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @return WP_Error|array The response to send back to the client.
	 */
	private function get_template( WP_REST_Request $request ) {
		$rendered_template_info = $this->templating_engine->get_rendered_file_by_id( $request->get_param( 'id' ), $request->get_param( 'include_metadata' ) );
		if ( is_null( $rendered_template_info ) ) {
			return new WP_Error( 'woocommerce_rest_not_found', __( 'Template not found', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $this->adjust_rendered_template_info_for_response( $rendered_template_info );
	}

	/**
	 * Generate a rendered template file.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @return WP_Error|WP_REST_Response The response to send back to the client.
	 * @throws Exception The template can't be found right after having been rendered.
	 */
	private function render_template( WP_REST_Request $request ) {
		$metadata                       = $request->get_param( 'metadata' );
		$metadata['expiration_date']    = $request->get_param( 'expiration_date' );
		$metadata['expiration_seconds'] = $request->get_param( 'expiration_seconds' );
		$metadata['is_public']          = $request->get_param( 'is_public' );

		try {
			$rendered_file_name = $this->templating_engine->render_template( $request->get_param( 'template_name' ), $request->get_param( 'variables' ), $metadata );
		} catch ( InvalidArgumentException $ex ) {
			return new WP_Error( 'woocommerce_rest_invalid_arguments', $ex->getMessage(), array( 'status' => 400 ) );
		}

		$rendered_template_info = $this->templating_engine->get_rendered_file_by_name( $rendered_file_name );
		if ( is_null( $rendered_template_info ) ) {
			throw new Exception( "Template {$request->get_param('template_name')} has just been rendered as $rendered_file_name, but now somehow it can't be found" );
		}

		return new WP_REST_Response( $this->adjust_rendered_template_info_for_response( $rendered_template_info ), 201 );
	}

	/**
	 * Deletes an existing rendered template file.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @return array The response to send back to the client.
	 */
	private function delete_template( WP_REST_Request $request ): array {
		$deleted = $this->templating_engine->delete_rendered_file_by_id( $request->get_param( 'id' ) );
		return array( 'deleted' => $deleted );
	}

	/**
	 * Adjust an array of data representing a rendered template file so that it's suitable to bre returned to the client.
	 *
	 * @param array $rendered_template_info The array to adjust.
	 * @return array The adjusted array.
	 */
	private function adjust_rendered_template_info_for_response( array $rendered_template_info ): array {
		unset( $rendered_template_info['file_path'] );
		if ( $rendered_template_info['is_public'] ) {
			$rendered_template_info['render_url'] = get_site_url( null, "/wc/file/{$rendered_template_info['file_name']}" );
		}
		return $rendered_template_info;
	}

	/**
	 * Handle the "init" action, add rewrite rules for the "wc/file" endpoint.
	 */
	private function handle_init() {
		add_rewrite_rule( '^wc/file/?$', 'index.php?wc-rendered-template=', 'top' );
		add_rewrite_rule( '^wc/file/(.+)$', 'index.php?wc-rendered-template=$matches[1]', 'top' );
		add_rewrite_endpoint( 'wc/file', EP_ALL );
	}

	/**
	 * Handle the "query_vars" action, add the "wc-rendered-template" variable for the "wc/file" endpoint.
	 *
	 * @param array $vars The original query variables.
	 * @return array The updated query variables.
	 */
	private function handle_query_vars( $vars ) {
		$vars[] = 'wc-rendered-template';
		return $vars;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.Missing, WordPress.WP.AlternativeFunctions

	/**
	 * Handle the "parse_request" action for the "wc/file" endpoint.
	 *
	 * If the request is not for "/wc-template/filename" or "index.php?wc-rendered-template=filename", it returns without doing anything.
	 * Otherwise, it will serve the contents of the file with the provided name if it exists, is public and has not expired,
	 * or will return a "Not found" status otherwise.
	 *
	 * The file will be served with a content type header taken from the "content-type" metadata key of the rendered file,
	 * or "text/html" if that key isn't present.
	 */
	private function handle_parse_request() {
		global $wp;

		// phpcs:ignore WordPress.Security
		$query_arg = wp_unslash( $_GET['wc-rendered-template'] ?? null );
		if ( ! is_null( $query_arg ) ) {
			$wp->query_vars['wc-rendered-template'] = $query_arg;
		}

		$template_file_name = $wp->query_vars['wc-rendered-template'] ?? null;
		if ( is_null( $template_file_name ) ) {
			return;
		}

		try {
			$rendered_template_info = $this->templating_engine->get_rendered_file_by_name( $template_file_name, true );
			if ( is_null( $rendered_template_info ) || $rendered_template_info['has_expired'] || ! $rendered_template_info['is_public'] ) {
				status_header( 404 );
				exit;
			}

			$rendered_file_path = $rendered_template_info['file_path'];
			if ( ! is_file( $rendered_file_path ) ) {
				throw new Exception( "File not found: $rendered_file_path" );
			}

			$file_length = filesize( $rendered_file_path );
			if ( false === $file_length ) {
				throw new Exception( "Can't retrieve file size: $rendered_file_path" );
			}

			$file_handle = fopen( $rendered_file_path, 'r' );
		} catch ( Exception $e ) {
			wc_get_logger()->error( "Error serving rendered template $template_file_name: {$e->getMessage()}" );
			status_header( 500 );
			exit;
		}

		$content_type = $rendered_template_info['metadata']['content-type'] ?? 'text/html';
		header( "Content-Type: $content_type" );
		header( "Content-Length: $file_length" );

		try {
			while ( ! feof( $file_handle ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo fread( $file_handle, 1024 );
			}

			/**
			 * Action that fires after a rendered file has been successfully served, right before terminating the request.
			 *
			 * @param array $rendered_template_info Information about the served file, as returned by get_rendered_file_by_name.
			 *
			 * @since 8.4.0
			 */
			do_action( 'woocommerce_rendered_template_served', $rendered_template_info );
		} catch ( Exception $e ) {
			wc_get_logger()->error( "Error serving rendered template $template_file_name: {$e->getMessage()}" );
			// We can't change the response status code at this point.
		} finally {
			fclose( $file_handle );
			exit;
		}
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.Missing, WordPress.WP.AlternativeFunctions

	/**
	 * Get a description of the arguments accepted by the GET and DELETE /templates endpoints.
	 *
	 * @return array A description of the arguments accepted by the GET and DELETE /templates endpoints.
	 */
	private function get_args_for_get_or_delete_template() {
		return array(
			'id'               => array(
				'description' => __( 'Unique identifier of the template.', 'woocommerce' ),
				'type'        => 'integer',
			),
			'include_metadata' => array(
				'required'    => false,
				'default'     => false,
				'type'        => 'boolean',
				'description' => __( 'True to include template metadata in the response.', 'woocommerce' ),
			),
		);
	}

	/**
	 * Get a description of the arguments accepted by the POST /templates/render endpoint.
	 *
	 * @return array A description of the arguments accepted by the POST /templates/render endpoint.
	 */
	private function get_args_for_render_template() {
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
				'description' => __( 'Whether the rendered template will get a public URL.', 'woocommerce' ),
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
}
