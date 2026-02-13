<?php
/**
 * REST API WC System Status controller
 *
 * Handles requests to the /system_status endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\FileManifest;

/**
 * System status controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_System_Status_V2_Controller
 */
class WC_REST_System_Status_Controller extends WC_REST_System_Status_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Register the routes for /system_status.
	 *
	 * Adds the installation integrity verify endpoint on top of the
	 * parent routes.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/installation_integrity/verify',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'verify_installation' ),
					'permission_callback' => array( $this, 'verify_installation_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Get the system status schema, conforming to JSON Schema.
	 *
	 * Extends the parent schema with the installation_integrity property.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['installation_integrity'] = array(
			'description' => __( 'Installation integrity check result.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view' ),
			'readonly'    => true,
			'properties'  => array(
				'status'  => array(
					'description' => __( 'Integrity check status.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'version' => array(
					'description' => __( 'Verified plugin version.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date'    => array(
					'description' => __( 'Date of the integrity check.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'details' => array(
					'description' => __( 'Details of the integrity check result. Structure varies by status.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}

	/**
	 * Return an array of sections and the data associated with each.
	 *
	 * @deprecated 3.9.0
	 * @return array
	 */
	public function get_item_mappings() {
		$mappings                           = parent::get_item_mappings();
		$mappings['installation_integrity'] = $this->get_installation_integrity();
		return $mappings;
	}

	/**
	 * Return an array of sections and the data associated with each.
	 *
	 * @since 3.9.0
	 * @param array $fields List of fields to be included on the response.
	 * @return array
	 */
	public function get_item_mappings_per_fields( $fields ) {
		$items = parent::get_item_mappings_per_fields( $fields );

		foreach ( $fields as $field ) {
			list( $prop ) = explode( '.', $field, 2 );
			if ( 'installation_integrity' === $prop ) {
				$items['installation_integrity'] = $this->get_installation_integrity();
				break;
			}
		}

		return $items;
	}

	/**
	 * Check whether a given request has permission to run installation verification.
	 *
	 * @since 10.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function verify_installation_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'edit' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot update resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Run a fresh installation integrity verification against the live filesystem.
	 *
	 * @since 10.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function verify_installation( $request ) {
		$result = FileManifest::run_fresh_verification( WC_PLUGIN_FILE );

		$response = array(
			'status'  => $result['status'],
			'version' => $result['version'],
			'date'    => gmdate( 'Y-m-d H:i:s' ),
			'details' => $this->format_integrity_details( $result ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get the installation integrity check result.
	 *
	 * Delegates to FileManifest::get_check_result() and returns a fallback
	 * when no check result has been stored yet.
	 *
	 * @since 10.6.0
	 * @return array
	 */
	protected function get_installation_integrity() {
		$result = FileManifest::get_check_result();

		if ( is_null( $result ) ) {
			return array(
				'status'  => 'not_checked',
				'version' => '',
				'date'    => '',
				'details' => new \stdClass(),
			);
		}

		return array(
			'status'  => $result['status'] ?? '',
			'version' => $result['version'] ?? '',
			'date'    => $result['date'] ?? '',
			'details' => $this->format_integrity_details( $result ),
		);
	}

	/**
	 * Format the integrity check details into a structured object
	 * appropriate for the REST API response.
	 *
	 * @since 10.6.0
	 * @param array $result The check result from FileManifest.
	 * @return \stdClass|array Structured details varying by status.
	 */
	private function format_integrity_details( $result ) {
		$status  = $result['status'] ?? '';
		$details = $result['details'] ?? array();

		if ( 'version_mismatch' === $status ) {
			$manifest_version = $result['manifest_version'] ?? '';

			// Fallback: extract from details strings for results stored
			// before manifest_version was added to the stored data.
			if ( empty( $manifest_version ) ) {
				$prefix = 'Manifest version: ';
				foreach ( $details as $detail ) {
					if ( 0 === strpos( $detail, $prefix ) ) {
						$manifest_version = substr( $detail, strlen( $prefix ) );
						break;
					}
				}
			}

			return array(
				'manifest_version' => $manifest_version,
			);
		}

		if ( 'missing_files' === $status && ! empty( $details ) ) {
			return array(
				'missing_files_count' => count( $details ),
				'missing_files'       => array_slice( $details, 0, 20 ),
			);
		}

		return new \stdClass();
	}
}
