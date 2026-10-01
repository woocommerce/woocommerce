<?php
/**
 * InMemoryWriteCapabilitiesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\AbilityContracts;
use Automattic\WooCommerce\Abilities\ObjectValidatorRegistry;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Optional capabilities of in-memory writes, run through the loader.
 */
class InMemoryWriteCapabilitiesTest extends \WC_Unit_Test_Case {

	private const OPTION = 'test_in_memory_option';

	/**
	 * Original action counts restored in tearDown.
	 *
	 * @var array<string, int|null>
	 */
	private $original_action_counts = array();

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
		add_action( 'woocommerce_register_object_validators', array( $this, 'register_test_validators' ) );
		add_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_definitions' ) );
		update_option( self::OPTION, 'original' );

		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', 'yes' );
		$callback = array( AbilitiesLoader::class, 'register_abilities' );
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		foreach ( $this->add_test_definitions( array() ) as $class_name ) {
			if ( wp_has_ability( $class_name::get_name() ) ) {
				wp_unregister_ability( $class_name::get_name() );
			}
		}
		remove_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_definitions' ) );
		remove_action( 'woocommerce_register_object_validators', array( $this, 'register_test_validators' ) );
		$this->reset_validator_registry();
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', 'no' );
		delete_option( self::OPTION );

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
	 * @testdox Should save a subject that is not a WC_Data object and return what respond() returns.
	 */
	public function test_in_memory_subject_saves_and_responds(): void {
		$result = $this->set_option( 'changed' );

		$this->assertSame(
			array(
				'name'  => self::OPTION,
				'value' => 'changed',
			),
			$result
		);
		$this->assertSame( 'changed', get_option( self::OPTION ) );
	}

	/**
	 * @testdox Should return the WP_Error a subject's save() returns.
	 */
	public function test_in_memory_subject_save_error_is_the_result(): void {
		$result = $this->set_option( 'unsavable' );

		$this->assertWPError( $result );
		$this->assertSame( 'test_option_unsavable', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 'original', get_option( self::OPTION ) );
	}

	/**
	 * @testdox Should run the validators registered for the write's subject type and save nothing on rejection.
	 */
	public function test_in_memory_subject_runs_validators_for_its_subject_type(): void {
		$result = $this->set_option( 'blocked' );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_rejected', $result->get_error_code() );
		$this->assertSame( 'Option blocked by validator.', $result->get_error_message() );
		$this->assertSame( 'original', get_option( self::OPTION ) );
	}

	/**
	 * @testdox Should return validate() errors for a subject that is not a WC_Data object.
	 */
	public function test_in_memory_subject_validate_error(): void {
		$result = $this->set_option( '' );

		$this->assertWPError( $result );
		$this->assertSame( 'test_empty_value', $result->get_error_code() );
		$this->assertSame( 'original', get_option( self::OPTION ) );
	}

	/**
	 * Add the test definitions to the loader.
	 *
	 * @param array $classes Ability definition class names.
	 * @return array
	 */
	public function add_test_definitions( array $classes ): array {
		$classes[] = TestOptionWriteDefinition::class;
		return $classes;
	}

	/**
	 * Register test validators.
	 *
	 * @param ObjectValidatorRegistry $registry Registry.
	 */
	public function register_test_validators( ObjectValidatorRegistry $registry ): void {
		$registry->register(
			'test_option',
			'test',
			static function ( TestOptionRecord $record ) {
				return 'blocked' === $record->value ? new \WP_Error( 'test_blocked', 'Option blocked by validator.' ) : true;
			}
		);
	}

	/**
	 * Run the option write.
	 *
	 * @param string $value Value.
	 * @return array|\WP_Error
	 */
	private function set_option( string $value ) {
		return wp_get_ability( TestOptionWriteDefinition::ABILITY_ID )->execute(
			array(
				'name'  => self::OPTION,
				'value' => $value,
			)
		);
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
