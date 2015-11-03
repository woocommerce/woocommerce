<?php
/**
 * Webhook Test Factory
 *
 * @see \WP_UnitTest_Factory_For_Post
 * @since 2.2
 */
class WC_Unit_Test_Factory_For_Webhook extends WP_UnitTest_Factory_For_Post {

	/**
	 * Setup factory.
	 *
	 * @since 2.2
	 * @param null $factory
	 */
	public function __construct( $factory = null ) {

		parent::__construct( $factory );

		// set default
		$this->default_generation_definitions = array(
			'post_status' => 'publish',
			'post_title'  => rand_str(),
			'post_type'   => 'shop_webhook',
		);
	}

	/**
	 * Create a mock webhook.
	 *
	 * @since 2.2
	 * @see WP_UnitTest_Factory_For_Post::create_object()
	 * @param array $args
	 * @return int webhook (post) ID
	 */
	public function create_object( $args ) {

		$id = parent::create_object( $args );

		$meta_args = array(
			'_topic'        => 'coupon.created',
			'_resource'     => 'coupon',
			'_event'        => 'created',
			'_hooks'        => array(
				'woocommerce_process_shop_coupon_meta',
				'woocommerce_api_create_coupon',
			),
			'_delivery_url' => 'http://requestb.in/Tt8675309',
		);

		foreach ( $meta_args as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}

		return $id;
	}

	/**
	 * Get a mock webhook object.
	 *
	 * @since 2.2
	 * @see WP_UnitTest_Factory_For_Post::get_object_by_id()
	 * @param int $id webhook ID
	 * @return \WC_Webhook webhook instance
	 */
	public function get_object_by_id( $id ) {

		return new WC_Webhook( $id );
	}

}
