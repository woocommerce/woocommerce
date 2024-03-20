<?php
/**
 * FeaturesControllerTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\Features;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\PluginUtil;

/**
 * Tests for the FeaturesController class.
 */
class FeaturesControllerTest extends \WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var FeaturesController
	 */
	private $sut;

	/**
	 * The fake PluginUtil instance to use.
	 *
	 * @var PluginUtil
	 */
	private $fake_plugin_util;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_up_plugins();

		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) {
				$this->reset_features_list( $this->sut );

				$features = array(
					'mature1'       => array(
						'name'            => 'Mature feature 1',
						'description'     => 'The mature feature number 1',
						'is_experimental' => false,
					),
					'mature2'       => array(
						'name'            => 'Mature feature 2',
						'description'     => 'The mature feature number 2',
						'is_experimental' => false,
					),
					'experimental1' => array(
						'name'            => 'Experimental feature 1',
						'description'     => 'The experimental feature number 1',
						'is_experimental' => true,
					),
					'experimental2' => array(
						'name'            => 'Experimental feature 2',
						'description'     => 'The experimental feature number 2',
						'is_experimental' => true,
					),
				);

				foreach ( $features as $slug => $definition ) {
					$features_controller->add_feature_definition( $slug, $definition['name'], $definition );
				}
			},
			11
		);

		$this->sut = new FeaturesController();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ), $this->fake_plugin_util );

		delete_option( 'woocommerce_feature_mature1_enabled' );
		delete_option( 'woocommerce_feature_mature2_enabled' );
		delete_option( 'woocommerce_feature_experimental1_enabled' );
		delete_option( 'woocommerce_feature_experimental2_enabled' );

		remove_all_filters( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION );
	}

	/**
	 * Runs before each test.
	 */
	private function set_up_plugins(): void {
		$this->reset_container_resolutions();
		$this->reset_legacy_proxy_mocks();

		// phpcs:disable Squiz.Commenting
		$this->fake_plugin_util = new class() extends PluginUtil {
			private $active_plugins;

			public function __construct() {
			}

			public function set_active_plugins( $plugins ) {
				$this->active_plugins = $plugins;
			}

			public function get_woocommerce_aware_plugins( bool $active_only = false ): array {
				$plugins = $this->active_plugins;
				if ( ! $active_only ) {
					$plugins[] = 'the_plugin_inactive';
				}

				return $plugins;
			}
		};
		// phpcs:enable Squiz.Commenting

		$this->fake_plugin_util->set_active_plugins(
			array(
				'the_plugin',
				'the_plugin_2',
				'the_plugin_3',
				'the_plugin_4',
			)
		);
	}

	/**
	 * Resets the array of registered features so we can populate it with test features.
	 *
	 * @param FeaturesController $sut The instance of the FeaturesController class.
	 *
	 * @return void
	 */
	private function reset_features_list( $sut ) {
		$reflection_class = new \ReflectionClass( $sut );

		$features = $reflection_class->getProperty( 'features' );
		$features->setAccessible( true );
		$features->setValue( $sut, array() );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		$this->reset_features_list( $this->sut );
		remove_all_actions( 'woocommerce_register_feature_definitions' );

		parent::tearDown();
	}

	/**
	 * @testdox 'get_features' returns existing non-experimental features without enabling information if requested to do so.
	 */
	public function test_get_features_not_including_experimental_not_including_values() {
		$actual = array_keys( $this->sut->get_features( false, false ) );

		$expected = array(
			'mature1',
			'mature2',
		);

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox 'get_features' returns all existing features without enabling information if requested to do so.
	 */
	public function test_get_features_including_experimental_not_including_values() {
		$actual = array_keys( $this->sut->get_features( true, false ) );

		$expected = array(
			'mature1',
			'mature2',
			'experimental1',
			'experimental2',
		);

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox 'get_features' returns all existing features with enabling information if requested to do so.
	 */
	public function test_get_features_including_experimental_and_values() {
		update_option( 'woocommerce_feature_mature1_enabled', 'yes' );
		update_option( 'woocommerce_feature_mature2_enabled', 'no' );
		update_option( 'woocommerce_feature_experimental1_enabled', 'yes' );
		// No option for experimental2.

		$actual = array_map(
			function ( $feature ) {
				return array_intersect_key(
					$feature,
					array( 'is_enabled' => '' )
				);
			},
			$this->sut->get_features( true, true )
		);

		$expected = array(
			'mature1'       => array(
				'is_enabled' => true,
			),
			'mature2'       => array(
				'is_enabled' => false,
			),
			'experimental1' => array(
				'is_enabled' => true,
			),
			'experimental2' => array(
				'is_enabled' => false,
			),
		);

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox 'feature_is_enabled' returns whether a feature is enabled, and returns false for invalid feature ids.
	 *
	 * @testWith ["mature1", true]
	 *           ["mature2", false]
	 *           ["experimental1", false]
	 *           ["NOT_EXISTING", false]
	 *
	 * @param string $feature_id Feature id to check.
	 * @param bool   $expected_to_be_enabled Expected result from the method.
	 */
	public function test_feature_is_enabled( $feature_id, $expected_to_be_enabled ) {
		update_option( 'woocommerce_feature_mature1_enabled', 'yes' );
		update_option( 'woocommerce_feature_mature2_enabled', 'no' );
		// No option for experimental1.

		$this->assertEquals( $expected_to_be_enabled, $this->sut->feature_is_enabled( $feature_id ) );
	}

	/**
	 * @testdox 'change_feature_enable' does nothing and returns false for an invalid feature id.
	 */
	public function test_change_feature_enable_for_non_existing_feature() {
		$result = $this->sut->change_feature_enable( 'NON_EXISTING', true );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox 'change_feature_enabled' works as expected with and without previous values for the feature enable options.
	 *
	 * @testWith [null, false, true, false, false]
	 *           [null, true, true, false, true]
	 *           ["no", false, false, false, false]
	 *           ["no", true, true, false, true]
	 *           ["yes", false, true, true, false]
	 *           ["yes", true, false, true, true]
	 *
	 * @param string|null $previous_value The previous value of the feature enable option.
	 * @param bool        $enable Whether the feature will be enabled or disabled.
	 * @param bool        $expected_result Expected value to be returned by 'change_feature_enable'.
	 * @param bool        $expected_previous_enabled Expected value to be returned by 'feature_is_enabled' before the feature status is changed.
	 * @param bool        $expected_new_enabled Expected value to be returned by 'feature_is_enabled' after the feature status is changed.
	 */
	public function test_change_feature_enable( $previous_value, $enable, $expected_result, $expected_previous_enabled, $expected_new_enabled ) {
		if ( $previous_value ) {
			update_option( 'woocommerce_feature_mature1_enabled', $previous_value );
		}

		$result = $this->sut->feature_is_enabled( 'mature1' );
		$this->assertEquals( $expected_previous_enabled, $result );

		$result = $this->sut->change_feature_enable( 'mature1', $enable );
		$this->assertEquals( $result, $expected_result );

		$result = $this->sut->feature_is_enabled( 'mature1' );
		$this->assertEquals( $expected_new_enabled, $result );
	}

	/**
	 * @testdox 'declare_compatibility' fails when invoked from outside the 'before_woocommerce_init' action.
	 */
	public function test_declare_compatibility_outside_before_woocommerce_init_hook() {
		$function = null;
		$message  = null;
		$version  = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_doing_it_wrong' => function ( $f, $m, $v ) use ( &$function, &$message, &$version ) {
					$function = $f;
					$message  = $m;
					$version  = $v;
				},
			)
		);

		$result = $this->sut->declare_compatibility( 'mature1', 'the_plugin' );
		$this->assertFalse( $result );

		$this->assertEquals( 'FeaturesController::declare_compatibility', $function );
		$this->assertEquals( 'FeaturesController::declare_compatibility should be called inside the before_woocommerce_init action.', $message );
		$this->assertEquals( '7.0', $version );
	}

	/**
	 * @testdox 'declare_compatibility' returns false for invalid feature ids.
	 */
	public function test_declare_compatibility_for_non_existing_feature() {
		$this->simulate_inside_before_woocommerce_init_hook();

		$result = $this->sut->declare_compatibility( 'NON_EXISTING', 'the_plugin' );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox 'declare_compatibility' registers internally the proper per-plugin information.
	 */
	public function test_declare_compatibility_by_plugin() {
		$this->simulate_inside_before_woocommerce_init_hook();

		$result = $this->sut->declare_compatibility( 'mature1', 'the_plugin' );
		$this->assertTrue( $result );
		$result = $this->sut->declare_compatibility( 'experimental1', 'the_plugin' );
		$this->assertTrue( $result );
		$result = $this->sut->declare_compatibility( 'experimental2', 'the_plugin', false );
		$this->assertTrue( $result );
		// Duplicate declaration is ok:.
		$result = $this->sut->declare_compatibility( 'experimental2', 'the_plugin', false );
		$this->assertTrue( $result );

		$compatibility_info_prop = new \ReflectionProperty( $this->sut, 'compatibility_info_by_plugin' );
		$compatibility_info_prop->setAccessible( true );
		$compatibility_info = $compatibility_info_prop->getValue( $this->sut );

		$expected = array(
			'the_plugin' => array(
				'compatible'   => array(
					'mature1',
					'experimental1',
				),
				'incompatible' => array(
					'experimental2',
				),
			),
		);

		$this->assertEquals( $expected, $compatibility_info );
	}

	/**
	 * @testdox 'declare_compatibility' registers internally the proper per-feature information.
	 */
	public function test_declare_compatibility_by_feature() {
		$this->simulate_inside_before_woocommerce_init_hook();

		$result = $this->sut->declare_compatibility( 'mature1', 'the_plugin_1' );
		$this->assertTrue( $result );
		$result = $this->sut->declare_compatibility( 'mature1', 'the_plugin_2' );
		$this->assertTrue( $result );
		$result = $this->sut->declare_compatibility( 'mature1', 'the_plugin_3', false );
		$this->assertTrue( $result );
		$result = $this->sut->declare_compatibility( 'experimental1', 'the_plugin_1', false );
		$this->assertTrue( $result );
		$result = $this->sut->declare_compatibility( 'experimental2', 'the_plugin_2', true );
		$this->assertTrue( $result );

		$compatibility_info_prop = new \ReflectionProperty( $this->sut, 'compatibility_info_by_feature' );
		$compatibility_info_prop->setAccessible( true );
		$compatibility_info = $compatibility_info_prop->getValue( $this->sut );

		$expected = array(
			'mature1'       => array(
				'compatible'   => array(
					'the_plugin_1',
					'the_plugin_2',
				),
				'incompatible' => array(
					'the_plugin_3',
				),
			),
			'mature2'       => array(
				'compatible'   => array(),
				'incompatible' => array(),
			),
			'experimental1' => array(
				'compatible'   => array(),
				'incompatible' => array(
					'the_plugin_1',
				),
			),
			'experimental2' => array(
				'compatible'   => array(
					'the_plugin_2',
				),
				'incompatible' => array(),
			),
		);

		$this->assertEquals( $expected, $compatibility_info );
	}

	/**
	 * @testdox 'declare_compatibility' throws when a plugin declares itself as both compatible and incompatible with a given feature.
	 */
	public function test_declare_compatibility_and_incompatibility_for_the_same_plugin() {
		$this->simulate_inside_before_woocommerce_init_hook();

		$this->ExpectException( \Exception::class );
		$this->ExpectExceptionMessage( "Plugin the_plugin is trying to declare itself as incompatible with the 'mature1' feature, but it already declared itself as compatible" );

		$this->sut->declare_compatibility( 'mature1', 'the_plugin', true );
		$this->sut->declare_compatibility( 'mature1', 'the_plugin', false );
	}

	/**
	 * @testdox 'get_compatible_features_for_plugin' fails when invoked before the 'woocommerce_init' hook.
	 */
	public function test_get_compatible_features_for_plugin_before_woocommerce_init_hook() {
		$function = null;
		$message  = null;
		$version  = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'did_action'        => function ( $action_name ) {
					return 'woocommerce_init' === $action_name ? false : did_action( $action_name );
				},
				'wc_doing_it_wrong' => function ( $f, $m, $v ) use ( &$function, &$message, &$version ) {
					$function = $f;
					$message  = $m;
					$version  = $v;
				},
			)
		);

		$this->sut->get_compatible_features_for_plugin( 'the_plugin' );

		$this->assertEquals( 'FeaturesController::get_compatible_features_for_plugin', $function );
		$this->assertEquals( 'FeaturesController::get_compatible_features_for_plugin should not be called before the woocommerce_init action.', $message );
		$this->assertEquals( '7.0', $version );
	}

	/**
	 * @testdox 'get_compatible_features_for_plugin' returns empty information for a plugin that has not declared compatibility with any feature.
	 */
	public function test_get_compatible_features_for_unregistered_plugin() {
		$this->simulate_after_woocommerce_init_hook();

		$result = $this->sut->get_compatible_features_for_plugin( 'the_plugin' );

		$expected = array(
			'compatible'   => array(),
			'incompatible' => array(),
			'uncertain'    => array( 'mature1', 'mature2', 'experimental1', 'experimental2' ),
		);
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'get_compatible_features_for_plugin' returns proper information for a plugin that has declared compatibility with the passed feature, and reacts to plugin deactivation accordingly.
	 */
	public function test_get_compatible_features_for_registered_plugin() {
		$this->simulate_inside_before_woocommerce_init_hook();

		$this->sut->declare_compatibility( 'mature1', 'the_plugin', true );
		$this->sut->declare_compatibility( 'mature2', 'the_plugin', true );
		$this->sut->declare_compatibility( 'experimental1', 'the_plugin', false );
		$this->reset_legacy_proxy_mocks();
		$this->simulate_after_woocommerce_init_hook();

		$result   = $this->sut->get_compatible_features_for_plugin( 'the_plugin' );
		$expected = array(
			'compatible'   => array( 'mature1', 'mature2' ),
			'incompatible' => array( 'experimental1' ),
			'uncertain'    => array( 'experimental2' ),
		);
		$this->assertEquals( $expected, $result );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'deactivated_plugin', 'the_plugin' );
		$this->fake_plugin_util->set_active_plugins( array( 'the_plugin_2', 'the_plugin_3', 'the_plugin_4' ) );

		$result   = $this->sut->get_compatible_features_for_plugin( 'the_plugin' );
		$expected = array(
			'compatible'   => array(),
			'incompatible' => array(),
			'uncertain'    => array( 'mature1', 'mature2', 'experimental1', 'experimental2' ),
		);
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'get_compatible_features_for_plugin' returns proper information for a plugin that has declared compatibility with the passed feature, when only enabled features are requested.
	 */
	public function test_get_compatible_enabled_features_for_registered_plugin() {
		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) {
				$this->reset_features_list( $this->sut );

				$features = array(
					'mature1'       => array(
						'name'            => 'Mature feature 1',
						'description'     => 'The mature feature number 1',
						'is_experimental' => false,
					),
					'mature2'       => array(
						'name'            => 'Mature feature 2',
						'description'     => 'The mature feature number 2',
						'is_experimental' => false,
					),
					'mature3'       => array(
						'name'            => 'Mature feature 3',
						'description'     => 'The mature feature number 3',
						'is_experimental' => false,
					),
					'experimental1' => array(
						'name'            => 'Experimental feature 1',
						'description'     => 'The experimental feature number 1',
						'is_experimental' => true,
					),
					'experimental2' => array(
						'name'            => 'Experimental feature 2',
						'description'     => 'The experimental feature number 2',
						'is_experimental' => true,
					),
					'experimental3' => array(
						'name'            => 'Experimental feature 3',
						'description'     => 'The experimental feature number 3',
						'is_experimental' => true,
					),
				);

				foreach ( $features as $slug => $definition ) {
					$features_controller->add_feature_definition( $slug, $definition['name'], $definition );
				}
			},
			20
		);

		$this->simulate_inside_before_woocommerce_init_hook();

		$this->sut->declare_compatibility( 'mature1', 'the_plugin', true );
		$this->sut->declare_compatibility( 'mature2', 'the_plugin', true );
		$this->sut->declare_compatibility( 'experimental1', 'the_plugin', false );
		$this->sut->declare_compatibility( 'experimental2', 'the_plugin', false );
		$this->reset_legacy_proxy_mocks();
		$this->simulate_after_woocommerce_init_hook();

		update_option( 'woocommerce_feature_mature1_enabled', 'yes' );
		update_option( 'woocommerce_feature_mature2_enabled', 'no' );
		update_option( 'woocommerce_feature_mature3_enabled', 'yes' );
		update_option( 'woocommerce_feature_experimental1_enabled', 'no' );
		update_option( 'woocommerce_feature_experimental2_enabled', 'yes' );
		update_option( 'woocommerce_feature_experimental3_enabled', 'no' );

		$result   = $this->sut->get_compatible_features_for_plugin( 'the_plugin', true );
		$expected = array(
			'compatible'   => array( 'mature1' ),
			'incompatible' => array( 'experimental2' ),
			'uncertain'    => array( 'mature3' ),
		);
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'get_compatible_plugins_for_feature' fails when invoked before the 'woocommerce_init' hook.
	 */
	public function test_get_compatible_plugins_for_feature_before_woocommerce_init_hook() {
		$function = null;
		$message  = null;
		$version  = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'did_action'        => function ( $action_name ) {
					return 'woocommerce_init' === $action_name ? false : did_action( $action_name );
				},
				'wc_doing_it_wrong' => function ( $f, $m, $v ) use ( &$function, &$message, &$version ) {
					$function = $f;
					$message  = $m;
					$version  = $v;
				},
			)
		);

		$this->sut->get_compatible_plugins_for_feature( 'mature1' );

		$this->assertEquals( 'FeaturesController::get_compatible_plugins_for_feature', $function );
		$this->assertEquals( 'FeaturesController::get_compatible_plugins_for_feature should not be called before the woocommerce_init action.', $message );
		$this->assertEquals( '7.0', $version );
	}

	/**
	 * @testdox 'get_compatible_plugins_for_feature' returns empty information for invalid feature ids when only active plugins are requested.
	 */
	public function test_get_compatible_active_plugins_for_non_existing_feature() {
		$this->simulate_after_woocommerce_init_hook();

		$result = $this->sut->get_compatible_plugins_for_feature( 'NON_EXISTING', true );

		$expected = array(
			'compatible'   => array(),
			'incompatible' => array(),
			'uncertain'    => array( 'the_plugin', 'the_plugin_2', 'the_plugin_3', 'the_plugin_4' ),
		);
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'get_compatible_plugins_for_feature' returns empty information for invalid feature ids when all plugins are requested.
	 */
	public function test_get_all_compatible_plugins_for_non_existing_feature() {
		$this->simulate_after_woocommerce_init_hook();

		$result = $this->sut->get_compatible_plugins_for_feature( 'NON_EXISTING', false );

		$expected = array(
			'compatible'   => array(),
			'incompatible' => array(),
			'uncertain'    => array(
				'the_plugin',
				'the_plugin_2',
				'the_plugin_3',
				'the_plugin_4',
				'the_plugin_inactive',
			),
		);
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'get_compatible_plugins_for_feature' returns empty information for features for which no compatibility has been declared when only active plugins are requested.
	 */
	public function test_get_active_compatible_plugins_for_existing_feature_without_compatibility_declarations() {
		$this->simulate_after_woocommerce_init_hook();

		$result = $this->sut->get_compatible_plugins_for_feature( 'mature1', true );

		$expected = array(
			'compatible'   => array(),
			'incompatible' => array(),
			'uncertain'    => array( 'the_plugin', 'the_plugin_2', 'the_plugin_3', 'the_plugin_4' ),
		);
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'get_compatible_plugins_for_feature' returns empty information for features for which no compatibility has been declared when all plugins are requested.
	 */
	public function test_get_all_compatible_plugins_for_existing_feature_without_compatibility_declarations() {
		$this->simulate_after_woocommerce_init_hook();

		$result = $this->sut->get_compatible_plugins_for_feature( 'mature1', false );

		$expected = array(
			'compatible'   => array(),
			'incompatible' => array(),
			'uncertain'    => array(
				'the_plugin',
				'the_plugin_2',
				'the_plugin_3',
				'the_plugin_4',
				'the_plugin_inactive',
			),
		);
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'get_compatible_plugins_for_feature' returns proper information for a feature for which compatibility has been declared, and reacts to plugin deactivation accordingly.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $active_only True to test retrieving only active plugins.
	 */
	public function test_get_compatible_plugins_for_feature( bool $active_only ) {
		$this->simulate_inside_before_woocommerce_init_hook();

		$this->fake_plugin_util->set_active_plugins(
			array(
				'the_plugin',
				'the_plugin_2',
				'the_plugin_3',
				'the_plugin_4',
				'the_plugin_5',
				'the_plugin_6',
			)
		);

		$this->sut->declare_compatibility( 'mature1', 'the_plugin', true );
		$this->sut->declare_compatibility( 'mature1', 'the_plugin_2', true );
		$this->sut->declare_compatibility( 'mature1', 'the_plugin_3', false );
		$this->sut->declare_compatibility( 'mature1', 'the_plugin_4', false );

		$this->simulate_after_woocommerce_init_hook();
		$result             = $this->sut->get_compatible_plugins_for_feature( 'mature1', $active_only );
		$expected_uncertain = $active_only ? array( 'the_plugin_5', 'the_plugin_6' ) : array(
			'the_plugin_5',
			'the_plugin_6',
			'the_plugin_inactive',
		);
		$expected           = array(
			'compatible'   => array( 'the_plugin', 'the_plugin_2' ),
			'incompatible' => array( 'the_plugin_3', 'the_plugin_4' ),
			'uncertain'    => $expected_uncertain,
		);
		$this->assertEquals( $expected, $result );

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'deactivated_plugin', 'the_plugin_2' );
		do_action( 'deactivated_plugin', 'the_plugin_4' );
		do_action( 'deactivated_plugin', 'the_plugin_6' );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->fake_plugin_util->set_active_plugins( array( 'the_plugin', 'the_plugin_3', 'the_plugin_5' ) );
		$result             = $this->sut->get_compatible_plugins_for_feature( 'mature1', $active_only );
		$expected_uncertain = $active_only ? array( 'the_plugin_5' ) : array( 'the_plugin_5', 'the_plugin_inactive' );
		$expected           = array(
			'compatible'   => array( 'the_plugin' ),
			'incompatible' => array( 'the_plugin_3' ),
			'uncertain'    => $expected_uncertain,
		);
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox The action defined by FEATURE_ENABLED_CHANGED_ACTION is fired when the enable status of a feature changes.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $do_enable Whether to enable or disable the feature.
	 */
	public function test_feature_enable_changed_hook( $do_enable ) {
		$feature_id = null;
		$enabled    = null;

		add_action(
			FeaturesController::FEATURE_ENABLED_CHANGED_ACTION,
			function ( $f, $e ) use ( &$feature_id, &$enabled ) {
				$feature_id = $f;
				$enabled    = $e;
			},
			10,
			2
		);

		$this->sut->change_feature_enable( 'mature1', $do_enable );

		$this->assertEquals( 'mature1', $feature_id );
		$this->assertEquals( $do_enable, $enabled );
	}

	/**
	 * Simulates that the code is running inside the 'before_woocommerce_init' action.
	 */
	private function simulate_inside_before_woocommerce_init_hook() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'doing_action' => function ( $action_name ) {
					return 'before_woocommerce_init' === $action_name || doing_action( $action_name );
				},
			)
		);
	}

	/**
	 * Simulates that the code is running after the 'woocommerce_init' action has been fired.
	 */
	private function simulate_after_woocommerce_init_hook() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'did_action' => function ( $action_name ) {
					return 'woocommerce_init' === $action_name || did_action( $action_name );
				},
			)
		);
	}

	/**
	 * Helper method to disable warning when calling declare_compatibility outside of before_init hook.
	 */
	private function disable_verify_init_warning() {
		$function = null;
		$message  = null;
		$version  = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_doing_it_wrong' => function ( $f, $m, $v ) use ( &$function, &$message, &$version ) {
					$function = $f;
					$message  = $m;
					$version  = $v;
				},
			)
		);
	}

	/**
	 * @testDox No warning is generated when all plugins have declared compatibility.
	 */
	public function test_no_warning_when_all_plugin_are_hpos_compatible() {
		$this->simulate_inside_before_woocommerce_init_hook();
		// phpcs:disable Squiz.Commenting
		$fake_plugin_util = new class() extends PluginUtil {
			private $active_plugins;

			public function __construct() {
			}

			public function set_active_plugins( $plugins ) {
				$this->active_plugins = $plugins;
			}

			public function get_woocommerce_aware_plugins( bool $active_only = false ): array {
				return $this->active_plugins;
			}

			public function get_plugins_excluded_from_compatibility_ui() {
				return array();
			}
		};

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_plugin_active' => function ( $plugin ) {
					return true;
				},
			)
		);
		// phpcs:enable

		$local_sut = new FeaturesController();

		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) use ( $local_sut ) {
				$this->reset_features_list( $local_sut );

				$features = array(
					'custom_order_tables'  => array(
						'name'               => __( 'High-Performance order storage', 'woocommerce' ),
						'is_experimental'    => true,
						'enabled_by_default' => false,
					),
					'cart_checkout_blocks' => array(
						'name'            => __( 'Cart & Checkout Blocks', 'woocommerce' ),
						'description'     => __( 'Optimize for faster checkout', 'woocommerce' ),
						'is_experimental' => false,
						'disable_ui'      => true,
					),
				);

				foreach ( $features as $slug => $definition ) {
					$features_controller->add_feature_definition( $slug, $definition['name'], $definition );
				}
			},
			20
		);

		$local_sut->init( wc_get_container()->get( LegacyProxy::class ), $fake_plugin_util );
		$plugins = array( 'compatible_plugin1', 'compatible_plugin2' );
		$fake_plugin_util->set_active_plugins( $plugins );
		foreach ( $plugins as $plugin ) {
			$local_sut->declare_compatibility( 'custom_order_tables', $plugin );
			$local_sut->declare_compatibility( 'cart_checkout_blocks', $plugin );
		}

		$cot_controller   = new CustomOrdersTableController();
		$cot_setting_call = function () use ( $fake_plugin_util, $local_sut ) {
			$this->plugin_util         = $fake_plugin_util;
			$this->features_controller = $local_sut;
			$this->data_synchronizer   = wc_get_container()->get( DataSynchronizer::class );

			return $this->get_hpos_setting_for_feature();
		};
		$cot_setting      = $cot_setting_call->call( $cot_controller );
		$actual           = call_user_func( $cot_setting['disabled'] );
		$this->assertEquals( array(), $actual );

		$incompatible_plugins = function () use ( $plugins ) {
			return $this->get_incompatible_plugins( 'all', array_flip( $plugins ) );
		};
		$this->assertEmpty( $incompatible_plugins->call( $local_sut ) );
	}

	/**
	 * @testDox If there is an incompatible plugin, it is returned by get_incompatible_plugins.
	 */
	public function test_show_warning_when_a_plugin_is_not_hpos_compatible() {
		$this->simulate_inside_before_woocommerce_init_hook();
		// phpcs:disable Squiz.Commenting
		$fake_plugin_util = new class() extends PluginUtil {
			private $active_plugins;

			public function __construct() {
			}

			public function set_active_plugins( $plugins ) {
				$this->active_plugins = $plugins;
			}

			public function get_woocommerce_aware_plugins( bool $active_only = false ): array {
				return $this->active_plugins;
			}

			public function get_plugins_excluded_from_compatibility_ui() {
				return array();
			}
		};
		// phpcs:enable

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_plugin_active' => function ( $plugin ) {
					return true;
				},
			)
		);

		$local_sut = new FeaturesController();

		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) use ( $local_sut ) {
				$this->reset_features_list( $local_sut );

				$features = array(
					'custom_order_tables'  => array(
						'name'               => __( 'High-Performance order storage', 'woocommerce' ),
						'is_experimental'    => true,
						'enabled_by_default' => false,
					),
					'cart_checkout_blocks' => array(
						'name'            => __( 'Cart & Checkout Blocks', 'woocommerce' ),
						'description'     => __( 'Optimize for faster checkout', 'woocommerce' ),
						'is_experimental' => false,
						'disable_ui'      => true,
					),
				);

				foreach ( $features as $slug => $definition ) {
					$features_controller->add_feature_definition( $slug, $definition['name'], $definition );
				}
			},
			20
		);

		$local_sut->init( wc_get_container()->get( LegacyProxy::class ), $fake_plugin_util );
		$plugins = array( 'compatible_plugin', 'incompatible_plugin' );
		$fake_plugin_util->set_active_plugins( $plugins );
		$local_sut->declare_compatibility( 'custom_order_tables', 'compatible_plugin' );
		$local_sut->declare_compatibility( 'cart_checkout_blocks', 'compatible_plugin' );
		$local_sut->declare_compatibility( 'custom_order_tables', 'incompatible_plugin', false );

		$cot_controller   = new CustomOrdersTableController();
		$cot_setting_call = function () use ( $fake_plugin_util, $local_sut ) {
			$this->plugin_util         = $fake_plugin_util;
			$this->features_controller = $local_sut;
			$this->data_synchronizer   = wc_get_container()->get( DataSynchronizer::class );

			return $this->get_hpos_setting_for_feature();
		};
		$cot_setting      = $cot_setting_call->call( $cot_controller );
		$actual           = call_user_func( $cot_setting['disabled'] );
		$this->assertEquals( array( 'yes' ), $actual );

		$incompatible_plugins = function () use ( $plugins ) {
			return $this->get_incompatible_plugins( 'all', array_flip( $plugins ) );
		};
		$this->assertEquals( array( 'incompatible_plugin' ), array_keys( $incompatible_plugins->call( $local_sut ) ) );
	}
}
