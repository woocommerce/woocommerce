<?php
/**
 * DomainAbilityTestCase class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Tests\Abilities\AbilityRegistriesTrait;

/**
 * Runs Core's abilities from fresh registries, so each test can set the feature,
 * extension fields and the personal data filter before the schemas are built.
 */
abstract class DomainAbilityTestCase extends \WC_Unit_Test_Case {

	use AbilityRegistriesTrait;

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
					$resource_name,
					'test-ext',
					'code',
					array_merge(
						array(
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
		$this->filter_personal_data( '__return_false' );
	}

	/**
	 * Replace the personal data filter for the rest of the test, and forget the decisions made so far.
	 *
	 * @param callable $callback      Filter callback.
	 * @param int      $accepted_args Number of arguments the callback takes.
	 */
	protected function filter_personal_data( callable $callback, int $accepted_args = 1 ): void {
		remove_all_filters( 'woocommerce_abilities_share_personal_data' );
		add_filter( 'woocommerce_abilities_share_personal_data', $callback, 10, $accepted_args );
		self::forget_personal_data_decisions();
	}
}
