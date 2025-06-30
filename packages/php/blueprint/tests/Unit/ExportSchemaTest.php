<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit;

use Automattic\WooCommerce\Blueprint\BuiltInExporters;
use Automattic\WooCommerce\Blueprint\ExportSchema;
use Automattic\WooCommerce\Blueprint\Tests\stubs\Exporters\EmptySetSiteOptionsExporter;
use Automattic\WooCommerce\Blueprint\Tests\TestCase;
use Mockery;
use WP_Error;

/**
 * Class ExportSchemaTest
 */
class ExportSchemaTest extends TestCase {

	/**
	 * Get a mock of the ExportSchema class.
	 *
	 * @param boolean $partial Whether to make the mock partial.
	 *
	 * @return ExportSchema|Mockery\MockInterface&Mockery\LegacyMockInterface
	 */
	public function get_mock( $partial = false ) {
		$mock = Mock( ExportSchema::class );
		if ( $partial ) {
			$mock->makePartial();
		}

		return $mock;
	}

	/**
	 * Test that it uses exporters passed to the constructor
	 * with the built-in exporters.
	 */
	public function test_it_uses_exporters_passed_to_the_constructor() {
		$empty_exporter     = new EmptySetSiteOptionsExporter();
		$mock               = Mock( ExportSchema::class, array( array( $empty_exporter ) ) );
		$built_in_exporters = ( new BuiltInExporters() )->get_all();
		$mock->makePartial();
		// Make sure wooblueprint_exporters filter passes the empty exporter + built-in exporters.
		// and then return only the empty exporter to test that it is used.
		// We're removing the built-in exporters as some of them make network calls.
		$mock->shouldReceive( 'wp_apply_filters' )
			->with( 'wooblueprint_exporters', array_merge( array( $empty_exporter ), $built_in_exporters ) )
			->andReturn( array( $empty_exporter ) );

		$result = $mock->export();
		$this->assertCount( 1, $result['steps'] );
		$this->assertEquals( 'setSiteOptions', $result['steps'][0]['step'] );
		$this->assertEquals( (object) array(), $result['steps'][0]['options'] );
	}

	/**
	 * Test that it correctly sets landingPage value from the filter.
	 */
	public function test_wooblueprint_export_landingpage_filter() {
		$exporter = $this->get_mock( true );
		$exporter->shouldReceive( 'wp_apply_filters' )
			->with( 'wooblueprint_exporters', Mockery::any() )
			->andReturn( array() );

		$exporter->shouldReceive( 'wp_apply_filters' )
			->with( 'wooblueprint_export_landingpage', Mockery::any() )
			->andReturn( '/test' );

		$result = $exporter->export();
		$this->assertEquals( '/test', $result['landingPage'] );
	}

	/**
	 * Test that it returns a WP_Error when the landing page path is invalid.
	 */
	public function test_returns_wp_error_when_landing_page_path_is_invalid() {
		$exporter = $this->get_mock( true );
		$exporter->shouldReceive( 'wp_apply_filters' )
			->with( 'wooblueprint_export_landingpage', Mockery::any() )
			->andReturn( 'invalid-path' );

		$result = $exporter->export();
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test that it filters out exporters that are not in the list of steps to export.
	 *
	 * @return void
	 */
	public function test_it_only_uses_exporters_specified_by_steps_argment() {
		$mock = Mock(
			ExportSchema::class,
			array(
				array(
					new EmptySetSiteOptionsExporter(),
				),
			)
		);
		$mock->makePartial();

		$result = $mock->export(
			array(
				'setSiteOptions',
			)
		);

		$this->assertCount( 1, $result['steps'] );
		$this->assertEquals( 'setSiteOptions', $result['steps'][0]['step'] );
	}

	/**
	 * Test that it filters out exporters that are not instances of StepExporter.
	 *
	 * @return void
	 */
	public function test_it_filters_out_invalid_exporters() {
		$empty_exporter   = new EmptySetSiteOptionsExporter();
		$invalid_exporter = new class() {
			/**
			 * Export method that should never be called.
			 *
			 * @throws \Exception If called.
			 */
			public function export() {
				throw new \Exception( 'This method should not be called.' );
			}
		};

		$mock = Mock(
			ExportSchema::class,
			array(
				array(
					$empty_exporter,
					$invalid_exporter,
				),
			)
		);
		$mock->makePartial();

		// Mock the filter to return our test exporters.
		$mock->shouldReceive( 'wp_apply_filters' )
			->with( 'wooblueprint_exporters', Mockery::any() )
			->andReturn( array( $empty_exporter, $invalid_exporter ) );

		$result = $mock->export();

		// Should only have one step from the valid exporter.
		$this->assertCount( 1, $result['steps'] );
		$this->assertEquals( 'setSiteOptions', $result['steps'][0]['step'] );
	}

	/**
	 * Test that it returns a WP_Error when the exporter is not capable.
	 */
	public function test_it_returns_wp_error_when_exporter_is_not_capable() {
		$exporter = Mockery::mock( EmptySetSiteOptionsExporter::class );
		$exporter->makePartial();
		$exporter->shouldReceive( 'check_step_capabilities' )
			->andReturn( false );

		$mock = Mock( ExportSchema::class, array( array( $exporter ) ) );
		$mock->makePartial();

		$result = $mock->export();
		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
