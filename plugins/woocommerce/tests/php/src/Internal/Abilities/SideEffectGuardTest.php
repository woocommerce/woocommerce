<?php
/**
 * SideEffectGuardTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\AbilityContracts;
use Automattic\WooCommerce\Abilities\AbilityFieldRegistry;
use Automattic\WooCommerce\Abilities\ObjectValidatorRegistry;
use Automattic\WooCommerce\Abilities\SideEffectGuard;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Steps of an in-memory write that save, send email or call out over HTTP.
 */
class SideEffectGuardTest extends \WC_Unit_Test_Case {

	private const ABILITY_IDS = array(
		TestSideEffectWriteDefinition::ABILITY_ID,
		'woocommerce/products-query',
		'woocommerce/product-create',
		'woocommerce/product-update',
		'woocommerce/product-delete',
		'woocommerce/orders-query',
		'woocommerce/order-update-status',
		'woocommerce/order-add-note',
	);

	/**
	 * Shared administrator used as the current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Original action counts restored in tearDown.
	 *
	 * @var array<string, int|null>
	 */
	private $original_action_counts = array();

	/**
	 * Product renamed by the tests.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Emails handed to PHPMailer.
	 *
	 * @var int
	 */
	private $mails_sent = 0;

	/**
	 * HTTP requests that reached a transport.
	 *
	 * @var int
	 */
	private $requests_sent = 0;

	/**
	 * Create immutable class fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->original_action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( ! wp_has_ability_category( 'woocommerce' ) ) {
			$callback = static function () {
				wp_register_ability_category(
					'woocommerce',
					array(
						'label'       => 'WooCommerce',
						'description' => 'Canonical store management abilities.',
					)
				);
			};
			add_action( 'wp_abilities_api_categories_init', $callback );
			do_action( 'wp_abilities_api_categories_init' );
			remove_action( 'wp_abilities_api_categories_init', $callback );
		}

		wp_set_current_user( self::$administrator_id );

		$this->reset_registries();
		add_action( 'woocommerce_register_object_validators', array( $this, 'register_test_validator' ) );
		add_action( 'woocommerce_register_ability_fields', array( $this, 'register_test_field' ) );
		add_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_definition' ) );
		add_action( 'phpmailer_init', array( $this, 'count_mail' ) );
		add_action( 'http_api_debug', array( $this, 'count_request' ) );

		$this->product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Original' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		$this->unregister_abilities();
		remove_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_definition' ) );
		remove_action( 'woocommerce_register_object_validators', array( $this, 'register_test_validator' ) );
		remove_action( 'woocommerce_register_ability_fields', array( $this, 'register_test_field' ) );
		remove_action( 'phpmailer_init', array( $this, 'count_mail' ) );
		remove_action( 'http_api_debug', array( $this, 'count_request' ) );
		$this->reset_registries();
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', 'no' );

		foreach ( $this->original_action_counts as $action => $original_count ) {
			if ( null !== $original_count ) {
				$wp_actions[ $action ] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $wp_actions[ $action ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox Should refuse a write whose apply() saves, and save nothing.
	 */
	public function test_apply_that_saves_is_refused(): void {
		$this->register_with_feature( true );

		$result = $this->rename( 'Renamed', 'save' );

		$this->assertSideEffectRefusal( $result, 'database write' );
		$this->assertSame( 'Original', $this->stored_name() );
	}

	/**
	 * @testdox Should refuse a write whose apply() sends email, and send nothing.
	 */
	public function test_apply_that_sends_email_is_refused(): void {
		$this->register_with_feature( true );

		$result = $this->rename( 'Renamed', 'mail' );

		$this->assertSideEffectRefusal( $result, 'email' );
		$this->assertSame( 0, $this->mails_sent );
		$this->assertSame( 'Original', $this->stored_name() );
	}

	/**
	 * @testdox Should refuse a write whose apply() calls out over HTTP, and send no request.
	 */
	public function test_apply_that_calls_http_is_refused(): void {
		$this->register_with_feature( true );

		$result = $this->rename( 'Renamed', 'http' );

		$this->assertSideEffectRefusal( $result, 'HTTP request' );
		$this->assertSame( 0, $this->requests_sent );
		$this->assertSame( 'Original', $this->stored_name() );
	}

	/**
	 * @testdox Should refuse a write whose object validator writes an option.
	 */
	public function test_validator_that_writes_is_refused(): void {
		$this->register_with_feature( true );

		$result = $this->rename( 'Option writer' );

		$this->assertSideEffectRefusal( $result, 'database write' );
		$this->assertNull( $this->stored_option( 'test_side_effect_option' ) );
		$this->assertSame( 'Original', $this->stored_name() );
	}

	/**
	 * @testdox Should refuse a product update whose extension field apply handler writes post meta directly.
	 */
	public function test_field_apply_that_writes_meta_is_refused_on_product_update(): void {
		$this->register_with_feature( true );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $this->product->get_id(),
				'name'       => 'Renamed',
				'extensions' => array( 'test_leaky' => array( 'code' => 'abc' ) ),
			)
		);

		$this->assertSideEffectRefusal( $result, 'database write' );
		$this->assertSame( array(), get_post_meta( $this->product->get_id(), '_test_leaky_code', false ) );
		$this->assertSame( 'Original', $this->stored_name() );
	}

	/**
	 * @testdox Should let a step write to WooCommerce's log table.
	 */
	public function test_step_may_write_to_log_table(): void {
		$this->register_with_feature( true );

		$result = $this->rename( 'Renamed', 'log' );

		$this->assertNotWPError( $result );
		$this->assertSame( 'Renamed', $this->stored_name() );
	}

	/**
	 * @testdox Should save a well-behaved write once.
	 */
	public function test_well_behaved_write_saves_once(): void {
		$this->register_with_feature( true );
		$updates = did_action( 'woocommerce_update_product' );

		$result = $this->rename( 'Renamed' );

		$this->assertSame(
			array(
				'product_id' => $this->product->get_id(),
				'name'       => 'Renamed',
			),
			$result
		);
		$this->assertSame( 'Renamed', $this->stored_name() );
		$this->assertSame( $updates + 1, did_action( 'woocommerce_update_product' ) );
	}

	/**
	 * @testdox Should lift every guard after the call, so a database write right after it works.
	 */
	public function test_guards_are_lifted_after_the_call(): void {
		$this->register_with_feature( true );
		$this->rename( 'Renamed', 'save' );

		update_option( 'test_side_effect_option', 'after' );

		$this->assertSame( 'after', $this->stored_option( 'test_side_effect_option' ) );
	}

	/**
	 * @testdox Should run the ability's own execute callback unguarded when the feature is off.
	 */
	public function test_feature_off_adds_no_guards(): void {
		$this->register_with_feature( false );

		$result = $this->rename( 'Renamed' );

		$this->assertSame( array( 'own_execute' => true ), $result );
		$this->assertSame( 'Renamed', $this->stored_name() );
	}

	/**
	 * @testdox Should return the steps' result, or a WP_Error when the steps tried a side effect.
	 */
	public function test_run_returns_result_or_error(): void {
		$this->assertSame( 'value', SideEffectGuard::run( static fn() => 'value' ) );

		$result = SideEffectGuard::run(
			static function () {
				update_option( 'test_side_effect_option', 'during' );
				return 'value';
			}
		);

		$this->assertSideEffectRefusal( $result, 'database write' );
		$this->assertNull( $this->stored_option( 'test_side_effect_option' ) );
	}

	/**
	 * Assert the result is the side-effect refusal naming what was attempted.
	 *
	 * @param mixed  $result    Ability result.
	 * @param string $attempted What the message must name.
	 */
	private function assertSideEffectRefusal( $result, string $attempted ): void {
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_side_effect', $result->get_error_code() );
		$this->assertSame( 500, $result->get_error_data()['status'] );
		$this->assertStringContainsString( $attempted, $result->get_error_message() );
		$this->assertStringNotContainsString( 'INSERT', $result->get_error_message() );
		$this->assertStringNotContainsString( 'UPDATE', $result->get_error_message() );
	}

	/**
	 * Run the test write.
	 *
	 * @param string $name        New name.
	 * @param string $side_effect Side effect apply() causes.
	 * @return mixed
	 */
	private function rename( string $name, string $side_effect = '' ) {
		return wp_get_ability( TestSideEffectWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'          => $this->product->get_id(),
				'name'        => $name,
				'side_effect' => $side_effect,
			)
		);
	}

	/**
	 * The product's title in the database, bypassing every cache.
	 */
	private function stored_name(): string {
		global $wpdb;
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $this->product->get_id() ) );
	}

	/**
	 * An option's value in the database, bypassing every cache.
	 *
	 * @param string $name Option name.
	 */
	private function stored_option( string $name ): ?string {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
	}

	/**
	 * Add the test definition to the loader.
	 *
	 * @param array $classes Ability definition class names.
	 * @return array
	 */
	public function add_test_definition( array $classes ): array {
		$classes[] = TestSideEffectWriteDefinition::class;
		return $classes;
	}

	/**
	 * Register a product validator that writes an option for one product name.
	 *
	 * @param ObjectValidatorRegistry $registry Registry.
	 */
	public function register_test_validator( ObjectValidatorRegistry $registry ): void {
		$registry->register(
			'product',
			'test',
			static function ( \WC_Product $product ) {
				if ( 'Option writer' === $product->get_name() ) {
					update_option( 'test_side_effect_option', 'during' );
				}
				return true;
			}
		);
	}

	/**
	 * Register a product field whose apply handler writes post meta directly.
	 *
	 * @param AbilityFieldRegistry $registry Registry.
	 */
	public function register_test_field( AbilityFieldRegistry $registry ): void {
		$registry->register(
			'product',
			'test_leaky',
			array(
				'description' => 'Test field that saves from apply.',
				'fields'      => array(
					'code' => array(
						'schema' => array( 'type' => 'string' ),
						'apply'  => static function ( $value, \WC_Product $product ) {
							update_post_meta( $product->get_id(), '_test_leaky_code', $value );
						},
						'read'   => static function ( \WC_Product $product ) {
							return $product->get_meta( '_test_leaky_code' );
						},
					),
				),
			)
		);
	}

	/**
	 * Count an email handed to PHPMailer.
	 */
	public function count_mail(): void {
		++$this->mails_sent;
	}

	/**
	 * Count an HTTP request that reached a transport.
	 */
	public function count_request(): void {
		++$this->requests_sent;
	}

	/**
	 * Set the feature, then register the abilities through the loader.
	 *
	 * @param bool $enabled Whether the feature is enabled.
	 */
	private function register_with_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );

		$this->unregister_abilities();
		$callback = array( AbilitiesLoader::class, 'register_abilities' );
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Unregister the abilities the tests register.
	 */
	private function unregister_abilities(): void {
		foreach ( self::ABILITY_IDS as $ability_id ) {
			if ( wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
	}

	/**
	 * Drop the shared registries so the next use collects registrations again.
	 */
	private function reset_registries(): void {
		foreach ( array( AbilityFieldRegistry::class, ObjectValidatorRegistry::class ) as $class_name ) {
			$reflection = new \ReflectionProperty( $class_name, 'instance' );
			$reflection->setAccessible( true );
			$reflection->setValue( null, null );
		}
	}
}
