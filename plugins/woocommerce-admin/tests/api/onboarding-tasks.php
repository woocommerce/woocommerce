<?php
/**
 * Onboarding Tasks REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use \Automattic\WooCommerce\Admin\API\OnboardingTasks;

/**
 * WC Tests API Onboarding Tasks
 */
class WC_Tests_API_Onboarding_Tasks extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/onboarding/tasks';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Empty the db of any products.
		$query    = new \WC_Product_Query();
		$products = $query->get_products();
		foreach ( $products as $product ) {
			$product->delete( true );
		}
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		parent::tearDown();
		$this->remove_color_or_logo_attribute_taxonomy();
	}

	/**
	 * Remove product attributes that where created in previous tests.
	 */
	public function remove_color_or_logo_attribute_taxonomy() {
		$taxonomies = get_taxonomies();
		foreach ( (array) $taxonomies as $taxonomy ) {
			// pa - product attribute.
			if ( 'pa_color' === $taxonomy || 'pa_logo' === $taxonomy ) {
				unregister_taxonomy( $taxonomy );
			}
		}
	}

	/**
	 * Test that sample product data is imported.
	 */
	public function test_import_sample_products() {
		wp_set_current_user( $this->user );

		$this->remove_color_or_logo_attribute_taxonomy();

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/import_sample_products' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'failed', $data );
		$this->assertEquals( 0, count( $data['failed'] ) );
		$this->assertArrayHasKey( 'imported', $data );
		$this->assertArrayHasKey( 'skipped', $data );
		// There might be previous products present.
		if ( 0 === count( $data['skipped'] ) ) {
			$this->assertGreaterThan( 1, count( $data['imported'] ) );
		}
		$this->assertArrayHasKey( 'updated', $data );
		$this->assertEquals( 0, count( $data['updated'] ) );
	}

	/**
	 * Test creating a product from a template name.
	 */
	public function test_create_product_from_template() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/create_product_from_template' );
		$request->set_param( 'template_name', 'physical' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'id', $data );
		$product = wc_get_product( $data['id'] );
		$this->assertEquals( 'auto-draft', $product->get_status() );
		$this->assertEquals( 'simple', $product->get_type() );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/create_product_from_template' );
		$request->set_param( 'template_name', 'digital' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'id', $data );
		$product = wc_get_product( $data['id'] );
		$this->assertEquals( 'auto-draft', $product->get_status() );
		$this->assertEquals( 'simple', $product->get_type() );
	}

	/**
	 * Test that we get an error when template_name does not exist.
	 */
	public function test_create_product_from_wrong_template_name() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/create_product_from_template' );
		$request->set_param( 'template_name', 'random' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * Test that Tasks data is returned by the endpoint.
	 */
	public function test_create_homepage() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/create_homepage' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $data['status'] );
		$this->assertEquals( get_option( 'woocommerce_onboarding_homepage_post_id' ), $data['post_id'] );
		$this->assertEquals( htmlspecialchars_decode( get_edit_post_link( get_option( 'woocommerce_onboarding_homepage_post_id' ) ) ), $data['edit_post_link'] );
	}

	/**
	 * Test that the default homepage template can be filtered.
	 */
	public function test_homepage_template_can_be_filtered() {
		wp_set_current_user( $this->user );

		add_filter(
			'woocommerce_admin_onboarding_homepage_template',
			function ( $template ) {
				return 'Custom post content';
			}
		);

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/create_homepage' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 'Custom post content', get_the_content( null, null, $data['post_id'] ) );
	}


}
