<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit;

use Automattic\WooCommerce\Blueprint\BuiltInExporters;
use Automattic\WooCommerce\Blueprint\ExportSchema;
use Automattic\WooCommerce\Blueprint\Tests\stubs\Exporters\EmptySetSiteOptionsExporter;
use Automattic\WooCommerce\Blueprint\Tests\TestCase;
use Mockery;
use Mockery\Mock;

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
		$this->assertEquals( array(), $result['steps'][0]['options'] );
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
			->andReturn( 'test' );

		$result = $exporter->export();
		$this->assertEquals( 'test', $result['landingPage'] );
	}

	/**
	 * Test that it uses the exporters from the filter.
	 *
	 * @return void
	 */
	public function test_wooblueprint_exporters_filter() {
	}

	/**
	 * Test that it filters out exporters that are not in the list of steps to export.
	 *
	 * @return void
	 */
	public function test_it_only_uses_exporters_specified_by_steps_argment() {
	}

	/**
	 * Test that it calls include_private_plugins method on ExportInstallPluginSteps when
	 * exporting a zip schema.
	 *
	 * @return void
	 */
	public function test_it_calls_include_private_plugins_for_zip_export() {
	}
}
