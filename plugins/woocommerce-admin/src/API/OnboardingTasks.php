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
}