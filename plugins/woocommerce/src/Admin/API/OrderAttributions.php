<?php
/**
 * REST API MarketingCampaigns Controller
 *
 * Handles requests to /marketing/campaigns.
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannels as MarketingChannelsService;
use Automattic\WooCommerce\Admin\Marketing\Price;
use WC_REST_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * MarketingCampaigns Controller.
 *
 * @internal
 * @extends WC_REST_Controller
 * @since x.x.x
 */
class OrderAttributions extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'order-attributions';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to view marketing campaigns.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Returns an aggregated array of marketing campaigns for all active marketing channels.
	 *
	 * @param WP_REST_Request $request Request data.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		return array(
			array(
				'value' => 'direct',
				'label' => __( 'Direct', 'woocommerce' ),
			),
			array(
				'value' => 'organic_search',
				'label' => __( 'Organic search', 'woocommerce' ),
			),
			array(
				'value' => 'social',
				'label' => __( 'Social', 'woocommerce' ),
			),
			array(
				'value' => 'email',
				'label' => __( 'Email', 'woocommerce' ),
			),
			array(
				'value' => 'affiliates',
				'label' => __( 'Affiliates', 'woocommerce' ),
			),
			array(
				'value' => 'referral',
				'label' => __( 'Referral', 'woocommerce' ),
			),
			array(
				'value' => 'paid_search',
				'label' => __( 'Paid Search', 'woocommerce' ),
			),
			array(
				'value' => 'other_advertising',
				'label' => __( 'Other Advertising', 'woocommerce' ),
			),
			array(
				'value' => 'display',
				'label' => __( 'Display', 'woocommerce' ),
			),
			array(
				'value' => 'unavailable',
				'label' => __( '(unavailable)', 'woocommerce' ),
			),
		);
	}



	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'order-attribution',
			'type'       => 'object',
			'properties' => array(
				'value' => array(
					'description' => __( 'The unique value for the order attribution.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'label' => array(
					'description' => __( 'The label for the order attribution', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

}
