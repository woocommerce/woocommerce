<?php
/**
 * REST API Onboarding Tasks Controller
 *
 * Handles requests to complete various onboarding tasks.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;
use Automattic\WooCommerce\Admin\Features\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Tasks Controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Controller
 */
class OnboardingTasks extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding/tasks';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import_sample_products',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_sample_products' ),
					'permission_callback' => array( $this, 'import_products_permission_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create_homepage',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_homepage' ),
					'permission_callback' => array( $this, 'create_homepage_permission_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to create a product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function import_products_permission_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'product', 'create' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce-admin' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create a product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_homepage_permission_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'page', 'create' ) || ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create a new homepage.', 'woocommerce-admin' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Import sample products from WooCommerce sample CSV.
	 *
	 * @return WP_Error|WP_REST_Response	 * 
	 */
	public static function import_sample_products() {
		include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
		$file = WC_ABSPATH . 'sample-data/sample_products.csv';

		if ( file_exists( $file ) && class_exists( 'WC_Product_CSV_Importer' ) ) {
			// Override locale so we can return mappings from WooCommerce in English language stores.
			global $locale;
			$locale = false;
			$importer_class = apply_filters( 'woocommerce_product_csv_importer_class', 'WC_Product_CSV_Importer' );
			$args           = array( 'parse' => true, 'mapping' => self::get_header_mappings( $file ) );
			$args           = apply_filters( 'woocommerce_product_csv_importer_args', $args, $importer_class );

			$importer = new $importer_class( $file, $args );
			$import   = $importer->import();
			return rest_ensure_response( $import );
		} else {
			return new \WP_Error( 'woocommerce_rest_import_error', __( 'Sorry, the sample products data file was not found.', 'woocommerce-admin' ) );
		}
	}

	/**
	 * Get header mappings from CSV columns.
	 *
	 * @param string File path.
	 * @return array Mapped headers.
	 */
	public static function get_header_mappings( $file ) {
		include_once WC_ABSPATH . 'includes/admin/importers/mappings/mappings.php';

		$importer_class  = apply_filters( 'woocommerce_product_csv_importer_class', 'WC_Product_CSV_Importer' );
		$importer        = new $importer_class( $file, array() );
		$raw_headers     = $importer->get_raw_keys();
		$default_columns = wc_importer_default_english_mappings( array() );
		$special_columns = wc_importer_default_special_english_mappings( array() );

		$headers = array();
		foreach ( $raw_headers as $key => $field ) {
			$index             = $field;
			$headers[ $index ] = $field;

			if ( isset( $default_columns[ $field ] ) ) {
				$headers[ $index ] = $default_columns[ $field ];
			} else {
				foreach ( $special_columns as $regex => $special_key ) {
					if ( preg_match( self::sanitize_special_column_name_regex( $regex ), $field, $matches ) ) {
						$headers[ $index ] = $special_key . $matches[1];
						break;
					}
				}
			}
		}

		return $headers;
	}

	/**
	 * Sanitize special column name regex.
	 *
	 * @param  string $value Raw special column name.
	 * @return string
	 */
	public static function sanitize_special_column_name_regex( $value ) {
		return '/' . str_replace( array( '%d', '%s' ), '(.*)', trim( quotemeta( $value ) ) ) . '/';
	}

	/**
	 * Create a homepage from a template.
	 */
	public static function create_homepage() {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Homepage', 'woocommerce-admin' ),
				'post_type'    => 'page',
				'post_status'  => 'draft',
				// @todo The images in this content should be replaced with working external links or imported.
				'post_content' => "<!-- wp:cover {\"url\":\"https://local.wordpress.test/wp-content/uploads/2019/05/parallax.jpeg\",\"id\":3624} -->\n<div class=\"wp-block-cover has-background-dim\" style=\"background-image:url(https://local.wordpress.test/wp-content/uploads/2019/05/parallax.jpeg)\"><div class=\"wp-block-cover__inner-container\"><!-- wp:paragraph {\"align\":\"center\",\"placeholder\":\"Write title…\",\"fontSize\":\"large\"} -->\n<p style=\"text-align:center\" class=\"has-large-font-size\">Welcome to the store</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph {\"align\":\"center\"} -->\n<p style=\"text-align:center\">Write a short welcome message here</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:button {\"align\":\"center\"} -->\n<div class=\"wp-block-button aligncenter\"><a class=\"wp-block-button__link\">Go shopping</a></div>\n<!-- /wp:button --></div></div>\n<!-- /wp:cover -->\n\n<!-- wp:heading {\"align\":\"center\"} -->\n<h2 style=\"text-align:center\">New products</h2>\n<!-- /wp:heading -->\n\n<!-- wp:woocommerce/product-new /-->\n\n<!-- wp:media-text {\"align\":\"\",\"backgroundColor\":\"light-gray\",\"mediaPosition\":\"right\",\"mediaId\":1257,\"mediaType\":\"image\"} -->\n<div class=\"wp-block-media-text has-media-on-the-right has-light-gray-background-color\"><figure class=\"wp-block-media-text__media\"><img src=\"https://local.wordpress.test/wp-content/uploads/2017/05/brady-bellini-191086-1024x616.jpg\" alt=\"\" class=\"wp-image-1257\"/></figure><div class=\"wp-block-media-text__content\"><!-- wp:paragraph {\"align\":\"center\",\"placeholder\":\"Content…\",\"fontSize\":\"large\"} -->\n<p style=\"text-align:center\" class=\"has-large-font-size\">Here's a business goal</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph {\"align\":\"center\"} -->\n<p style=\"text-align:center\">Describe your business aspiration here.</p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:media-text -->\n\n<!-- wp:media-text {\"align\":\"\",\"backgroundColor\":\"light-gray\",\"mediaId\":1257,\"mediaType\":\"image\"} -->\n<div class=\"wp-block-media-text has-light-gray-background-color\"><figure class=\"wp-block-media-text__media\"><img src=\"https://local.wordpress.test/wp-content/uploads/2017/05/brady-bellini-191086-1024x616.jpg\" alt=\"\" class=\"wp-image-1257\"/></figure><div class=\"wp-block-media-text__content\"><!-- wp:paragraph {\"align\":\"center\",\"placeholder\":\"Content…\",\"fontSize\":\"large\"} -->\n<p style=\"text-align:center\" class=\"has-large-font-size\">Another business goal</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph {\"align\":\"center\"} -->\n<p style=\"text-align:center\">Describe your business aspiration here.</p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:media-text -->\n\n<!-- wp:media-text {\"align\":\"\",\"backgroundColor\":\"light-gray\",\"mediaPosition\":\"right\",\"mediaId\":1257,\"mediaType\":\"image\"} -->\n<div class=\"wp-block-media-text has-media-on-the-right has-light-gray-background-color\"><figure class=\"wp-block-media-text__media\"><img src=\"https://local.wordpress.test/wp-content/uploads/2017/05/brady-bellini-191086-1024x616.jpg\" alt=\"\" class=\"wp-image-1257\"/></figure><div class=\"wp-block-media-text__content\"><!-- wp:paragraph {\"align\":\"center\",\"placeholder\":\"Content…\",\"fontSize\":\"large\"} -->\n<p style=\"text-align:center\" class=\"has-large-font-size\">A final business goal</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph {\"align\":\"center\"} -->\n<p style=\"text-align:center\">Describe your business aspiration here.</p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:media-text -->\n\n<!-- wp:woocommerce/featured-product {\"editMode\":false,\"productId\":2567} -->\n<!-- wp:button {\"align\":\"center\"} -->\n<div class=\"wp-block-button aligncenter\"><a class=\"wp-block-button__link\" href=\"https://local.wordpress.test/shop/decor/wordpress-pennant\">Shop now</a></div>\n<!-- /wp:button -->\n<!-- /wp:woocommerce/featured-product -->"
			)
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_option( 'woocommerce_onboarding_homepage_post_id', $post_id );

			return array(
				'status'         => 'success',
				'message'        => __( 'Homepage created successfully.', 'woocommerce-admin' ),
				'post_id'        => $post_id,
				'edit_post_link' => htmlspecialchars_decode( get_edit_post_link( $post_id ) ),
			);
		} else {
			return $post_id;
		}
	}
}