<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\RemoteFreeExtensions;

use Mockery;
use WC_Unit_Test_Case;
use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\ProcessCoreProfilerPluginInstallOptions;

/**
 * Class ProcessCoreProfilerPluginInstallOptionsTest
 */
class ProcessCoreProfilerPluginInstallOptionsTest extends WC_Unit_Test_Case {

	/**
	 * Test that get_install_options returns the correct options.
	 * @return void
	 */
	public function test_get_install_options_returns_correct_options() {
		$plugins = array(
			(object) array(
				'key'             => 'test-plugin:some-extra',
				'install_options' => array( 'option1' => 'value1' ),
			),
		);

		$instance = new ProcessCoreProfilerPluginInstallOptions( $plugins, 'test-plugin' );

		$this->assertEquals( array( 'option1' => 'value1' ), $instance->get_install_options( 'test-plugin' ) );
	}

	/**
	 * Test that get_install_options returns null when the plugin is not found.
	 * @return void
	 */
	public function test_get_install_options_returns_null_when_plugin_not_found() {
		$plugins = array(
			(object) array(
				'key'             => 'different-plugin:extra',
				'install_options' => array( 'option1' => 'value1' ),
			),
		);

		$instance = new ProcessCoreProfilerPluginInstallOptions( $plugins, 'test-plugin' );

		$this->assertNull( $instance->get_install_options( 'test-plugin' ) );
	}

	/**
	 * Test that matches_plugin_slug returns true when the plugin slug matches.
	 * @return void
	 */
	public function test_matches_plugin_slug_returns_true_when_matching() {
		$plugin = (object) array( 'key' => 'my-plugin:extra-info' );

		$instance = new ProcessCoreProfilerPluginInstallOptions( array(), 'my-plugin' );

		$reflection = new \ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'matches_plugin_slug' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $instance, $plugin, 'my-plugin' ) );
	}

	/**
	 * Test that matches_plugin_slug returns false when the plugin slug does not match.
	 * @return void
	 */
	public function test_matches_plugin_slug_returns_false_when_not_matching() {
		$plugin = (object) array( 'key' => 'different-plugin:extra-info' );

		$instance = new ProcessCoreProfilerPluginInstallOptions( array(), 'my-plugin' );

		$reflection = new \ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'matches_plugin_slug' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $instance, $plugin, 'my-plugin' ) );
	}

	/**
	 * Test that add_option calls the correct function.
	 * @return void
	 */
	public function test_add_option_calls_add_option_function() {
		$mock = Mockery::mock( ProcessCoreProfilerPluginInstallOptions::class )
						->makePartial()
						->shouldAllowMockingProtectedMethods();

		$mock->shouldReceive( 'add_option' )
			->once()
			->with( 'test_option', 'test_value', 'yes' );

		$reflection = new \ReflectionClass( $mock );
		$method     = $reflection->getMethod( 'add_option' );
		$method->setAccessible( true );

		$method->invoke( $mock, 'test_option', 'test_value', 'yes' );
		$this->assertTrue( true );
	}

	/**
	 * Test that 'install_options' does not change existing options.
	 * It can only add new options.
	 *
	 * @return void
	 */
	public function test_it_does_not_change_existing_options() {
		$plugins = array(
			(object) array(
				'key'             => 'test-plugin',
				'install_options' => array(
					(object) array(
						'name'  => 'test-option',
						'value' => 'new-value',
					),
				),
			),
		);

		$instance = new ProcessCoreProfilerPluginInstallOptions( $plugins, 'test-plugin' );
		$instance->process_install_options();

		$this->assertEquals( 'new-value', get_option( 'test-option' ) );

		$plugins[0]->install_options[0]->value = 'changed-value';
		$instance                              = new ProcessCoreProfilerPluginInstallOptions( $plugins, 'test-plugin' );
		$instance->process_install_options();

		// The value should not change.
		$this->assertEquals( 'new-value', get_option( 'test-option' ) );
	}

	/**
	 * Test that disallowed options are not added and an error is logged.
	 *
	 * @return void
	 */
	public function test_disallowed_option_is_not_added_and_logs_error() {
		$disallowed_option = 'siteurl';
		$option_value = 'should-not-be-added';

		$logger = Mockery::mock( \WC_Logger_Interface::class );
		$logger->shouldReceive( 'error' )
				->once()
				->with( 'Disallowed option: ' . $disallowed_option );

		$plugins = array(
			(object) array(
				'key'             => 'test-plugin',
				'install_options' => array(
					(object) array(
						'name'  => $disallowed_option,
						'value' => $option_value,
					),
				),
			),
		);

		$instance = new ProcessCoreProfilerPluginInstallOptions( $plugins, 'test-plugin', $logger );
		$instance->process_install_options();

		$this->assertNotEquals( $option_value, get_option( $disallowed_option ) );
	}
}
