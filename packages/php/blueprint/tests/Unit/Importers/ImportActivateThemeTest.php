<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportActivateTheme;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivateTheme;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test the ImportActivateTheme class.
 *
 * @package Automattic\WooCommerce\Blueprint\Tests\Unit\Importers
 */
class ImportActivateThemeTest extends TestCase {
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
	 * Test successful theme activation process.
	 *
	 * @return void
	 */
	public function test_process_successful_theme_activation() {
		$theme_name = 'sample-theme';

		// Create a mock schema object.
		$schema            = Mockery::mock();
		$schema->themeName = $theme_name; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Create a partial mock of ImportActivateTheme.
		$import_activate_theme = Mockery::mock( ImportActivateTheme::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock the wp_switch_theme method.
		$import_activate_theme->shouldReceive( 'wp_switch_theme' )
			->with( $theme_name )
			->andReturn( true );

		// Execute the process method.
		$result = $import_activate_theme->process( $schema );

		// Assert the result is an instance of StepProcessorResult.
		$this->assertInstanceOf( StepProcessorResult::class, $result );

		// Assert success.
		$this->assertTrue( $result->is_success() );
		$this->assertEquals( ActivateTheme::get_step_name(), $result->get_step_name() );

		// Assert the debug message is added.
		$messages = $result->get_messages( 'debug' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Switched theme to '{$theme_name}'.", $messages[0]['message'] );
	}

	/**
	 * Test theme activation process when theme switching fails.
	 *
	 * @return void
	 */
	public function test_process_theme_activation_without_switching() {
		$theme_name = 'invalid-theme';

		// Create a mock schema object.
		$schema            = Mockery::mock();
		$schema->themeName = $theme_name; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Create a partial mock of ImportActivateTheme.
		$import_activate_theme = Mockery::mock( ImportActivateTheme::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock the wp_switch_theme method.
		$import_activate_theme->shouldReceive( 'wp_switch_theme' )
			->with( $theme_name )
			->andReturn( false );

		// Execute the process method.
		$result = $import_activate_theme->process( $schema );

		// Assert the result is an instance of StepProcessorResult.
		$this->assertInstanceOf( StepProcessorResult::class, $result );

		// Assert success because the process itself is considered successful.
		$this->assertTrue( $result->is_success() );
		$this->assertEquals( ActivateTheme::get_step_name(), $result->get_step_name() );

		// Assert there are no debug messages.
		$messages = $result->get_messages( 'debug' );
		$this->assertCount( 0, $messages );
	}

	/**
	 * Test getting the step class.
	 *
	 * @return void
	 */
	public function test_get_step_class() {
		$import_activate_theme = new ImportActivateTheme();

		// Assert the correct step class is returned.
		$this->assertEquals( ActivateTheme::class, $import_activate_theme->get_step_class() );
	}
}
