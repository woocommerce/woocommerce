<?php
/**
 * Integration tests for the plans REST controller.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api\Rest;

use Automattic\WooCommerce\SubscriptionsEngine\Api\Plans;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\PlansController;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;
use EngineIntegrationTestCase;
use RuntimeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\PlansController
 */
class PlansControllerTest extends EngineIntegrationTestCase {

	private const BASE = '/wc/v3/subscriptions-engine/plans';

	private const EXTENSION_SLUG = 'woocommerce-subscriptions-lite';

	/**
	 * Admin user id.
	 *
	 * @var int
	 */
	private $admin_id;

	public function setUp(): void {
		parent::setUp();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->assertIsInt( $admin_id );

		$this->admin_id = $admin_id;
		rest_get_server();
	}

	public function tearDown(): void {
		remove_all_actions( 'woocommerce_subscriptions_engine_validate_plan' );
		StatusRegistry::reset();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	public function test_collection_requires_manage_woocommerce(): void {
		wp_set_current_user( 0 );

		$response = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_patch_replaces_a_policy_wholesale_and_keeps_omitted_policies(): void {
		wp_set_current_user( $this->admin_id );

		$billing = array(
			'period'         => 'month',
			'interval'       => 1,
			'max_cycles'     => 12,
			'trial_duration' => array(
				'length' => 7,
				'unit'   => 'day',
			),
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Monthly',
				'billing_policy' => $billing,
				'pricing_policy' => array(
					'policies'      => array(
						array(
							'type'  => 'percentage',
							'value' => 10,
						),
					),
					'one_time_fees' => array(
						array(
							'kind'   => 'setup',
							'amount' => 5,
						),
					),
				),
			)
		);

		$this->assertSame( 201, $created->get_status() );
		$created_data = $this->response_data( $created );
		$this->assertSame( PlanStatus::ACTIVE, $created_data['status'] );
		$this->assertSame( self::EXTENSION_SLUG, $created_data['extension_slug'] );

		$id = $this->int_value( $created_data, 'id' );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Monthly plus',
				'pricing_policy' => array(
					'policies' => array( array( 'type' => 'bogo' ) ),
				),
			)
		);

		$this->assertSame( 200, $patched->get_status() );
		$patched_data = $this->response_data( $patched );
		$this->assertSame( 'Monthly plus', $patched_data['name'] );
		$this->assertSame( array( 'policies' => array( array( 'type' => 'bogo' ) ) ), $patched_data['pricing_policy'], 'A present policy replaces the stored payload; nothing is merged.' );
		$this->assertSame( $billing, $patched_data['billing_policy'], 'An omitted policy keeps its stored payload.' );

		$fetched = $this->response_data( $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) ) );
		$this->assertSame( array( 'policies' => array( array( 'type' => 'bogo' ) ) ), $fetched['pricing_policy'] );
		$this->assertSame( $billing, $fetched['billing_policy'] );

		$list = $this->request(
			'GET',
			self::BASE,
			array(),
			array(
				'search'         => 'plus',
				'extension_slug' => self::EXTENSION_SLUG,
			)
		);
		$this->assertSame( 200, $list->get_status() );
		$this->assertSame( '1', $list->get_headers()['X-WP-Total'] );
		$this->assertCount( 1, $this->response_data( $list ) );
	}

	public function test_billing_and_delivery_round_trip_opaquely(): void {
		wp_set_current_user( $this->admin_id );

		$billing  = array(
			'cadence' => 'every full moon',
			'nested'  => array( 'anything' => array( 1, 2 ) ),
		);
		$delivery = array(
			'anchor' => array( 'weekday' => 'tue' ),
			'note'   => 'opaque',
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug'  => self::EXTENSION_SLUG,
				'name'            => 'Opaque',
				'billing_policy'  => $billing,
				'delivery_policy' => $delivery,
			)
		);

		$this->assertSame( 201, $created->get_status(), 'The engine does not validate cadence.' );
		$id = $this->int_value( $this->response_data( $created ), 'id' );

		$fetched = $this->response_data( $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) ) );
		$this->assertSame( $billing, $fetched['billing_policy'] );
		$this->assertSame( $delivery, $fetched['delivery_policy'] );
	}

	public function test_create_without_billing_policy_stores_null(): void {
		wp_set_current_user( $this->admin_id );

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'No billing',
			)
		);

		$this->assertSame( 201, $created->get_status() );
		$data = $this->response_data( $created );
		$this->assertNull( $data['billing_policy'] );
		$this->assertNull( $data['pricing_policy'] );
		$this->assertNull( $data['delivery_policy'] );
	}

	public function test_response_has_exactly_the_record_fields(): void {
		wp_set_current_user( $this->admin_id );

		$id   = $this->create_plan( 'Fields' );
		$data = $this->response_data( $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) ) );

		$this->assertEqualsCanonicalizing(
			array( 'id', 'extension_slug', 'status', 'name', 'billing_policy', 'pricing_policy', 'delivery_policy', 'date_created_gmt', 'date_updated_gmt' ),
			array_keys( $data )
		);
		$this->assertIsString( $data['date_created_gmt'] );
		$this->assertIsString( $data['date_updated_gmt'] );
	}

	public function test_an_empty_policy_object_is_returned_as_a_json_object(): void {
		wp_set_current_user( $this->admin_id );

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Empty pricing',
				'pricing_policy' => array(),
			)
		);

		$this->assertSame( 201, $created->get_status() );
		$this->assertSame( '{}', wp_json_encode( $this->response_data( $created )['pricing_policy'] ) );
	}

	public function test_create_round_trips_the_pricing_payload_opaquely(): void {
		wp_set_current_user( $this->admin_id );

		$pricing_policy = array(
			'policies'   => array(
				array(
					'type'            => 'tiered',
					'duration_cycles' => 1,
				),
				array( 'type' => 'bogo' ),
			),
			'custom_key' => array( 'nested' => 'kept' ),
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Opaque monthly',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => $pricing_policy,
			)
		);

		$this->assertSame( 201, $created->get_status() );
		$created_data = $this->response_data( $created );
		$this->assertSame( $pricing_policy, $created_data['pricing_policy'], 'Unknown types and keys come back unchanged; no value is added.' );

		// A fresh read round-trips the stored shape through the database.
		$id      = $this->int_value( $created_data, 'id' );
		$fetched = $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
		$this->assertSame( 200, $fetched->get_status() );
		$this->assertSame( $pricing_policy, $this->response_data( $fetched )['pricing_policy'] );
	}

	public function test_arbitrary_payload_values_are_stored_as_given(): void {
		wp_set_current_user( $this->admin_id );

		$pricing_policy = array(
			'policies' => array(
				array(
					'type'  => 'bogo',
					'value' => 5,
				),
			),
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Any payload',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => $pricing_policy,
			)
		);

		$this->assertSame( 201, $created->get_status(), 'Payload semantics belong to the owning extension.' );
		$this->assertSame( $pricing_policy, $this->response_data( $created )['pricing_policy'] );
	}

	/**
	 * @dataProvider provide_non_object_pricing_policies
	 *
	 * @param mixed  $invalid     Non-object pricing payload.
	 * @param string $create_code Create error code: core schema validation rejects a scalar first.
	 * @param string $patch_code  PATCH error code: the route has no arg schema, so the facade rejects.
	 */
	public function test_non_object_pricing_policy_is_rejected( $invalid, string $create_code, string $patch_code ): void {
		wp_set_current_user( $this->admin_id );

		$id = $this->create_plan( 'Patch target' );

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Bad payload',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => $invalid,
			)
		);
		$this->assertSame( 400, $created->get_status() );
		$this->assertSame( $create_code, $this->response_data( $created )['code'] );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'pricing_policy' => $invalid,
			)
		);
		$this->assertSame( 400, $patched->get_status() );
		$patched_data = $this->response_data( $patched );
		$this->assertSame( $patch_code, $patched_data['code'] );
		$this->assertIsString( $patched_data['message'] );
		if ( 'woocommerce_subscriptions_engine_invalid_plan' === $patch_code ) {
			$this->assertStringContainsString( 'pricing_policy', $patched_data['message'], 'REST errors carry the facade message, which names the invalid field.' );
		}

		// The rejected writes left the plan untouched and created nothing.
		$fetched = $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
		$this->assertSame( 200, $fetched->get_status() );
		$this->assertNull( $this->response_data( $fetched )['pricing_policy'] );

		$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
		$this->assertSame( '1', $list->get_headers()['X-WP-Total'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string, 2: string}>
	 */
	public function provide_non_object_pricing_policies(): array {
		return array(
			'list'   => array( array( array( 'type' => 'bogo' ) ), 'woocommerce_subscriptions_engine_invalid_plan', 'woocommerce_subscriptions_engine_invalid_plan' ),
			'string' => array( 'bogo', 'rest_invalid_param', 'woocommerce_subscriptions_engine_invalid_plan' ),
		);
	}

	public function test_update_rejects_an_empty_name_with_a_message_naming_the_field(): void {
		wp_set_current_user( $this->admin_id );
		$id = $this->create_plan( 'Monthly' );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => '',
			)
		);

		$this->assertSame( 400, $patched->get_status() );
		$data = $this->response_data( $patched );
		$this->assertSame( 'woocommerce_subscriptions_engine_invalid_plan', $data['code'] );
		$this->assertIsString( $data['message'] );
		$this->assertStringContainsString( 'name', $data['message'] );
	}

	public function test_validate_action_receives_errors_a_plan_view_and_slug(): void {
		wp_set_current_user( $this->admin_id );

		$calls = array();
		add_action(
			'woocommerce_subscriptions_engine_validate_plan',
			static function ( $errors, $plan, $extension_slug ) use ( &$calls ): void {
				self::assertInstanceOf( WP_Error::class, $errors );
				self::assertFalse( $errors->has_errors() );
				self::assertInstanceOf( PlanView::class, $plan );
				$calls[] = array( $plan->get_id(), $plan->get_name(), $plan->get_pricing_policy(), $extension_slug );
			},
			10,
			3
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Validated',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => array(
					'policies'   => array( array( 'type' => 'bogo' ) ),
					'custom_key' => 'kept',
				),
			)
		);
		$this->assertSame( 201, $created->get_status() );
		$id = $this->int_value( $this->response_data( $created ), 'id' );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Validated again',
				'pricing_policy' => array( 'one_time_fees' => array( array( 'amount' => 5 ) ) ),
			)
		);
		$this->assertSame( 200, $patched->get_status() );

		$renamed = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Renamed only',
			)
		);
		$this->assertSame( 200, $renamed->get_status() );

		$replaced_pricing = array( 'one_time_fees' => array( array( 'amount' => 5 ) ) );

		$this->assertSame(
			array(
				array(
					0,
					'Validated',
					array(
						'policies'   => array( array( 'type' => 'bogo' ) ),
						'custom_key' => 'kept',
					),
					self::EXTENSION_SLUG,
				),
				array( $id, 'Validated again', $replaced_pricing, self::EXTENSION_SLUG ),
				array( $id, 'Renamed only', $replaced_pricing, self::EXTENSION_SLUG ),
			),
			$calls,
			'Create passes a view with id 0; updates pass the would-be state with the request applied.'
		);
	}

	/**
	 * @dataProvider provide_rejecting_errors
	 *
	 * @param array<string, mixed> $data            Error data the owner adds.
	 * @param int                  $expected_status Expected response status.
	 */
	public function test_validate_action_error_rejects_create_and_update( array $data, int $expected_status ): void {
		wp_set_current_user( $this->admin_id );
		$id = $this->create_plan( 'Untouched' );

		add_action(
			'woocommerce_subscriptions_engine_validate_plan',
			static function ( WP_Error $errors ) use ( $data ): void {
				$errors->add( 'owner_rejected', 'No.', $data );
			}
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Rejected',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => array( 'policies' => array() ),
			)
		);
		$this->assertSame( $expected_status, $created->get_status() );
		$this->assertSame( 'owner_rejected', $this->response_data( $created )['code'] );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Changed',
				'pricing_policy' => array( 'policies' => array() ),
			)
		);
		$this->assertSame( $expected_status, $patched->get_status() );
		$this->assertSame( 'owner_rejected', $this->response_data( $patched )['code'] );

		remove_all_actions( 'woocommerce_subscriptions_engine_validate_plan' );

		$fetched = $this->response_data( $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) ) );
		$this->assertSame( 'Untouched', $fetched['name'] );
		$this->assertNull( $fetched['pricing_policy'] );

		$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
		$this->assertSame( '1', $list->get_headers()['X-WP-Total'] );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: int}>
	 */
	public function provide_rejecting_errors(): array {
		return array(
			'without status' => array( array(), 400 ),
			'with status'    => array( array( 'status' => 422 ), 422 ),
		);
	}

	public function test_validate_action_errors_from_several_callbacks_accumulate(): void {
		wp_set_current_user( $this->admin_id );

		add_action(
			'woocommerce_subscriptions_engine_validate_plan',
			static function ( WP_Error $errors ): void {
				$errors->add( 'first_rejection', 'First.' );
			}
		);
		add_action(
			'woocommerce_subscriptions_engine_validate_plan',
			static function ( WP_Error $errors ): void {
				$errors->add( 'second_rejection', 'Second.', array( 'status' => 422 ) );
			},
			20
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Rejected twice',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
			)
		);

		$this->assertSame( 400, $created->get_status() );
		$data = $this->response_data( $created );
		$this->assertSame( 'first_rejection', $data['code'] );
		$this->assertSame(
			array(
				array(
					'code'    => 'second_rejection',
					'message' => 'Second.',
					'data'    => array( 'status' => 422 ),
				),
			),
			$data['additional_errors']
		);
	}

	public function test_validate_action_receives_a_read_only_view_not_the_entity(): void {
		wp_set_current_user( $this->admin_id );

		$views = array();
		add_action(
			'woocommerce_subscriptions_engine_validate_plan',
			static function ( WP_Error $errors, $plan ) use ( &$views ): void {
				unset( $errors );
				$views[] = $plan;
			},
			10,
			2
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'As sent',
				'pricing_policy' => array( 'policies' => array( array( 'type' => 'raw' ) ) ),
			)
		);
		$this->assertSame( 201, $created->get_status() );
		$this->assertSame( 'As sent', $this->response_data( $created )['name'] );

		$this->assertCount( 1, $views );
		$this->assertNotInstanceOf( Plan::class, $views[0], 'Callbacks never receive the mutable Core entity.' );
		$this->assertInstanceOf( PlanView::class, $views[0] );
		$reflection = new \ReflectionClass( $views[0] );
		$this->assertTrue( $reflection->isFinal(), 'No subclass can add write access to the view.' );
		$this->assertSame( array(), $reflection->getProperties( \ReflectionProperty::IS_PUBLIC ), 'The view has no writable state.' );
		foreach ( $reflection->getMethods( \ReflectionMethod::IS_PUBLIC ) as $method ) {
			if ( $method->isStatic() ) {
				continue;
			}
			$this->assertSame( 0, $method->getNumberOfParameters(), sprintf( 'PlanView::%s() takes no arguments, so the view has no mutators.', $method->getName() ) );
		}
		$this->assertSame( 'As sent', $views[0]->get_name() );
	}

	public function test_throwing_validate_callback_fails_the_write_without_storing(): void {
		wp_set_current_user( $this->admin_id );
		$id = $this->create_plan( 'Untouched' );

		add_action(
			'woocommerce_subscriptions_engine_validate_plan',
			static function (): void {
				throw new RuntimeException( 'Internal detail.' );
			}
		);

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Thrown',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
			)
		);
		$this->assertSame( 500, $created->get_status() );
		$created_data = $this->response_data( $created );
		$this->assertSame( 'woocommerce_subscriptions_engine_plan_validation_failed', $created_data['code'] );
		$this->assertIsString( $created_data['message'] );
		$this->assertStringNotContainsString( 'Internal detail.', $created_data['message'] );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Changed',
			)
		);
		$this->assertSame( 500, $patched->get_status() );
		$this->assertSame( 'woocommerce_subscriptions_engine_plan_validation_failed', $this->response_data( $patched )['code'] );

		remove_all_actions( 'woocommerce_subscriptions_engine_validate_plan' );

		$fetched = $this->response_data( $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) ) );
		$this->assertSame( 'Untouched', $fetched['name'] );

		$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
		$this->assertSame( '1', $list->get_headers()['X-WP-Total'] );
	}

	public function test_reorder_route_is_not_registered(): void {
		wp_set_current_user( $this->admin_id );
		$id = $this->create_plan( 'First' );

		$this->assertArrayNotHasKey( self::BASE . '/reorder', rest_get_server()->get_routes() );

		$response = $this->request(
			'POST',
			self::BASE . '/reorder',
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'ids'            => array( $id ),
			)
		);
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_patch_with_null_pricing_policy_clears_the_payload(): void {
		wp_set_current_user( $this->admin_id );

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Priced',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => array( 'policies' => array( array( 'type' => 'raw' ) ) ),
			)
		);
		$this->assertSame( 201, $created->get_status() );
		$id = $this->int_value( $this->response_data( $created ), 'id' );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'pricing_policy' => null,
			)
		);
		$this->assertSame( 200, $patched->get_status() );
		$this->assertNull( $this->response_data( $patched )['pricing_policy'] );

		$fetched = $this->response_data( $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) ) );
		$this->assertNull( $fetched['pricing_policy'] );
	}

	public function test_list_with_multiple_extension_slugs_returns_all_plans(): void {
		wp_set_current_user( $this->admin_id );

		$first_id  = $this->create_plan( 'First', self::EXTENSION_SLUG );
		$second_id = $this->create_plan( 'Second', 'woocommerce-subscriptions-test' );

		$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => implode( ',', array( self::EXTENSION_SLUG, 'woocommerce-subscriptions-test' ) ) ) );
		$this->assertSame( 200, $list->get_status() );
		$this->assertSame( '2', $list->get_headers()['X-WP-Total'] );
		$response_data = $this->response_data( $list );
		$this->assertIsArray( $response_data );
		$first_data  = $this->array_value( $response_data, 0 );
		$second_data = $this->array_value( $response_data, 1 );
		$this->assertCount( 2, $response_data );
		$this->assertSame( $first_id, $this->int_value( $first_data, 'id' ) );
		$this->assertSame( self::EXTENSION_SLUG, $first_data['extension_slug'] );
		$this->assertSame( $second_id, $this->int_value( $second_data, 'id' ) );
		$this->assertSame( 'woocommerce-subscriptions-test', $second_data['extension_slug'] );
	}

	public function test_list_trims_and_deduplicates_extension_slugs(): void {
		wp_set_current_user( $this->admin_id );

		$first_id  = $this->create_plan( 'First', self::EXTENSION_SLUG );
		$second_id = $this->create_plan( 'Second', 'woocommerce-subscriptions-test' );

		$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => self::EXTENSION_SLUG . ', ' . self::EXTENSION_SLUG . ',woocommerce-subscriptions-test' ) );

		$this->assertSame( 200, $list->get_status() );
		$this->assertSame( '2', $list->get_headers()['X-WP-Total'] );
		$response_data = $this->response_data( $list );
		$this->assertCount( 2, $response_data );
		$this->assertSame(
			array( $first_id, $second_id ),
			array_map(
				function ( $row ): int {
					$this->assertIsArray( $row );
					return $this->int_value( $row, 'id' );
				},
				$response_data
			)
		);
	}

	public function test_list_rejects_invalid_extension_slug_lists(): void {
		wp_set_current_user( $this->admin_id );

		foreach ( array( 'any,' . self::EXTENSION_SLUG, self::EXTENSION_SLUG . ',', ',' . self::EXTENSION_SLUG, 'lite,,test' ) as $extension_slug ) {
			$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => $extension_slug ) );

			$this->assertSame( 400, $list->get_status(), 'Failed for extension_slug=' . $extension_slug );
		}
	}

	public function test_list_with_any_extension_slug_returns_all_plans(): void {
		wp_set_current_user( $this->admin_id );

		$first_id  = $this->create_plan( 'First', self::EXTENSION_SLUG );
		$second_id = $this->create_plan( 'Second', 'woocommerce-subscriptions-test' );

		$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => 'any' ) );
		$this->assertSame( 200, $list->get_status() );
		$this->assertSame( '2', $list->get_headers()['X-WP-Total'] );
		$response_data = $this->response_data( $list );
		$this->assertIsArray( $response_data );
		$first_data  = $this->array_value( $response_data, 0 );
		$second_data = $this->array_value( $response_data, 1 );
		$this->assertCount( 2, $response_data );
		$this->assertSame( $first_id, $this->int_value( $first_data, 'id' ) );
		$this->assertSame( self::EXTENSION_SLUG, $first_data['extension_slug'] );
		$this->assertSame( $second_id, $this->int_value( $second_data, 'id' ) );
		$this->assertSame( 'woocommerce-subscriptions-test', $second_data['extension_slug'] );
	}

	public function test_list_defaults_to_id_order_and_rejects_retired_orderby_values(): void {
		wp_set_current_user( $this->admin_id );

		$charlie = $this->create_plan( 'Charlie' );
		$alpha   = $this->create_plan( 'Alpha' );

		$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
		$this->assertSame( array( $charlie, $alpha ), $this->response_ids( $list ) );

		$by_name = $this->request(
			'GET',
			self::BASE,
			array(),
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'orderby'        => 'name',
			)
		);
		$this->assertSame( array( $alpha, $charlie ), $this->response_ids( $by_name ) );

		foreach ( array( 'status', 'sort_order' ) as $orderby ) {
			$rejected = $this->request(
				'GET',
				self::BASE,
				array(),
				array(
					'extension_slug' => self::EXTENSION_SLUG,
					'orderby'        => $orderby,
				)
			);
			$this->assertSame( 400, $rejected->get_status(), "orderby={$orderby} must be rejected." );
		}
	}

	public function test_status_filter_accepts_a_registered_extension_status_and_rejects_others(): void {
		wp_set_current_user( $this->admin_id );
		StatusRegistry::register( StatusRegistry::KIND_PLAN, 'seasonal' );

		$this->create_plan( 'Active' );
		$seasonal = $this->create_plan( 'Seasonal' );
		$patched  = $this->request(
			'PATCH',
			self::BASE . '/' . $seasonal,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => 'seasonal',
			)
		);
		$this->assertSame( 'seasonal', $this->response_data( $patched )['status'] );

		$list = $this->request(
			'GET',
			self::BASE,
			array(),
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => 'seasonal',
			)
		);
		$this->assertSame( 200, $list->get_status() );
		$this->assertSame( array( $seasonal ), $this->response_ids( $list ) );

		$unknown = $this->request(
			'GET',
			self::BASE,
			array(),
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => 'never-registered',
			)
		);
		$this->assertSame( 400, $unknown->get_status() );

		$bad_patch = $this->request(
			'PATCH',
			self::BASE . '/' . $seasonal,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => 'never-registered',
			)
		);
		$this->assertSame( 400, $bad_patch->get_status() );
	}

	public function test_single_plan_routes_reject_wildcard_and_list_extension_slugs(): void {
		wp_set_current_user( $this->admin_id );

		$id = $this->create_plan( 'Scoped' );

		foreach ( array( 'any', self::EXTENSION_SLUG . ',woocommerce-subscriptions-test' ) as $extension_slug ) {
			$this->assertSame( 400, $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => $extension_slug ) )->get_status() );
			$this->assertSame(
				400,
				$this->request(
					'PATCH',
					self::BASE . '/' . $id,
					array(
						'extension_slug' => $extension_slug,
						'name'           => 'Invalid scope',
					)
				)->get_status()
			);
		}
	}

	public function test_single_plan_routes_404_a_plan_of_another_extension_slug_or_an_unknown_id(): void {
		wp_set_current_user( $this->admin_id );

		$foreign_id = $this->create_plan( 'Foreign', 'woocommerce-subscriptions-test' );
		$unknown_id = $foreign_id + 1000;

		foreach ( array( $foreign_id, $unknown_id ) as $id ) {
			$get = $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
			$this->assertSame( 404, $get->get_status() );
			$this->assertSame( 'woocommerce_subscriptions_engine_plan_not_found', $this->response_data( $get )['code'] );

			$patch = $this->request(
				'PATCH',
				self::BASE . '/' . $id,
				array(
					'extension_slug' => self::EXTENSION_SLUG,
					'name'           => 'Hijacked',
				)
			);
			$this->assertSame( 404, $patch->get_status() );
			$this->assertSame( 'woocommerce_subscriptions_engine_plan_not_found', $this->response_data( $patch )['code'] );
		}

		$foreign = Plans::get( $foreign_id );
		$this->assertNotNull( $foreign );
		$this->assertSame( 'Foreign', $foreign->get_name(), 'A PATCH under another slug writes nothing.' );

		$own = $this->request( 'GET', self::BASE . '/' . $foreign_id, array(), array( 'extension_slug' => 'woocommerce-subscriptions-test' ) );
		$this->assertSame( 200, $own->get_status(), 'The plan resolves under its own slug.' );
	}

	public function test_create_rejects_wildcard_and_list_extension_slugs(): void {
		wp_set_current_user( $this->admin_id );

		foreach ( array( 'any', self::EXTENSION_SLUG . ',woocommerce-subscriptions-test' ) as $extension_slug ) {
			$response = $this->request(
				'POST',
				self::BASE,
				array(
					'name'           => 'Invalid scope',
					'billing_policy' => array(
						'period'   => 'month',
						'interval' => 1,
					),
					'extension_slug' => $extension_slug,
				)
			);

			$this->assertSame( 400, $response->get_status() );
		}
	}

	public function test_create_validates_the_status_and_keeps_the_callback_out_of_the_schema(): void {
		wp_set_current_user( $this->admin_id );

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Unregistered',
				'status'         => 'never-registered',
			)
		);
		$this->assertSame( 400, $created->get_status() );
		$this->assertSame( 'rest_invalid_param', $this->response_data( $created )['code'] );

		$schema = ( new PlansController() )->get_public_item_schema();
		$this->assertIsArray( $schema['properties']['status'] );
		$this->assertArrayNotHasKey( 'validate_callback', $schema['properties']['status'] );
		$this->assertArrayNotHasKey( 'arg_options', $schema['properties']['status'] );
	}

	public function test_create_surfaces_a_failed_insert_as_a_logged_create_error(): void {
		global $wpdb;

		wp_set_current_user( $this->admin_id );

		$break_plan_inserts = static function ( $query ) {
			if ( is_string( $query ) && 0 === stripos( ltrim( $query ), 'INSERT' ) && false !== strpos( $query, 'wc_selling_plans' ) ) {
				return 'INSERT INTO nonexistent_table_for_this_test (id) VALUES (1)';
			}

			return $query;
		};
		$errors             = array();
		$capture            = static function ( $message, $level ) use ( &$errors ) {
			if ( 'error' === $level && is_string( $message ) && false !== strpos( $message, 'the plan write failed' ) ) {
				$errors[] = $message;
			}
			return $message;
		};
		add_filter( 'query', $break_plan_inserts );
		add_filter( 'woocommerce_logger_log_message', $capture, 10, 2 );
		$suppressed = $wpdb->suppress_errors( true );

		try {
			$response = $this->request(
				'POST',
				self::BASE,
				array(
					'extension_slug' => self::EXTENSION_SLUG,
					'name'           => 'Doomed insert',
				)
			);
		} finally {
			$wpdb->suppress_errors( $suppressed );
			remove_filter( 'query', $break_plan_inserts );
			remove_filter( 'woocommerce_logger_log_message', $capture, 10 );
		}

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_subscriptions_engine_plan_create_failed', $this->response_data( $response )['code'] );
		$this->assertStringNotContainsString( 'nonexistent_table_for_this_test', (string) wp_json_encode( $response->get_data() ), 'The database error never reaches the client.' );
		$this->assertNotEmpty( $errors, 'A failed write is logged.' );
		$this->assertStringContainsString( 'nonexistent_table_for_this_test', $errors[0], 'The log carries the database error.' );
	}

	public function test_update_surfaces_a_failed_write_as_an_error(): void {
		global $wpdb;

		wp_set_current_user( $this->admin_id );
		$id = $this->create_plan( 'Doomed to fail' );

		// Break UPDATEs against the plans table for this request so the write
		// errors at the database and the repository reports failure.
		$break_plan_updates = static function ( $query ) {
			if ( is_string( $query ) && 0 === stripos( ltrim( $query ), 'UPDATE' ) && false !== strpos( $query, 'wc_selling_plans' ) ) {
				return 'UPDATE nonexistent_table_for_this_test SET id = id';
			}

			return $query;
		};
		add_filter( 'query', $break_plan_updates );
		$suppressed = $wpdb->suppress_errors( true );

		try {
			$response = $this->request(
				'PATCH',
				self::BASE . '/' . $id,
				array(
					'extension_slug' => self::EXTENSION_SLUG,
					'name'           => 'New name',
				)
			);
		} finally {
			$wpdb->suppress_errors( $suppressed );
			remove_filter( 'query', $break_plan_updates );
		}

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_subscriptions_engine_plan_update_failed', $this->response_data( $response )['code'] );
		$this->assertStringNotContainsString( 'nonexistent_table_for_this_test', (string) wp_json_encode( $response->get_data() ), 'The database error never reaches the client.' );
	}

	public function test_archive_and_restore(): void {
		wp_set_current_user( $this->admin_id );

		$first = $this->create_plan( 'First' );

		$archived = $this->request(
			'PATCH',
			self::BASE . '/' . $first,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => PlanStatus::ARCHIVED,
			)
		);
		$this->assertSame( PlanStatus::ARCHIVED, $this->response_data( $archived )['status'] );

		$restored = $this->request(
			'PATCH',
			self::BASE . '/' . $first,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => PlanStatus::ACTIVE,
			)
		);
		$this->assertSame( PlanStatus::ACTIVE, $this->response_data( $restored )['status'] );
	}

	public function test_delete_route_is_not_exposed(): void {
		wp_set_current_user( $this->admin_id );
		$id = $this->create_plan( 'Delete guard' );

		$response = rest_do_request( new WP_REST_Request( 'DELETE', self::BASE . '/' . $id ) );

		$this->assertContains( $response->get_status(), array( 404, 405 ), 'DELETE must not remove plans.' );
	}

	/**
	 * Create a basic plan and return its id.
	 *
	 * @param string $name           Plan name.
	 * @param string $extension_slug Extension slug.
	 * @return int Plan id.
	 */
	private function create_plan( string $name, string $extension_slug = self::EXTENSION_SLUG ): int {
		$response = $this->request(
			'POST',
			self::BASE,
			array(
				'name'           => $name,
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'extension_slug' => $extension_slug,
			)
		);

		$this->assertSame( 201, $response->get_status() );

		return $this->int_value( $this->response_data( $response ), 'id' );
	}

	/**
	 * Make a REST request with JSON-like params.
	 *
	 * @param string               $method Method.
	 * @param string               $path   Route path.
	 * @param array<string, mixed> $body   Body params.
	 * @param array<string, mixed> $query  Query params.
	 */
	private function request( string $method, string $path, array $body = array(), array $query = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $path );
		if ( ! empty( $body ) ) {
			$request->set_body_params( $body );
		}
		if ( ! empty( $query ) ) {
			$request->set_query_params( $query );
		}

		return rest_do_request( $request );
	}

	/**
	 * Get response data as an array.
	 *
	 * @param WP_REST_Response $response Response.
	 * @return array<array-key, mixed>
	 */
	private function response_data( WP_REST_Response $response ): array {
		$data = $response->get_data();
		$this->assertIsArray( $data );

		return $data;
	}

	/**
	 * Get response item ids.
	 *
	 * @param WP_REST_Response $response Response.
	 * @return array<int, int>
	 */
	private function response_ids( WP_REST_Response $response ): array {
		$ids = array();
		foreach ( $this->response_data( $response ) as $row ) {
			$this->assertIsArray( $row );
			$ids[] = $this->int_value( $row, 'id' );
		}

		return $ids;
	}

	/**
	 * Get a nested array value.
	 *
	 * @param array<array-key, mixed> $data Data.
	 * @param array-key               $key  Key.
	 * @return array<array-key, mixed>
	 */
	private function array_value( array $data, $key ): array {
		$this->assertArrayHasKey( $key, $data );
		$value = $data[ $key ];
		$this->assertIsArray( $value );

		return $value;
	}

	/**
	 * Get an integer value.
	 *
	 * @param array<array-key, mixed> $data Data.
	 * @param array-key               $key  Key.
	 */
	private function int_value( array $data, $key ): int {
		$this->assertArrayHasKey( $key, $data );
		$value = $data[ $key ];
		if ( ! is_numeric( $value ) ) {
			$this->fail( 'Expected a numeric value.' );
		}

		return (int) $value;
	}
}
