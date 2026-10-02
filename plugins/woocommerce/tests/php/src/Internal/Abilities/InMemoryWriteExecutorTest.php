<?php
/**
 * InMemoryWriteExecutorTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\AbilityContracts;
use Automattic\WooCommerce\Abilities\ObjectValidatorRegistry;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * An in-memory write ability run through the loader.
 */
class InMemoryWriteExecutorTest extends \WC_Unit_Test_Case {

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

		$this->reset_validator_registry();
		add_action( 'woocommerce_register_object_validators', array( $this, 'register_test_validator' ) );
		add_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_definition' ) );

		$this->product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Original' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		foreach ( array( TestInMemoryWriteDefinition::ABILITY_ID, TestThrowingWriteDefinition::ABILITY_ID, TestKindWriteDefinition::ABILITY_ID ) as $ability_id ) {
			if ( wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
		remove_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_definition' ) );
		remove_action( 'woocommerce_register_object_validators', array( $this, 'register_test_validator' ) );
		$this->reset_validator_registry();
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', 'no' );

		foreach ( $this->original_action_counts as $action => $original_count ) {
			if ( null !== $original_count ) {
				$wp_actions[ $action ] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $wp_actions[ $action ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should return what respond() returns after the save.
	 */
	public function test_success_returns_respond_output(): void {
		$this->register_with_feature( true );

		$result = wp_get_ability( TestInMemoryWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'   => $this->product->get_id(),
				'name' => 'Renamed',
			)
		);

		$this->assertSame(
			array(
				'product_id' => $this->product->get_id(),
				'name'       => 'Renamed',
			),
			$result
		);
		$this->assertSame( 'Renamed', wc_get_product( $this->product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return a WP_Error and save nothing when validate() refuses the input.
	 */
	public function test_validate_error_returns_wp_error_and_saves_nothing(): void {
		$this->register_with_feature( true );

		$result = wp_get_ability( TestInMemoryWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'   => $this->product->get_id(),
				'name' => '',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'test_empty_name', $result->get_error_code() );
		$this->assertSame( 'Name is empty.', $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 'Original', wc_get_product( $this->product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return a WP_Error and save nothing when an object validator rejects the object.
	 */
	public function test_validator_rejection_returns_wp_error_and_saves_nothing(): void {
		$this->register_with_feature( true );

		$result = wp_get_ability( TestInMemoryWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'   => $this->product->get_id(),
				'name' => 'Blocked',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_rejected', $result->get_error_code() );
		$this->assertSame( 'Blocked by validator.', $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 'Original', wc_get_product( $this->product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return a WP_Error when the object to change does not exist.
	 */
	public function test_missing_subject_returns_wp_error(): void {
		$this->register_with_feature( true );

		$result = wp_get_ability( TestInMemoryWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'   => PHP_INT_MAX,
				'name' => 'Renamed',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_not_found', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should return a 400 WP_Error when the save throws a data exception.
	 */
	public function test_save_data_exception_returns_wp_error(): void {
		$this->register_with_feature( true );
		$throw = static function () {
			throw new \WC_Data_Exception( 'test_invalid', 'Invalid data.' );
		};
		add_action( 'woocommerce_before_product_object_save', $throw );

		$result = wp_get_ability( TestInMemoryWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'   => $this->product->get_id(),
				'name' => 'Renamed',
			)
		);
		remove_action( 'woocommerce_before_product_object_save', $throw );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_save_failed', $result->get_error_code() );
		$this->assertSame( 'Invalid data.', $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 'Original', wc_get_product( $this->product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return a 500 WP_Error when the save throws any other exception.
	 */
	public function test_save_exception_returns_wp_error(): void {
		$this->register_with_feature( true );
		$throw = static function () {
			throw new \RuntimeException( 'Database is gone.' );
		};
		add_action( 'woocommerce_before_product_object_save', $throw );

		$result = wp_get_ability( TestInMemoryWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'   => $this->product->get_id(),
				'name' => 'Renamed',
			)
		);
		remove_action( 'woocommerce_before_product_object_save', $throw );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_save_failed', $result->get_error_code() );
		$this->assertSame( 500, $result->get_error_data()['status'] );
		$this->assertSame( 'Original', wc_get_product( $this->product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return a WP_Error and save nothing when validate() or apply() throws.
	 *
	 * @testWith ["validate", "data", 400]
	 *           ["validate", "other", 500]
	 *           ["apply", "data", 400]
	 *           ["apply", "other", 500]
	 *
	 * @param string $step   Step that throws.
	 * @param string $kind   Exception kind.
	 * @param int    $status Expected status.
	 */
	public function test_step_exception_returns_wp_error_and_saves_nothing( string $step, string $kind, int $status ): void {
		$this->register_with_feature( true );

		$result = wp_get_ability( TestThrowingWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'       => $this->product->get_id(),
				'name'     => 'Renamed',
				'throw_in' => $step,
				'throw'    => $kind,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_save_failed', $result->get_error_code() );
		$this->assertSame( $status, $result->get_error_data()['status'] );
		if ( 'data' === $kind ) {
			$this->assertSame( 'Invalid data.', $result->get_error_message() );
		}
		$this->assertSame( 'Original', wc_get_product( $this->product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should run the validators registered for a WC_Data object's kind: order, subscription, product, coupon, or its object type.
	 */
	public function test_validators_keyed_by_object_kind(): void {
		$subscription = new class() extends \WC_Order {
			/**
			 * Order type.
			 *
			 * @return string
			 */
			public function get_type() {
				return 'shop_subscription';
			}
		};
		$product      = new \WC_Product_Simple();
		$product->set_name( 'Blocked' );
		$registry = ObjectValidatorRegistry::instance();

		$this->assertSame( 'Order validator.', $registry->validate( new \WC_Order() ) );
		$this->assertSame( 'Subscription validator.', $registry->validate( $subscription ) );
		$this->assertSame( 'Blocked by validator.', $registry->validate( $product ) );
		$this->assertSame( 'Customer validator.', $registry->validate( new \WC_Customer() ) );
		$this->assertSame( 'Coupon validator.', $registry->validate( new \WC_Coupon() ) );
	}

	/**
	 * @testdox Should run the validators for an in-memory write's subject_type(), even on a WC_Data subject.
	 */
	public function test_in_memory_write_validators_follow_subject_type(): void {
		$this->register_with_feature( true );

		$result = wp_get_ability( TestKindWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'   => $this->product->get_id(),
				'name' => 'Blocked kind',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'Kind validator.', $result->get_error_message() );
		$this->assertSame( 'Original', wc_get_product( $this->product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should use the class's own execute callback when the feature is off.
	 */
	public function test_feature_off_uses_own_execute(): void {
		$this->register_with_feature( false );

		$result = wp_get_ability( TestInMemoryWriteDefinition::ABILITY_ID )->execute(
			array(
				'id'   => $this->product->get_id(),
				'name' => 'Renamed',
			)
		);

		$this->assertSame( array( 'own_execute' => true ), $result );
		$this->assertSame( 'Original', wc_get_product( $this->product->get_id() )->get_name() );
	}

	/**
	 * Add the test definition to the loader.
	 *
	 * @param array $classes Ability definition class names.
	 * @return array
	 */
	public function add_test_definition( array $classes ): array {
		$classes[] = TestInMemoryWriteDefinition::class;
		$classes[] = TestThrowingWriteDefinition::class;
		$classes[] = TestKindWriteDefinition::class;
		return $classes;
	}

	/**
	 * Register a product validator that rejects one name, a validator for an
	 * extension-defined kind, and validators that reject every order,
	 * subscription, customer and coupon.
	 *
	 * @param ObjectValidatorRegistry $registry Registry.
	 */
	public function register_test_validator( ObjectValidatorRegistry $registry ): void {
		$registry->register(
			'product',
			'test',
			static function ( \WC_Product $product ) {
				return 'Blocked' === $product->get_name() ? new \WP_Error( 'test_blocked', 'Blocked by validator.' ) : true;
			}
		);
		$registry->register(
			'test_kind',
			'test',
			static function ( \WC_Product $product ) {
				return 'Blocked kind' === $product->get_name() ? new \WP_Error( 'test_blocked', 'Kind validator.' ) : true;
			}
		);
		foreach ( array( 'order', 'subscription', 'customer', 'coupon' ) as $kind ) {
			$registry->register(
				$kind,
				'test',
				static function () use ( $kind ) {
					return new \WP_Error( 'test_blocked', ucfirst( $kind ) . ' validator.' );
				}
			);
		}
	}

	/**
	 * Set the feature, then register the test ability through the loader.
	 *
	 * @param bool $enabled Whether the feature is enabled.
	 */
	private function register_with_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );

		$callback = array( AbilitiesLoader::class, 'register_abilities' );
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Drop the shared validator registry so the next collection runs again.
	 */
	private function reset_validator_registry(): void {
		$reflection = new \ReflectionProperty( ObjectValidatorRegistry::class, 'instance' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, null );
	}
}
