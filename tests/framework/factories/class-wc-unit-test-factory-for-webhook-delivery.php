<?php
/**
 * Webhook Delivery Test Factory
 *
 * @see \WP_UnitTest_Factory_For_Comment
 * @since 2.2
 */
class WC_Unit_Test_Factory_For_Webhook_Delivery extends WP_UnitTest_Factory_For_Comment {

	/**
	 * Setup factory.
	 *
	 * @since 2.2
	 * @param null $factory
	 */
	public function __construct( $factory = null ) {

		parent::__construct( $factory );

		// set defaults
		$this->default_generation_definitions = array(
			'comment_author'       => __( 'WooCommerce', 'woocommerce' ),
			'comment_author_email' => 'woocommerce@noreply.com',
			'comment_agent'        => 'WooCommerce Hookshot',
			'comment_type'         => 'webhook_delivery',
			'comment_parent'       => 0,
			'comment_approved'     => 1,
			'comment_content'      => 'HTTP 200: OK',
		);
	}

	/**
	 * Create a mock webhook delivery.
	 *
	 * @since 2.2
	 * @see WP_UnitTest_Factory_For_comment::create_object()
	 * @param array $args
	 * @return int webhook delivery (comment) ID
	 */
	public function create_object( $args ) {

		$id = parent::create_object( $args );

		$comment_meta_args = array(
			'_request_method'    => 'POST',
			'_request_headers'   => array( 'User-Agent', 'WooCommerce Hookshot' ),
			'_request_body'      => "webhook_id={$id}",
			'_response_code'     => 200,
			'_response_messaage' => 'OK',
			'_response_headers'  => array( 'server' => 'nginx' ),
			'_response_body'     => 'OK',
			'_duration'          => '0.47976',
		);

		foreach ( $comment_meta_args as $key => $value ) {
			update_comment_meta( $id, $key, $value );
		}

		return $id;
	}

}
