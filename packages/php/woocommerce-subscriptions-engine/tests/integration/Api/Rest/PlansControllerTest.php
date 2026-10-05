<?php
/**
 * Integration tests for the plans REST controller.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api\Rest;

use Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\PlansController;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use EngineIntegrationTestCase;
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
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	public function test_collection_requires_manage_woocommerce(): void {
		wp_set_current_user( 0 );

		$response = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_create_list_and_partial_patch_preserves_advanced_pricing_fields(): void {
		wp_set_current_user( $this->admin_id );

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Monthly',
				'description'    => 'Ships every month',
				'billing_policy' => array(
					'period'         => 'month',
					'interval'       => 1,
					'max_cycles'     => 12,
					'trial_duration' => array(
						'length' => 7,
						'unit'   => 'day',
					),
				),
				'pricing_policy' => array(
					'policies'      => array(
						array(
							'type'  => 'percentage',
							'value' => 10,
						),
					),
					'one_time_fees' => array(
						array(
							'kind'      => 'setup',
							'amount'    => 5,
							'taxable'   => true,
							'tax_class' => '',
						),
					),
				),
			)
		);

		$this->assertSame( 201, $created->get_status() );
		$created_data = $this->response_data( $created );
		$this->assertSame( 'global', $created_data['scope'] );
		$this->assertSame( Plan::STATUS_ACTIVE, $created_data['status'] );
		$this->assertSame( self::EXTENSION_SLUG, $created_data['extension_slug'] );
		$this->assertArrayNotHasKey( 'group', $created_data );
		$this->assertArrayNotHasKey( 'merchant_code', $created_data );

		$id = $this->int_value( $created_data, 'id' );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Monthly plus',
				'billing_policy' => array(
					'period'   => 'week',
					'interval' => 2,
				),
				'pricing_policy' => array(
					'policies' => array(
						array(
							'type'            => 'fixed_amount',
							'value'           => 2,
							'duration_cycles' => 3,
						),
					),
				),
			)
		);

		$this->assertSame( 200, $patched->get_status() );
		$patched_data   = $this->response_data( $patched );
		$billing_policy = $this->array_value( $patched_data, 'billing_policy' );
		$pricing_policy = $this->array_value( $patched_data, 'pricing_policy' );
		$policies       = $this->array_value( $pricing_policy, 'policies' );
		$first_policy   = $this->array_value( $policies, 0 );
		$one_time_fees  = $this->array_value( $pricing_policy, 'one_time_fees' );
		$first_fee      = $this->array_value( $one_time_fees, 0 );
		$this->assertSame( 'Monthly plus', $patched_data['name'] );
		$this->assertSame( 'week', $billing_policy['period'] );
		$this->assertSame( 2, $billing_policy['interval'] );
		$this->assertSame( 12, $billing_policy['max_cycles'] );
		$this->assertSame(
			array(
				'length' => 7,
				'unit'   => 'day',
			),
			$billing_policy['trial_duration']
		);
		// Stored as sent: the engine coerces nothing inside the payload.
		$this->assertSame(
			array(
				'type'            => 'fixed_amount',
				'value'           => 2,
				'duration_cycles' => 3,
			),
			$first_policy
		);
		$this->assertSame(
			array(
				'kind'      => 'setup',
				'amount'    => 5,
				'taxable'   => true,
				'tax_class' => '',
			),
			$first_fee
		);

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

	public function test_update_replaces_provided_top_level_keys_and_keeps_omitted_ones(): void {
		wp_set_current_user( $this->admin_id );

		$created = $this->request(
			'POST',
			self::BASE,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'Discounted monthly',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
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
					'custom_key'    => 'kept',
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
				'pricing_policy' => array(
					'policies' => array(
						array( 'type' => 'bogo' ),
					),
				),
			)
		);
		$this->assertSame( 200, $patched->get_status() );

		$fetched = $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
		$this->assertSame( 200, $fetched->get_status() );
		$this->assertSame(
			array(
				'policies'      => array(
					array( 'type' => 'bogo' ),
				),
				'one_time_fees' => array(
					array(
						'kind'   => 'setup',
						'amount' => 5,
					),
				),
				'custom_key'    => 'kept',
			),
			$this->response_data( $fetched )['pricing_policy']
		);
	}

	public function test_bogo_with_a_non_zero_value_is_stored_as_given(): void {
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
				'name'           => 'Any bogo',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => $pricing_policy,
			)
		);

		$this->assertSame( 201, $created->get_status(), 'Pricing semantics belong to the owning extension.' );
		$this->assertSame( $pricing_policy, $this->response_data( $created )['pricing_policy'] );
	}

	/**
	 * @dataProvider provide_non_object_pricing_policies
	 *
	 * @param mixed  $invalid     Non-object pricing payload.
	 * @param string $create_code Create error code: core schema validation rejects a scalar first.
	 * @param string $patch_code  PATCH error code: the route has no arg schema, so the controller rejects.
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
		$this->assertSame( $patch_code, $this->response_data( $patched )['code'] );

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

	public function test_validate_action_receives_errors_a_plan_copy_and_slug(): void {
		wp_set_current_user( $this->admin_id );

		$calls = array();
		add_action(
			'woocommerce_subscriptions_engine_validate_plan',
			static function ( $errors, $plan, $extension_slug ) use ( &$calls ): void {
				self::assertInstanceOf( WP_Error::class, $errors );
				self::assertFalse( $errors->has_errors() );
				self::assertInstanceOf( Plan::class, $plan );
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

		$merged_pricing = array(
			'policies'      => array( array( 'type' => 'bogo' ) ),
			'custom_key'    => 'kept',
			'one_time_fees' => array( array( 'amount' => 5 ) ),
		);

		$this->assertSame(
			array(
				array(
					null,
					'Validated',
					array(
						'policies'   => array( array( 'type' => 'bogo' ) ),
						'custom_key' => 'kept',
					),
					self::EXTENSION_SLUG,
				),
				array( $id, 'Validated again', $merged_pricing, self::EXTENSION_SLUG ),
				array( $id, 'Renamed only', $merged_pricing, self::EXTENSION_SLUG ),
			),
			$calls,
			'Create passes a plan with a null id; updates pass the stored plan with the request merged in.'
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

	public function test_validate_action_cannot_change_the_stored_plan(): void {
		wp_set_current_user( $this->admin_id );

		add_action(
			'woocommerce_subscriptions_engine_validate_plan',
			static function ( WP_Error $errors, Plan $plan ): void {
				$plan->set_name( 'Changed by callback' );
				$plan->set_pricing_policy( array( 'policies' => array( array( 'type' => 'changed' ) ) ) );
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
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => array( 'policies' => array( array( 'type' => 'raw' ) ) ),
			)
		);
		$this->assertSame( 201, $created->get_status() );
		$this->assertSame( 'As sent', $this->response_data( $created )['name'] );
		$id = $this->int_value( $this->response_data( $created ), 'id' );

		$patched = $this->request(
			'PATCH',
			self::BASE . '/' . $id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'name'           => 'As sent again',
			)
		);
		$this->assertSame( 200, $patched->get_status() );

		remove_all_actions( 'woocommerce_subscriptions_engine_validate_plan' );

		$fetched = $this->response_data( $this->request( 'GET', self::BASE . '/' . $id, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) ) );
		$this->assertSame( 'As sent again', $fetched['name'] );
		$this->assertSame( array( 'policies' => array( array( 'type' => 'raw' ) ) ), $fetched['pricing_policy'] );
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

	public function test_list_can_order_by_status(): void {
		wp_set_current_user( $this->admin_id );

		$active_before = $this->create_plan( 'Active before' );
		$archived      = $this->create_plan( 'Archived' );
		$active_after  = $this->create_plan( 'Active after' );

		$archived_response = $this->request(
			'PATCH',
			self::BASE . '/' . $archived,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => Plan::STATUS_ARCHIVED,
			)
		);
		$this->assertSame( 200, $archived_response->get_status() );

		$list = $this->request(
			'GET',
			self::BASE,
			array(),
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'orderby'        => 'status',
				'order'          => 'desc',
			)
		);

		$this->assertSame( 200, $list->get_status() );
		$this->assertSame( array( $archived, $active_before, $active_after ), $this->response_ids( $list ) );
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
			$this->assertSame(
				400,
				$this->request(
					'POST',
					self::BASE . '/reorder',
					array(
						'extension_slug' => $extension_slug,
						'ids'            => array( $id ),
					)
				)->get_status()
			);
		}
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
	}

	public function test_archive_restore_and_reorder(): void {
		wp_set_current_user( $this->admin_id );

		$first  = $this->create_plan( 'First' );
		$second = $this->create_plan( 'Second' );

		$archived = $this->request(
			'PATCH',
			self::BASE . '/' . $first,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => Plan::STATUS_ARCHIVED,
			)
		);
		$this->assertSame( Plan::STATUS_ARCHIVED, $this->response_data( $archived )['status'] );

		$restored = $this->request(
			'PATCH',
			self::BASE . '/' . $first,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => Plan::STATUS_ACTIVE,
			)
		);
		$this->assertSame( Plan::STATUS_ACTIVE, $this->response_data( $restored )['status'] );

		$reordered = $this->request(
			'POST',
			self::BASE . '/reorder',
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'ids'            => array( $second, $first ),
			)
		);
		$this->assertSame( 200, $reordered->get_status() );

		$list = $this->request( 'GET', self::BASE, array(), array( 'extension_slug' => self::EXTENSION_SLUG ) );
		$ids  = array();
		foreach ( $this->response_data( $list ) as $row ) {
			$this->assertIsArray( $row );
			$ids[] = $this->int_value( $row, 'id' );
		}
		$this->assertSame( array( $second, $first ), $ids );
	}

	public function test_reorder_rejects_duplicate_ids(): void {
		wp_set_current_user( $this->admin_id );

		$first  = $this->create_plan( 'First' );
		$second = $this->create_plan( 'Second' );

		$reordered = $this->request(
			'POST',
			self::BASE . '/reorder',
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'ids'            => array( $second, $first, $second ),
			)
		);

		$this->assertSame( 400, $reordered->get_status() );
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
