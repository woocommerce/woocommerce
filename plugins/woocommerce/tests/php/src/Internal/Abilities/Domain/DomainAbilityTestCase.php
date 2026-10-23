<?php
/**
 * DomainAbilityTestCase class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\AbilityResources;
use Automattic\WooCommerce\Internal\Abilities\PersonalData;

/**
 * Runs Core's abilities from fresh registries, so each test can choose the
 * extensible abilities feature, extension fields and the personal data filter
 * before the abilities build their schemas.
 */
abstract class DomainAbilityTestCase extends \WC_Unit_Test_Case {

	/**
	 * Administrator used as the current user.
	 *
	 * @var int
	 */
	protected static $administrator_id;

	/**
	 * Init action count restored in tearDown.
	 *
	 * @var int|null
	 */
	private $original_init_count;

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

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();

		AbilitiesLoader::init();
		self::reset_registries();
		wp_set_current_user( self::$administrator_id );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		try {
			self::reset_registries();
		} finally {
			parent::tearDown();
		}

		if ( null !== $this->original_init_count ) {
			$wp_actions['init'] = $this->original_init_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Run an ability as the current user, with its permission check.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return mixed|\WP_Error
	 */
	protected function run_ability( string $name, array $input = array() ) {
		$ability = wp_get_ability( $name );
		$this->assertNotNull( $ability, "Ability {$name} should be registered." );

		return $ability->execute( $input );
	}

	/**
	 * Turn the extensible abilities feature on and register a test field on a resource.
	 *
	 * @param string   $resource_name Resource.
	 * @param callable $get_callback  Field get_callback.
	 * @param array    $extra         More field arguments, such as `personal_data` or `update_callback`.
	 */
	protected function add_test_field( string $resource_name, callable $get_callback, array $extra = array() ): void {
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'yes' );
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $resource_name, $get_callback, $extra ) {
				ExtensibleAbilities::register_field(
					array_merge(
						array(
							'resource'     => $resource_name,
							'namespace'    => 'test-ext',
							'field'        => 'code',
							'schema'       => array( 'type' => array( 'string', 'null' ) ),
							'get_callback' => $get_callback,
						),
						$extra
					)
				);
			}
		);
	}

	/**
	 * Withhold personal data from the abilities for the rest of the test.
	 */
	protected function withhold_personal_data(): void {
		add_filter( PersonalData::FILTER, '__return_false' );
	}

	/**
	 * Drop every registered field and ability, so the next read fires the init actions again.
	 */
	private static function reset_registries(): void {
		global $wp_actions;

		$properties = array(
			ExtensibleAbilities::class => array( 'fields', 'reported', 'validators', 'filters' ),
			AbilityResources::class    => array( 'resources', 'fields', 'groups', 'extension_fields' ),
		);
		foreach ( $properties as $class => $names ) {
			foreach ( $names as $property ) {
				$reflection = new \ReflectionProperty( $class, $property );
				$reflection->setAccessible( true );
				$reflection->setValue( null, array() );
			}
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
