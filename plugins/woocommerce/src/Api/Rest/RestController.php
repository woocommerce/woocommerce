<?php

use Automattic\WooCommerce\Api\Queries\GetOrder;

class RestController {
	public function register_routes() {
		// AUTOMATICALLY GENERATED ENDPOINT
		// Example call:
		// GET http://localhost/wp-json/wc/v4/rest/order?
		//     id=1234&_fieldArguments[total_amount][decimals]=4&_fieldArguments[lines][first]=200&_fieldArguments[lines][_fieldArguments][line_amount][decimals]=4
		register_rest_route(
			'wc/v4',
			'/rest/order',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => function( WP_REST_Request $request ) {
						$id        = $request->get_param( 'id' );

						$fields = null;
						if($request->has_param('_fields')) {
							$fields = parse_str($request->get_param('_fields'));
						}

						$fieldArgs = null;
						parse_str($request->get_param( '_fieldArguments' ) ?? '');

						return wc_get_container()->get(GetOrder::class)->run($id, $fieldArgs, $fields);
					},
					'args'     => function() {
						return array(
							'id'  => array(
								'description' => __( 'The id of the order.', 'woocommerce' ),
								'type'        => 'int',
							),
							'_field_arguments'  => array(
								'description' => __( 'Field arguments for the query.', 'woocommerce' ),
								'type'        => 'array',
								'default'     => []
							),
							'_fields'  => array(
								'description' => __( 'Fields to be returned in the response.', 'woocommerce' ),
								'type'        => 'array',
								'default'     => null
							)
						);
					}
				),
			),

			// MANUALLY GENERATED ENDPOINT
			// Example call:
			// GET http://localhost/wp-json/wc/v4/rest/order?
			//     id=1234&amount_decimals=4
			register_rest_route(
				'wc/v4',
				'/rest/order/(?P<id>[\d]+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'callback' => function( WP_REST_Request $request ) {
							$id        = $request->get_param( 'id' );

							$fieldArgs = [];
							if($request->has_param('amount_decimals')) {
								$decimals = $request->get_param('amount_decimals');
								$fieldArgs = array(
									'total_amount' => array(
										'decimals' => $decimals
									),
									'applied_coupons' => array(
										'_fieldArguments' => array(
											'line_amount' => array(
												'decimals' => $decimals
											)
										)
									),
								);
							}

							return wc_get_container()->get(GetOrder::class)->run($id, $fieldArgs, null);
						},
						'args'     => function() {
							return array(
								'id'  => array(
									'description' => __( 'The id of the order.', 'woocommerce' ),
									'type'        => 'int',
								),
								'amount_decimals'  => array(
									'description' => __( 'Decimals to include in the total order and applied coupon amounts.', 'woocommerce' ),
									'type'        => 'int',
									'default'     => 2
								)
							);
						}
					),
				)
			)
		);
	}
}
