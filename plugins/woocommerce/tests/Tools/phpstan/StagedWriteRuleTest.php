<?php
/**
 * StagedWriteRuleTest class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Tests for StagedWriteRule.
 *
 * @extends RuleTestCase<StagedWriteRule>
 */
final class StagedWriteRuleTest extends RuleTestCase {

	/**
	 * Rule under test.
	 *
	 * @return StagedWriteRule
	 */
	protected function getRule(): Rule {
		return new StagedWriteRule();
	}

	/**
	 * @testdox A stage method that does not start with refuse_unstaged_override() is reported.
	 */
	public function test_stage_method_without_leading_refusal_is_reported(): void {
		$this->assert_errors(
			'stage-refusal.php',
			array(
				array( 'woocommerce.stagedTwinRefusal', 'Method Stage_Refusal_Fixture::stage_late() must start with $this->refuse_unstaged_override().', 9 ),
				array( 'woocommerce.stagedTwinRefusal', 'Method Stage_Refusal_Fixture::stage_missing() must start with $this->refuse_unstaged_override().', 14 ),
			)
		);
	}

	/**
	 * @testdox Each kind of write outside save() is reported, and a method that only reads is not.
	 */
	public function test_writes_outside_save_are_reported(): void {
		$this->assert_errors(
			'writes.php',
			array(
				array( 'woocommerce.stagedWrite', self::write_message( 'Writes_Fixture', 'through_data_store_property' ), 4 ),
				array( 'woocommerce.stagedWrite', self::write_message( 'Writes_Fixture', 'through_loaded_data_store' ), 8 ),
				array( 'woocommerce.stagedWrite', self::write_message( 'Writes_Fixture', 'through_get_data_store' ), 13 ),
				array( 'woocommerce.stagedWrite', self::write_message( 'Writes_Fixture', 'through_wpdb' ), 17 ),
				array( 'woocommerce.stagedWrite', self::write_message( 'Writes_Fixture', 'through_function' ), 22 ),
				array( 'woocommerce.stagedWrite', self::write_message( 'Writes_Fixture', 'through_save' ), 26 ),
			)
		);
	}

	/**
	 * @testdox A saving method that calls its own stage twin is not reported, and one that calls another twin is.
	 */
	public function test_only_the_named_twin_exempts_a_saving_method(): void {
		$this->assert_errors(
			'twins.php',
			array(
				array( 'woocommerce.stagedWrite', self::write_message( 'Twins_Fixture', 'add_note' ), 28 ),
			)
		);
	}

	/**
	 * @testdox Save pipeline methods, deletes, lock methods, static methods and classes outside WC_Data are not reported.
	 */
	public function test_exempt_methods_and_classes_are_not_reported(): void {
		$this->assert_errors( 'exempt.php', array() );
	}

	/**
	 * Analyse a fixture and compare identifier, message and line of every error.
	 *
	 * @param string                          $fixture  Fixture file name in the data directory.
	 * @param array<array{string,string,int}> $expected Expected errors in line order.
	 */
	private function assert_errors( string $fixture, array $expected ): void {
		$errors = $this->gatherAnalyserErrors( array( __DIR__ . '/data/' . $fixture ) );

		$actual = array_map( fn( $error ) => array( $error->getIdentifier(), $error->getMessage(), $error->getLine() ), $errors );
		usort( $actual, fn( $a, $b ) => $a[2] <=> $b[2] );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Message of a woocommerce.stagedWrite error.
	 *
	 * @param string $class_name  Class name.
	 * @param string $method_name Method name.
	 * @return string
	 */
	private static function write_message( string $class_name, string $method_name ): string {
		return sprintf( 'Method %s::%s() writes outside save(). Move the in-memory change to a stage_*() twin, call it here, then save. See WC_Order::stage_status().', $class_name, $method_name );
	}
}
