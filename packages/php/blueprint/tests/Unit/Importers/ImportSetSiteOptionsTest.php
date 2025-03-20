<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportSetSiteOptions;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Mockery;
use PHPUnit\Framework\TestCase;

class ImportSetSiteOptionsTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_process_updates_options_successfully() {
		$schema = Mockery::mock();
		$schema->options = [
			'site_name' => 'My New Site',
			'admin_email' => 'admin@example.com',
		];

		$importSetSiteOptions = Mockery::mock(ImportSetSiteOptions::class)
		                               ->makePartial()
		                               ->shouldAllowMockingProtectedMethods();

		// Mock `wp_update_option` to return true for successful updates
		$importSetSiteOptions->shouldReceive('wp_update_option')
		                     ->with('site_name', 'My New Site')
		                     ->andReturn(true);
		$importSetSiteOptions->shouldReceive('wp_update_option')
		                     ->with('admin_email', 'admin@example.com')
		                     ->andReturn(true);

		$result = $importSetSiteOptions->process($schema);

		$this->assertInstanceOf(StepProcessorResult::class, $result);
		$this->assertTrue($result->is_success());

		$messages = $result->get_messages('info');
		$this->assertCount(2, $messages);
		$this->assertEquals('site_name has been updated', $messages[0]['message']);
		$this->assertEquals('admin_email has been updated', $messages[1]['message']);
	}

	public function test_process_option_already_up_to_date() {
		$schema = Mockery::mock();
		$schema->options = [
			'site_name' => 'Existing Site',
		];

		$importSetSiteOptions = Mockery::mock(ImportSetSiteOptions::class)
		                               ->makePartial()
		                               ->shouldAllowMockingProtectedMethods();

		// Mock `wp_update_option` to return false
		$importSetSiteOptions->shouldReceive('wp_update_option')
		                     ->with('site_name', 'Existing Site')
		                     ->andReturn(false);

		// Mock `wp_get_option` to return the same value
		$importSetSiteOptions->shouldReceive('wp_get_option')
		                     ->with('site_name')
		                     ->andReturn('Existing Site');

		$result = $importSetSiteOptions->process($schema);

		$this->assertInstanceOf(StepProcessorResult::class, $result);
		$this->assertTrue($result->is_success());

		$messages = $result->get_messages('info');
		$this->assertCount(1, $messages);
		$this->assertEquals('site_name has not been updated because the current value is already up to date.', $messages[0]['message']);
	}

	public function test_get_step_class() {
		$importSetSiteOptions = new ImportSetSiteOptions();

		$this->assertEquals(SetSiteOptions::class, $importSetSiteOptions->get_step_class());
	}
}
