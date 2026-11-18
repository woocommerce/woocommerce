<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * POC: can WooPayments data reach the Core abilities through ExtensibleAbilities?
 */
class WooPaymentsOrderFieldsPocTest extends \WC_REST_Unit_Test_Case {

	private static $administrator_id;

	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	public function setUp(): void {
		global $wp_actions;
		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore
		parent::setUp();
		wc_get_container()->get( AbilitiesLoader::class );
		$this->reset_registries();
		wp_set_current_user( self::$administrator_id );
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'yes' );
		add_action( 'woocommerce_extensible_abilities_resources_init', array( $this, 'register_resources' ) );
		add_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_wcpay' ) );
	}

	public function tearDown(): void {
		remove_action( 'woocommerce_extensible_abilities_resources_init', array( $this, 'register_resources' ) );
		remove_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_wcpay' ) );
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'no' );
		$this->reset_registries();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	public function register_resources(): void {
		ExtensibleAbilities::register_resource( 'wcpay_payout' );
	}

	/**
	 * What WooPayments would register: its order meta as read-only order fields, plus order filters.
	 */
	public function register_wcpay(): void {
		$meta_fields = array(
			'intent_id'            => '_intent_id',
			'charge_id'            => '_charge_id',
			'intention_status'     => '_intention_status',
			'fraud_outcome_status' => '_wcpay_fraud_outcome_status',
		);
		foreach ( $meta_fields as $field => $meta_key ) {
			ExtensibleAbilities::register_field(
				array(
					'resource'      => 'order',
					'namespace'     => 'woocommerce-payments',
					'field'         => $field,
					'output_schema' => array( 'type' => 'string' ),
					'get_callback'  => static fn( \WC_Order $order ) => $order->get_meta( $meta_key ) ?: null,
				)
			);
		}
		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'order',
				'namespace'     => 'woocommerce-payments',
				'field'         => 'open_dispute_ids',
				'output_schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'get_callback'  => static fn( \WC_Order $order ) => $order->get_meta( '_wcpay_open_dispute_ids' ) ?: null,
			)
		);

		ExtensibleAbilities::register_query_filter(
			array(
				'resource'  => 'order',
				'namespace' => 'woocommerce-payments',
				'filter'    => 'fraud_outcome_status',
				'schema'    => array(
					'type' => 'string',
					'enum' => array( 'review', 'block', 'allow' ),
				),
				'callback'  => static fn( string $status ) => array(
					'meta_query' => array( // phpcs:ignore
						array(
							'key'   => '_wcpay_fraud_outcome_status',
							'value' => $status,
						),
					),
				),
			)
		);
		ExtensibleAbilities::register_query_filter(
			array(
				'resource'  => 'order',
				'namespace' => 'woocommerce-payments',
				'filter'    => 'intention_status',
				'schema'    => array( 'type' => 'string' ),
				'callback'  => static fn( string $status ) => array(
					'meta_query' => array( // phpcs:ignore
						array(
							'key'   => '_intention_status',
							'value' => $status,
						),
					),
				),
			)
		);
		ExtensibleAbilities::register_query_filter(
			array(
				'resource'  => 'order',
				'namespace' => 'woocommerce-payments',
				'filter'    => 'has_open_dispute',
				'schema'    => array( 'type' => 'boolean' ),
				'callback'  => static fn( bool $has ) => array(
					'meta_query' => array( // phpcs:ignore
						array(
							'key'     => '_wcpay_open_dispute_ids',
							'compare' => $has ? 'EXISTS' : 'NOT EXISTS',
						),
					),
				),
			)
		);

		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'wcpay_payout',
				'namespace'     => 'woocommerce-payments',
				'field'         => 'poc_payout_amount',
				'output_schema' => array( 'type' => 'integer' ),
				'get_callback'  => static fn() => 100,
			)
		);
	}

	public function test_order_abilities_return_woopayments_order_meta(): void {
		$order = $this->create_order(
			array(
				'_intent_id'                  => 'pi_123',
				'_charge_id'                  => 'ch_123',
				'_intention_status'           => 'requires_capture',
				'_wcpay_fraud_outcome_status' => 'review',
				'_wcpay_open_dispute_ids'     => array( 'du_1' ),
			)
		);

		$orders = array_column( $this->run_ability( 'woocommerce/orders-query', array( 'per_page' => 10 ) )['orders'], null, 'id' );

		$this->assertSame(
			array(
				'woocommerce-payments' => array(
					'intent_id'            => 'pi_123',
					'charge_id'            => 'ch_123',
					'intention_status'     => 'requires_capture',
					'fraud_outcome_status' => 'review',
					'open_dispute_ids'     => array( 'du_1' ),
				),
			),
			$orders[ $order->get_id() ]['extensions']
		);
	}

	public function test_orders_query_filters_by_woopayments_meta(): void {
		$review   = $this->create_order(
			array(
				'_wcpay_fraud_outcome_status' => 'review',
				'_intention_status'           => 'requires_capture',
			)
		);
		$disputed = $this->create_order(
			array(
				'_wcpay_open_dispute_ids' => array( 'du_1' ),
				'_intention_status'       => 'succeeded',
			)
		);
		$this->create_order( array( '_intention_status' => 'succeeded' ) );

		$ids = fn( array $filter ) => array_column(
			$this->run_ability(
				'woocommerce/orders-query',
				array( 'filters' => array( 'extensions' => array( 'woocommerce-payments' => $filter ) ) )
			)['orders'],
			'id'
		);

		$this->assertSame( array( $review->get_id() ), $ids( array( 'fraud_outcome_status' => 'review' ) ) );
		$this->assertSame( array( $review->get_id() ), $ids( array( 'intention_status' => 'requires_capture' ) ) );
		$this->assertSame( array( $disputed->get_id() ), $ids( array( 'has_open_dispute' => true ) ) );
	}

	public function test_a_custom_resource_reaches_no_core_ability(): void {
		$this->run_ability( 'woocommerce/orders-query', array( 'per_page' => 1 ) );

		$found = array();
		foreach ( wp_get_abilities() as $ability ) {
			if ( false !== strpos( (string) wp_json_encode( $ability->get_output_schema() ), 'poc_payout_amount' ) ) {
				$found[] = $ability->get_name();
			}
		}

		$this->assertTrue( ExtensibleAbilities::is_resource_registered( 'wcpay_payout' ) );
		$this->assertSame( array(), $found );
	}

	private function create_order( array $meta ): \WC_Order {
		$order = \WC_Helper_Order::create_order();
		foreach ( $meta as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}
		$order->save();
		return $order;
	}

	private function run_ability( string $name, array $input ): array {
		$request = new \WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $name . '/run' );
		$request->set_query_params( array( 'input' => $input ) );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data();
	}

	private function reset_registries(): void {
		global $wp_actions;
		foreach ( array( 'fields', 'validators', 'syncs', 'filters', 'resources' ) as $property ) {
			$reflection = new \ReflectionProperty( ExtensibleAbilities::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}
		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_extensible_abilities_resources_init'], $wp_actions['woocommerce_extensible_abilities_init'] );
	}
}
