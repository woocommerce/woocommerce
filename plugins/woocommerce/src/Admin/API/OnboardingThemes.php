<?php
/**
 * REST API Onboarding Themes Controller
 *
 * Handles requests to install and activate themes.
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingThemes as Themes;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Themes Controller.
 *
 * @internal
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/recommended',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_recommended_themes' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'industry' => array(
						'type'        => 'string',
						'description' => 'Limits the results to themes relevant for this industry (optional)',
					),
					'currency' => array(
						'type'        => 'string',
						'enum'        => array( 'USD', 'AUD', 'CAD', 'EUR', 'GBP' ),
						'default'     => 'USD',
						'description' => 'Returns pricing in this currency (optional, default: USD)',
					),
				),
				'schema'              => array( $this, 'get_recommended_item_schema' ),
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
		if ( ! current_user_can( 'switch_themes' ) ) {
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
		$theme = sanitize_text_field( $request['theme'] );

		$installed_themes = wp_get_themes();

		if ( in_array( $theme, array_keys( $installed_themes ), true ) ) {
			return( array(
				'slug'   => $theme,
				'name'   => $installed_themes[ $theme ]->get( 'Name' ),
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
				'slug'   => $theme,
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
					$theme
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
					$theme
				),
				500
			);
		}

		return array(
			'slug'   => $theme,
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
		$theme = sanitize_text_field( $request['theme'] );

		require_once ABSPATH . 'wp-admin/includes/theme.php';

		$installed_themes = wp_get_themes();

		if ( ! in_array( $theme, array_keys( $installed_themes ), true ) ) {
			/* translators: %s: theme slug (example: woocommerce-services) */
			return new \WP_Error( 'woocommerce_rest_invalid_theme', sprintf( __( 'Invalid theme %s.', 'woocommerce' ), $theme ), 404 );
		}

		$result = switch_theme( $theme );
		if ( ! is_null( $result ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_theme', sprintf( __( 'The requested theme could not be activated.', 'woocommerce' ), $theme ), 500 );
		}

		return( array(
			'slug'   => $theme,
			'name'   => $installed_themes[ $theme ]->get( 'Name' ),
			'status' => 'success',
		) );
	}

	/**
	 * Get recommended themes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|array Theme activation status.
	 */
	public function get_recommended_themes( $request ) {
		// Check if "industry" and "currency" parameters are provided in the request.
		$industry = $request->get_param( 'industry' );
		$currency = $request->get_param( 'currency' ) ?? 'USD';

		// Return empty response if marketplace suggestions are disabled.
		if (
			/**
			 * Filter allow marketplace suggestions.
			 *
			 * User can disable all suggestions via filter.
			 *
			 * @since 8.3.0
			 */
			! apply_filters( 'woocommerce_allow_marketplace_suggestions', true ) ||
			get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) === 'no'
		) {

			/**
			 * Filter the onboarding recommended themes response.
			 *
			 * @since 8.3.0
			 *
			 * @param array $response The recommended themes response.
			 * @param array $filtered_themes The filtered themes.
			 * @param string $industry The industry to filter by (if provided).
			 * @param string $currency The currency to convert prices to. (USD, AUD, CAD, EUR, GBP).
			 *
			 * @return array
			 */
			return apply_filters(
				'__experimental_woocommerce_rest_get_recommended_themes',
				array(
					'themes' => array(),
					'_links' => array(
						'browse_all' => array(
							'href' => home_url( '/wp-admin/themes.php' ),
						),
					),
				),
				$industry,
				$currency
			);
		}

		$in_app_purchase_params               = \WC_Admin_Addons::get_in_app_purchase_url_params();
		$in_app_purchase_params['wccom-back'] = rawurlencode( '/wp-admin/admin.php?page=wc-admin&path=/customize-store' );

		$core_themes = array(
			array(
				'name'           => 'Twenty Twenty-Four',
				'price'          => __( 'Free', 'woocommerce' ),
				'is_free'        => true,
				'color_palettes' => array(
					array(
						'title'     => 'Black and white',
						'primary'   => '#FEFBF3',
						'secondary' => '#7F7E7A',
					),
					array(
						'title'     => 'Brown Sugar',
						'primary'   => '#EFEBE0',
						'secondary' => '#AC6239',
					),
					array(
						'title'     => 'Midnight',
						'primary'   => '#161514',
						'secondary' => '#AFADA7',
					),
					array(
						'title'     => 'Olive',
						'primary'   => '#FEFBF3',
						'secondary' => '#7F7E7A',
					),
				),
				'total_palettes' => 0,
				'slug'           => 'twentytwentyfour',
				'thumbnail_url'  => 'https://i0.wp.com/themes.svn.wordpress.org/twentytwentyfour/1.0/screenshot.png',
				'link_url'       => 'https://wordpress.org/themes/twentytwentyfour/',
			),
			array(
				'name'           => 'Highline',
				/* translators: %d: price */
				'price'          => sprintf( __( '$%d/year', 'woocommerce' ), 79 ),
				'is_free'        => false,
				'color_palettes' => array(
					array(
						'title'     => 'Primary',
						'primary'   => '#211f1d',
						'secondary' => '#211f1d',
					),
					array(
						'title'     => 'Additional color',
						'primary'   => '#aaaaaa',
						'secondary' => '#aaaaaa',
					),
					array(
						'title'     => 'Accent Background',
						'primary'   => '#b04b3c',
						'secondary' => '#b04b3c',
					),
					array(
						'title'     => 'Secondary Background',
						'primary'   => '#dabfa1',
						'secondary' => '#dabfa1',
					),
				),
				'total_palettes' => 9,
				'slug'           => 'highline',
				'thumbnail_url'  => 'https://woocommerce.com/wp-content/uploads/2023/12/Featured-image-538x403-1.png',
				'link_url'       => add_query_arg( $in_app_purchase_params, 'https://woocommerce.com/products/highline/' ),
			),
			array(
				'name'           => 'Luminate',
				/* translators: %d: price */
				'price'          => sprintf( __( '$%d/year', 'woocommerce' ), 79 ),
				'is_free'        => false,
				'color_palettes' => array(
					array(
						'title'     => 'Primary',
						'primary'   => '#242a2e',
						'secondary' => '#242a2e',
					),
					array(
						'title'     => 'Lite',
						'primary'   => '#f6f5f2',
						'secondary' => '#f6f5f2',
					),
					array(
						'title'     => 'Grey',
						'primary'   => '#a5a5a5',
						'secondary' => '#a5a5a5',
					),
					array(
						'title'     => 'Lite Grey',
						'primary'   => '#e4e4e1',
						'secondary' => '#e4e4e1',
					),
				),
				'total_palettes' => 5,
				'slug'           => 'luminate',
				'thumbnail_url'  => 'https://woocommerce.com/wp-content/uploads/2022/07/Featured-image-538x403-2.png',
				'link_url'       => add_query_arg( $in_app_purchase_params, 'https://woocommerce.com/products/luminate/' ),
			),
			array(
				'name'           => 'Gizmo',
				/* translators: %d: price */
				'price'          => sprintf( __( '$%d/year', 'woocommerce' ), 79 ),
				'is_free'        => false,
				'color_palettes' => array(
					array(
						'title'     => 'Primary',
						'primary'   => '#ff5833',
						'secondary' => '#ff5833',
					),
					array(
						'title'     => 'Foreground',
						'primary'   => '#111111',
						'secondary' => '#111111',
					),
					array(
						'title'     => 'Background',
						'primary'   => '#FFFFFF',
						'secondary' => '#FFFFFF',
					),
					array(
						'title'     => 'Base',
						'primary'   => '#595959',
						'secondary' => '#595959',
					),
				),
				'total_palettes' => 10,
				'slug'           => 'gizmo',
				'thumbnail_url'  => 'https://woocommerce.com/wp-content/uploads/2022/11/gizmo-regular-card-product-logo.jpg?w=900',
				'link_url'       => add_query_arg( $in_app_purchase_params, 'https://woocommerce.com/products/gizmo/' ),
			),
		);

		// To be implemented: 1. Fetch themes from the marketplace API. 2. Convert prices to the requested currency.
		// These are Dotcom themes.
		$default_themes = array(
			array(
				'name'           => 'Tsubaki',
				'price'          => __( 'Free', 'woocommerce' ),
				'is_free'        => true,
				'color_palettes' => array(),
				'total_palettes' => 0,
				'slug'           => 'tsubaki',
				'thumbnail_url'  => 'https://i0.wp.com/s2.wp.com/wp-content/themes/premium/tsubaki/screenshot.png',
				'link_url'       => 'https://wordpress.com/theme/tsubaki/',
			),
			array(
				'name'           => 'Tazza',
				'price'          => __( 'Free', 'woocommerce' ),
				'is_free'        => true,
				'color_palettes' => array(),
				'total_palettes' => 0,
				'slug'           => 'tazza',
				'thumbnail_url'  => 'https://i0.wp.com/s2.wp.com/wp-content/themes/premium/tazza/screenshot.png',
				'link_url'       => 'https://wordpress.com/theme/tazza/',
			),
			array(
				'name'           => 'Amulet',
				'price'          => __( 'Free', 'woocommerce' ),
				'is_free'        => true,
				'color_palettes' => array(
					array(
						'title'     => 'Default',
						'primary'   => '#FEFBF3',
						'secondary' => '#7F7E7A',
					),
					array(
						'title'     => 'Brown Sugar',
						'primary'   => '#EFEBE0',
						'secondary' => '#AC6239',
					),
					array(
						'title'     => 'Midnight',
						'primary'   => '#161514',
						'secondary' => '#AFADA7',
					),
					array(
						'title'     => 'Olive',
						'primary'   => '#FEFBF3',
						'secondary' => '#7F7E7A',
					),
				),
				'total_palettes' => 5,
				'slug'           => 'amulet',
				'thumbnail_url'  => 'https://i0.wp.com/s2.wp.com/wp-content/themes/premium/amulet/screenshot.png',
				'link_url'       => 'https://wordpress.com/theme/amulet/',
			),
			array(
				'name'           => 'Zaino',
				'price'          => __( 'Free', 'woocommerce' ),
				'is_free'        => true,
				'color_palettes' => array(
					array(
						'title'     => 'Default',
						'primary'   => '#202124',
						'secondary' => '#E3CBC0',
					),
					array(
						'title'     => 'Aubergine',
						'primary'   => '#1B1031',
						'secondary' => '#E1746D',
					),
					array(
						'title'     => 'Block out',
						'primary'   => '#FF5252',
						'secondary' => '#252525',
					),
					array(
						'title'     => 'Canary',
						'primary'   => '#FDFF85',
						'secondary' => '#353535',
					),
				),
				'total_palettes' => 11,
				'slug'           => 'zaino',
				'thumbnail_url'  => 'https://i0.wp.com/s2.wp.com/wp-content/themes/premium/zaino/screenshot.png',
				'link_url'       => 'https://wordpress.com/theme/zaino/',
			),
		);

		$ai_connection_enabled = get_option( 'woocommerce_blocks_allow_ai_connection' );
		$themes                = $ai_connection_enabled ? $default_themes : $core_themes;

		// To be implemented: Filter themes based on industry.
		if ( $industry ) {
			$filtered_themes = array_filter(
				$themes,
				function ( $theme ) use ( $industry ) {
					// Filter themes by industry.
					// Example: return $theme['industry'] === $industry;.
					return true;
				}
			);
		} else {
			$filtered_themes = $themes;
		}

		$response = array(
			'themes' => $filtered_themes,
			'_links' => array(
				'browse_all' => array(
					'href' => admin_url( 'themes.php' ),
				),
			),
		);

		/**
		 * Filter the onboarding recommended themes response.
		 *
		 * @since 8.3.0
		 *
		 * @param array $response The recommended themes response.
		 * @param array $filtered_themes The filtered themes.
		 * @param string $industry The industry to filter by (if provided).
		 * @param string $currency The currency to convert prices to. (USD, AUD, CAD, EUR, GBP).
		 *
		 * @return array
		 */
		$filtered_response = apply_filters(
			'__experimental_woocommerce_rest_get_recommended_themes',
			$response,
			$industry,
			$currency
		);

		/**
		 * Loop through themes checking to see if any are currently active
		 */
		$active_theme = get_stylesheet();

		foreach ( $filtered_response['themes'] as &$theme ) {
			if ( $theme['slug'] === $active_theme ) {
				$theme['is_active'] = true;
			} else {
				$theme['is_active'] = false;
			}
		}

		return $filtered_response;
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

	/**
	 * Get the recommended themes schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_recommended_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'onboarding_theme',
			'type'       => 'object',
			'properties' => array(
				'themes' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'name'           => array(
								'type'        => 'string',
								'description' => 'Theme Name',
							),
							'price'          => array(
								'type'        => 'string',
								'description' => 'Price',
							),
							'is_free'        => array(
								'type'        => 'boolean',
								'description' => 'Whether theme is free',
							),
							'is_active'      => array(
								'type'        => 'boolean',
								'description' => 'Whether theme is active',
							),
							'thumbnail_url'  => array(
								'type'        => 'string',
								'description' => 'Thumbnail URL',
							),
							'link_url'       => array(
								'type'        => 'string',
								'description' => 'Link URL for the theme',
							),
							'color_palettes' => array(
								'type'        => 'array',
								'description' => 'Array of color palette objects',
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'primary'   => array(
											'type'        => 'string',
											'description' => 'Primary color',
										),
										'secondary' => array(
											'type'        => 'string',
											'description' => 'Secondary color',
										),
									),
								),
							),
						),
					),
				),
				'_links' => array(
					'type'        => 'object',
					'description' => 'Links related to this response',
					'properties'  => array(
						'browse_all' => array(
							'type'        => 'object',
							'description' => 'Link to browse all themes',
							'properties'  => array(
								'href' => array(
									'type'        => 'string',
									'description' => 'URL for browsing all themes',
								),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
