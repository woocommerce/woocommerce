<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportInstallTheme;
use Automattic\WooCommerce\Blueprint\ResourceStorages;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test the ImportInstallTheme class.
 *
 * @package Automattic\WooCommerce\Blueprint\Tests\Unit\Importers
 */
class ImportInstallThemeTest extends TestCase {
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
	 * Test successful theme installation and switching.
	 *
	 * @return void
	 */
	public function test_process_successful_installation_and_switching() {
		$theme_slug = 'sample-theme';

		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->themeData = (object) array(
			'slug'     => $theme_slug,
			'resource' => 'valid-resource',
			'activate' => true,
		);

		$resource_storage = Mockery::mock( ResourceStorages::class );
		$resource_storage->shouldReceive( 'is_supported_resource' )
			->with( 'valid-resource' )
			->andReturn( true );
		$resource_storage->shouldReceive( 'download' )
			->with( $theme_slug, 'valid-resource' )
			->andReturn( '/path/to/theme.zip' );

		$import_install_theme = Mockery::mock( ImportInstallTheme::class, array( $resource_storage ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$import_install_theme->shouldReceive( 'wp_get_themes' )
			->andReturn( array() );
		$import_install_theme->shouldReceive( 'install' )
			->with( '/path/to/theme.zip' )
			->andReturn( true );
		$import_install_theme->shouldReceive( 'wp_switch_theme' )
			->with( $theme_slug )
			->andReturn( true );

		$result = $import_install_theme->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertTrue( $result->is_success() );
	}

	/**
	 * Test theme installation failure.
	 *
	 * @return void
	 */
	public function test_process_installation_failure() {
		$theme_slug = 'failed-theme';

		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->themeData = (object) array(
			'slug'     => $theme_slug,
			'resource' => 'wordpress.org/themes',
			'activate' => false,
		);

		$resource_storage = Mockery::mock( ResourceStorages::class );
		$resource_storage->shouldReceive( 'is_supported_resource' )
			->with( 'wordpress.org/themes' )
			->andReturn( true );
		$resource_storage->shouldReceive( 'download' )
			->with( $theme_slug, 'wordpress.org/themes' )
			->andReturn( '/path/to/theme.zip' );

		$import_install_theme = Mockery::mock( ImportInstallTheme::class, array( $resource_storage ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$import_install_theme->shouldReceive( 'wp_get_themes' )
			->andReturn( array() );
		$import_install_theme->shouldReceive( 'install' )
			->with( '/path/to/theme.zip' )
			->andReturn( false );

		$result = $import_install_theme->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertFalse( $result->is_success() );
		$error_messages = $result->get_messages( 'error' );
		$this->assertCount( 1, $error_messages ); // Only error message.
		$this->assertEquals( "Failed to install theme '{$theme_slug}'.", $error_messages[1]['message'] );
	}
}
