<?php
/**
 * AbilityResourcesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Abilities;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Tests\Internal\Abilities\Domain\DomainAbilityTestCase;

/**
 * Resource ownership and shared field declarations.
 */
class AbilityResourcesTest extends DomainAbilityTestCase {

	/**
	 * Enable extension registration for resource tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->set_feature( true );
	}

	/**
	 * @testdox Should let an extension declare its own resource with root fields, the same way Core does.
	 */
	public function test_an_extension_resource_has_root_fields(): void {
		$register_resource = static function () {
			AbilityResources::register_resource( 'ticket' );
			AbilityResources::register_field(
				'ticket',
				'id',
				array(
					'schema'       => array( 'type' => 'integer' ),
					'get_callback' => static fn( array $ticket ) => $ticket['id'],
				)
			);
			AbilityResources::register_field(
				'ticket',
				'email',
				array(
					'schema'        => array( 'type' => 'string' ),
					'get_callback'  => static fn( array $ticket ) => $ticket['email'],
					'personal_data' => true,
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		$this->withhold_personal_data();
		$this->get_output_schema( 'woocommerce/products-query' );
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );

		$output = AbilityResources::output_data(
			'ticket',
			array(
				'id'    => 7,
				'email' => 'jane@example.com',
			)
		);

		$this->assertSame( array( 'id' => 7 ), $output );
		$this->assertSame( array( 'id' ), array_keys( AbilityResources::output_schema( 'ticket' )['properties'] ) );
	}

	/**
	 * @testdox Should identify a failing root field and keep its exception text in developer diagnostics.
	 * @testWith ["Exception"]
	 *           ["Error"]
	 * @param string $exception_class Throwable class.
	 */
	public function test_root_callback_failures_keep_context_without_exception_text( string $exception_class ): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->register_resources( 'ticket' );
		AbilityResources::register_field(
			'ticket',
			'code',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function () use ( $exception_class ) {
					throw new $exception_class( 'Raw callback sentinel' );
				},
			)
		);
		$diagnostics = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$diagnostics ) {
				if ( AbilityResources::class . '::register_field' === $function_name ) {
					$diagnostics[] = $message;
				}
			},
			10,
			2
		);

		$output = AbilityResources::output_data( 'ticket', array() );

		$this->assertWPError( $output );
		$this->assertSame( 'woocommerce_ability_extension_failed', $output->get_error_code() );
		$this->assertSame( 'Reading the "code" field of the ticket failed.', $output->get_error_message() );
		$this->assertSame(
			array(
				'status'   => 500,
				'resource' => 'ticket',
				'field'    => 'code',
			),
			$output->get_error_data()
		);
		$this->assertStringNotContainsString( 'Raw callback sentinel', wp_json_encode( $output ) );
		$this->assertCount( 1, $diagnostics );
		$this->assertStringContainsString( 'Reading the "code" field of the ticket failed. Raw callback sentinel', $diagnostics[0] );
	}

	/**
	 * @testdox Should preserve a deliberate root callback error and its useful details.
	 */
	public function test_root_callback_errors_are_preserved(): void {
		$this->register_resources( 'ticket' );
		$error = new \WP_Error(
			'ticket_unavailable',
			'Choose another ticket.',
			array(
				'status' => 409,
				'retry'  => false,
			)
		);
		AbilityResources::register_field(
			'ticket',
			'code',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => $error,
			)
		);

		$this->assertSame( $error, AbilityResources::output_data( 'ticket', array() ) );
	}

	/**
	 * @testdox Should refuse a root field that an extension adds to a Core resource, or after the init action.
	 */
	public function test_root_fields_on_resources_of_others_are_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$register = static function () {
			AbilityResources::register_field(
				'product',
				'early',
				array(
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'x',
				)
			);
		};
		$late     = static function () {
			AbilityResources::register_field(
				'product',
				'late',
				array(
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'x',
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register );
		add_action( 'woocommerce_extensible_abilities_init', $late );
		$schema = $this->get_output_schema( 'woocommerce/products-query' )['properties']['products']['items']['properties'];
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register );
		remove_action( 'woocommerce_extensible_abilities_init', $late );

		$this->assertArrayNotHasKey( 'early', $schema );
		$this->assertArrayNotHasKey( 'late', $schema );
		$this->assertArrayHasKey( 'name', $schema );
	}

	/**
	 * @testdox Should refuse a root field that an extension adds to its own resource after the resources action.
	 */
	public function test_root_fields_on_an_extension_resource_close_after_the_resources_action(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$register_resource = static function () {
			AbilityResources::register_resource( 'ticket' );
		};
		$late              = static function () {
			AbilityResources::register_field(
				'ticket',
				'late',
				array(
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'x',
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		add_action( 'woocommerce_extensible_abilities_init', $late );
		$this->get_output_schema( 'woocommerce/products-query' );
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		remove_action( 'woocommerce_extensible_abilities_init', $late );

		$this->assertTrue( AbilityResources::is_registered( 'ticket' ) );
		$this->assertArrayNotHasKey( 'late', AbilityResources::output_schema( 'ticket' )['properties'] );
	}

	/**
	 * @testdox Should refuse a root field on a Core resource after Core registered it, also while the feature is off.
	 */
	public function test_root_fields_on_core_resources_are_refused_while_the_feature_is_off(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'no' );
		AbilitiesLoader::register_core_resources();

		AbilityResources::register_field(
			'product',
			'early',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'x',
			)
		);

		$this->assertArrayNotHasKey( 'early', AbilityResources::output_schema( 'product' )['properties'] );
	}

	/**
	 * @testdox Should refuse a root field named extensions, which would clash with the extension fields.
	 */
	public function test_a_root_field_named_extensions_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->register_resources( 'ticket' );

		AbilityResources::register_field(
			'ticket',
			'extensions',
			array(
				'schema'       => array( 'type' => 'object' ),
				'get_callback' => static fn() => array(),
			)
		);

		$this->assertSame( array(), AbilityResources::output_schema( 'ticket' )['properties'] );
	}

	/**
	 * @testdox Should refuse a $ref that the registry would not resolve: in an extension field, or under oneOf in a root field.
	 */
	public function test_an_unresolved_ref_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		AbilitiesLoader::register_core_resources();
		$this->register_resources( 'ticket' );

		ExtensibleAbilities::register_field(
			array_merge(
				array(
					'output_schema' => array(
						'type' => 'object',
						'$ref' => 'product',
					),
					'get_callback'  => static fn() => null,
				),
				array(
					'resource'  => 'product',
					'namespace' => 'test-ext',
					'field'     => 'related',
				)
			)
		);
		AbilityResources::register_field(
			'ticket',
			'product',
			array(
				'schema'       => array(
					'type'  => array( 'object', 'null' ),
					'oneOf' => array(
						array( '$ref' => 'product' ),
						array( 'type' => 'null' ),
					),
				),
				'get_callback' => static fn() => null,
			)
		);

		$this->assertArrayNotHasKey( 'extensions', AbilityResources::output_schema( 'product' )['properties'] );
		$this->assertSame( array(), AbilityResources::output_schema( 'ticket' )['properties'] );
	}

	/**
	 * @testdox Should report a $ref to a resource that is not registered once, and fall back to a plain object.
	 */
	public function test_a_ref_to_an_unregistered_resource_is_reported(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::resolve_schema' );
		$this->register_resources( 'ticket' );
		AbilityResources::register_field(
			'ticket',
			'event',
			array(
				'schema'       => array( '$ref' => 'event' ),
				'get_callback' => static fn() => array( 'id' => 1 ),
			)
		);

		$reports = 0;
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name ) use ( &$reports ) {
				$reports += AbilityResources::class . '::resolve_schema' === $function_name ? 1 : 0;
			}
		);

		$schema = AbilityResources::output_schema( 'ticket' );
		AbilityResources::output_schema( 'ticket' );
		$output = AbilityResources::output_data( 'ticket', array() );

		$this->assertSame( 1, $reports );
		$this->assertSame( array( 'type' => 'object' ), $schema['properties']['event'] );
		$this->assertEquals( new \stdClass(), $output['event'] );
	}

	/**
	 * @testdox Should refuse a namespace or an update_callback on a root field, since only extension fields have them.
	 */
	public function test_a_root_field_with_extension_field_arguments_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->register_resources( 'ticket' );

		AbilityResources::register_field(
			'ticket',
			'id',
			array(
				'schema'          => array( 'type' => 'integer' ),
				'get_callback'    => static fn() => 1,
				'update_callback' => '__return_null',
			)
		);
		AbilityResources::register_field(
			'ticket',
			'code',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'T1',
				'namespace'    => 'test-ext',
			)
		);

		$this->assertSame( array(), AbilityResources::output_schema( 'ticket' )['properties'] );
	}

	/**
	 * @testdox Should refuse a Core resource registered before Core, also while the feature is off.
	 */
	public function test_a_core_resource_claimed_before_core_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_resource' );
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->set_feature( false );

		AbilityResources::register_resource( 'order' );
		AbilityResources::register_field(
			'order',
			'billing_email',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'jane@example.com',
			)
		);
		AbilitiesLoader::register_core_resources();
		$shared = AbilityResources::output_schema( 'order' )['properties'];
		$this->withhold_personal_data();
		$withheld = AbilityResources::output_schema( 'order' )['properties'];

		$this->assertSame( 'string', $shared['billing_email']['type'] );
		$this->assertArrayNotHasKey( 'billing_email', $withheld );
		$this->assertArrayHasKey( 'total', $withheld );
	}

	/**
	 * @testdox Should share registrations across both APIs without letting an extension replace a root field.
	 */
	public function test_field_registration_uses_one_registry(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		AbilitiesLoader::register_core_resources();
		$personal = array(
			'output_schema' => array( 'type' => 'string' ),
			'get_callback'  => static fn() => 'private@example.test',
			'personal_data' => true,
			'set_callback'  => '__return_null',
			'input_schema'  => array( 'type' => 'string' ),
		);
		AbilityResources::register_field( 'order', 'email', $personal, 'test-ext' );
		ExtensibleAbilities::register_field(
			array_merge(
				array(
					'output_schema' => array( 'type' => 'string' ),
					'get_callback'  => static fn() => 'replacement',
				),
				array(
					'resource'  => 'order',
					'namespace' => 'test-ext',
					'field'     => 'email',
				)
			)
		);
		ExtensibleAbilities::register_field(
			array_merge(
				array(
					'output_schema' => array( 'type' => 'string' ),
					'get_callback'  => static fn() => 'extension value',
				),
				array(
					'resource'  => 'order',
					'namespace' => 'test-ext',
					'field'     => 'billing_email',
				)
			)
		);
		$this->withhold_personal_data();
		$order = new \WC_Order();
		$order->set_billing_email( 'core@example.test' );

		$output = AbilityResources::output_data( 'order', $order );
		$schema = AbilityResources::output_schema( 'order' )['properties'];

		$this->assertArrayNotHasKey( 'billing_email', $output );
		$this->assertArrayNotHasKey( 'billing_email', $schema );
		$this->assertArrayNotHasKey( 'email', $output['extensions']['test-ext'] );
		$this->assertArrayNotHasKey( 'email', $schema['extensions']['properties']['test-ext']['properties'] );
		$this->assertSame( 'extension value', $output['extensions']['test-ext']['billing_email'] );
		$fields = ExtensibleAbilities::get_fields( 'order' );
		$this->assertTrue( $fields['test-ext']['email']['personal_data'] );
		$this->assertSame( '__return_null', $fields['test-ext']['email']['set_callback'] );
		$this->assertFalse( $fields['test-ext']['billing_email']['personal_data'] );
	}

	/**
	 * @testdox Should invalidate output fields when either API adds a field and hide extensions while disabled.
	 */
	public function test_registry_cache_and_extension_feature_gate(): void {
		AbilitiesLoader::register_core_resources();
		$this->assertArrayNotHasKey( 'extensions', AbilityResources::output_schema( 'order' )['properties'] );
		$field = array(
			'output_schema' => array( 'type' => 'string' ),
			'get_callback'  => static fn() => 'visible',
		);
		AbilityResources::register_field( 'order', 'first', $field, 'test-ext' );
		$this->assertArrayHasKey( 'first', AbilityResources::output_schema( 'order' )['properties']['extensions']['properties']['test-ext']['properties'] );
		ExtensibleAbilities::register_field(
			array_merge(
				$field,
				array(
					'resource'  => 'order',
					'namespace' => 'test-ext',
					'field'     => 'second',
				)
			)
		);
		$this->assertArrayHasKey( 'second', AbilityResources::output_schema( 'order' )['properties']['extensions']['properties']['test-ext']['properties'] );

		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'no' );
		ExtensibleAbilities::register_field(
			array_merge(
				$field,
				array(
					'resource'  => 'order',
					'namespace' => 'test-ext',
					'field'     => 'disabled',
				)
			)
		);
		$this->withhold_personal_data();
		$schema = AbilityResources::output_schema( 'order' )['properties'];
		$output = AbilityResources::output_data( 'order', new \WC_Order() );

		$this->assertArrayHasKey( 'disabled', ExtensibleAbilities::get_fields( 'order' )['test-ext'] );
		$this->assertArrayNotHasKey( 'extensions', $schema );
		$this->assertArrayNotHasKey( 'extensions', $output );
		$this->assertArrayNotHasKey( 'billing_email', $schema );
		$this->assertArrayNotHasKey( 'billing_email', $output );
	}

	/**
	 * Turn the feature on or off.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );
	}

	/**
	 * Output schema of a registered ability.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_output_schema( string $name ): array {
		return wp_get_ability( $name )->get_output_schema();
	}
}
