<?php
/**
 * Settings page discovery for the REST API.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Pages;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use WC_Admin_Settings;
use WC_Settings_Page;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Lists registered settings pages without loading their fields.
 *
 * @since 11.3.0
 * @internal
 */
final class Controller extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/pages';

	/**
	 * Register the collection route.
	 *
	 * @since 11.3.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check access to settings navigation.
	 *
	 * @since 11.3.0
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get settings pages in their registered order, including extension pages.
	 *
	 * @since 11.3.0
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		try {
			$pages = WC_Admin_Settings::get_settings_pages();
			if ( ! is_array( $pages ) ) {
				return $this->get_discovery_error();
			}

			$items = array();
			foreach ( $pages as $page ) {
				if ( ! $page instanceof WC_Settings_Page ) {
					continue;
				}
				$id    = $page->get_id();
				$label = $page->get_label();
				if ( ! is_string( $id ) || '' === $id || ! is_string( $label ) ) {
					continue;
				}
				$item     = array(
					'id'    => $id,
					'label' => wp_strip_all_tags( $label ),
					'url'   => add_query_arg(
						array(
							'page' => 'wc-settings',
							'tab'  => $id,
						),
						admin_url( 'admin.php' )
					),
				);
				$response = $this->prepare_item_for_response( $item, $request );
				if ( is_wp_error( $response ) ) {
					return $response;
				}
				$items[] = $this->prepare_response_for_collection( $response );
			}

			return rest_ensure_response( $items );
		} catch ( \Throwable $error ) {
			return $this->get_discovery_error();
		}
	}

	/**
	 * Report discovery failures without exposing extension internals.
	 *
	 * @return WP_Error
	 */
	private function get_discovery_error(): WP_Error {
		return new WP_Error(
			'woocommerce_rest_settings_pages_error',
			__( 'Unable to load settings pages.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Get navigation metadata for the response.
	 *
	 * @since 11.3.0
	 * @param array           $item Navigation metadata.
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		return $item;
	}

	/**
	 * Get the settings page schema.
	 *
	 * @since 11.3.0
	 * @return array
	 */
	protected function get_schema(): array {
		$properties = array(
			'id'    => array(
				'description' => __( 'Registered settings page identifier.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'label' => array(
				'description' => __( 'Navigation label.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'url'   => array(
				'description' => __( 'Classic settings URL.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
		);

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'settings_page',
			'type'       => 'object',
			'properties' => $properties,
		);
	}
}
