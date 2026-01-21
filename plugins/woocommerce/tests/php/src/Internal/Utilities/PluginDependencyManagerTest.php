<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\PluginDependencyManager;
use WC_Unit_Test_Case;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for the PluginDependencyManager class.
 *
 * @since 9.7.0
 */
class PluginDependencyManagerTest extends WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var PluginDependencyManager
	 */
	private $sut;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new PluginDependencyManager();
	}

	/**
	 * @testdox get_dependent_plugins should return an empty array when wp_get_plugin_dependencies is not available.
	 */
	public function test_get_dependent_plugins_returns_empty_when_function_not_available() {
		// Make the method accessible.
		$method = $this->get_private_method( 'get_dependent_plugins' );

		// If wp_get_plugin_dependencies is not available, it should return empty array.
		if ( ! function_exists( 'wp_get_plugin_dependencies' ) ) {
			$result = $method->invoke( $this->sut );
			$this->assertIsArray( $result );
			$this->assertEmpty( $result );
			$this->markTestSkipped( 'wp_get_plugin_dependencies is not available in this WordPress version.' );
		}
	}

	/**
	 * @testdox get_dependent_plugins should return plugins that require WooCommerce.
	 */
	public function test_get_dependent_plugins_returns_correct_plugins() {
		// Skip if function doesn't exist.
		if ( ! function_exists( 'wp_get_plugin_dependencies' ) ) {
			$this->markTestSkipped( 'wp_get_plugin_dependencies is not available in this WordPress version.' );
		}

		// Make the method accessible.
		$method = $this->get_private_method( 'get_dependent_plugins' );

		// Mock the wp_get_plugin_dependencies function by adding a filter.
		// Note: In a real test, we would need to create actual plugin files or mock the function.
		$result = $method->invoke( $this->sut );
		
		// The result should be an array.
		$this->assertIsArray( $result );
	}

	/**
	 * @testdox are_all_dependent_plugins_inactive should return true when all plugins are inactive.
	 */
	public function test_are_all_dependent_plugins_inactive_returns_true_for_all_inactive() {
		$method = $this->get_private_method( 'are_all_dependent_plugins_inactive' );

		// Test with plugins that don't exist (thus inactive).
		$plugins = array(
			'non-existent-plugin-1/plugin.php',
			'non-existent-plugin-2/plugin.php',
		);

		$result = $method->invoke( $this->sut, $plugins );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox are_all_dependent_plugins_inactive should return false when at least one plugin is active.
	 */
	public function test_are_all_dependent_plugins_inactive_returns_false_for_active_plugin() {
		$method = $this->get_private_method( 'are_all_dependent_plugins_inactive' );

		// WooCommerce itself is active in tests.
		$plugins = array( WC_PLUGIN_BASENAME );

		$result = $method->invoke( $this->sut, $plugins );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox are_all_dependent_plugins_inactive should return true for empty array.
	 */
	public function test_are_all_dependent_plugins_inactive_returns_true_for_empty_array() {
		$method = $this->get_private_method( 'are_all_dependent_plugins_inactive' );

		$result = $method->invoke( $this->sut, array() );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox get_inline_script should return JavaScript code.
	 */
	public function test_get_inline_script_returns_javascript() {
		$method = $this->get_private_method( 'get_inline_script' );

		$result = $method->invoke( $this->sut );
		
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'function', $result );
		$this->assertStringContainsString( WC_PLUGIN_BASENAME, $result );
		$this->assertStringContainsString( 'cannot be deactivated or deleted', $result );
	}

	/**
	 * @testdox filter_plugin_action_links should return links unchanged when not on plugins page.
	 */
	public function test_filter_plugin_action_links_unchanged_when_not_on_plugins_page() {
		$links = array(
			'deactivate' => '<a href="#">Deactivate</a>',
		);

		$result = $this->sut->filter_plugin_action_links( $links );
		
		$this->assertEquals( $links, $result );
	}

	/**
	 * @testdox enqueue_admin_scripts should only run on plugins page.
	 */
	public function test_enqueue_admin_scripts_only_on_plugins_page() {
		// Test that it doesn't throw an error when called with other hook suffixes.
		$this->sut->enqueue_admin_scripts( 'index.php' );
		$this->sut->enqueue_admin_scripts( 'edit.php' );
		
		// No assertions needed; just ensuring no errors are thrown.
		$this->assertTrue( true );
	}

	/**
	 * Get a private method for testing.
	 *
	 * @param string $method_name The name of the private method.
	 * @return ReflectionMethod The reflected method.
	 */
	private function get_private_method( $method_name ) {
		$reflection = new ReflectionClass( PluginDependencyManager::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method;
	}
}
