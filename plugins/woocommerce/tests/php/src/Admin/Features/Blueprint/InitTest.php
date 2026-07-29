<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\Features\Blueprint\Init;
use Automattic\WooCommerce\Tests\Admin\Features\Blueprint\Stubs\DummyExporter;
use Automattic\WooCommerce\Tests\Admin\Features\Blueprint\Stubs\ThemeStub;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * Class InitTest
 *
 * @package Automattic\WooCommerce\Tests\Admin\Features\Blueprint
 */
class InitTest extends MockeryTestCase {
	/**
	 * The Init instance.
	 *
	 * @var Mockery\MockInterface
	 */
	protected $init;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create a Mockery mock of Init class.
		$this->init = Mockery::mock( Init::class )->makePartial();
	}

	/**
	 * Test the get_plugins_for_export_group method.
	 */
	public function test_get_plugins_for_export_group() {
		// Fake plugins list.
		$mock_plugins = array(
			'plugin-1/plugin.php' => array( 'Name' => 'Plugin One' ),
			'plugin-2/plugin.php' => array( 'Name' => 'Plugin Two' ),
		);

		$active_plugins = array( 'plugin-1/plugin.php' );

		$plugins_api = (object) array(
			'plugin-1' => array( 'Name' => 'Plugin One' ),
			'plugin-2' => array( 'Name' => 'Plugin Two' ),
		);

		// Mock methods.
		$this->init->shouldReceive( 'wp_get_plugins' )->andReturn( $mock_plugins );
		$this->init->shouldReceive( 'wp_get_option' )->andReturn( $active_plugins );
		$this->init->shouldReceive( 'wp_plugins_api' )->andReturn( $plugins_api );

		// Run the function.
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

	/**
	 * Test the get_themes_for_export_group method.
	 */
	public function test_get_themes_for_export_group() {
		// Create mock themes that mimic WP_Theme.
		$mock_theme_1      = $this->createThemeStub( 'theme-one', 'Theme One' );
		$mock_theme_2      = $this->createThemeStub( 'theme-two', 'Theme Two' );
		$mock_theme_3      = $this->createThemeStub( 'custom-theme', 'Custom Theme' );
		$mock_active_theme = $this->createThemeStub( 'theme-one', 'Theme One' );
		$themes_api        = (object) array(
			'theme-one' => array( 'download_link' => 'https://example.com/theme-one.zip' ),
			'theme-two' => array( 'download_link' => 'https://example.com/theme-two.zip' ),
		);

		// Mock methods.
		$this->init->shouldReceive( 'wp_get_themes' )->andReturn(
			array(
				'theme-one'    => $mock_theme_1,
				'theme-two'    => $mock_theme_2,
				'custom-theme' => $mock_theme_3,
			)
		);
		$this->init->shouldReceive( 'wp_get_theme' )->andReturn( $mock_active_theme );
		$this->init
			->shouldReceive( 'wp_themes_api' )
			->once()
			->with(
				'theme_information',
				Mockery::on(
					function ( $args ) {
						return array( 'theme-one', 'theme-two', 'custom-theme' ) === $args['slugs'];
					}
				)
			)
			->andReturn( $themes_api );

		// Run the function.
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

	/**
	 * Test the get_step_groups_for_js method.
	 */
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
				'description' => 'Includes all the items featured in WooCommerce | Settings.',
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
				'description' => 'Includes all the installed plugins.',
				'label'       => 'Plugins',
				'icon'        => 'plugins',
				'items'       => $mock_plugins,
			),
			array(
				'id'          => 'themes',
				'description' => 'Includes all the installed themes.',
				'label'       => 'Themes',
				'icon'        => 'layout',
				'items'       => $mock_themes,
			),
		);

		$this->assertSame( $expected, $result );
	}

	/**
	 * Helper method to create a WP_Theme-like object.
	 *
	 * @param string $stylesheet The stylesheet of the theme.
	 * @param string $name The name of the theme.
	 *
	 * @return ThemeStub The theme object.
	 */
	private function createThemeStub( string $stylesheet, string $name ): ThemeStub {
		return new ThemeStub( $stylesheet, $name );
	}

	/**
	 * Tear down the test.
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();

		delete_transient( $this->init::INSTALLED_WP_ORG_PLUGINS_TRANSIENT );
		delete_transient( $this->init::INSTALLED_WP_ORG_THEMES_TRANSIENT );
	}
}
