<?php

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\Features\Blueprint\Init;
use Automattic\WooCommerce\Tests\Admin\Features\Blueprint\stubs\DummyExporter;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class InitTest extends MockeryTestCase {
	protected $init;

	protected function setUp(): void {
		parent::setUp();

		// Create a Mockery mock of Init class
		$this->init = Mockery::mock( Init::class )->makePartial();
	}

	public function test_get_plugins_for_export_group() {
		// Fake plugins list
		$mock_plugins = array(
			'plugin-1/plugin.php' => array( 'Name' => 'Plugin One' ),
			'plugin-2/plugin.php' => array( 'Name' => 'Plugin Two' ),
		);

		$active_plugins = array( 'plugin-1/plugin.php' );

		// Mock methods
		$this->init->shouldReceive( 'wp_get_plugins' )->andReturn( $mock_plugins );
		$this->init->shouldReceive( 'wp_get_option' )->andReturn( $active_plugins );

		// Run the function
		$result = $this->init->get_plugins_for_export_group();

		$expected = array(
			array(
				'id'      => 'plugin-1/plugin.php',
				'label'   => 'Plugin One',
				'checked' => true,
			),
			array(
				'id'      => 'plugin-2/plugin.php',
				'label'   => 'Plugin Two',
				'checked' => false,
			),
		);

		$this->assertSame( $expected, $result );
	}

	public function test_get_themes_for_export_group() {
		// Create mock themes that mimic WP_Theme
		$mock_theme_1      = $this->createMockTheme( 'theme-one', 'Theme One' );
		$mock_theme_2      = $this->createMockTheme( 'theme-two', 'Theme Two' );
		$mock_active_theme = $this->createMockTheme( 'theme-one', 'Theme One' );

		// Mock methods
		$this->init->shouldReceive( 'wp_get_themes' )->andReturn( array( $mock_theme_1, $mock_theme_2 ) );
		$this->init->shouldReceive( 'wp_get_theme' )->andReturn( $mock_active_theme );

		// Run the function
		$result = $this->init->get_themes_for_export_group();

		$expected = array(
			array(
				'id'      => 'theme-one',
				'label'   => 'Theme One',
				'checked' => true,
			),
			array(
				'id'      => 'theme-two',
				'label'   => 'Theme Two',
				'checked' => false,
			),
		);

		$this->assertSame( $expected, $result );
	}

	public function test_get_step_groups_for_js() {
		$this->init->shouldReceive( 'get_woo_exporters' )->andReturn( array( new DummyExporter() ) );

		$mock_plugins = array(
			array(
				'id'      => 'plugin-1',
				'label'   => 'Plugin One',
				'checked' => true,
			),
		);
		$mock_themes  = array(
			array(
				'id'      => 'theme-one',
				'label'   => 'Theme One',
				'checked' => true,
			),
		);

		$this->init->shouldReceive( 'get_plugins_for_export_group' )->andReturn( $mock_plugins );
		$this->init->shouldReceive( 'get_themes_for_export_group' )->andReturn( $mock_themes );

		$result = $this->init->get_step_groups_for_js();

		$expected = array(
			array(
				'id'          => 'settings',
				'description' => 'It includes all the items featured in WooCommerce | Settings.',
				'label'       => 'WooCommerce Settings',
				'icon'        => 'settings',
				'items'       => array(
					array(
						'id'          => 'dummy',
						'label'       => 'Dummy',
						'description' => 'description',
						'checked'     => true,
					),
				),
			),
			array(
				'id'          => 'plugins',
				'description' => 'It includes all the installed plugins and extensions.',
				'label'       => 'Plugins and extensions',
				'icon'        => 'plugins',
				'items'       => $mock_plugins,
			),
			array(
				'id'          => 'themes',
				'description' => 'It includes all the installed themes.',
				'label'       => 'Themes',
				'icon'        => 'brush',
				'items'       => $mock_themes,
			),
		);

		$this->assertSame( $expected, $result );
	}

	/**
	 * Helper method to create a mock WP_Theme-like object.
	 */
	private function createMockTheme( string $stylesheet, string $name ) {
		$mock_theme = Mockery::mock( 'stdClass' );
		$mock_theme->shouldReceive( 'get_stylesheet' )->andReturn( $stylesheet );
		$mock_theme->shouldReceive( 'get' )->with( 'Name' )->andReturn( $name );

		return $mock_theme;
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}
}
