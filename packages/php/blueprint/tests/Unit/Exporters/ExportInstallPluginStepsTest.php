<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Steps\Step;
use Automattic\WooCommerce\Blueprint\Tests\TestCase;

class ExportInstallPluginStepsTest extends TestCase {

	protected array $plugins = array(
		'plugina/plugina.php' => array(
			'Title' => 'plugina',
			'RequiresPlugins' => array('pluginc'),
		),
		'pluginb/pluginb.php' => array(
			'Title' => 'pluginb',
			'RequiresPlugins' => array( 'plugina' ),
		),
		'pluginc/pluginc.php' => array(
			'Title' => 'pluginc',
			'RequiresPlugins' => array(),
		),
	);

	private function get_mock() {
		$mock = Mock( ExportInstallPluginSteps::class )->makePartial();
		$mock->shouldReceive( 'wp_get_plugins' )->andReturn( $this->plugins );
		return $mock;
	}

	/**
	 * When everything is working as expected.
	 *
	 * @return void
	 */
	public function test_export() {
	    $mock = $this->get_mock();
		$mock->shouldReceive('wp_plugins_api')->andReturn((object) array(
			'download_link' => 'download_link_url',
		));

		/**
		 * @var Step[] $result The result of the export.
		 */
		$result = $mock->export();
		$this->assertCount( 3, $result );

		$slugs = array_map(fn($step) => $step->prepare_json_array()['pluginZipFile']['slug'], $result);
		$this->assertContains('plugina', $slugs);
		$this->assertContains('pluginb', $slugs);
		$this->assertContains('pluginc', $slugs);

	}

	/**
	 * When a plugin does not have a download link, it should not be included in the export.
	 * @return void
	 */
	public function test_export_does_not_include_plugins_with_unknown_download_link() {
		$mock = Mock( ExportInstallPluginSteps::class )->makePartial();

		// Return an empty object for the plugina.
		$mock->shouldReceive( 'wp_plugins_api' )->withArgs(function($method, $args) {
			if ($method === 'plugin_information' && $args['slug'] === 'plugina') {
				return true;
			}
			return false;
		})->andReturn( (object) array() );

		$mock->shouldReceive('wp_plugins_api')
			->andReturn((object) array(
				'download_link' => 'download_link_url',
			));


		$result = $mock->export();
		$this->assertCount( 2, $result );
	}
	/**
	 * Dependencies must be installed first before installing the plugins that
	 * require them. Make sure we return the dependencies first.
	 */
	public function test_it_should_return_dependencies_first() {
		$instance = new ExportInstallPluginSteps();
		$plugins = $instance->sort_plugins_by_dep($this->plugins);

		$this->assertEquals( array(
			'pluginc/pluginc.php' => array(
				'Title' => 'pluginc',
				'RequiresPlugins' => array(),
			),
			'plugina/plugina.php' => array(
				'Title' => 'plugina',
				'RequiresPlugins' => array('pluginc'),
			),
			'pluginb/pluginb.php' => array(
				'Title' => 'pluginb',
				'RequiresPlugins' => array( 'plugina' ),
			),
		), $plugins );
	}
}
