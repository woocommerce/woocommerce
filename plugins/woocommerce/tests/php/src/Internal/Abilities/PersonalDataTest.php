<?php
/**
 * PersonalDataTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Abilities\PersonalData;
use Automattic\WooCommerce\Tests\Internal\Abilities\Domain\DomainAbilityTestCase;

/**
 * Personal data in Core's order abilities, with the filter on and off.
 */
class PersonalDataTest extends DomainAbilityTestCase {

	/**
	 * @testdox Should share personal data by default.
	 */
	public function test_is_shared_by_default(): void {
		$this->assertTrue( PersonalData::is_shared( 'order' ) );
	}

	/**
	 * @testdox Should withhold personal data for any filter value other than true.
	 * @dataProvider provide_values_other_than_true
	 *
	 * @param mixed $value Filter value.
	 */
	public function test_is_shared_fails_closed( $value ): void {
		$this->filter_personal_data( static fn() => $value );

		$this->assertFalse( PersonalData::is_shared( 'order' ) );
	}

	/**
	 * Truthy values are included, so a bool cast instead of `true ===` fails the test.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provide_values_other_than_true(): array {
		return array(
			'null'        => array( null ),
			'integer 1'   => array( 1 ),
			'string 1'    => array( '1' ),
			'string yes'  => array( 'yes' ),
			'array true'  => array( array( true ) ),
			'string true' => array( 'true' ),
		);
	}

	/**
	 * @testdox Should ask the filter once per resource, so the output schema and the output agree.
	 */
	public function test_is_shared_asks_once_per_resource(): void {
		$asked = array();
		$this->filter_personal_data(
			static function ( $shared, string $resource_name ) use ( &$asked ) {
				$asked[] = $resource_name;
				return $shared;
			},
			2
		);
		$order = $this->create_order_with_email();

		$this->get_order_item_schema();
		$this->run_ability( 'woocommerce/orders-query', array( 'id' => $order->get_id() ) );
		$this->run_ability( 'woocommerce/orders-query', array( 'id' => $order->get_id() ) );

		$this->assertContains( 'order', $asked );
		$this->assertSame( array_unique( $asked ), $asked, 'Each resource should be asked once.' );
	}

	/**
	 * @testdox Should return the billing email of an order while personal data is shared.
	 */
	public function test_orders_query_returns_billing_email_by_default(): void {
		$order = $this->create_order_with_email();

		$result = $this->run_ability( 'woocommerce/orders-query', array( 'id' => $order->get_id() ) );

		$this->assertSame( 'jane@example.com', $result['orders'][0]['billing_email'] );
		$this->assertArrayHasKey( 'billing_email', $this->get_order_item_schema()['properties'] );
	}

	/**
	 * @testdox Should leave personal data fields out of the output and the output schema while personal data is withheld.
	 */
	public function test_orders_query_withholds_personal_data(): void {
		$order = $this->create_order_with_email();
		$this->add_test_field(
			'order',
			static function ( \WC_Order $order ) {
				return $order->get_billing_phone();
			},
			array( 'personal_data' => true )
		);
		$this->withhold_personal_data();

		$result = $this->run_ability( 'woocommerce/orders-query', array( 'id' => $order->get_id() ) );

		$this->assertNotWPError( $result );
		$this->assertArrayNotHasKey( 'billing_email', $result['orders'][0] );
		$this->assertArrayNotHasKey( 'extensions', $result['orders'][0], 'A personal extension field should be left out.' );
		$this->assertStringNotContainsString( 'jane@example.com', (string) wp_json_encode( $result ) );
		$schema = $this->get_order_item_schema();
		$this->assertArrayNotHasKey( 'billing_email', $schema['properties'], 'The schema should not tell that the billing email exists.' );
		$this->assertArrayNotHasKey( 'extensions', $schema['properties'], 'The schema should not tell that the personal extension field exists.' );
	}

	/**
	 * @testdox Should keep extension fields that are not personal data while personal data is withheld.
	 */
	public function test_orders_query_keeps_other_extension_fields(): void {
		$order = $this->create_order_with_email();
		$this->add_test_field(
			'order',
			static function () {
				return 'gift';
			}
		);
		$this->withhold_personal_data();

		$result = $this->run_ability( 'woocommerce/orders-query', array( 'id' => $order->get_id() ) );

		$this->assertSame( 'gift', $result['orders'][0]['extensions']['test-ext']['code'] );
	}

	/**
	 * @testdox Should withhold personal data also while the extensible abilities feature is off.
	 */
	public function test_withholds_personal_data_while_extensible_abilities_feature_is_off(): void {
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'no' );
		$order = $this->create_order_with_email();
		$this->withhold_personal_data();

		$result = $this->run_ability( 'woocommerce/orders-query', array( 'id' => $order->get_id() ) );

		$this->assertArrayNotHasKey( 'billing_email', $result['orders'][0] );
		$this->assertArrayNotHasKey( 'billing_email', $this->get_order_item_schema()['properties'] );
	}

	/**
	 * @testdox Should withhold the billing email from a write ability that returns an order.
	 */
	public function test_order_write_ability_withholds_personal_data(): void {
		$order = $this->create_order_with_email();
		$this->withhold_personal_data();

		$result = $this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertArrayNotHasKey( 'billing_email', $result['order'] );
		$this->assertArrayNotHasKey( 'billing_email', wp_get_ability( 'woocommerce/order-update-status' )->get_output_schema()['properties']['order']['properties'] );
	}

	/**
	 * Output schema of one order in woocommerce/orders-query.
	 *
	 * @return array<string, mixed>
	 */
	private function get_order_item_schema(): array {
		return wp_get_ability( 'woocommerce/orders-query' )->get_output_schema()['properties']['orders']['items'];
	}

	/**
	 * Create an order with a billing email.
	 *
	 * @return \WC_Order
	 */
	private function create_order_with_email(): \WC_Order {
		$order = \WC_Helper_Order::create_order();
		$order->set_billing_email( 'jane@example.com' );
		$order->set_billing_phone( '555-0100' );
		$order->save();

		return $order;
	}
}
