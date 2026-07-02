<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Bin;

use WC_Unit_Test_Case;

require_once __DIR__ . '/../../../../bin/check-removed-classes.php';

/**
 * Tests for the WC_Removed_Classes_Checker class (bin/check-removed-classes.php).
 */
class CheckRemovedClassesTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var \WC_Removed_Classes_Checker
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new \WC_Removed_Classes_Checker();
	}

	/**
	 * @testdox Should extract namespaced class, interface, trait and enum declarations.
	 */
	public function test_extracts_namespaced_type_declarations(): void {
		$code = '<?php
namespace Automattic\WooCommerce\Internal\Foo;

class SomeClass {}
interface SomeInterface {}
trait SomeTrait {}
enum SomeEnum: string {
	case A = "a";
}
';

		$declarations = $this->sut->extract_declarations( $code );

		$this->assertSame(
			array(
				'Automattic\WooCommerce\Internal\Foo\SomeClass',
				'Automattic\WooCommerce\Internal\Foo\SomeInterface',
				'Automattic\WooCommerce\Internal\Foo\SomeTrait',
				'Automattic\WooCommerce\Internal\Foo\SomeEnum',
			),
			$declarations
		);
	}

	/**
	 * @testdox Should extract global-namespace legacy class declarations.
	 */
	public function test_extracts_global_namespace_declarations(): void {
		$code = '<?php
class WC_Legacy_Thing extends WC_Other_Thing {
	public function noop() {}
}
';

		$this->assertSame( array( 'WC_Legacy_Thing' ), $this->sut->extract_declarations( $code ) );
	}

	/**
	 * @testdox Should ignore anonymous classes and ::class constant references.
	 */
	public function test_ignores_anonymous_classes_and_class_constants(): void {
		$code = '<?php
namespace Foo;

class RealClass {
	public function make() {
		$anonymous = new class() {
			public function noop() {}
		};
		return array( $anonymous, RealClass::class, \Bar\Other::class );
	}
}
';

		$this->assertSame( array( 'Foo\RealClass' ), $this->sut->extract_declarations( $code ) );
	}

	/**
	 * @testdox Should report a type present in the old scan but missing from the new scan.
	 */
	public function test_reports_removed_type(): void {
		$old = array(
			'Foo\KeptClass'    => 'src/KeptClass.php',
			'Foo\RemovedClass' => 'src/RemovedClass.php',
		);
		$new = array(
			'Foo\KeptClass' => 'src/KeptClass.php',
			'Foo\NewClass'  => 'src/NewClass.php',
		);

		$removals = $this->sut->find_unallowed_removals( $old, $new, array() );

		$this->assertSame( array( 'Foo\RemovedClass' => 'src/RemovedClass.php' ), $removals );
	}

	/**
	 * @testdox Should not report a type whose file moved but whose name is unchanged.
	 */
	public function test_does_not_report_moved_type(): void {
		$old = array( 'Foo\MovedClass' => 'src/Old/MovedClass.php' );
		$new = array( 'Foo\MovedClass' => 'src/New/MovedClass.php' );

		$this->assertSame( array(), $this->sut->find_unallowed_removals( $old, $new, array() ) );
	}

	/**
	 * @testdox Should not report removals that are allowlisted.
	 */
	public function test_does_not_report_allowlisted_removal(): void {
		$old = array( 'Foo\RemovedClass' => 'src/RemovedClass.php' );
		$new = array();

		$removals = $this->sut->find_unallowed_removals( $old, $new, array( 'Foo\RemovedClass' ) );

		$this->assertSame( array(), $removals );
	}

	/**
	 * @testdox Should parse allowlist files ignoring comments and blank lines.
	 */
	public function test_parses_allowlist_ignoring_comments_and_blank_lines(): void {
		$contents = "# A comment\n\nFoo\\AllowedOne\n  \\Foo\\AllowedTwo  \n# Another comment\n";

		$this->assertSame( array( 'Foo\AllowedOne', 'Foo\AllowedTwo' ), $this->sut->parse_allowlist( $contents ) );
	}

	/**
	 * @testdox Should scan a plugin-shaped directory tree and skip excluded paths.
	 */
	public function test_scans_plugin_tree_and_skips_excluded_paths(): void {
		// phpcs:disable WordPress.WP.AlternativeFunctions -- direct filesystem calls to build and clean up a temp fixture tree.
		$root = sys_get_temp_dir() . '/wc-class-check-' . uniqid();
		mkdir( $root . '/src/Deep', 0777, true );
		mkdir( $root . '/src/vendor', 0777, true );
		mkdir( $root . '/includes', 0777, true );
		file_put_contents( $root . '/src/Deep/A.php', "<?php\nnamespace Foo\\Deep;\nclass A {}\n" );
		file_put_contents( $root . '/src/vendor/B.php', "<?php\nnamespace Bundled;\nclass B {}\n" );
		file_put_contents( $root . '/includes/class-wc-legacy.php', "<?php\nclass WC_Legacy {}\n" );

		try {
			$types = $this->sut->scan( $root );
		} finally {
			unlink( $root . '/src/Deep/A.php' );
			unlink( $root . '/src/vendor/B.php' );
			unlink( $root . '/includes/class-wc-legacy.php' );
			rmdir( $root . '/src/Deep' );
			rmdir( $root . '/src/vendor' );
			rmdir( $root . '/src' );
			rmdir( $root . '/includes' );
			rmdir( $root );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions

		$this->assertSame(
			array(
				'Foo\Deep\A' => 'src/Deep/A.php',
				'WC_Legacy'  => 'includes/class-wc-legacy.php',
			),
			$types
		);
	}

	/**
	 * @testdox Should reject a scan root that is not a plugin checkout.
	 */
	public function test_scan_rejects_non_plugin_root(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->scan( sys_get_temp_dir() );
	}
}
