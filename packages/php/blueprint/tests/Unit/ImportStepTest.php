<?php

use Automattic\WooCommerce\Blueprint\Tests\stubs\Importers\DummyImporter;
use Automattic\WooCommerce\Blueprint\Tests\stubs\Steps\DummyStep;
use Automattic\WooCommerce\Blueprint\ImportStep;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;

/**
 * Class ImportStepTest
 */
class ImportStepTest extends \WP_UnitTestCase {

	/**
	 * Tear down Mockery after each test.
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Set up the test.
	 *
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
		$this->markTestSkipped( 'Skipping for now as it is failing due to the way the mock is created.' );
		$importer = new ImportStep( (object) array( 'step' => 'dummy' ) );
		$result   = $importer->import();
		$this->assertInstanceOf( StepProcessorResult::class, $result );
	}

	/**
	 * Test it returns warn when it cannot find valid importer.
	 *
	 * @return void
	 */
	public function test_it_returns_error_when_it_cannot_find_valid_importer() {
		$rand     = wp_rand( 1, 99999999 );
		$importer = new ImportStep( (object) array( 'step' => 'dummy' . $rand ) );
		$result   = $importer->import();

		$this->assertCount( 1, $result->get_messages( 'error' ) );
		$this->assertEquals( 'Unable to find an importer', $result->get_messages( 'error' )[0]['message'] );
	}

	/**
	 * Test it returns error when importer is not a step processor.
	 *
	 * @return void
	 */
	public function test_it_returns_error_when_importer_is_not_a_step_processor() {
		// Create a filter that adds an invalid importer (not implementing StepProcessor).
		add_filter(
			'wooblueprint_importers',
			function ( $importers ) {
				$importers[] = new class() {
					/**
					 * Get the step class name.
					 *
					 * @return string
					 */
					public function get_step_class() {
						return DummyStep::class;
					}
				};
				return $importers;
			}
		);

		$importer = new ImportStep( (object) array( 'step' => DummyStep::get_step_name() ) );
		$result   = $importer->import();

		$this->assertCount( 1, $result->get_messages( 'error' ) );
		$this->assertEquals( 'Incorrect importer type', $result->get_messages( 'error' )[0]['message'] );
	}

	/**
	 * Test it returns validation error.
	 *
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

	/**
	 * Test it returns error when step capabilities are not valid.
	 *
	 * @return void
	 */
	public function test_it_returns_error_when_step_capabilities_are_not_valid() {
		// create a user with editor role using wp native function.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );

		wp_set_current_user( $user_id );

		$importer = new ImportStep( (object) array( 'step' => 'setSiteOptions' ) );
		$result   = $importer->import();
		$this->assertNotEmpty( $result->get_messages( 'error' ) );
		$this->assertEquals( 'User does not have the required capabilities to run step', $result->get_messages( 'error' )[0]['message'] );
	}
}
