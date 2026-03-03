<?php
/**
 * FeaturesControllerTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\Features;

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
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
			array( $this, 'register_dummy_features' ),
			11,
			1
		);

		// phpcs:disable Squiz.Commenting.FunctionComment.Missing
		$dummy_feature_registerer = new class() {
			public function add_feature_definition( $features_controller ) {
			}
		};
		// phpcs:enable Squiz.Commenting.FunctionComment.Missing
		$container = wc_get_container();
		$container->replace( CustomOrdersTableController::class, $dummy_feature_registerer );
		$container->replace( CostOfGoodsSoldController::class, $dummy_feature_registerer );

		$this->sut = new FeaturesController();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ), $this->fake_plugin_util );

		delete_option( 'woocommerce_feature_mature1_enabled' );
		delete_option( 'woocommerce_feature_mature2_enabled' );
		delete_option( 'woocommerce_feature_experimental1_enabled' );
		delete_option( 'woocommerce_feature_experimental2_enabled' );

		remove_all_filters( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION );
	}

	/**
	 * Register dummy features for unit tests.
	 *
	 * @param FeaturesController $features_controller The instance of FeaturesController to register the features in.
	 */
	public function register_dummy_features( $features_controller ) {
		$features = array(
			'mature1'       => array(
				'name'                         => 'Mature feature 1',
				'description'                  => 'The mature feature number 1',
				'is_experimental'              => false,
				'default_plugin_compatibility' => 'compatible',
			),
			'mature2'       => array(
				'name'                         => 'Mature feature 2',
				'description'                  => 'The mature feature number 2',
				'is_experimental'              => false,
				'default_plugin_compatibility' => 'compatible',
			),
			'experimental1' => array(
				'name'                         => 'Experimental feature 1',
				'description'                  => 'The experimental feature number 1',
				'is_experimental'              => true,
				'default_plugin_compatibility' => 'compatible',
			),
			'experimental2' => array(
				'name'                         => 'Experimental feature 2',
				'description'                  => 'The experimental feature number 2',
				'is_experimental'              => true,
				'default_plugin_compatibility' => 'compatible',
			),
		);

		$this->reset_features_list( $features_controller, $features );
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

			public function get_wp_plugin_id( $plugin_file ) {
				// For test fakes like 'the_plugin', return as-is (assume normalized).
				return $plugin_file;
			}
		};
		// phpcs:enable Squiz.Commenting

		// Set private $proxy via reflection (fixes null error).
		$parent_reflection = new \ReflectionClass( PluginUtil::class );
		$proxy_prop        = $parent_reflection->getProperty( 'proxy' );
		$proxy_prop->setAccessible( true );
		$proxy_prop->setValue( $this->fake_plugin_util, wc_get_container()->get( LegacyProxy::class ) );

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
	 * Resets the array of registered features and repopulates it with test features.
	 *
	 * @param FeaturesController $sut The instance of the FeaturesController class.
	 * @param array              $features The list of features to repopulate the controller with.
	 *
	 * @return void
	 */
	private function reset_features_list( $sut, $features ) {
		$reflection_class = new \ReflectionClass( $sut );

		$features_property = $reflection_class->getProperty( 'features' );
		$features_property->setAccessible( true );
		$features_property->setValue( $sut, array() );

		$compat_property = $reflection_class->getProperty( 'compatibility_info_by_feature' );
		$compat_property->setAccessible( true );
		$compat_property->setValue( $sut, array() );

		foreach ( $features as $slug => $definition ) {
			$sut->add_feature_definition( $slug, $definition['name'], $definition );
		}

		$init_compat_info = $reflection_class->getMethod( 'init_compatibility_info_by_feature' );
		$init_compat_info->setAccessible( true );
		$init_compat_info->invoke( $sut );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		remove_action(
			'woocommerce_register_feature_definitions',
			array( $this, 'register_dummy_features' ),
			11,
			1
		);
		$this->reset_container_replacements();
		$this->reset_container_resolutions();

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

		// Allow the lazy/deffered processing to happen.
		$this->sut->get_compatible_plugins_for_feature( '' );

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

		// Allow the lazy/deffered processing to happen.
		$this->sut->get_compatible_plugins_for_feature( '' );

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
		$this->ExpectExceptionMessage( esc_html( "Plugin the_plugin is trying to declare itself as incompatible with the 'mature1' feature, but it already declared itself as compatible" ) );

		$this->sut->declare_compatibility( 'mature1', 'the_plugin', true );
		$this->sut->declare_compatibility( 'mature1', 'the_plugin', false );
		// Allow the lazy/deffered processing to happen.
		$this->sut->get_compatible_plugins_for_feature( '' );
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
				$features = array(
					'mature1'       => array(
						'name'                         => 'Mature feature 1',
						'description'                  => 'The mature feature number 1',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'compatible',
					),
					'mature2'       => array(
						'name'                         => 'Mature feature 2',
						'description'                  => 'The mature feature number 2',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'compatible',
					),
					'mature3'       => array(
						'name'                         => 'Mature feature 3',
						'description'                  => 'The mature feature number 3',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'compatible',
					),
					'experimental1' => array(
						'name'                         => 'Experimental feature 1',
						'description'                  => 'The experimental feature number 1',
						'is_experimental'              => true,
						'default_plugin_compatibility' => 'compatible',
					),
					'experimental2' => array(
						'name'                         => 'Experimental feature 2',
						'description'                  => 'The experimental feature number 2',
						'is_experimental'              => true,
						'default_plugin_compatibility' => 'compatible',
					),
					'experimental3' => array(
						'name'                         => 'Experimental feature 3',
						'description'                  => 'The experimental feature number 3',
						'is_experimental'              => true,
						'default_plugin_compatibility' => 'compatible',
					),
				);

				$this->reset_features_list( $features_controller, $features );
			},
			20
		);

		$this->sut = new FeaturesController();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ), $this->fake_plugin_util );
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
	 * @testdox Deprecated features are included in 'get_compatible_features_for_plugin' results.
	 */
	public function test_deprecated_features_included_in_get_compatible_features_for_plugin() {
		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) {
				$features = array(
					'active_feature'     => array(
						'name'                         => 'Active feature',
						'description'                  => 'An active feature',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'compatible',
					),
					'deprecated_feature' => array(
						'name'                         => 'Deprecated feature',
						'description'                  => 'A deprecated feature',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'compatible',
						'deprecated_since'             => '10.5.0',
						'deprecated_value'             => true,
					),
				);

				$this->reset_features_list( $features_controller, $features );
			},
			20
		);

		$this->sut = new FeaturesController();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ), $this->fake_plugin_util );
		$this->simulate_inside_before_woocommerce_init_hook();

		$this->sut->declare_compatibility( 'active_feature', 'the_plugin', true );
		$this->sut->declare_compatibility( 'deprecated_feature', 'the_plugin', true );
		$this->reset_legacy_proxy_mocks();
		$this->simulate_after_woocommerce_init_hook();

		// Test without enabled_features_only - all features should appear.
		$result = $this->sut->get_compatible_features_for_plugin( 'the_plugin', false );

		// Both features should appear in compatible list.
		$this->assertContains( 'active_feature', $result['compatible'] );
		$this->assertContains( 'deprecated_feature', $result['compatible'] );
	}

	/**
	 * @testdox Deprecated features with deprecated_value=true are included when filtering by enabled features.
	 */
	public function test_deprecated_features_with_true_value_included_when_filtering_enabled_features() {
		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) {
				$features = array(
					'active_feature'              => array(
						'name'                         => 'Active feature',
						'description'                  => 'An active feature',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'compatible',
					),
					'deprecated_enabled_feature'  => array(
						'name'                         => 'Deprecated enabled feature',
						'description'                  => 'A deprecated feature with true value',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'compatible',
						'deprecated_since'             => '10.5.0',
						'deprecated_value'             => true,
					),
					'deprecated_disabled_feature' => array(
						'name'                         => 'Deprecated disabled feature',
						'description'                  => 'A deprecated feature with false value',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'compatible',
						'deprecated_since'             => '10.5.0',
						'deprecated_value'             => false,
					),
				);

				$this->reset_features_list( $features_controller, $features );
			},
			20
		);

		$this->sut = new FeaturesController();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ), $this->fake_plugin_util );
		$this->simulate_inside_before_woocommerce_init_hook();

		$this->sut->declare_compatibility( 'active_feature', 'the_plugin', true );
		$this->sut->declare_compatibility( 'deprecated_enabled_feature', 'the_plugin', true );
		$this->sut->declare_compatibility( 'deprecated_disabled_feature', 'the_plugin', true );
		$this->reset_legacy_proxy_mocks();
		$this->simulate_after_woocommerce_init_hook();

		update_option( 'woocommerce_feature_active_feature_enabled', 'yes' );

		// Test with enabled_features_only = true.
		$result = $this->sut->get_compatible_features_for_plugin( 'the_plugin', true );

		// Active and deprecated_enabled features should appear (deprecated_value=true).
		$this->assertContains( 'active_feature', $result['compatible'] );
		$this->assertContains( 'deprecated_enabled_feature', $result['compatible'] );
		// Deprecated with deprecated_value=false should NOT appear.
		$this->assertNotContains( 'deprecated_disabled_feature', $result['compatible'] );
		$this->assertNotContains( 'deprecated_disabled_feature', $result['incompatible'] );
		$this->assertNotContains( 'deprecated_disabled_feature', $result['uncertain'] );
	}

	/**
	 * @testdox Deprecated features can be checked in 'get_incompatible_plugins' without triggering deprecation notices.
	 */
	public function test_deprecated_features_in_get_incompatible_plugins_without_notices() {
		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) {
				$features = array(
					'active_feature'              => array(
						'name'                         => 'Active feature',
						'description'                  => 'An active feature',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'incompatible',
					),
					'deprecated_enabled_feature'  => array(
						'name'                         => 'Deprecated enabled feature',
						'description'                  => 'A deprecated feature that is considered enabled',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'incompatible',
						'deprecated_since'             => '10.5.0',
						'deprecated_value'             => true,
					),
					'deprecated_disabled_feature' => array(
						'name'                         => 'Deprecated disabled feature',
						'description'                  => 'A deprecated feature that is considered disabled',
						'is_experimental'              => false,
						'default_plugin_compatibility' => 'incompatible',
						'deprecated_since'             => '10.5.0',
						'deprecated_value'             => false,
					),
				);

				$this->reset_features_list( $features_controller, $features );
			},
			20
		);

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

			public function is_woocommerce_aware_plugin( $plugin ): bool {
				return in_array( $plugin, $this->active_plugins, true );
			}

			public function get_plugins_excluded_from_compatibility_ui() {
				return array();
			}

			public function get_wp_plugin_id( $plugin_file ) {
				return $plugin_file;
			}
		};
		// phpcs:enable Squiz.Commenting

		// Set private $proxy via reflection.
		$parent_reflection = new \ReflectionClass( PluginUtil::class );
		$proxy_prop        = $parent_reflection->getProperty( 'proxy' );
		$proxy_prop->setAccessible( true );
		$proxy_prop->setValue( $fake_plugin_util, wc_get_container()->get( LegacyProxy::class ) );

		$this->sut = new FeaturesController();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ), $fake_plugin_util );
		$this->simulate_inside_before_woocommerce_init_hook();

		$fake_plugin_util->set_active_plugins( array( 'test_plugin' ) );

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_plugin_active' => function ( $plugin ) {
					unset( $plugin );
					return true;
				},
			)
		);

		$this->reset_legacy_proxy_mocks();
		$this->simulate_after_woocommerce_init_hook();

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_plugin_active' => function ( $plugin ) {
					unset( $plugin );
					return true;
				},
			)
		);

		update_option( 'woocommerce_feature_active_feature_enabled', 'yes' );

		$incompatible_plugins = function () {
			return $this->get_incompatible_plugins( 'all', array( 'test_plugin' => array() ) );
		};
		$result               = $incompatible_plugins->call( $this->sut );

		// The method should complete without triggering deprecation notices.
		// Deprecated features with deprecated_value=true are still included in checks.
		// Deprecated features with deprecated_value=false are excluded from enabled-only checks.
		$this->assertIsArray( $result );
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
		// phpcs:disable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter.Found
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

		// Set private $proxy via reflection (fixes null error).
		$parent_reflection = new \ReflectionClass( PluginUtil::class );
		$proxy_prop        = $parent_reflection->getProperty( 'proxy' );
		$proxy_prop->setAccessible( true );
		$proxy_prop->setValue( $fake_plugin_util, wc_get_container()->get( LegacyProxy::class ) );

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_plugin_active' => function ( $plugin ) {
					return true;
				},
			)
		);
		// phpcs:enable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter.Found

		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) {
				$features = array(
					'custom_order_tables'  => array(
						'name'                         => __( 'High-Performance order storage', 'woocommerce' ),
						'is_experimental'              => true,
						'enabled_by_default'           => false,
						'default_plugin_compatibility' => 'compatible',
					),
					'cart_checkout_blocks' => array(
						'name'                         => __( 'Cart & Checkout Blocks', 'woocommerce' ),
						'description'                  => __( 'Optimize for faster checkout', 'woocommerce' ),
						'is_experimental'              => false,
						'disable_ui'                   => true,
						'default_plugin_compatibility' => 'compatible',
					),
				);

				$this->reset_features_list( $features_controller, $features );
			},
			20
		);

		$local_sut = new FeaturesController();
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
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_is_enabled True to test with HPOS enabled, false to test with HPOS disabled.
	 */
	public function test_show_warning_when_a_plugin_is_not_hpos_compatible_if_hpos_is_enabled( bool $hpos_is_enabled ) {
		$this->simulate_inside_before_woocommerce_init_hook();
		// phpcs:disable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter.Found
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
			public function get_wp_plugin_id( $plugin_file ) {
				// For test fakes like 'the_plugin', return as-is (assume normalized).
				return $plugin_file;
			}
		};

		// Set private $proxy via reflection.
		$parent_reflection = new \ReflectionClass( PluginUtil::class );
		$proxy_prop        = $parent_reflection->getProperty( 'proxy' );
		$proxy_prop->setAccessible( true );
		$proxy_prop->setValue( $fake_plugin_util, wc_get_container()->get( LegacyProxy::class ) );

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_plugin_active' => function ( $plugin ) {
					return true;
				},
			)
		);
		// phpcs:enable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter.Found

		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) {
				$features = array(
					'custom_order_tables'  => array(
						'name'                         => __( 'High-Performance order storage', 'woocommerce' ),
						'is_experimental'              => false,
						'enabled_by_default'           => false,
						'option_key'                   => CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION,
						'default_plugin_compatibility' => 'incompatible',
					),
					'cart_checkout_blocks' => array(
						'name'                         => __( 'Cart & Checkout Blocks', 'woocommerce' ),
						'description'                  => __( 'Optimize for faster checkout', 'woocommerce' ),
						'is_experimental'              => false,
						'disable_ui'                   => true,
						'default_plugin_compatibility' => 'compatible',
					),
				);

				$this->reset_features_list( $features_controller, $features );
			},
			20
		);

		$local_sut = new FeaturesController();
		$local_sut->init( wc_get_container()->get( LegacyProxy::class ), $fake_plugin_util );
		$local_sut->change_feature_enable( 'custom_order_tables', $hpos_is_enabled );
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

		$expected = $hpos_is_enabled ? array( 'incompatible_plugin' ) : array();
		$this->assertEquals( $expected, array_keys( $incompatible_plugins->call( $local_sut ) ) );
	}

	/**
	 * @testdox Declarations are queued lazily and processed only on query.
	 */
	public function test_lazy_declaration_and_processing() {
		$this->simulate_inside_before_woocommerce_init_hook();

		// Goal: Replace $this->sut's ->plugin_util with a mocked version that
		// doesn't scan the disk, but resolves fake paths for plugin1 and plugin2, and
		// checks how often get_wp_plugin_id() is called.

		// Mock PluginUtil, including methods that could introduce environmental noise.
		$plugin_util_mock = $this->getMockBuilder( PluginUtil::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_wp_plugin_id', 'get_woocommerce_aware_plugins' ) )
			->getMock();

		$plugin_util_mock->expects( $this->exactly( 2 ) ) // Called once per each file during processing.
			->method( 'get_wp_plugin_id' )
			->willReturnMap(
				array(
					array( '/path/to/plugin1.php', 'plugin1/plugin1.php' ),
					array( '/path/to/plugin2.php', 'plugin2/plugin2.php' ),
				)
			);

		$plugin_util_mock->method( 'get_woocommerce_aware_plugins' )
			->willReturn( array() ); // Mock to empty to avoid real/environmental plugins in 'uncertain'.

		// Manually set private $proxy on the mock via reflection on the parent class.
		// If we don't, the mocked PluginUtil will try to call things using ->proxy, which hasn't
		// been set, and crash.
		$parent_reflection = new \ReflectionClass( PluginUtil::class );
		$proxy_prop        = $parent_reflection->getProperty( 'proxy' );
		$proxy_prop->setAccessible( true );
		$proxy_prop->setValue( $plugin_util_mock, wc_get_container()->get( LegacyProxy::class ) );

		// Inject the mock into $sut's $plugin_util via reflection.
		$sut_reflection   = new \ReflectionClass( $this->sut );
		$plugin_util_prop = $sut_reflection->getProperty( 'plugin_util' );
		$plugin_util_prop->setAccessible( true );
		$plugin_util_prop->setValue( $this->sut, $plugin_util_mock );

		// Queue declarations without processing.
		$result1 = $this->sut->declare_compatibility( 'mature1', '/path/to/plugin1.php', true );
		$result2 = $this->sut->declare_compatibility( 'experimental1', '/path/to/plugin2.php', false );
		$this->assertTrue( $result1 );
		$this->assertTrue( $result2 );

		// Inspect pending queue - there should be 2 pending declarations.
		$pending_prop = $sut_reflection->getProperty( 'pending_declarations' );
		$pending_prop->setAccessible( true );
		$pending = $pending_prop->getValue( $this->sut );
		$this->assertCount( 2, $pending );

		$this->simulate_after_woocommerce_init_hook();

		// Query triggers processing.
		$compat = $this->sut->get_compatible_plugins_for_feature( 'mature1' );
		$this->assertEquals(
			array(
				'compatible'   => array( 'plugin1/plugin1.php' ),
				'incompatible' => array(),
				'uncertain'    => array(),
			),
			$compat
		);

		// Pending queue should be cleared after processing.
		$pending = $pending_prop->getValue( $this->sut );
		$this->assertEmpty( $pending );

		// Second query shouldn't re-process.
		$this->sut->get_compatible_plugins_for_feature( 'experimental1' );
		$pending = $pending_prop->getValue( $this->sut );
		$this->assertEmpty( $pending );
	}

	/**
	 * @testdox Conflicts are detected after lazy processing.
	 */
	public function test_lazy_conflict_detection() {
		$this->simulate_inside_before_woocommerce_init_hook();

		// Goal: Replace $this->sut's ->plugin_util with a mocked version that
		// doesn't scan the disk, but resolves fake paths for our non-existant plugin.php.
		$plugin_util_mock = $this->getMockBuilder( PluginUtil::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_wp_plugin_id' ) )
			->getMock();

		$plugin_util_mock->expects( $this->atLeastOnce() )
			->method( 'get_wp_plugin_id' )
			->willReturn( 'plugin/plugin.php' ); // All plugins resolve to the same path (we only register 1 anyway).

		// Set private $proxy on the mock via reflection on parent.
		// If we don't, the mocked PluginUtil will try to call things using ->proxy, which hasn't
		// been set, and crash.
		$parent_reflection = new \ReflectionClass( PluginUtil::class );
		$proxy_prop        = $parent_reflection->getProperty( 'proxy' );
		$proxy_prop->setAccessible( true );
		$proxy_prop->setValue( $plugin_util_mock, wc_get_container()->get( LegacyProxy::class ) );

		// Inject mock into $sut.
		$sut_reflection   = new \ReflectionClass( $this->sut );
		$plugin_util_prop = $sut_reflection->getProperty( 'plugin_util' );
		$plugin_util_prop->setAccessible( true );
		$plugin_util_prop->setValue( $this->sut, $plugin_util_mock );

		// Queue conflicting declarations (same file/path).
		$this->sut->declare_compatibility( 'mature1', '/path/to/plugin.php', true );
		$this->sut->declare_compatibility( 'mature1', '/path/to/plugin.php', false );

		$this->simulate_after_woocommerce_init_hook();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessageMatches( '/trying to declare itself as incompatible.*already declared itself as compatible/' );

		// Query triggers processing and throws on conflict.
		$this->sut->get_compatible_features_for_plugin( 'plugin/plugin.php' );
	}

	/**
	 * @testdox Deactivation clears compatibility info even after lazy processing.
	 */
	public function test_deactivation_after_lazy_processing() {
		$this->simulate_inside_before_woocommerce_init_hook();

		// Goal: Replace $this->sut's ->plugin_util with a mocked version that
		// doesn't scan the disk, but resolves fake paths for our non-existant plugin.php.
		// Also replace get_woocommerce_aware_plugins to simulate deactivation.
		$plugin_util_mock = $this->getMockBuilder( PluginUtil::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_wp_plugin_id', 'get_woocommerce_aware_plugins' ) )
			->getMock();

		$plugin_util_mock->expects( $this->atLeastOnce() )
			->method( 'get_wp_plugin_id' )
			->willReturn( 'plugin/plugin.php' );

		// Control get_woocommerce_aware_plugins to simulate before/after deactivation.
		$deactivated   = false; // Flag to toggle in callback.
		$aware_plugins = array( 'plugin/plugin.php', 'other/plugin.php' ); // Controlled list.
		$plugin_util_mock->method( 'get_woocommerce_aware_plugins' )
					->will(
						$this->returnCallback(
							function ( $active_only ) use ( &$deactivated, $aware_plugins ) {
								if ( $deactivated && $active_only ) {
									// After deactivation, exclude from active-only list.
									return array_filter(
										$aware_plugins,
										function ( $p ) {
											return 'plugin/plugin.php' !== $p;
										}
									);
								}
								// Otherwise, return full list (includes inactive if !active_only).
								return $aware_plugins;
							}
						)
					);

		// Set private $proxy on mock via parent reflection.
		// If we don't, the mocked PluginUtil will try to call things using ->proxy, which hasn't
		// been set, and crash.
		$parent_reflection = new \ReflectionClass( PluginUtil::class );
		$proxy_prop        = $parent_reflection->getProperty( 'proxy' );
		$proxy_prop->setAccessible( true );
		$proxy_prop->setValue( $plugin_util_mock, wc_get_container()->get( LegacyProxy::class ) );

		// Inject mock into sut.
		$sut_reflection   = new \ReflectionClass( $this->sut );
		$plugin_util_prop = $sut_reflection->getProperty( 'plugin_util' );
		$plugin_util_prop->setAccessible( true );
		$plugin_util_prop->setValue( $this->sut, $plugin_util_mock );

		// Queue declaration.
		$this->sut->declare_compatibility( 'mature1', '/path/to/plugin.php', true );

		$this->simulate_after_woocommerce_init_hook();

		// Trigger processing and check before deactivation.
		$compat_before = $this->sut->get_compatible_plugins_for_feature( 'mature1' );
		$this->assertContains( 'plugin/plugin.php', $compat_before['compatible'] );
		$this->assertNotContains( 'plugin/plugin.php', $compat_before['uncertain'] );

		// Simulate deactivation: set flag (for mock callback) and trigger action (to unset compatibility).
		$deactivated = true;
		do_action( 'deactivated_plugin', 'plugin/plugin.php' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		// Check after: compatibility unset, so moves to 'uncertain' (still in aware list when ! active_only).
		$compat_after = $this->sut->get_compatible_plugins_for_feature( 'mature1' );
		$this->assertNotContains( 'plugin/plugin.php', $compat_after['compatible'] );
		$this->assertContains( 'plugin/plugin.php', $compat_after['uncertain'] );
	}
}
