<?php
/**
 * Settings API Tests.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Utilities\ArrayUtil;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Class Settings.
 */
class Settings extends WC_REST_Unit_Test_Case {
	use ArraySubsetAsserts;

	/**
	 * Whether the email settings contract state needs restoring.
	 *
	 * @var bool
	 */
	private $email_contract_state_active = false;

	/**
	 * Missing-option sentinel for the email improvements feature.
	 *
	 * @var stdClass|null
	 */
	private $email_improvements_missing;

	/**
	 * Previous email improvements feature option.
	 *
	 * @var mixed
	 */
	private $previous_email_improvements_option;

	/**
	 * Previous email singleton.
	 *
	 * @var WC_Emails|null
	 */
	private $previous_emails_instance;

	/**
	 * Email singleton instance property.
	 *
	 * @var ReflectionProperty|null
	 */
	private $emails_instance_property;

	/**
	 * Setup our test server, endpoints, and user info.
	 *
	 * @throws Throwable When route or fixture setup fails.
	 */
	public function setUp(): void {
		parent::setUp();

		try {
			if ( 'test_email_setting_group_contracts' === $this->getName( false ) ) {
				$this->set_up_email_setting_group_contract_state();
			}

			$this->initialize_rest_api_routes();
			$this->endpoint = new WC_REST_Setting_Options_Controller();
			\Automattic\WooCommerce\RestApi\UnitTests\Helpers\SettingsHelper::register();
			$this->user = $this->factory->user->create(
				array(
					'role' => 'administrator',
				)
			);
		} catch ( Throwable $throwable ) {
			$this->restore_email_setting_group_contract_state();
			throw $throwable;
		}
	}

	/**
	 * Restore test-specific email state before the base teardown restores hooks.
	 */
	public function tearDown(): void {
		try {
			$this->restore_email_setting_group_contract_state();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Enable email improvements before REST callbacks capture email objects.
	 */
	private function set_up_email_setting_group_contract_state(): void {
		$this->email_improvements_missing         = new stdClass();
		$this->previous_email_improvements_option = get_option( 'woocommerce_feature_email_improvements_enabled', $this->email_improvements_missing );
		$this->emails_instance_property           = new ReflectionProperty( WC_Emails::class, 'instance' );
		$this->emails_instance_property->setAccessible( true );
		$this->previous_emails_instance    = $this->emails_instance_property->getValue();
		$this->email_contract_state_active = true;

		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
		$this->emails_instance_property->setValue( null, null );
	}

	/**
	 * Restore the exact option presence/value and email singleton.
	 */
	private function restore_email_setting_group_contract_state(): void {
		if ( ! $this->email_contract_state_active ) {
			return;
		}

		try {
			try {
				$this->emails_instance_property->setValue( null, $this->previous_emails_instance );
			} finally {
				$this->restore_option(
					'woocommerce_feature_email_improvements_enabled',
					$this->previous_email_improvements_option,
					$this->email_improvements_missing
				);
			}
		} finally {
			$this->email_contract_state_active        = false;
			$this->email_improvements_missing         = null;
			$this->previous_email_improvements_option = null;
			$this->previous_emails_instance           = null;
			$this->emails_instance_property           = null;
		}
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/settings', $routes );
		$this->assertArrayHasKey( '/wc/v3/settings/(?P<group_id>[\w-]+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/settings/(?P<group_id>[\w-]+)/(?P<id>[\w-]+)', $routes );
	}

	/**
	 * Test getting all groups.
	 *
	 * @since 3.5.0
	 */
	public function test_get_groups() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$matching_settings_data = current(
			array_filter(
				$data,
				function ( $settings ) {
					return 'test' === $settings['id'];
				}
			)
		);
		$this->assertIsArray( $matching_settings_data );

		$this->assertArraySubset(
			array(
				'id'          => 'test',
				'label'       => 'Test extension',
				'parent_id'   => '',
				'description' => 'My awesome test settings.',
				'sub_groups'  => array( 'sub-test' ),
				'_links'      => array(
					'options' => array(
						array(
							'href' => rest_url( '/wc/v3/settings/test' ),
						),
					),
				),
			),
			$matching_settings_data
		);

		$matching_settings_data = current(
			array_filter(
				$data,
				function ( $settings ) {
					return 'sub-test' === $settings['id'];
				}
			)
		);
		$this->assertIsArray( $matching_settings_data );

		$this->assertArraySubset(
			array(
				'id'          => 'sub-test',
				'label'       => 'Sub test',
				'parent_id'   => 'test',
				'description' => '',
				'sub_groups'  => array(),
				'_links'      => array(
					'options' => array(
						array(
							'href' => rest_url( '/wc/v3/settings/sub-test' ),
						),
					),
				),
			),
			$matching_settings_data
		);
	}

	/**
	 * @testdox Core settings groups expose stable IDs and email subgroup relationships.
	 */
	public function test_core_settings_groups_contract(): void {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		$groups = $this->index_settings_by_id( $data );
		foreach ( array( 'wc_admin', 'general', 'products', 'tax', 'shipping', 'checkout', 'account', 'email', 'integration', 'advanced' ) as $group_id ) {
			$this->assertArrayHasKey( $group_id, $groups, "Expected the {$group_id} settings group to be registered." );
			$this->assertSame( '', $groups[ $group_id ]['parent_id'] );
		}

		$expected_email_groups = array(
			'email_new_order',
			'email_cancelled_order',
			'email_failed_order',
			'email_customer_on_hold_order',
			'email_customer_processing_order',
			'email_customer_completed_order',
			'email_customer_refunded_order',
			'email_customer_invoice',
			'email_customer_note',
			'email_customer_reset_password',
			'email_customer_new_account',
		);

		$this->assertIsArray( $groups['email']['sub_groups'] );
		foreach ( $expected_email_groups as $group_id ) {
			$this->assertContains( $group_id, $groups['email']['sub_groups'] );
			$this->assertArrayHasKey( $group_id, $groups );
			$this->assertSame( 'email', $groups[ $group_id ]['parent_id'] );
			$this->assertSame( array(), $groups[ $group_id ]['sub_groups'] );
		}
	}

	/**
	 * @testdox Core settings pages expose stable setting IDs, types, and option keys.
	 * @dataProvider core_setting_group_contracts_provider
	 *
	 * @param string $group_id          Settings group ID.
	 * @param array  $expected_settings Expected setting contracts keyed by setting ID.
	 */
	public function test_core_setting_group_contracts( string $group_id, array $expected_settings ): void {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', "/wc/v3/settings/{$group_id}" ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assert_setting_contracts( $data, $expected_settings );
	}

	/**
	 * Data provider for core settings page contracts.
	 *
	 * @return array
	 */
	public function core_setting_group_contracts_provider(): array {
		return array(
			'products' => array(
				'products',
				array(
					'woocommerce_weight_unit' => array(
						'type'        => 'select',
						'option_keys' => array( 'kg', 'g', 'lbs', 'oz' ),
					),
				),
			),
			'tax'      => array(
				'tax',
				array(
					'woocommerce_prices_include_tax' => array(
						'type'        => 'radio',
						'option_keys' => array( 'yes', 'no' ),
					),
				),
			),
			'shipping' => array(
				'shipping',
				array(
					'woocommerce_ship_to_destination' => array(
						'type'        => 'radio',
						'option_keys' => array( 'shipping', 'billing', 'billing_only' ),
					),
				),
			),
			'checkout' => array( 'checkout', array() ),
			'account'  => array(
				'account',
				array(
					'woocommerce_enable_guest_checkout' => array( 'type' => 'checkbox' ),
				),
			),
			'email'    => array(
				'email',
				array(
					'woocommerce_email_from_name'    => array( 'type' => 'text' ),
					'woocommerce_email_from_address' => array( 'type' => 'email' ),
				),
			),
			'advanced' => array(
				'advanced',
				array(
					'woocommerce_cart_page_id'          => array( 'type' => 'select' ),
					'woocommerce_checkout_page_id'      => array( 'type' => 'select' ),
					'woocommerce_myaccount_page_id'     => array( 'type' => 'select' ),
					'woocommerce_checkout_pay_endpoint' => array( 'type' => 'text' ),
					'woocommerce_checkout_order_received_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_add_payment_method_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_delete_payment_method_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_orders_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_view_order_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_downloads_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_edit_account_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_edit_address_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_payment_methods_endpoint' => array( 'type' => 'text' ),
					'woocommerce_myaccount_lost_password_endpoint' => array( 'type' => 'text' ),
					'woocommerce_logout_endpoint'       => array( 'type' => 'text' ),
					'woocommerce_allow_tracking'        => array( 'type' => 'checkbox' ),
					'woocommerce_show_marketplace_suggestions' => array( 'type' => 'checkbox' ),
					'woocommerce_analytics_enabled'     => array( 'type' => 'checkbox' ),
				),
			),
		);
	}

	/**
	 * @testdox General settings support registered single and batch updates with persisted values.
	 */
	public function test_general_settings_crud_contract(): void {
		wp_set_current_user( $this->user );

		$missing                    = new stdClass();
		$previous_allowed_countries = get_option( 'woocommerce_allowed_countries', $missing );
		$previous_currency          = get_option( 'woocommerce_currency', $missing );

		try {
			update_option( 'woocommerce_allowed_countries', 'all' );
			update_option( 'woocommerce_currency', 'USD' );

			$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/general' ) );
			$this->assertSame( 200, $response->get_status() );
			$this->assert_setting_contracts(
				$response->get_data(),
				array(
					'woocommerce_allowed_countries' => array(
						'type'        => 'select',
						'option_keys' => array( 'all', 'all_except', 'specific' ),
					),
					'woocommerce_currency'          => array( 'type' => 'select' ),
				)
			);

			$request = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_allowed_countries' );
			$request->set_body_params( array( 'value' => 'specific' ) );
			$response = $this->server->dispatch( $request );
			$data     = $response->get_data();

			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( 'woocommerce_allowed_countries', $data['id'] );
			$this->assertSame( 'general', $data['group_id'] );
			$this->assertSame( 'specific', $data['value'] );
			$this->assertSame( 'specific', get_option( 'woocommerce_allowed_countries' ) );

			$request = new WP_REST_Request( 'POST', '/wc/v3/settings/general/batch' );
			$request->set_body_params(
				array(
					'update' => array(
						array(
							'id'    => 'woocommerce_allowed_countries',
							'value' => 'all_except',
						),
						array(
							'id'    => 'woocommerce_currency',
							'value' => 'EUR',
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );
			$data     = $response->get_data();

			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( 'all_except', $data['update'][0]['value'] );
			$this->assertSame( 'EUR', $data['update'][1]['value'] );

			foreach ( array(
				'woocommerce_allowed_countries' => 'all_except',
				'woocommerce_currency'          => 'EUR',
			) as $setting_id => $expected_value ) {
				$response = $this->server->dispatch( new WP_REST_Request( 'GET', "/wc/v3/settings/general/{$setting_id}" ) );
				$data     = $response->get_data();

				$this->assertSame( 200, $response->get_status() );
				$this->assertSame( $setting_id, $data['id'] );
				$this->assertSame( 'general', $data['group_id'] );
				$this->assertSame( $expected_value, $data['value'] );
				$this->assertSame( $expected_value, get_option( $setting_id ) );
			}
		} finally {
			$this->restore_option( 'woocommerce_allowed_countries', $previous_allowed_countries, $missing );
			$this->restore_option( 'woocommerce_currency', $previous_currency, $missing );
		}
	}

	/**
	 * @testdox Email settings groups expose stable field IDs, types, and email format options.
	 * @dataProvider email_setting_group_contracts_provider
	 *
	 * @param string $group_id          Email settings group ID.
	 * @param array  $expected_settings Expected setting contracts keyed by setting ID.
	 * @param bool   $has_enabled       Whether the email supports an enabled setting.
	 */
	public function test_email_setting_group_contracts( string $group_id, array $expected_settings, bool $has_enabled = true ): void {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', "/wc/v3/settings/{$group_id}" ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$common_settings = array(
			'cc'         => array( 'type' => 'text' ),
			'bcc'        => array( 'type' => 'text' ),
			'email_type' => array(
				'type'        => 'select',
				'option_keys' => array( 'plain', 'html', 'multipart' ),
			),
		);
		if ( $has_enabled ) {
			$common_settings['enabled'] = array( 'type' => 'checkbox' );
		}

		$this->assert_setting_contracts(
			$data,
			array_merge( $expected_settings, $common_settings )
		);
	}

	/**
	 * Data provider for email settings group contracts.
	 *
	 * @return array
	 */
	public function email_setting_group_contracts_provider(): array {
		return array(
			'new order'        => array(
				'email_new_order',
				array(
					'recipient' => array( 'type' => 'text' ),
					'subject'   => array( 'type' => 'text' ),
				),
			),
			'failed order'     => array(
				'email_failed_order',
				array(
					'recipient' => array( 'type' => 'text' ),
					'subject'   => array( 'type' => 'text' ),
				),
			),
			'on-hold order'    => array( 'email_customer_on_hold_order', array( 'subject' => array( 'type' => 'text' ) ) ),
			'processing order' => array( 'email_customer_processing_order', array( 'subject' => array( 'type' => 'text' ) ) ),
			'completed order'  => array( 'email_customer_completed_order', array( 'subject' => array( 'type' => 'text' ) ) ),
			'refunded order'   => array(
				'email_customer_refunded_order',
				array(
					'subject_full'    => array( 'type' => 'text' ),
					'subject_partial' => array( 'type' => 'text' ),
				),
			),
			'customer invoice' => array(
				'email_customer_invoice',
				array(
					'subject'      => array( 'type' => 'text' ),
					'heading'      => array( 'type' => 'text' ),
					'subject_paid' => array( 'type' => 'text' ),
					'heading_paid' => array( 'type' => 'text' ),
				),
				false,
			),
			'customer note'    => array( 'email_customer_note', array( 'subject' => array( 'type' => 'text' ) ) ),
			'reset password'   => array( 'email_customer_reset_password', array( 'subject' => array( 'type' => 'text' ) ) ),
			'new account'      => array( 'email_customer_new_account', array( 'subject' => array( 'type' => 'text' ) ) ),
		);
	}

	/**
	 * Index settings or groups by their stable ID.
	 *
	 * @param array $settings Settings or groups from a REST response.
	 * @return array
	 */
	private function index_settings_by_id( array $settings ): array {
		$indexed = array();
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) ) {
				$indexed[ $setting['id'] ] = $setting;
			}
		}
		return $indexed;
	}

	/**
	 * Assert stable setting contracts without depending on translated copy.
	 *
	 * @param array $settings          Settings from a REST response.
	 * @param array $expected_settings Expected contracts keyed by setting ID.
	 */
	private function assert_setting_contracts( array $settings, array $expected_settings ): void {
		$this->assertIsArray( $settings );
		$indexed = $this->index_settings_by_id( $settings );

		foreach ( $expected_settings as $setting_id => $expected ) {
			$this->assertArrayHasKey( $setting_id, $indexed, "Expected the {$setting_id} setting to be exposed." );
			$this->assertSame( $expected['type'], $indexed[ $setting_id ]['type'] );

			if ( isset( $expected['option_keys'] ) ) {
				$this->assertArrayHasKey( 'options', $indexed[ $setting_id ] );
				$actual_keys   = array_keys( $indexed[ $setting_id ]['options'] );
				$expected_keys = $expected['option_keys'];
				sort( $actual_keys );
				sort( $expected_keys );
				$this->assertSame( $expected_keys, $actual_keys );
			}
		}
	}

	/**
	 * Restore an option to its exact pre-test presence and value.
	 *
	 * @param string   $option_name    Option name.
	 * @param mixed    $previous_value Previous option value or the missing sentinel.
	 * @param stdClass $missing        Missing-option sentinel.
	 */
	private function restore_option( string $option_name, $previous_value, stdClass $missing ): void {
		if ( $missing === $previous_value ) {
			delete_option( $option_name );
		} else {
			update_option( $option_name, $previous_value );
		}
	}

	/**
	 * Test /settings without valid permissions/creds.
	 *
	 * @since 3.5.0
	 */
	public function test_get_groups_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test /settings without valid permissions/creds.
	 *
	 * @since 3.5.0
	 * @covers WC_Rest_Settings_Controller::get_items
	 */
	public function test_get_groups_none_registered() {
		wp_set_current_user( $this->user );

		remove_all_filters( 'woocommerce_settings_groups' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings' ) );
		$this->assertEquals( 500, $response->get_status() );

		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\SettingsHelper::register();
	}

	/**
	 * Test groups schema.
	 *
	 * @since 3.5.0
	 */
	public function test_get_group_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/settings' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 5, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'parent_id', $properties );
		$this->assertArrayHasKey( 'label', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'sub_groups', $properties );
	}

	/**
	 * Test settings schema.
	 *
	 * @since 3.5.0
	 */
	public function test_get_setting_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/settings/test/woocommerce_shop_page_display' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 10, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'label', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'value', $properties );
		$this->assertArrayHasKey( 'default', $properties );
		$this->assertArrayHasKey( 'tip', $properties );
		$this->assertArrayHasKey( 'placeholder', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'options', $properties );
		$this->assertArrayHasKey( 'group_id', $properties );
	}

	/**
	 * Test getting a single group.
	 *
	 * @since 3.5.0
	 */
	public function test_get_group() {
		wp_set_current_user( $this->user );

		// test route callback receiving an empty group id.
		$result = $this->endpoint->get_group_settings( '' );
		$this->assertWPError( $result );

		// test getting a group that does not exist.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/not-real' ) );
		$this->assertEquals( 404, $response->get_status() );

		// test getting the 'invalid' group.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/invalid' ) );
		$this->assertEquals( 404, $response->get_status() );

		// test getting a valid group with settings attached to it.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/test' ) );
		$data     = $response->get_data();
		$this->assertEquals( 1, count( $data ) );
		$this->assertEquals( 'woocommerce_shop_page_display', $data[0]['id'] );
		$this->assertEmpty( $data[0]['value'] );
	}

	/**
	 * Test getting a single group without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_get_group_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/coupon-data' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single setting.
	 *
	 * @since 3.5.0
	 */
	public function test_update_setting() {
		wp_set_current_user( $this->user );

		// test defaults first.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/test/woocommerce_shop_page_display' ) );
		$data     = $response->get_data();
		$this->assertEquals( '', $data['value'] );

		// test updating shop display setting.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'test', 'woocommerce_shop_page_display' ) );
		$request->set_body_params(
			array(
				'value' => 'both',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'both', $data['value'] );
		$this->assertEquals( 'both', get_option( 'woocommerce_shop_page_display' ) );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'test', 'woocommerce_shop_page_display' ) );
		$request->set_body_params(
			array(
				'value' => 'subcategories',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'subcategories', $data['value'] );
		$this->assertEquals( 'subcategories', get_option( 'woocommerce_shop_page_display' ) );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'test', 'woocommerce_shop_page_display' ) );
		$request->set_body_params(
			array(
				'value' => '',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( '', $data['value'] );
		$this->assertEquals( '', get_option( 'woocommerce_shop_page_display' ) );
	}

	/**
	 * Test updating multiple settings at once.
	 *
	 * @since 3.5.0
	 */
	public function test_update_settings() {
		wp_set_current_user( $this->user );

		// test defaults first.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/test' ) );
		$data     = $response->get_data();
		$this->assertEquals( '', $data[0]['value'] );

		// test setting both at once.
		$request = new WP_REST_Request( 'POST', '/wc/v3/settings/test/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'    => 'woocommerce_shop_page_display',
						'value' => 'both',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'both', $data['update'][0]['value'] );
		$this->assertEquals( 'both', get_option( 'woocommerce_shop_page_display' ) );

		// test updating one, but making sure the other value stays the same.
		$request = new WP_REST_Request( 'POST', '/wc/v3/settings/test/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'    => 'woocommerce_shop_page_display',
						'value' => 'subcategories',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'subcategories', $data['update'][0]['value'] );
		$this->assertEquals( 'subcategories', get_option( 'woocommerce_shop_page_display' ) );
	}

	/**
	 * Test getting a single setting.
	 *
	 * @since 3.5.0
	 */
	public function test_get_setting() {
		wp_set_current_user( $this->user );

		// test getting an invalid setting from a group that does not exist.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/not-real/woocommerce_shop_page_display' ) );
		$data     = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting an invalid setting from a group that does exist.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/invalid/invalid' ) );
		$data     = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting a valid setting.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/test/woocommerce_shop_page_display' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 'woocommerce_shop_page_display', $data['id'] );
		$this->assertEquals( 'Shop page display', $data['label'] );
		$this->assertEquals( '', $data['default'] );
		$this->assertEquals( 'select', $data['type'] );
		$this->assertEquals( '', $data['value'] );
	}

	/**
	 * Test getting a single setting without valid user permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_setting_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/test/woocommerce_shop_page_display' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests the GET single setting route handler receiving an empty setting ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_setting_empty_setting_id() {
		$result = $this->endpoint->get_setting( 'test', '' );

		$this->assertWPError( $result );
	}

	/**
	 * Tests the GET single setting route handler receiving an invalid setting ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_setting_invalid_setting_id() {
		$result = $this->endpoint->get_setting( 'test', 'invalid' );

		$this->assertWPError( $result );
	}

	/**
	 * Tests the GET single setting route handler encountering an invalid setting type.
	 *
	 * @since 3.5.0
	 */
	public function test_get_setting_invalid_setting_type() {
		// $controller = $this->getMock( 'WC_Rest_Setting_Options_Controller', array( 'get_group_settings', 'is_setting_type_valid' ) );
		$controller = $this->getMockBuilder( 'WC_Rest_Setting_Options_Controller' )->setMethods( array( 'get_group_settings', 'is_setting_type_valid' ) )->getMock();

		$controller
			->expects( $this->any() )
			->method( 'get_group_settings' )
			->will( $this->returnValue( \Automattic\WooCommerce\RestApi\UnitTests\Helpers\SettingsHelper::register_test_settings( array() ) ) );

		$controller
			->expects( $this->any() )
			->method( 'is_setting_type_valid' )
			->will( $this->returnValue( false ) );

		$result = $controller->get_setting( 'test', 'woocommerce_shop_page_display' );

		$this->assertWPError( $result );
	}

	/**
	 * Test updating a single setting without valid user permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_update_setting_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'test', 'woocommerce_shop_page_display' ) );
		$request->set_body_params(
			array(
				'value' => 'subcategories',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}


	/**
	 * Test updating multiple settings without valid user permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_update_settings_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/wc/v3/settings/test/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'    => 'woocommerce_shop_page_display',
						'value' => 'subcategories',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a bad setting ID.
	 *
	 * @since 3.5.0
	 * @covers WC_Rest_Setting_Options_Controller::update_item
	 */
	public function test_update_setting_bad_setting_id() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/settings/test/invalid' );
		$request->set_body_params(
			array(
				'value' => 'test',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests our classic setting registration to make sure settings added for WP-Admin are available over the API.
	 *
	 * @since 3.5.0
	 */
	public function test_classic_settings() {
		wp_set_current_user( $this->user );

		// Make sure the group is properly registered.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/products' ) );
		$data     = $response->get_data();
		$this->assertTrue( is_array( $data ) );

		$setting_downloads_required = null;
		foreach ( $data as $setting ) {
			if ( 'woocommerce_downloads_require_login' === $setting['id'] ) {
				$setting_downloads_required = $setting;
				break;
			}
		}

		$this->assertNotEmpty( $setting_downloads_required );

		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'          => 'woocommerce_downloads_require_login',
					'label'       => 'Access restriction',
					'description' => 'Downloads require login',
					'type'        => 'checkbox',
					'default'     => 'no',
					'tip'         => 'This setting does not apply to guest purchases.',
					'value'       => 'no',
					'_links'      => array(
						'self'       => array(
							array(
								'href' => rest_url( '/wc/v3/settings/products/woocommerce_downloads_require_login' ),
							),
						),
						'collection' => array(
							array(
								'href' => rest_url( '/wc/v3/settings/products' ),
							),
						),
					),
				),
				$setting_downloads_required
			)
		);

		// test get single.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/products/woocommerce_dimension_unit' ) );
		$data     = $response->get_data();

		$this->assertEquals( 'in', $data['default'] );

		// test update.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'products', 'woocommerce_dimension_unit' ) );
		$request->set_body_params(
			array(
				'value' => 'yd',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'yd', $data['value'] );
		$this->assertEquals( 'yd', get_option( 'woocommerce_dimension_unit' ) );
	}

	/**
	 * Tests our email etting registration to make sure settings added for WP-Admin are available over the API.
	 *
	 * @since 3.5.0
	 */
	public function test_email_settings() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/email_new_order' ) );
		$settings = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$recipient_setting = null;
		foreach ( $settings as $setting ) {
			if ( 'recipient' === $setting['id'] ) {
				$recipient_setting = $setting;
				break;
			}
		}

		$this->assertNotEmpty( $recipient_setting );

		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'          => 'recipient',
					'label'       => 'Recipient(s)',
					'description' => 'Enter recipients (comma separated) for this email. Defaults to <code>admin@example.org</code>.',
					'type'        => 'text',
					'default'     => '',
					'tip'         => 'Enter recipients (comma separated) for this email. Defaults to <code>admin@example.org</code>.',
					'value'       => '',
					'_links'      => array(
						'self'       => array(
							array(
								'href' => rest_url( '/wc/v3/settings/email_new_order/recipient' ),
							),
						),
						'collection' => array(
							array(
								'href' => rest_url( '/wc/v3/settings/email_new_order' ),
							),
						),
					),
				),
				$recipient_setting
			)
		);

		// test get single.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/email_new_order/subject' ) );
		$setting  = $response->get_data();

		$this->assertEquals(
			array(
				'id'          => 'subject',
				'label'       => 'Subject',
				'description' => 'Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
				'type'        => 'text',
				'default'     => '',
				'tip'         => 'Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
				'value'       => '',
				'group_id'    => 'email_new_order',
			),
			$setting
		);

		// test update.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'email_new_order', 'subject' ) );
		$request->set_body_params(
			array(
				'value' => 'This is my subject',
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();

		$this->assertEquals(
			array(
				'id'          => 'subject',
				'label'       => 'Subject',
				'description' => 'Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
				'type'        => 'text',
				'default'     => '',
				'tip'         => 'Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
				'value'       => 'This is my subject',
				'group_id'    => 'email_new_order',
			),
			$setting
		);

		// test updating another subject and making sure it works with a "similar" id.
		$request  = new WP_REST_Request( 'GET', sprintf( '/wc/v3/settings/%s/%s', 'email_customer_new_account', 'subject' ) );
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();

		$this->assertEmpty( $setting['value'] );

		// test update.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'email_customer_new_account', 'subject' ) );
		$request->set_body_params(
			array(
				'value' => 'This is my new subject',
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();

		$this->assertEquals( 'This is my new subject', $setting['value'] );

		// make sure the other is what we left it.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/email_new_order/subject' ) );
		$setting  = $response->get_data();

		$this->assertEquals( 'This is my subject', $setting['value'] );
	}

	/**
	 * Test validation of checkbox settings.
	 *
	 * @since 3.5.0
	 */
	public function test_validation_checkbox() {
		wp_set_current_user( $this->user );

		// test bogus value.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'email_cancelled_order', 'enabled' ) );
		$request->set_body_params(
			array(
				'value' => 'not_yes_or_no',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// test yes.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'email_cancelled_order', 'enabled' ) );
		$request->set_body_params(
			array(
				'value' => 'yes',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// test no.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'email_cancelled_order', 'enabled' ) );
		$request->set_body_params(
			array(
				'value' => 'no',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test validation of radio settings.
	 *
	 * @since 3.5.0
	 */
	public function test_validation_radio() {
		wp_set_current_user( $this->user );

		// not a valid option.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'shipping', 'woocommerce_ship_to_destination' ) );
		$request->set_body_params(
			array(
				'value' => 'billing2',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// valid.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'shipping', 'woocommerce_ship_to_destination' ) );
		$request->set_body_params(
			array(
				'value' => 'billing',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test validation of multiselect.
	 *
	 * @since 3.5.0
	 */
	public function test_validation_multiselect() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', sprintf( '/wc/v3/settings/%s/%s', 'general', 'woocommerce_specific_allowed_countries' ) ) );
		$setting  = $response->get_data();
		$this->assertEmpty( $setting['value'] );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'general', 'woocommerce_specific_allowed_countries' ) );
		$request->set_body_params(
			array(
				'value' => array( 'AX', 'DZ', 'MMM' ),
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( array( 'AX', 'DZ' ), $setting['value'] );
	}

	/**
	 * Test validation of select.
	 *
	 * @since 3.5.0
	 */
	public function test_validation_select() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', sprintf( '/wc/v3/settings/%s/%s', 'products', 'woocommerce_weight_unit' ) ) );
		$setting  = $response->get_data();
		$this->assertEquals( 'lbs', $setting['value'] );

		// invalid.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'products', 'woocommerce_weight_unit' ) );
		$request->set_body_params(
			array(
				'value' => 'pounds', // invalid, should be lbs.
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// valid.
		$request = new WP_REST_Request( 'PUT', sprintf( '/wc/v3/settings/%s/%s', 'products', 'woocommerce_weight_unit' ) );
		$request->set_body_params(
			array(
				'value' => 'kg', // valid.
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( 'kg', $setting['value'] );
	}

	/**
	 * Test to make sure the 'base location' setting is present in the response.
	 * That it is returned as 'select' and not 'single_select_country',
	 * and that both state and country options are returned.
	 *
	 * @since 3.5.0
	 */
	public function test_woocommerce_default_country() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/general/woocommerce_default_country' ) );
		$setting  = $response->get_data();

		$this->assertEquals( 'select', $setting['type'] );
		$this->assertArrayHasKey( 'GB', $setting['options'] );
		$this->assertArrayHasKey( 'US:OR', $setting['options'] );
	}

	/**
	 * Test to make sure the store address setting can be fetched and updated.
	 *
	 * @since 3.5.0
	 */
	public function test_woocommerce_store_address() {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_store_address', rand( 1000, 9999 ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/general/woocommerce_store_address' ) );
		$setting  = $response->get_data();
		$this->assertEquals( 'text', $setting['type'] );

		// Repalce the old value with something uniquely new.
		$old_value = $setting['value'];
		$new_value = $old_value . ' ' . rand( 1000, 9999 );
		$request   = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_store_address' );
		$request->set_body_params(
			array(
				'value' => $new_value,
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( $new_value, $setting['value'] );

		// Put the original value back.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_store_address' );
		$request->set_body_params(
			array(
				'value' => $old_value,
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( $old_value, $setting['value'] );
	}

	/**
	 * Test to make sure the store address 2 (line 2) setting can be fetched and updated.
	 *
	 * @since 3.5.0
	 */
	public function test_woocommerce_store_address_2() {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_store_address_2', rand( 1000, 9999 ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/general/woocommerce_store_address_2' ) );
		$setting  = $response->get_data();
		$this->assertEquals( 'text', $setting['type'] );

		// Repalce the old value with something uniquely new.
		$old_value = $setting['value'];
		$new_value = $old_value . ' ' . rand( 1000, 9999 );
		$request   = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_store_address_2' );
		$request->set_body_params(
			array(
				'value' => $new_value,
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( $new_value, $setting['value'] );

		// Put the original value back.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_store_address_2' );
		$request->set_body_params(
			array(
				'value' => $old_value,
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( $old_value, $setting['value'] );
	}

	/**
	 * Test to make sure the store city setting can be fetched and updated.
	 *
	 * @since 3.5.0
	 */
	public function test_woocommerce_store_city() {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_store_city', rand( 1000, 9999 ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/general/woocommerce_store_city' ) );
		$setting  = $response->get_data();
		$this->assertEquals( 'text', $setting['type'] );

		// Repalce the old value with something uniquely new.
		$old_value = $setting['value'];
		$new_value = $old_value . ' ' . rand( 1000, 9999 );
		$request   = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_store_city' );
		$request->set_body_params(
			array(
				'value' => $new_value,
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( $new_value, $setting['value'] );

		// Put the original value back.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_store_city' );
		$request->set_body_params(
			array(
				'value' => $old_value,
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( $old_value, $setting['value'] );
	}

	/**
	 * Test to make sure the store postcode setting can be fetched and updated.
	 *
	 * @since 3.5.0
	 */
	public function test_woocommerce_store_postcode() {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_store_postcode', rand( 1000, 9999 ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/settings/general/woocommerce_store_postcode' ) );
		$setting  = $response->get_data();
		$this->assertEquals( 'text', $setting['type'] );

		// Repalce the old value with something uniquely new.
		$old_value = $setting['value'];
		$new_value = $old_value . ' ' . rand( 1000, 9999 );
		$request   = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_store_postcode' );
		$request->set_body_params(
			array(
				'value' => $new_value,
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( $new_value, $setting['value'] );

		// Put the original value back.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/settings/general/woocommerce_store_postcode' );
		$request->set_body_params(
			array(
				'value' => $old_value,
			)
		);
		$response = $this->server->dispatch( $request );
		$setting  = $response->get_data();
		$this->assertEquals( $old_value, $setting['value'] );
	}
}
