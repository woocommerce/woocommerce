<?php

use Automattic\WooCommerce\Blueprint\Tests\stubs\Importers\DummyImporter;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\Blueprint\ImportStep;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;

/**
 * Class ImportStepTest
 */
class ImportStepTest extends TestCase {

	/**
	 * Tear down Mockery after each test.
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter(
			'wooblueprint_importers',
			function ( $importers ) {
				$importers[] = new DummyImporter();
				return $importers;
			}
		);
	}

	/**
	 * Test import when everything is valid.
	 *
	 * @return void
	 */
	public function test_import() {
		$importer = new ImportStep( (object) array( 'step' => 'dummy' ) );
		$result   = $importer->import();
		$this->assertInstanceOf( StepProcessorResult::class, $result );
	}

	/**
	 * @return void
	 */
	public function test_it_returns_warn_when_it_cannot_find_valid_importer() {
		$rand     = wp_rand( 1, 99999999 );
		$importer = new ImportStep( (object) array( 'step' => 'dummy' . $rand ) );
		$result   = $importer->import();

		$this->assertCount( 1, $result->get_messages( 'warn' ) );
		$this->assertEquals( 'Unable to find an importer for dummy' . $rand, $result->get_messages( 'warn' )[0]['message'] );
	}

	/**
	 * @return void
	 */
	public function test_it_returns_validation_error() {
		// Pass 'invalidProp', which is not a valid property for the schema.
		$importer = new ImportStep(
			(object) array(
				'step'        => 'dummy',
				'invalidProp' => false,
			)
		);
		$result   = $importer->import();
		$this->assertNotEmpty( $result->get_messages( 'error' ) );
		$this->assertEquals( 'Schema validation failed for step dummy', $result->get_messages( 'error' )[0]['message'] );
	}
}
