<?php
/**
 * Behavior contracts for code transformations.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Tools\CodeHacking;

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\BypassFinalsHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\CodeHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;
use ParseError;
use ReflectionProperty;
use RuntimeException;
use WC_Unit_Test_Case;

require_once WC_ABSPATH . 'tests/php/helpers/class-wc-code-hacker-transform-fixture.php';

/** Preserve source invalidation and current callback dispatch across transformations. */
class CodeHackTransformationTest extends WC_Unit_Test_Case {
	/** @var FunctionsMockerHack|null Original singleton registered by the suite bootstrap. */
	private $original_function_hack;

	/** @var StaticMockerHack|null Original singleton registered by the suite bootstrap. */
	private $original_static_hack;

	/**
	 * The System Under Test.
	 *
	 * @var CodeHack
	 */
	private $sut;

	/** Configure isolated allowlists after the base resets the registered suite mocks. */
	public function setUp(): void {
		parent::setUp();
		$this->original_function_hack = FunctionsMockerHack::get_hack_instance();
		$this->original_static_hack   = StaticMockerHack::get_hack_instance();
		FunctionsMockerHack::initialize( array( 'strtoupper' ) );
		StaticMockerHack::initialize( array( 'WC_Code_Hacker_Transform_Fixture' ) );
	}

	/** Restore the exact registered singleton objects, including their original configuration. */
	public function tearDown(): void {
		try {
			self::restore_singleton( FunctionsMockerHack::class, $this->original_function_hack );
			self::restore_singleton( StaticMockerHack::class, $this->original_static_hack );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Restore the singleton registered with the source stream wrapper.
	 *
	 * @param class-string  $class_name Mocker class.
	 * @param CodeHack|null $original Original singleton.
	 */
	private static function restore_singleton( string $class_name, $original ): void {
		$instance = new ReflectionProperty( $class_name, 'instance' );
		if ( PHP_VERSION_ID < 80100 ) {
			$instance->setAccessible( true );
		}
		$instance->setValue( null, $original );
	}

	/**
	 * Select a built-in transformation.
	 *
	 * @param string $type Transformation type.
	 */
	private static function hack( string $type ): CodeHack {
		if ( 'functions' === $type ) {
			return FunctionsMockerHack::get_hack_instance();
		}
		if ( 'static' === $type ) {
			return StaticMockerHack::get_hack_instance();
		}
		return new BypassFinalsHack();
	}

	/**
	 * Run generated return-expression fixtures in a temporary PHP file.
	 *
	 * @param string $code Generated fixture source.
	 * @return mixed Fixture return value.
	 * @throws RuntimeException When the temporary fixture is unavailable.
	 */
	private static function evaluate( string $code ) {
		$file = tmpfile();
		if ( false === $file ) {
			throw new RuntimeException( 'The code transformation fixture could not be created.' );
		}
		try {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Keep generated PHP in a self-deleting temporary stream.
			fwrite( $file, $code );
			$metadata = stream_get_meta_data( $file );
			$path     = $metadata['uri'] ?? null;
			if ( ! is_string( $path ) ) {
				throw new RuntimeException( 'The code transformation fixture path is unavailable.' );
			}
			return include $path;
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the stream also deletes the temporary fixture.
			fclose( $file );
		}
	}

	/**
	 * @testdox Should transform changed source at the same path using its current bytes.
	 * @testWith ["functions"]
	 *           ["static"]
	 *           ["finals"]

	 * @param string $type Transformation type.
	 */
	public function test_changed_source_at_the_same_path( string $type ): void {
		$cases                             = array(
			'functions' => array(
				'<?php return strtoupper("before");',
				'<?php return strtoupper("after");',
				'<?php return Automattic\\WooCommerce\\Testing\\Tools\\CodeHacking\\Hacks\\FunctionsMockerHack::strtoupper("after");',
			),
			'static'    => array(
				'<?php return WC_Code_Hacker_Transform_Fixture::value();',
				'<?php return WC_Code_Hacker_Transform_Fixture::value() . " after";',
				'<?php return Automattic\\WooCommerce\\Testing\\Tools\\CodeHacking\\Hacks\\StaticMockerHack::invoke__value__for__WC_Code_Hacker_Transform_Fixture() . " after";',
			),
			'finals'    => array(
				'<?php final class Before {}',
				'<?php final class After {}',
				'<?php  class After {}',
			),
		);
		list( $before, $after, $expected ) = $cases[ $type ];
		$this->sut                         = self::hack( $type );
		$this->sut->hack( $before, 'same.php' );
		$this->assertSame( $expected, $this->sut->hack( $after, 'same.php' ), 'A source edit at an existing path must be transformed independently.' );
	}

	/** @testdox Should dispatch transformed function calls through the current mock after reset and re-registration. */
	public function test_function_mocks_remain_dynamic(): void {
		$this->sut = FunctionsMockerHack::get_hack_instance();
		$source    = '<?php return strtoupper("hello");';
		$this->assertSame( 'HELLO', self::evaluate( $this->sut->hack( $source, 'mock.php' ) ) );
		FunctionsMockerHack::add_function_mocks(
			array(
				'strtoupper' => static function () {
					return 'first';
				},
			)
		);
		$this->assertSame( 'first', self::evaluate( $this->sut->hack( $source, 'mock.php' ) ) );
		$this->sut->reset();
		$this->assertSame( 'HELLO', self::evaluate( $this->sut->hack( $source, 'mock.php' ) ) );
		FunctionsMockerHack::add_function_mocks(
			array(
				'strtoupper' => static function () {
					return 'second';
				},
			)
		);
		$this->assertSame( 'second', self::evaluate( $this->sut->hack( $source, 'mock.php' ) ) );
	}

	/** @testdox Should dispatch transformed static calls through the current mock after reset and re-registration. */
	public function test_static_mocks_remain_dynamic(): void {
		$this->sut = StaticMockerHack::get_hack_instance();
		$source    = '<?php return WC_Code_Hacker_Transform_Fixture::value();';
		$this->assertSame( 'native', self::evaluate( $this->sut->hack( $source, 'static.php' ) ) );
		StaticMockerHack::add_method_mocks(
			array(
				'WC_Code_Hacker_Transform_Fixture' => array(
					'value' => static function () {
						return 'first';
					},
				),
			)
		);
		$this->assertSame( 'first', self::evaluate( $this->sut->hack( $source, 'static.php' ) ) );
		$this->sut->reset();
		$this->assertSame( 'native', self::evaluate( $this->sut->hack( $source, 'static.php' ) ) );
		StaticMockerHack::add_method_mocks(
			array(
				'WC_Code_Hacker_Transform_Fixture' => array(
					'value' => static function () {
						return 'second';
					},
				),
			)
		);
		$this->assertSame( 'second', self::evaluate( $this->sut->hack( $source, 'static.php' ) ) );
	}

	/**
	 * @testdox Should use a reinitialized allowlist for source transformed by the previous instance.
	 * @testWith ["functions"]
	 *           ["static"]

	 * @param string $type Transformation type.
	 */
	public function test_allowlist_reinitialization( string $type ): void {
		$source    = 'functions' === $type ? '<?php return strtoupper("hello");' : '<?php return WC_Code_Hacker_Transform_Fixture::value();';
		$this->sut = self::hack( $type );
		$this->assertNotSame( $source, $this->sut->hack( $source, 'same.php' ), 'The initial allowlist must transform this fixture.' );
		if ( 'functions' === $type ) {
			FunctionsMockerHack::initialize( array( 'strtolower' ) );
		} else {
			StaticMockerHack::initialize( array( 'SomeOtherClass' ) );
		}
		$this->sut = self::hack( $type );
		$this->assertSame( $source, $this->sut->hack( $source, 'same.php' ), 'A new allowlist must not inherit the previous transformation.' );
	}

	/**
	 * @testdox Should follow external mutations of referenced allowlist entries.
	 * @testWith ["functions"]
	 *           ["static"]

	 * @param string $type Transformation type.
	 */
	public function test_referenced_allowlist_mutation( string $type ): void {
		$name      = 'functions' === $type ? 'strtoupper' : 'WC_Code_Hacker_Transform_Fixture';
		$source    = 'functions' === $type ? '<?php return strtoupper("hello");' : '<?php return WC_Code_Hacker_Transform_Fixture::value();';
		$allowlist = array( &$name );
		if ( 'functions' === $type ) {
			FunctionsMockerHack::initialize( $allowlist );
		} else {
			StaticMockerHack::initialize( $allowlist );
		}
		$this->sut = self::hack( $type );
		$this->assertNotSame( $source, $this->sut->hack( $source, 'same.php' ), 'The referenced initial name must be transformed.' );
		$name = 'functions' === $type ? 'strtolower' : 'SomeOtherClass';
		$this->assertSame( $source, $this->sut->hack( $source, 'same.php' ), 'External changes to a referenced allowlist must affect the next transformation.' );
		$name = 'functions' === $type ? 'strtoupper' : 'WC_Code_Hacker_Transform_Fixture';
		$this->assertNotSame( $source, $this->sut->hack( $source, 'same.php' ), 'Restoring the referenced name must restore its transformation.' );
	}

	/**
	 * @testdox Should keep rejecting invalid source on repeated attempts and recover for valid source.
	 * @testWith ["functions"]
	 *           ["static"]
	 *           ["finals"]

	 * @param string $type Transformation type.
	 */
	public function test_parse_error_retry_and_recovery( string $type ): void {
		$this->sut = self::hack( $type );
		$this->sut->hack( '<?php final class Before {}', 'same.php' );
		$errors = 0;
		for ( $attempt = 0; $attempt < 2; ++$attempt ) {
			try {
				$this->sut->hack( '<?php final class {', 'same.php' );
				$this->fail( 'Each invalid-source attempt must raise ParseError.' );
			} catch ( ParseError $error ) {
				++$errors;
			}
		}
		$this->assertSame( 2, $errors );
		$this->assertSame( '<?php return true;', $this->sut->hack( '<?php return true;', 'same.php' ), 'Failed parsing must not poison later valid source.' );
	}
}
