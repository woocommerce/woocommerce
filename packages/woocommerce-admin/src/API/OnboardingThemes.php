<?php
/**
 * REST API Onboarding Themes Controller
 *
 * Handles requests to install and activate themes.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Features\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Themes Controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Controller
 */
class OnboardingThemes extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding/themes';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/install',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'install_theme' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'activate_theme' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to manage themes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'install_themes' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot manage themes.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Installs the requested theme.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|array Theme installation status.
	 */
	public function install_theme( $request ) {
		$allowed_themes = Onboarding::get_allowed_themes();
		$theme          = sanitize_title_with_dashes( $request['theme'] );

		if ( ! in_array( $theme, $allowed_themes, true ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_theme', __( 'Invalid theme.', 'woocommerce' ), 404 );
		}

		$slug             = sanitize_key( $theme );
		$installed_themes = wp_get_themes();

		if ( in_array( $slug, array_keys( $installed_themes ), true ) ) {
			return( array(
				'slug'   => $slug,
				'name'   => $installed_themes[ $slug ]->get( 'Name' ),
				'status' => 'success',
			) );
		}

		include_once ABSPATH . '/wp-admin/includes/admin.php';
		include_once ABSPATH . '/wp-admin/includes/theme-install.php';
		include_once ABSPATH . '/wp-admin/includes/theme.php';
		include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . '/wp-admin/includes/class-theme-upgrader.php';

		$api = themes_api(
			'theme_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return new \WP_Error(
				'woocommerce_rest_theme_install',
				sprintf(
					/* translators: %s: theme slug (example: woocommerce-services) */
					__( 'The requested theme `%s` could not be installed. Theme API call failed.', 'woocommerce' ),
					$slug
				),
				500
			);
		}

		$upgrader = new \Theme_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) || is_null( $result ) ) {
			return new \WP_Error(
				'woocommerce_rest_theme_install',
				sprintf(
					/* translators: %s: theme slug (example: woocommerce-services) */
					__( 'The requested theme `%s` could not be installed.', 'woocommerce' ),
					$slug
				),
				500
			);
		}

		return array(
			'slug'   => $slug,
			'name'   => $api->name,
			'status' => 'success',
		);
	}

	/**
	 * Activate the requested theme.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|array Theme activation status.
	 */
	public function activate_theme( $request ) {
		$allowed_themes = Onboarding::get_allowed_themes();
		$theme          = sanitize_title_with_dashes( $request['theme'] );
		if ( ! in_array( $theme, $allowed_themes, true ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_theme', __( 'Invalid theme.', 'woocommerce' ), 404 );
		}

		require_once ABSPATH . 'wp-admin/includes/theme.php';

		$slug             = sanitize_key( $theme );
		$installed_themes = wp_get_themes();

		if ( ! in_array( $theme, array_keys( $installed_themes ), true ) ) {
			/* translators: %s: theme slug (example: woocommerce-services) */
			return new \WP_Error( 'woocommerce_rest_invalid_theme', sprintf( __( 'Invalid theme %s.', 'woocommerce' ), $slug ), 404 );
		}

		$result = switch_theme( $theme );
		if ( ! is_null( $result ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_theme', sprintf( __( 'The requested theme could not be activated.', 'woocommerce' ), $slug ), 500 );
		}

		return( array(
			'slug'   => $theme,
			'name'   => $installed_themes[ $theme ]->get( 'Name' ),
			'status' => 'success',
		) );
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'onboarding_theme',
			'type'       => 'object',
			'properties' => array(
				'slug'   => array(
					'description' => __( 'Theme slug.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'   => array(
					'description' => __( 'Theme name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status' => array(
					'description' => __( 'Theme status.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
