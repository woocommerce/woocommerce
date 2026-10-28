<?php
/**
 * NestedResourcesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Abilities;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Tests\Internal\Abilities\Domain\DomainAbilityTestCase;

/**
 * Resources nested in other resources, in the output and the output schema.
 */
class NestedResourcesTest extends DomainAbilityTestCase {

	/**
	 * Set up a `test_order` resource with a nullable `customer`.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->register_resources( 'test_order', 'test_customer' );
		foreach ( array( 'test_order', 'test_customer' ) as $resource_name ) {
			AbilityResources::register_field(
				$resource_name,
				'id',
				array(
					'schema'       => array( 'type' => 'integer' ),
					'get_callback' => static fn( array $subject ) => $subject['id'],
				)
			);
		}
		AbilityResources::register_field(
			'test_customer',
			'email',
			array(
				'schema'        => array( 'type' => array( 'string', 'null' ) ),
				'get_callback'  => static fn( array $customer ) => $customer['email'],
				'personal_data' => true,
			)
		);
		AbilityResources::register_field(
			'test_order',
			'customer',
			array(
				'schema'       => array(
					'$ref' => 'test_customer',
					'type' => array( 'object', 'null' ),
				),
				'get_callback' => static fn( array $order ) => $order['customer'],
			)
		);
	}

	/**
	 * @testdox Should stop at a reference back to a resource being formatted, the same way as its schema.
	 */
	public function test_a_reference_back_to_a_resource_stops(): void {
		AbilityResources::register_field(
			'test_customer',
			'last_order',
			array(
				'schema'       => array(
					'$ref' => 'test_order',
					'type' => array( 'object', 'null' ),
				),
				'get_callback' => static fn( array $customer ) => $customer['last_order'] ?? null,
			)
		);
		$order = array(
			'id'       => 1,
			'customer' => array(
				'id'         => 3,
				'email'      => 'jane@example.com',
				'last_order' => array(
					'id'       => 1,
					'customer' => null,
				),
			),
		);

		$output = $this->output( array( 'order' => $order ) );
		$schema = $this->get_output_schema();

		$last_order_schema = $schema['properties']['order']['properties']['customer']['properties']['last_order'];
		$this->assertArrayNotHasKey( 'properties', $last_order_schema );
		$this->assertEquals( new \stdClass(), $output['order']['customer']['last_order'] );
		$this->assertTrue( rest_validate_value_from_schema( $output, $schema ), 'The output should match its schema.' );
	}

	/**
	 * @testdox Should return a nested resource without fields as an empty object.
	 */
	public function test_an_empty_nested_resource_is_an_object(): void {
		$this->withhold_personal_data();
		AbilityResources::register_field(
			'test_order',
			'customer_email',
			array(
				'schema'       => array(
					'$ref'   => 'test_customer',
					'fields' => array( 'email' ),
				),
				'get_callback' => static fn( array $order ) => $order['customer'],
			)
		);

		$output = $this->output(
			array(
				'order' => array(
					'id'       => 1,
					'customer' => array(
						'id'    => 3,
						'email' => 'jane@example.com',
					),
				),
			)
		);

		$this->assertSame( '{}', wp_json_encode( $output['order']['customer_email'] ) );
		$this->assertTrue( rest_validate_value_from_schema( $output, $this->get_output_schema() ), 'The output should match its schema.' );
	}

	/**
	 * @testdox Should ask the personal data filter once per resource, so a store can withhold one resource only.
	 */
	public function test_withholding_is_scoped_to_the_nested_resource(): void {
		AbilityResources::register_field(
			'test_order',
			'note',
			array(
				'schema'        => array( 'type' => 'string' ),
				'get_callback'  => static fn( array $order ) => $order['note'],
				'personal_data' => true,
			)
		);
		$asked = array();
		$this->filter_personal_data(
			static function ( $shared, string $resource_name ) use ( &$asked ) {
				$asked[] = $resource_name;
				return 'test_customer' === $resource_name ? false : $shared;
			},
			2
		);

		$output = $this->output(
			array(
				'order' => array(
					'id'       => 1,
					'note'     => 'Ring twice',
					'customer' => array(
						'id'    => 3,
						'email' => 'jane@example.com',
					),
				),
			)
		);
		$schema = $this->get_output_schema()['properties']['order']['properties'];

		$this->assertEqualsCanonicalizing( array( 'test_order', 'test_customer' ), $asked );
		$this->assertSame( 'Ring twice', $output['order']['note'] );
		$this->assertSame( array( 'id' => 3 ), $output['order']['customer'] );
		$this->assertArrayHasKey( 'note', $schema );
		$this->assertArrayNotHasKey( 'email', $schema['customer']['properties'] );
	}

	/**
	 * @testdox Should allow null for a nested resource whose field schema allows it, and leave its personal data out.
	 */
	public function test_nested_resource_can_be_null(): void {
		$this->withhold_personal_data();
		$schema = $this->get_output_schema();

		$guest    = $this->output(
			array(
				'order' => array(
					'id'       => 1,
					'customer' => null,
				),
			)
		);
		$customer = $this->output(
			array(
				'order' => array(
					'id'       => 2,
					'customer' => array(
						'id'    => 3,
						'email' => 'jane@example.com',
					),
				),
			)
		);

		$customer_schema = $schema['properties']['order']['properties']['customer'];
		$this->assertSame( array( 'object', 'null' ), $customer_schema['type'] );
		$this->assertArrayNotHasKey( 'email', $customer_schema['properties'], 'The nested schema should leave out its own personal data.' );
		$this->assertNull( $guest['order']['customer'] );
		$this->assertTrue( rest_validate_value_from_schema( $guest, $schema ), 'A guest order should match the schema.' );
		$this->assertSame( 3, $customer['order']['customer']['id'] );
		$this->assertArrayNotHasKey( 'email', $customer['order']['customer'], 'The nested customer should leave out its own personal data.' );
		$this->assertTrue( rest_validate_value_from_schema( $customer, $schema ), 'An order with a customer should match the schema.' );
	}

	/**
	 * @testdox Should nest only the listed fields of a resource, without its extension fields unless listed.
	 */
	public function test_nested_resource_with_a_field_list(): void {
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'yes' );
		ExtensibleAbilities::register_field(
			'test_customer',
			'test-ext',
			'tier',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'gold',
			)
		);
		AbilityResources::register_field(
			'test_order',
			'customer_ref',
			array(
				'schema'       => array(
					'$ref'   => 'test_customer',
					'fields' => array( 'id' ),
				),
				'get_callback' => static fn( array $order ) => $order['customer'],
			)
		);

		$schema = $this->get_output_schema();
		$output = $this->output(
			array(
				'order' => array(
					'id'       => 1,
					'customer' => array(
						'id'    => 3,
						'email' => 'jane@example.com',
					),
				),
			)
		);

		$order_schema = $schema['properties']['order'];
		$this->assertSame( array( 'id' ), array_keys( $order_schema['properties']['customer_ref']['properties'] ) );
		$this->assertArrayNotHasKey( 'fields', $order_schema['properties']['customer_ref'] );
		$this->assertSame( array( 'id' => 3 ), $output['order']['customer_ref'] );
		$this->assertSame( 'gold', $output['order']['customer']['extensions']['test-ext']['tier'], 'A nested resource without a field list should keep its extension fields.' );
		$this->assertTrue( rest_validate_value_from_schema( $output, $schema ), 'The output should match its schema.' );
	}

	/**
	 * Formatted values keyed like an ability output, with the order under `order`.
	 *
	 * @param array<string, mixed> $values Values, with the order under `order`.
	 * @return array<string, mixed>
	 */
	private function output( array $values ): array {
		return array( 'order' => AbilityResources::output_data( 'test_order', $values['order'] ) );
	}

	/**
	 * The output schema that matches output().
	 *
	 * @return array<string, mixed>
	 */
	private function get_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array( 'order' => AbilityResources::output_schema( 'test_order' ) ),
			'additionalProperties' => false,
		);
	}
}
