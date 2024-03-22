<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;


/**
 * AdditionalFields Controller Tests.
 *
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WooCommerce.Commenting.CommentHooks.MissingHookComment
 */
class AdditionalFields extends MockeryTestCase {

	/**
	 * Fields to register.
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Checkout fields controller.
	 * @var CheckoutFields
	 */
	protected $controller;
	/**
	 * Setup products and a cart, as well as register fields.
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$this->register_fields();
		$this->controller = Package::container()->get( CheckoutFields::class );

		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();
		$fixtures->payments_enable_bacs();
		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
		);
		$this->reset_session();
	}

	/**
	 * Tear down Rest API server and remove fields.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
		$this->unregister_fields();
	}

	/**
	 * Register fields for testing.
	 */
	private function register_fields() {
		$this->fields = array(
			array(
				'id'                => 'plugin-namespace/gov-id',
				'label'             => 'Government ID',
				'location'          => 'address',
				'type'              => 'text',
				'required'          => true,
				'attributes'        => array(
					'title'          => 'This is a gov id',
					'autocomplete'   => 'gov-id',
					'autocapitalize' => 'none',
					'maxLength'      => '30',
				),
				'sanitize_callback' => function ( $value ) {
					return trim( $value );
				},
				'validate_callback' => function ( $value ) {
					return strlen( $value ) > 3;
				},
			),
			array(
				'id'       => 'plugin-namespace/job-function',
				'label'    => 'What is your main role at your company?',
				'location' => 'contact',
				'required' => true,
				'type'     => 'select',
				'options'  => array(
					array(
						'label' => 'Director',
						'value' => 'director',
					),
					array(
						'label' => 'Engineering',
						'value' => 'engineering',
					),
					array(
						'label' => 'Customer Support',
						'value' => 'customer-support',
					),
					array(
						'label' => 'Other',
						'value' => 'other',
					),
				),
			),
			array(
				'id'       => 'plugin-namespace/leave-on-porch',
				'label'    => __( 'Please leave my package on the porch if I\'m not home', 'woocommerce' ),
				'location' => 'additional',
				'type'     => 'checkbox',
			),
		);
		array_map( '__experimental_woocommerce_blocks_register_checkout_field', $this->fields );
	}

	/**
	 * Unregister fields after testing.
	 */
	private function unregister_fields() {
		array_map( '__internal_woocommerce_blocks_deregister_checkout_field', array_column( $this->fields, 'id' ) );
	}

	/**
	 * delete the current user, and empties the cart.
	 */
	private function reset_session() {
		wp_set_current_user( 0 );
		$customer = get_user_by( 'email', 'testaccount@test.com' );

		if ( $customer ) {
			wp_delete_user( $customer->ID );
		}
		\wc_empty_cart();
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
	}

	/**
	 * Test if suite valid fields register without an error.
	 */
	public function test_fields_register_without_error() {
		// We first unregister existing fields.
		$this->unregister_fields();
		// add our callbacks.
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->never();
		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);
		// register fields.
		$this->register_fields();
		// remove our callbacks.
		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);
	}

	/**
	 * Test that fields are registered in correct locations.
	 */
	public function test_fields_in_correct_locations() {
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'plugin-namespace/gov-id', $data['schema']['properties']['billing_address']['properties'] );
		$this->assertArrayHasKey( 'plugin-namespace/gov-id', $data['schema']['properties']['shipping_address']['properties'] );
		$this->assertArrayHasKey( 'plugin-namespace/job-function', $data['schema']['properties']['additional_fields']['properties'] );
		$this->assertArrayHasKey( 'plugin-namespace/leave-on-porch', $data['schema']['properties']['additional_fields']['properties'] );
	}

	/**
	 * Ensures registered fields show up in address schema.
	 */
	public function test_additional_fields_schema() {
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals(
			array(
				'description' => 'Government ID',
				'type'        => 'string',
				'context'     => array(
					'view',
					'edit',
				),
				'required'    => true,
			),
			$data['schema']['properties']['billing_address']['properties']['plugin-namespace/gov-id'],
			print_r( $data['schema']['properties']['billing_address']['properties'], true )
		);
	}

	/**
	 * Ensures select fields show an enum in the schema.
	 */
	public function test_select_enum_in_schema() {
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals(
			array(
				'description' => 'What is your main role at your company?',
				'type'        => 'string',
				'enum'        => array( 'director', 'engineering', 'customer-support', 'other' ),
				'context'     => array(
					'view',
					'edit',
				),
				'required'    => true,
			),
			$data['schema']['properties']['additional_fields']['properties']['plugin-namespace/job-function'],
			print_r( $data['schema']['properties']['additional_fields'], true )
		);
	}

	/**
	 * Ensures checkbox fields show up in the schema as optional booleans.
	 */
	public function test_checkbox_in_schema() {
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals(
			array(
				'description' => __( 'Please leave my package on the porch if I\'m not home', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array(
					'view',
					'edit',
				),
				'required'    => false,
			),
			$data['schema']['properties']['additional_fields']['properties']['plugin-namespace/leave-on-porch'],
			print_r( $data['schema']['properties']['additional_fields'], true )
		);
	}

	/**
	 * Ensures optional fields show up in the schema as optional.
	 */
	public function test_optional_field_in_schema() {
		$id = 'plugin-namespace/optional-field';
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Optional Field',
				'location' => 'additional',
				'type'     => 'text',
				'required' => false,
			)
		);
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals(
			array(
				'description' => 'Optional Field',
				'type'        => 'string',
				'context'     => array(
					'view',
					'edit',
				),
				'required'    => false,
			),
			$data['schema']['properties']['additional_fields']['properties'][ $id ],
			print_r( $data['schema']['properties']['additional_fields'], true )
		);

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );
	}

	/**
	 * Ensure an error is triggered when a field is registered without an ID.
	 */
	public function test_missing_id_in_registration() {
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				'A checkout field cannot be registered without an id.',
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'label'    => 'Invalid ID',
				'location' => 'additional',
				'type'     => 'text',
				'required' => false,
			)
		);
		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field didn't register.
		$this->assertEquals( \count( $this->controller->get_additional_fields() ), count( $this->fields ), \sprintf( 'An unexpected field is registered' ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered with an invalid ID.
	 */
	public function test_invalid_id_in_registration() {
		$id                    = 'invalid-id';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( \sprintf( 'Unable to register field with id: "%s". A checkout field id must consist of namespace/name.', $id ) ),

			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Invalid ID',
				'location' => 'additional',
				'type'     => 'text',
				'required' => false,
			)
		);
		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field didn't register.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered without a label.
	 */
	public function test_missing_label_in_registration() {
		$id                    = 'plugin-namespace/missing-label';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( \sprintf( 'Unable to register field with id: "%s". The field label is required.', $id ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'location' => 'additional',
				'type'     => 'text',
				'required' => false,
			)
		);
		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field didn't register.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered without a location key.
	 */
	public function test_missing_location_in_registration() {
		$id                    = 'plugin-namespace/missing-location';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( \sprintf( 'Unable to register field with id: "%s". The field location is required.', $id ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'    => $id,
				'label' => 'Missing Location',
				'type'  => 'text',
			)
		);
		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field didn't register.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered with an invalid location key (contact, address, additional).
	 */
	public function test_invalid_location_in_registration() {
		$id                    = 'plugin-namespace/invalid-location';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( \sprintf( 'Unable to register field with id: "%s". The field location is invalid.', $id ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Invalid Location',
				'location' => 'invalid',
				'type'     => 'text',
				'required' => false,
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field didn't register.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered with an existing id.
	 */
	public function test_already_registered_field() {
		$id                    = 'plugin-namespace/gov-id';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( \sprintf( 'Unable to register field with id: "%s". The field is already registered.', $id ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Government ID',
				'location' => 'address',
				'type'     => 'text',
				'required' => true,
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);
	}

	/**
	 * Ensure an error is triggered when a field is registered with an invalid type (text, select, checkbox).
	 */
	public function test_invalid_type_in_registration() {
		$id                    = 'plugin-namespace/invalid-type';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html(
					sprintf(
						'Unable to register field with id: "%s". Registering a field with type "%s" is not supported. The supported types are: %s.',
						$id,
						'invalid',
						implode( ', ', array( 'text', 'select', 'checkbox' ) )
					)
				),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Invalid Type',
				'location' => 'additional',
				'type'     => 'invalid',
				'required' => false,
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field didn't register.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered with an invalid sanitize callback.
	 */
	public function test_invalid_sanitize_in_registration() {
		$id                    = 'plugin-namespace/invalid-sanitize';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'Unable to register field with id: "%s". %s', $id, 'The sanitize_callback must be a valid callback.' ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'                => $id,
				'label'             => 'Invalid Sanitize',
				'location'          => 'additional',
				'type'              => 'text',
				'sanitize_callback' => 'invalid_sanitize_callback',
				'required'          => false,
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field didn't register.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered with an invalid validate callback.
	 */
	public function test_invalid_validate_in_registration() {
		$id                    = 'plugin-namespace/invalid-validate';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'Unable to register field with id: "%s". %s', $id, 'The validate_callback must be a valid callback.' ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'                => $id,
				'label'             => 'Invalid Validate',
				'location'          => 'additional',
				'type'              => 'text',
				'validate_callback' => 'invalid_validate_callback',
				'required'          => false,
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field didn't register.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered with an invalid attributes prop.
	 */
	public function test_invalid_attribute_in_registration() {
		$id                    = 'plugin-namespace/invalid-attribute';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'An invalid attributes value was supplied when registering field with id: "%s". %s', $id, 'Attributes must be a non-empty array.' ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'         => $id,
				'label'      => 'Invalid Attribute',
				'location'   => 'additional',
				'attributes' => 'invalid',
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures it's registered without attributes.
		$this->assertEmpty( $this->controller->get_additional_fields()[ $id ]['attributes'] );

		// Fields should makes it to Store API.
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals(
			array(
				'description' => 'Invalid Attribute',
				'type'        => 'string',
				'context'     => array(
					'view',
					'edit',
				),
				'required'    => false,
			),
			$data['schema']['properties']['additional_fields']['properties'][ $id ],
			print_r( $data['schema']['properties']['additional_fields'], true )
		);

		// Unregister the field.
		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered anymore.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered if a field is registered with invalid attributes values.
	 */
	public function test_invalid_attributes_values_in_registration() {
		$id                    = 'plugin-namespace/invalid-attribute-values';
		$invalid_attributes    = array( 'invalidAttribute' );
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'Invalid attribute found when registering field with id: "%s". Attributes: %s are not allowed.', $id, implode( ', ', $invalid_attributes ) ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'         => $id,
				'label'      => 'Invalid Attribute Values',
				'location'   => 'additional',
				'attributes' => array(
					'title'            => 'title',
					'maxLength'        => '20',
					'autocomplete'     => 'gov-id',
					'autocapitalize'   => 'none',
					'invalidAttribute' => 'invalidAttribute',
				),
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures it's registered without invalid attributes.
		$this->assertArrayNotHasKey( 'invalidAttribute', $this->controller->get_additional_fields()[ $id ]['attributes'] );

		// Fields should still be registered regardless of the error.
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals(
			array(
				'description' => 'Invalid Attribute Values',
				'type'        => 'string',
				'context'     => array(
					'view',
					'edit',
				),
				'required'    => false,
			),
			$data['schema']['properties']['additional_fields']['properties'][ $id ],
			print_r( $data['schema']['properties']['additional_fields'], true )
		);

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered anymore.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a select is registered without options prop.
	 */
	public function test_missing_select_options_in_registration() {
		$id                    = 'plugin-namespace/missing-options';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'Unable to register field with id: "%s". %s', $id, 'Fields of type "select" must have an array of "options".' ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Missing Options',
				'location' => 'additional',
				'type'     => 'select',
				'required' => false,
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a select is registered with an invalid options array.
	 */
	public function test_invalid_select_options_in_registration() {
		$id                    = 'plugin-namespace/invalid-options';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'Unable to register field with id: "%s". %s', $id, 'Fields of type "select" must have an array of "options" and each option must contain a "value" and "label" member.' ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Invalid Options',
				'location' => 'additional',
				'type'     => 'select',
				'options'  => array( // numeric array instead of associative array.
					'invalidValue',
				),
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a select is registered with duplicate options.
	 */
	public function test_duplicate_select_options_in_registration() {
		$id                    = 'plugin-namespace/duplicate-options';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'Duplicate key found when registering field with id: "%s". The value in each option of "select" fields must be unique. Duplicate value "%s" found. The duplicate key will be removed.', $id, 'duplicate' ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Duplicate Options',
				'location' => 'additional',
				'type'     => 'select',
				'options'  => array(
					array(
						'label' => 'Option 1',
						'value' => 'duplicate',
					),
					array(
						'label' => 'Option 2',
						'value' => 'duplicate',
					),
				),
				'required' => true,
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Fields should still be registered regardless of the error, but with no duplicate values.
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();

		$this->assertEquals(
			array( 'duplicate' ),
			$data['schema']['properties']['additional_fields']['properties'][ $id ]['enum'],
			print_r( $data['schema']['properties']['additional_fields'], true )
		);

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered anymore.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure a select has an extra empty option if it's optional.
	 */
	public function test_optional_select_has_empty_value() {
		$id = 'plugin-namespace/optional-select';
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Optional Select',
				'location' => 'additional',
				'type'     => 'select',
				'options'  => array(
					array(
						'label' => 'Option 1',
						'value' => 'option-1',
					),
					array(
						'label' => 'Option 2',
						'value' => 'option-2',
					),
				),
			)
		);
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals(
			array( '', 'option-1', 'option-2' ),
			$data['schema']['properties']['additional_fields']['properties'][ $id ]['enum'],
			print_r( $data['schema']['properties']['additional_fields']['properties'][ $id ], true )
		);

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a checkbox is registered as required.
	 */
	public function test_invalid_required_prop_checkbox() {
		$id                    = 'plugin-namespace/checkbox-only-optional';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'Registering checkbox fields as required is not supported. "%s" will be registered as optional.', $id ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Checkbox Only Optional',
				'location' => 'additional',
				'type'     => 'checkbox',
				'required' => true,
			)
		);

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

		// Fields should still be registered regardless of the error, but with required as optional.
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();

		$this->assertEquals(
			false,
			$data['schema']['properties']['additional_fields']['properties'][ $id ]['required'],
			print_r( $data['schema']['properties']['additional_fields']['properties'][ $id ], true )
		);

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensure an error is triggered when a field is registered with hidden set to true.
	 */
	public function test_register_hidden_field_error() {
		$id                    = 'plugin-namespace/hidden-field';
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_blocks_register_checkout_field',
				\esc_html( sprintf( 'Registering a field with hidden set to true is not supported. The field "%s" will be registered as visible.', $id ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Hidden Field',
				'location' => 'address',
				'type'     => 'text',
				'hidden'   => true,
			)
		);

		// Fields should still be registered regardless of the error, but not hidden.
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();

		$this->assertArrayHasKey( $id, $data['schema']['properties']['billing_address']['properties'] );

		\remove_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			)
		);

			\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensures that placing an order with the correct values actually work.
	 */
	public function test_placing_order_with_valid_fields() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'email'                   => 'testaccount@test.com',
					'plugin-namespace/gov-id' => 'my-gov-id',

				),
				'shipping_address'  => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(
					'plugin-namespace/job-function'   => 'engineering',
					'plugin-namespace/leave-on-porch' => true,
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status(), print_r( $data, true ) );
	}

	/**
	 * Ensures that placing an order with an invalid text value fails
	 */
	public function test_placing_order_with_invalid_text() {
		$id      = 'plugin-namespace/gov-id';
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
					$id          => array( 'array-instead-of-text' ),

				),
				'shipping_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					$id          => array( 'array-instead-of-text' ),

				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(
					'plugin-namespace/job-function'   => 'engineering',
					'plugin-namespace/leave-on-porch' => true,
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status(), print_r( $data, true ) );
		$this->assertEquals( \sprintf( 'Invalid %s provided.', $id ), $data['data']['params']['billing_address'], print_r( $data, true ) );
	}

	/**
	 * Ensures that a string is sanitized correctly via the provided sanitize callback.
	 */
	public function test_placing_order_sanitize_text() {
		$id = 'plugin-namespace/sanitize-text';
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'                => $id,
				'label'             => 'Sanitize Text',
				'location'          => 'additional',
				'type'              => 'text',
				'sanitize_callback' => function ( $value ) {
					return 'sanitized-' . $value;
				},
			)
		);
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'email'                   => 'testaccount@test.com',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'shipping_address'  => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(
					$id => 'value',
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status(), print_r( $data, true ) );
		$this->assertEquals( 'sanitized-value', $data['additional_fields'][ $id ], print_r( $data, true ) );

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensures that the provided validate callback works and prevents an order.
	 */
	public function test_placing_order_validate_text() {
		$id = 'plugin-namespace/validate-text';
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'                => $id,
				'label'             => 'Validate Text',
				'location'          => 'additional',
				'type'              => 'text',
				'validate_callback' => function ( $value ) {
					if ( 'invalid' === $value ) {
						return new \WP_Error( 'invalid_value', 'Invalid value provided.' );
					}
					return true;
				},
			)
		);
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'email'                   => 'testaccount@test.com',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'shipping_address'  => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(
					$id => 'invalid',
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status(), print_r( $data, true ) );
		$this->assertEquals( 'Invalid value provided.', $data['data']['params']['additional_fields'], print_r( $data, true ) );

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensures sanitize filters are being called.
	 */
	public function test_sanitize_filter() {
		$id = 'plugin-namespace/filter-sanitize';
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Filter Sanitize',
				'location' => 'additional',
				'type'     => 'text',
			)
		);

		add_filter(
			'__experimental_woocommerce_blocks_sanitize_additional_field',
			function ( $value, $key ) use ( $id ) {
				if ( $key === $id ) {
					return 'sanitized-' . $value;
				}
				return $value;
			},
			10,
			2
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'email'                   => 'testaccount@test.com',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'shipping_address'  => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(
					$id => 'value',
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status(), print_r( $data, true ) );
		$this->assertEquals( 'sanitized-value', $data['additional_fields'][ $id ], print_r( $data, true ) );

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensures validate filters are being called.
	 */
	public function test_validate_filter() {
		$id = 'plugin-namespace/filter-validate';
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'Filter Validate',
				'location' => 'contact',
				'type'     => 'text',
				'required' => true,
			)
		);

		add_action(
			'__experimental_woocommerce_blocks_validate_additional_field',
			function ( \WP_Error $errors, $key, $value ) use ( $id ) {
				if ( $key === $id && 'invalid' === $value ) {
					$errors->add( 'my_invalid_value', 'Invalid value provided.' );
				}
			},
			10,
			3
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'email'                   => 'testaccount@test.com',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'shipping_address'  => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'plugin-namespace/gov-id' => 'my-gov-id',
				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(
					$id => 'invalid',
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status(), print_r( $data, true ) );
		$this->assertEquals( 'Invalid value provided.', $data['data']['params']['additional_fields'], print_r( $data, true ) );

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensures an error is returned when required fields in Address are missing.
	 */
	public function test_place_order_required_address_field() {
		$id    = 'plugin-namespace/my-required-field';
		$label = 'My Required Field';
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => $label,
				'location' => 'address',
				'type'     => 'text',
				'required' => true,
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'email'                   => 'testaccount@test.com',
					'plugin-namespace/gov-id' => 'gov id',
				),
				'shipping_address'  => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'plugin-namespace/gov-id' => 'gov id',
				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status(), print_r( $data, true ) );
		$this->assertEquals( \sprintf( 'There was a problem with the provided shipping address: %s is required', $label ), $data['message'], print_r( $data, true ) );

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensures an error is returned when required fields in Contact are missing.
	 */
	public function test_place_order_required_contact_field() {
		$id = 'plugin-namespace/my-required-contact-field';
		\__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => $id,
				'label'    => 'My Required Field',
				'location' => 'contact',
				'type'     => 'text',
				'required' => true,
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'email'                   => 'testaccount@test.com',
					'plugin-namespace/gov-id' => 'gov id',
				),
				'shipping_address'  => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'plugin-namespace/gov-id' => 'gov id',
				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status(), print_r( $data, true ) );
		$this->assertEquals( \sprintf( '%s is not of type string.', $id ), $data['data']['params']['additional_fields'], print_r( $data, true ) );

		\__internal_woocommerce_blocks_deregister_checkout_field( $id );

		// Ensures the field isn't registered.
		$this->assertFalse( $this->controller->is_field( $id ), \sprintf( '%s is still registered', $id ) );
	}

	/**
	 * Ensures that placing an order with an invalid select value fails
	 */
	public function test_placing_order_with_invalid_select() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'   => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'email'                   => 'testaccount@test.com',
					'plugin-namespace/gov-id' => 'my-gov-id',

				),
				'shipping_address'  => (object) array(
					'first_name'              => 'test',
					'last_name'               => 'test',
					'company'                 => '',
					'address_1'               => 'test',
					'address_2'               => '',
					'city'                    => 'test',
					'state'                   => '',
					'postcode'                => 'cb241ab',
					'country'                 => 'GB',
					'phone'                   => '',
					'plugin-namespace/gov-id' => 'my-gov-id',

				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(
					'plugin-namespace/job-function' => 'invalid-prop',
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'plugin-namespace/job-function is not one of director, engineering, customer-support, and other.', $data['data']['params']['additional_fields'], print_r( $data, true ) );
	}
}
