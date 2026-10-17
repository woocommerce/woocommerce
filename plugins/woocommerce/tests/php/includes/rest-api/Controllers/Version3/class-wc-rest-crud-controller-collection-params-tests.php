<?php
declare( strict_types = 1 );

/**
 * Tests for the collection params filter of the V3 CRUD controller.
 *
 * @package WooCommerce\Tests\API
 */

/**
 * Class WC_REST_CRUD_Controller_Collection_Params_Tests.
 */
class WC_REST_CRUD_Controller_Collection_Params_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * The collection params filter keeps the post type slug as its second argument and receives the post type object as its third.
	 */
	public function test_collection_params_filter_receives_post_type_slug_and_object() {
		$controller = new class() extends WC_REST_CRUD_Controller {
			/**
			 * Post type.
			 *
			 * @var string
			 */
			protected $post_type = 'shop_order';
		};

		$received = array();
		$callback = function ( $params, $post_type, $post_type_object ) use ( &$received ) {
			$received = array( $post_type, $post_type_object );
			return $params;
		};
		add_filter( 'rest_shop_order_collection_params', $callback, 10, 3 );

		$controller->get_collection_params();

		remove_filter( 'rest_shop_order_collection_params', $callback, 10 );

		$this->assertSame( 'shop_order', $received[0] );
		$this->assertInstanceOf( WP_Post_Type::class, $received[1] );
		$this->assertSame( 'shop_order', $received[1]->name );
	}
}
