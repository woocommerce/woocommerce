<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportActivateTheme;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivateTheme;
use Mockery;
use PHPUnit\Framework\TestCase;

class ImportActivateThemeTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_process_successful_theme_activation() {
		$themeName = 'sample-theme';

		// Create a mock schema object
		$schema = Mockery::mock();
		$schema->themeName = $themeName;

		// Create a partial mock of ImportActivateTheme
		$importActivateTheme = Mockery::mock(ImportActivateTheme::class)
		                              ->makePartial()
		                              ->shouldAllowMockingProtectedMethods();

		// Mock the wp_switch_theme method
		$importActivateTheme->shouldReceive('wp_switch_theme')
		                    ->with($themeName)
		                    ->andReturn(true);

		// Execute the process method
		$result = $importActivateTheme->process($schema);

		// Assert the result is an instance of StepProcessorResult
		$this->assertInstanceOf(StepProcessorResult::class, $result);

		// Assert success
		$this->assertTrue($result->is_success());
		$this->assertEquals(ActivateTheme::get_step_name(), $result->get_step_name());

		// Assert the debug message is added
		$messages = $result->get_messages('debug');
		$this->assertCount(1, $messages);
		$this->assertEquals("Switched theme to '{$themeName}'.", $messages[0]['message']);
	}

	public function test_process_theme_activation_without_switching() {
		$themeName = 'invalid-theme';

		// Create a mock schema object
		$schema = Mockery::mock();
		$schema->themeName = $themeName;

		// Create a partial mock of ImportActivateTheme
		$importActivateTheme = Mockery::mock(ImportActivateTheme::class)
		                              ->makePartial()
		                              ->shouldAllowMockingProtectedMethods();

		// Mock the wp_switch_theme method
		$importActivateTheme->shouldReceive('wp_switch_theme')
		                    ->with($themeName)
		                    ->andReturn(false);

		// Execute the process method
		$result = $importActivateTheme->process($schema);

		// Assert the result is an instance of StepProcessorResult
		$this->assertInstanceOf(StepProcessorResult::class, $result);

		// Assert success because the process itself is considered successful
		$this->assertTrue($result->is_success());
		$this->assertEquals(ActivateTheme::get_step_name(), $result->get_step_name());

		// Assert there are no debug messages
		$messages = $result->get_messages('debug');
		$this->assertCount(0, $messages);
	}

	public function test_get_step_class() {
		$importActivateTheme = new ImportActivateTheme();

		// Assert the correct step class is returned
		$this->assertEquals(ActivateTheme::class, $importActivateTheme->get_step_class());
	}
}
