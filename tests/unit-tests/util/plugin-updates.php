<?php
/**
 * File for the WC_Tests_Plugin_Updates class.
 *
 * @package WooCommerce\Tests\Util
 */

/**
 * Class WC_Plugin_Updates.
 * @package WooCommerce\Tests\Util
 * @since 3.2.0
 */
class WC_Tests_Plugin_Updates extends WC_Unit_Test_Case {

	/** @var WC_Plugin_Updates instance */
	protected $updates;

	/** @var array of plugin data for testing*/
	protected $plugins = array();

	/**
	 * Setup test.
	 *
	 * @since 3.2.0
	 */
	public function setUp() {
		parent::setUp();

		if ( ! class_exists( 'WC_Plugin_Updates' ) ) {
			$bootstrap = WC_Unit_Tests_Bootstrap::instance();
			include_once $bootstrap->plugin_dir . '/includes/admin/plugin-updates/class-wc-plugin-updates.php';
		}

		$this->updates = new WC_Plugin_Updates();
		$this->plugins = array();

		add_filter( 'woocommerce_get_plugins_with_header', array( $this, 'populate_untested_plugins' ), 10, 2 );
	}

	/**
	 * Allow this test suite to easily define plugin results to test for the version tested header.
	 *
	 * @param array  $plugins array of plugin data in same format as get_plugins.
	 * @param string $header plugin header results matched on.
	 * @return array modified $plugins.
	 * @since 3.2.0
	 */
	public function populate_untested_plugins( $plugins, $header ) {
		if ( WC_Plugin_Updates::VERSION_TESTED_HEADER === $header && ! empty( $this->plugins ) ) {
			$plugins = $this->plugins;
			update_option( 'active_plugins', array_keys( $this->plugins ) );
		}
		return $plugins;
	}

	/**
	 * Test WC_Plugin_Updates::get_untested_plugins with a variety of tested plugins for major release.
	 *
	 * @since 3.2.0
	 */
	public function test_get_untested_plugins_major_good() {
		$release = 'major';

		$this->plugins = array(
			'test/test.php'   => array(
				'Name'                                   => 'Test plugin',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4.0.0',
			),
			'test2/test2.php' => array(
				'Name'                                   => 'Test plugin 2',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '5.0',
			),
			'test3/test3.php' => array(
				'Name'                                   => 'Test plugin 3',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4.1.0',
			),
			'test4/test4.php' => array(
				'Name'                                   => 'Test plugin 4',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4.0.1',
			),
		);
		$new_version   = '4.0.0';
		$untested      = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertArrayNotHasKey( 'test/test.php', $untested );
		$this->assertArrayNotHasKey( 'test2/test2.php', $untested );
		$this->assertArrayNotHasKey( 'test3/test3.php', $untested );
		$this->assertArrayNotHasKey( 'test4/test4.php', $untested );

		$new_version = '3.9.0';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertArrayNotHasKey( 'test/test.php', $untested );
		$this->assertArrayNotHasKey( 'test2/test2.php', $untested );
		$this->assertArrayNotHasKey( 'test3/test3.php', $untested );
		$this->assertArrayNotHasKey( 'test4/test4.php', $untested );

		$new_version = '4.3.0';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertArrayNotHasKey( 'test/test.php', $untested );
		$this->assertArrayNotHasKey( 'test2/test2.php', $untested );
		$this->assertArrayNotHasKey( 'test3/test3.php', $untested );
		$this->assertArrayNotHasKey( 'test4/test4.php', $untested );

		$new_version = '4.0.2';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertArrayNotHasKey( 'test/test.php', $untested );
		$this->assertArrayNotHasKey( 'test2/test2.php', $untested );
		$this->assertArrayNotHasKey( 'test3/test3.php', $untested );
		$this->assertArrayNotHasKey( 'test4/test4.php', $untested );
	}

	/**
	 * Test WC_Plugin_Updates::get_untested_plugins with a variety of untested plugins for major release.
	 *
	 * @since 3.2.0
	 */
	public function test_get_untested_plugins_major_bad() {
		$release = 'major';
		$current_version_parts = explode( '.', WC_VERSION );
		$current_major_version = $current_version_parts[0];

		$this->plugins = array(
			'test/test.php'   => array(
				'Name'                                   => 'Test plugin',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => $current_major_version . '.0.0',
			),
			'test2/test2.php' => array(
				'Name'                                   => 'Test plugin 2',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => $current_major_version . '.9.9',
			),
			'test3/test3.php' => array(
				'Name'                                   => 'Test plugin 3',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => $current_major_version . '.0',
			),
		);
		$plugin_keys   = array_keys( $this->plugins );

		// current: X.Y.Z, new: (X+1).0.
		// as current < new, all plugins are untested.
		$new_version = ( $current_major_version + 1 ) . '.0.0';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertEquals( $plugin_keys, array_intersect( $plugin_keys, array_keys( $untested ) ) );

		// current: X.Y.Z, new: (X+1).3.0.
		// as current < new, all plugins are untested.
		$new_version = ( $current_major_version + 1 ) . '.3.0';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertEquals( $plugin_keys, array_intersect( $plugin_keys, array_keys( $untested ) ) );

		// current: X.Y.Z, new: (X+1).0.2.
		// as current < new, all plugins are untested.
		$new_version = ( $current_major_version + 1 ) . '.0.2';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertEquals( $plugin_keys, array_intersect( $plugin_keys, array_keys( $untested ) ) );
	}

	/**
	 * Test WC_Plugin_Updates::get_untested_plugins with a variety of tested plugins for minor release.
	 *
	 * @since 3.2.0
	 */
	public function test_get_untested_plugins_minor_good() {
		$release = 'minor';

		$this->plugins = array(
			'test/test.php'   => array(
				'Name'                                   => 'Test plugin',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4.1.0',
			),
			'test2/test2.php' => array(
				'Name'                                   => 'Test plugin 2',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '5.0.0',
			),
			'test3/test3.php' => array(
				'Name'                                   => 'Test plugin 3',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4.1.1',
			),
			'test4/test4.php' => array(
				'Name'                                   => 'Test plugin 4',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4.2.1',
			),
		);
		$new_version   = '4.1.0';
		$untested      = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertArrayNotHasKey( 'test/test.php', $untested );
		$this->assertArrayNotHasKey( 'test2/test2.php', $untested );
		$this->assertArrayNotHasKey( 'test3/test3.php', $untested );
		$this->assertArrayNotHasKey( 'test4/test4.php', $untested );

		$new_version = '4.2.0';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertArrayHasKey( 'test/test.php', $untested );
		$this->assertArrayNotHasKey( 'test2/test2.php', $untested );
		$this->assertArrayHasKey( 'test3/test3.php', $untested );
		$this->assertArrayNotHasKey( 'test4/test4.php', $untested );

		$new_version = '4.1.5';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertArrayNotHasKey( 'test/test.php', $untested );
		$this->assertArrayNotHasKey( 'test2/test2.php', $untested );
		$this->assertArrayNotHasKey( 'test3/test3.php', $untested );
		$this->assertArrayNotHasKey( 'test4/test4.php', $untested );
	}

	/**
	 * Test WC_Plugin_Updates::get_untested_plugins with a variety of untested plugins for minor release.
	 *
	 * @since 3.2.0
	 */
	public function test_get_untested_plugins_minor_bad() {
		$release = 'minor';

		$this->plugins = array(
			'test/test.php'   => array(
				'Name'                                   => 'Test plugin',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4.1.0',
			),
			'test2/test2.php' => array(
				'Name'                                   => 'Test plugin 2',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '3.9.0',
			),
			'test3/test3.php' => array(
				'Name'                                   => 'Test plugin 3',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4.2',
			),
		);
		$plugin_keys   = array_keys( $this->plugins );

		$new_version = '4.3.0';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertEquals( $plugin_keys, array_intersect( $plugin_keys, array_keys( $untested ) ) );

		$new_version = '4.3.1';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertEquals( $plugin_keys, array_intersect( $plugin_keys, array_keys( $untested ) ) );

		$new_version = '4.1.0';
		$this->assertArrayHasKey( 'test2/test2.php', $this->updates->get_untested_plugins( $new_version, $release ) );

		$new_version = '4.1.5';
		$this->assertArrayHasKey( 'test2/test2.php', $this->updates->get_untested_plugins( $new_version, $release ) );
	}

	/**
	 * Test WC_Plugin_Updates::get_untested_plugins with a variety of incorrect values for the header.
	 * Generally headers with incorrectly formatted values should be ignored, but mostly errors should be avoided.
	 *
	 * @since 3.2.0
	 */
	public function test_get_untested_plugins_malformed() {
		$release = 'minor';

		$this->plugins = array(
			'test/test.php'   => array(
				'Name'                                   => 'Test plugin',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => '4',
			),
			'test2/test2.php' => array(
				'Name'                                   => 'Test plugin 2',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => 'Latest release',
			),
			'test3/test3.php' => array(
				'Name'                                   => 'Test plugin 3',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => 'WC 3.0.0',
			),
			'test4/test4.php' => array(
				'Name'                                   => 'Test plugin 4',
				WC_Plugin_Updates::VERSION_TESTED_HEADER => ' ',
			),
		);

		$release     = 'major';
		$new_version = '5.0.0';
		$this->assertArrayHasKey( 'test/test.php', $this->updates->get_untested_plugins( $new_version, $release ) );

		$release     = 'minor';
		$new_version = '4.1.0';
		$untested    = $this->updates->get_untested_plugins( $new_version, $release );
		$this->assertArrayNotHasKey( 'test/test.php', $untested );
		$this->assertArrayNotHasKey( 'test2/test2.php', $untested );
		$this->assertArrayNotHasKey( 'test3/test3.php', $untested );
		$this->assertArrayNotHasKey( 'test4/test4.php', $untested );
	}
}
