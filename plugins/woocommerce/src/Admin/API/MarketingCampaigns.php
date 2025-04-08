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
class MarketingCampaigns extends WC_REST_Controller {

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
	protected $rest_base = 'marketing/campaigns';

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
					'args'                => $this->get_collection_params(),
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
		/**
		 * MarketingChannels class.
		 *
		 * @var MarketingChannelsService $marketing_channels_service
		 */
		$marketing_channels_service = wc_get_container()->get( MarketingChannelsService::class );

		// Aggregate the campaigns from all registered marketing channels.
		$responses = array();
		foreach ( $marketing_channels_service->get_registered_channels() as $channel ) {
			foreach ( $channel->get_campaigns() as $campaign ) {
				$response    = $this->prepare_item_for_response( $campaign, $request );
				$responses[] = $this->prepare_response_for_collection( $response );
			}
		}

		// Pagination.
		$page              = $request['page'];
		$items_per_page    = $request['per_page'];
		$offset            = ( $page - 1 ) * $items_per_page;
		$paginated_results = array_slice( $responses, $offset, $items_per_page );

		$response = rest_ensure_response( $paginated_results );

		$total_campaigns = count( $responses );
		$max_pages       = ceil( $total_campaigns / $items_per_page );
		$response->header( 'X-WP-Total', $total_campaigns );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		// Add previous and next page links to response header.
		$request_params = $request->get_query_params();
		$base           = add_query_arg( urlencode_deep( $request_params ), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Get formatted price based on Price type.
	 *
	 * This uses plugins/woocommerce/i18n/currency-info.php and plugins/woocommerce/i18n/locale-info.php to get option object based on $price->currency.
	 *
	 * Example:
	 *
	 * - When $price->currency is 'USD' and $price->value is '1000', it should return '$1000.00'.
	 * - When $price->currency is 'JPY' and $price->value is '1000', it should return '¥1,000'.
	 * - When $price->currency is 'AED' and $price->value is '1000', it should return '5.000,00 د.إ'.
	 *
	 * @param Price $price Price object.
	 * @return String formatted price.
	 */
	private function get_formatted_price( $price ) {
		// Get $num_decimals to be passed to wc_price.
		$locale_info_all = include WC()->plugin_path() . '/i18n/locale-info.php';
		$locale_index    = array_search( $price->get_currency(), array_column( $locale_info_all, 'currency_code' ), true );
		$locale          = array_values( $locale_info_all )[ $locale_index ];
		$num_decimals    = $locale['num_decimals'];

		// Get $currency_info based on user locale or default locale.
		$currency_locales = $locale['locales'];
		$user_locale      = get_user_locale();
		$currency_info    = $currency_locales[ $user_locale ] ?? $currency_locales['default'];

		// Get $price_format to be passed to wc_price.
		$currency_pos     = $currency_info['currency_pos'];
		$currency_formats = array(
			'left'        => '%1$s%2$s',
			'right'       => '%2$s%1$s',
			'left_space'  => '%1$s&nbsp;%2$s',
			'right_space' => '%2$s&nbsp;%1$s',
		);
		$price_format     = $currency_formats[ $currency_pos ] ?? $currency_formats['left'];

		$price_value     = wc_format_decimal( $price->get_value() );
		$price_formatted = wc_price(
			$price_value,
			array(
				'currency'           => $price->get_currency(),
				'decimal_separator'  => $currency_info['decimal_sep'],
				'thousand_separator' => $currency_info['thousand_sep'],
				'decimals'           => $num_decimals,
				'price_format'       => $price_format,
			)
		);

		return html_entity_decode( wp_strip_all_tags( $price_formatted ) );
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param MarketingCampaign $item    WordPress representation of the item.
	 * @param WP_REST_Request   $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'         => $item->get_id(),
			'channel'    => $item->get_type()->get_channel()->get_slug(),
			'title'      => $item->get_title(),
			'manage_url' => $item->get_manage_url(),
		);

		if ( $item->get_cost() instanceof Price ) {
			$data['cost'] = array(
				'value'     => wc_format_decimal( $item->get_cost()->get_value() ),
				'currency'  => $item->get_cost()->get_currency(),
				'formatted' => $this->get_formatted_price( $item->get_cost() ),
			);
		}

		if ( $item->get_sales() instanceof Price ) {
			$data['sales'] = array(
				'value'     => wc_format_decimal( $item->get_sales()->get_value() ),
				'currency'  => $item->get_sales()->get_currency(),
				'formatted' => $this->get_formatted_price( $item->get_sales() ),
			);
		}

		$context = $request['context'] ?? 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'marketing_campaign',
			'type'       => 'object',
			'properties' => array(
				'id'         => array(
					'description' => __( 'The unique identifier for the marketing campaign.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'channel'    => array(
					'description' => __( 'The unique identifier for the marketing channel that this campaign belongs to.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'title'      => array(
					'description' => __( 'Title of the marketing campaign.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'manage_url' => array(
					'description' => __( 'URL to the campaign management page.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'cost'       => array(
					'description' => __( 'Cost of the marketing campaign.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'value'    => array(
							'type'     => 'string',
							'context'  => array( 'view' ),
							'readonly' => true,
						),
						'currency' => array(
							'type'     => 'string',
							'context'  => array( 'view' ),
							'readonly' => true,
						),
					),
				),
				'sales'      => array(
					'description' => __( 'Sales of the marketing campaign.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'value'    => array(
							'type'     => 'string',
							'context'  => array( 'view' ),
							'readonly' => true,
						),
						'currency' => array(
							'type'     => 'string',
							'context'  => array( 'view' ),
							'readonly' => true,
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();
		unset( $params['search'] );

		return $params;
	}
}
