<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * CartAddItem class.
 */
class CartAddItem extends AbstractCartRoute {
    /**
     * The route identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'cart-add-item';

    /**
     * Get the path of this REST route.
     *
     * @return string
     */
    public function get_path() {
        return self::get_path_regex();
    }

    /**
     * Get the path of this rest route.
     *
     * @return string
     */
    public static function get_path_regex() {
        return '/cart/add-item';
    }

    /**
     * Get method arguments for this REST route.
     *
     * @return array An array of endpoints.
     */
    public function get_args() {
        return [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'get_response' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'id'        => [
                        'description'       => __( 'The cart item product or variation ID.', 'woocommerce' ),
                        'type'              => 'integer',
                        'context'           => [ 'view', 'edit' ],
                        'sanitize_callback' => 'absint',
                    ],
                    'quantity'  => [
                        'description' => __( 'Quantity of this item to add to the cart.', 'woocommerce' ),
                        'type'        => 'number',
                        'context'     => [ 'view', 'edit' ],
                        'arg_options' => [
                            'sanitize_callback' => 'wc_stock_amount',
                        ],
                    ],
                    'variation' => [
                        'description' => __( 'Chosen attributes (for variations).', 'woocommerce' ),
                        'type'        => 'array',
                        'context'     => [ 'view', 'edit' ],
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'attribute' => [
                                    'description' => __( 'Variation attribute name.', 'woocommerce' ),
                                    'type'        => 'string',
                                    'context'     => [ 'view', 'edit' ],
                                ],
                                'value'     => [
                                    'description' => __( 'Variation attribute value.', 'woocommerce' ),
                                    'type'        => 'string',
                                    'context'     => [ 'view', 'edit' ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'schema'      => [ $this->schema, 'get_public_item_schema' ],
            'allow_batch' => [ 'v1' => true ],
        ];
    }

    /**
     * Handle the request and return a valid response for this endpoint.
     *
     * @throws RouteException On error.
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    protected function get_route_post_response( \WP_REST_Request $request ) {
        // ** ADDED: Nonce validation **
        $nonce = $request->get_header( 'Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wc_store_api' ) ) {
            return new \WP_Error(
                'woocommerce_rest_missing_nonce',
                __( 'Missing or invalid nonce. This endpoint requires a valid Nonce header.', 'woocommerce' ),
                array( 'status' => 400 )
            );
        }

        // Do not allow key to be specified during creation.
        if ( ! empty( $request['key'] ) ) {
            throw new RouteException( 'woocommerce_rest_cart_item_exists', esc_html__( 'Cannot create an existing cart item.', 'woocommerce' ), 400 );
        }

        $add_to_cart_data = apply_filters(
            'woocommerce_store_api_add_to_cart_data',
            array(
                'id'             => $request['id'],
                'quantity'       => $request['quantity'],
                'variation'      => $request['variation'],
                'cart_item_data' => [],
            ),
            $request
        );

        $this->cart_controller->add_to_cart( $add_to_cart_data );

        $response = rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) );
        $response->set_status( 201 );
        return $response;
    }
}
