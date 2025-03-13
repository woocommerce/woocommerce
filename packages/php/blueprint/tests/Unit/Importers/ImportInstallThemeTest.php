<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportInstallTheme;
use Automattic\WooCommerce\Blueprint\ResourceStorages;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;
use Mockery;
use PHPUnit\Framework\TestCase;

class ImportInstallThemeTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_process_successful_installation_and_switching() {
		$themeSlug = 'sample-theme';

		$schema = Mockery::mock();
		$schema->themeZipFile = (object)[
			'slug' => $themeSlug,
			'resource' => 'valid-resource',
			'activate' => true,
		];

		$resourceStorage = Mockery::mock(ResourceStorages::class);
		$resourceStorage->shouldReceive('is_supported_resource')
		                ->with('valid-resource')
		                ->andReturn(true);
		$resourceStorage->shouldReceive('download')
		                ->with($themeSlug, 'valid-resource')
		                ->andReturn('/path/to/theme.zip');

		$importInstallTheme = Mockery::mock(ImportInstallTheme::class, [$resourceStorage])
		                             ->makePartial()
		                             ->shouldAllowMockingProtectedMethods();

		$importInstallTheme->shouldReceive('wp_get_themes')
		                   ->andReturn([]);
		$importInstallTheme->shouldReceive('install')
		                   ->with('/path/to/theme.zip')
		                   ->andReturn(true);
		$importInstallTheme->shouldReceive('wp_switch_theme')
		                   ->with($themeSlug)
		                   ->andReturn(true);

		$result = $importInstallTheme->process($schema);

		$this->assertInstanceOf(StepProcessorResult::class, $result);
		$this->assertTrue($result->is_success());
	}

	public function test_process_installation_failure() {
		$themeSlug = 'failed-theme';

		$schema = Mockery::mock();
		$schema->themeZipFile = (object)[
			'slug' => $themeSlug,
			'resource' => 'valid-resource',
			'activate' => false,
		];

		$resourceStorage = Mockery::mock(ResourceStorages::class);
		$resourceStorage->shouldReceive('is_supported_resource')
		                ->with('valid-resource')
		                ->andReturn(true);
		$resourceStorage->shouldReceive('download')
		                ->with($themeSlug, 'valid-resource')
		                ->andReturn('/path/to/theme.zip');

		$importInstallTheme = Mockery::mock(ImportInstallTheme::class, [$resourceStorage])
		                             ->makePartial()
		                             ->shouldAllowMockingProtectedMethods();

		$importInstallTheme->shouldReceive('wp_get_themes')
		                   ->andReturn([]);
		$importInstallTheme->shouldReceive('install')
		                   ->with('/path/to/theme.zip')
		                   ->andReturn(false);

		$result = $importInstallTheme->process($schema);

		$this->assertInstanceOf(StepProcessorResult::class, $result);
		$this->assertFalse($result->is_success());
		$errorMessages = $result->get_messages('error');
		$this->assertCount(1, $errorMessages); // Only error message
		$this->assertEquals("Failed to install theme '{$themeSlug}'.", $errorMessages[1]['message']);

	}
}
