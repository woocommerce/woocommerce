<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportInstallPlugin;
use Automattic\WooCommerce\Blueprint\ResourceStorages;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\InstallPlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test the ImportInstallPlugin class.
 *
 * @package Automattic\WooCommerce\Blueprint\Tests\Unit\Importers
 */
class ImportInstallPluginTest extends TestCase {
	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test plugin installation when plugin is already installed.
	 *
	 * @return void
	 */
	public function test_process_skipped_installation() {
		$plugin_slug = 'already-installed-plugin';

		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->pluginData = (object) array(
			'slug'     => $plugin_slug,
			'resource' => 'wordpress.org/plugins',
		);

		$resource_storage      = Mockery::mock( ResourceStorages::class );
		$import_install_plugin = Mockery::mock( ImportInstallPlugin::class, array( $resource_storage ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$import_install_plugin->shouldReceive( 'get_installed_plugins_paths' )
			->andReturn( array( $plugin_slug => '/path/to/plugin' ) );

		$result = $import_install_plugin->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertTrue( $result->is_success() );
		$this->assertEquals( InstallPlugin::get_step_name(), $result->get_step_name() );
		$messages = $result->get_messages( 'info' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Skipped installing {$plugin_slug}. It is already installed.", $messages[0]['message'] );
	}

	/**
	 * Test plugin installation with invalid resource type.
	 *
	 * @return void
	 */
	public function test_process_invalid_resource() {
		$plugin_slug = 'invalid-resource-plugin';

		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->pluginData = (object) array(
			'slug'     => $plugin_slug,
			'resource' => 'invalid-resource',
		);

		$resource_storage = Mockery::mock( ResourceStorages::class );
		$resource_storage->shouldReceive( 'is_supported_resource' )
			->with( 'invalid-resource' )
			->andReturn( false );

		$import_install_plugin = Mockery::mock( ImportInstallPlugin::class, array( $resource_storage ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$result = $import_install_plugin->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$messages = $result->get_messages( 'info' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Skipped installing a plugin. Unsupported resource type. Only 'wordpress.org/plugins' is supported at the moment.", $messages[0]['message'] );
	}

	/**
	 * Test successful plugin installation and activation.
	 *
	 * @return void
	 */
	public function test_process_successful_installation_and_activation() {
		$plugin_slug = 'sample-plugin';

		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->pluginData = (object) array(
			'slug'     => $plugin_slug,
			'resource' => 'wordpress.org/plugins',
		);

		$schema->options = (object) array(
			'activate' => true,
		);

		$resource_storage = Mockery::mock( ResourceStorages::class );
		$resource_storage->shouldReceive( 'is_supported_resource' )
			->with( 'wordpress.org/plugins' )
			->andReturn( true );
		$resource_storage->shouldReceive( 'download' )
			->with( $plugin_slug, 'wordpress.org/plugins' )
			->andReturn( '/path/to/plugin.zip' );

		$import_install_plugin = Mockery::mock( ImportInstallPlugin::class, array( $resource_storage ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$import_install_plugin->shouldReceive( 'get_installed_plugins_paths' )
			->andReturn( array() );
		$import_install_plugin->shouldReceive( 'install' )
			->with( '/path/to/plugin.zip' )
			->andReturn( true );
		$import_install_plugin->shouldReceive( 'activate' )
			->with( $plugin_slug )
			->andReturnNull();

		$result = $import_install_plugin->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertTrue( $result->is_success() );
		$messages = $result->get_messages( 'info' );
		$this->assertCount( 2, $messages );
		$this->assertEquals( "Installed {$plugin_slug}.", $messages[0]['message'] );
		$this->assertEquals( "Activated {$plugin_slug}.", $messages[1]['message'] );
	}

	/**
	 * Test getting the step class.
	 *
	 * @return void
	 */
	public function test_get_step_class() {
		$resource_storage      = Mockery::mock( ResourceStorages::class );
		$import_install_plugin = new ImportInstallPlugin( $resource_storage );

		$this->assertEquals( InstallPlugin::class, $import_install_plugin->get_step_class() );
	}
}
