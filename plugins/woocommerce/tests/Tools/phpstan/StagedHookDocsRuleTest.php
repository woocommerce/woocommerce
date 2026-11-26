<?php
/**
 * StagedHookDocsRuleTest class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use SzepeViktor\PHPStan\WordPress\HookDocBlock;

/**
 * Tests for StagedHookDocsRule.
 *
 * @extends RuleTestCase<StagedHookDocsRule>
 */
final class StagedHookDocsRuleTest extends RuleTestCase {

	/**
	 * Rule under test.
	 *
	 * @return StagedHookDocsRule
	 */
	protected function getRule(): Rule {
		return new StagedHookDocsRule( self::getContainer()->getByType( HookDocBlock::class ), self::getContainer()->getByType( RuleLevelHelper::class ) );
	}

	/**
	 * Config files with the docblock reader of phpstan-wordpress.
	 *
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array {
		return array( __DIR__ . '/data/staged-hooks.neon' );
	}

	/**
	 * @testdox The docblock of a do_staged_action() or apply_staged_filters() call is checked with $staged as its last argument.
	 */
	public function test_wrapper_docblocks_are_checked_with_staged(): void {
		$this->analyse(
			array( __DIR__ . '/data/staged-hooks.php' ),
			array(
				array( 'Expected 3 @param tags, found 2.', 20 ),
				array( '@param int $flag does not accept actual type of parameter: bool.', 29 ),
			)
		);
	}
}
