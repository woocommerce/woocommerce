<?php
/**
 * Base Command Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Commands
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Commands;

use Automattic\WooCommerce\Internal\CLI\Migrator\Commands\BaseCommand;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;

/**
 * Test cases for BaseCommand abstract class.
 */
class BaseCommandTest extends \WC_Unit_Test_Case {

	/**
	 * Test concrete implementation of BaseCommand for testing.
	 */
	private $test_command;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Create a concrete test implementation of BaseCommand
		$this->test_command = new class extends BaseCommand {
			public function __invoke( $args, $assoc_args ) {
				return 'test-invoked';
			}
		};
	}

	/**
	 * Test that BaseCommand can be instantiated via concrete implementation.
	 */
	public function test_base_command_instantiation() {
		$this->assertInstanceOf( BaseCommand::class, $this->test_command );
	}

	/**
	 * Test dependency injection via init method.
	 */
	public function test_dependency_injection_via_init() {
		$credential_manager = new CredentialManager();
		$platform_registry = new PlatformRegistry();

		$this->test_command->init( $credential_manager, $platform_registry );

		// Test that dependencies are available (via reflection since they're protected)
		$reflection = new \ReflectionClass( $this->test_command );
		
		$credential_property = $reflection->getProperty( 'credential_manager' );
		$credential_property->setAccessible( true );
		$injected_credential_manager = $credential_property->getValue( $this->test_command );
		
		$registry_property = $reflection->getProperty( 'platform_registry' );
		$registry_property->setAccessible( true );
		$injected_platform_registry = $registry_property->getValue( $this->test_command );

		$this->assertSame( $credential_manager, $injected_credential_manager );
		$this->assertSame( $platform_registry, $injected_platform_registry );
	}

	/**
	 * Test that init method type hints work correctly.
	 */
	public function test_init_method_type_hints() {
		$reflection = new \ReflectionClass( BaseCommand::class );
		$init_method = $reflection->getMethod( 'init' );
		$parameters = $init_method->getParameters();

		$this->assertCount( 2, $parameters );
		$this->assertEquals( CredentialManager::class, (string) $parameters[0]->getType() );
		$this->assertEquals( PlatformRegistry::class, (string) $parameters[1]->getType() );
	}

	/**
	 * Test that BaseCommand is abstract and cannot be instantiated.
	 */
	public function test_base_command_is_abstract() {
		$reflection = new \ReflectionClass( BaseCommand::class );
		
		$this->assertTrue( $reflection->isAbstract() );
	}

	/**
	 * Test that concrete implementation can invoke successfully.
	 */
	public function test_concrete_implementation_can_invoke() {
		$result = $this->test_command->__invoke( array(), array() );
		$this->assertEquals( 'test-invoked', $result );
	}

	/**
	 * Test that dependencies are accessible after injection.
	 */
	public function test_dependencies_accessible_after_injection() {
		// Create a test command that exposes protected properties for testing
		$test_command = new class extends BaseCommand {
			public function __invoke( $args, $assoc_args ) {
				return 'test';
			}

			public function get_credential_manager() {
				return $this->credential_manager;
			}

			public function get_platform_registry() {
				return $this->platform_registry;
			}
		};

		$credential_manager = new CredentialManager();
		$platform_registry = new PlatformRegistry();

		// Before injection - properties should not be initialized
		$reflection = new \ReflectionClass( $test_command );
		$credential_prop = $reflection->getProperty( 'credential_manager' );
		$platform_prop = $reflection->getProperty( 'platform_registry' );
		$credential_prop->setAccessible( true );
		$platform_prop->setAccessible( true );
		
		$this->assertFalse( $credential_prop->isInitialized( $test_command ) );
		$this->assertFalse( $platform_prop->isInitialized( $test_command ) );

		// After injection - should be available
		$test_command->init( $credential_manager, $platform_registry );
		$this->assertSame( $credential_manager, $test_command->get_credential_manager() );
		$this->assertSame( $platform_registry, $test_command->get_platform_registry() );
	}

	/**
	 * Test dependency injection pattern follows WooCommerce standards.
	 */
	public function test_dependency_injection_pattern() {
		// Verify that BaseCommand follows the WooCommerce DI pattern:
		// 1. No constructor dependencies
		// 2. Dependencies injected via init() method
		// 3. Dependencies stored as protected properties
		
		$reflection = new \ReflectionClass( BaseCommand::class );
		
		// Should have no constructor parameters
		$constructor = $reflection->getConstructor();
		if ( $constructor ) {
			$this->assertCount( 0, $constructor->getParameters(), 'BaseCommand should have no constructor dependencies' );
		}

		// Should have init method for DI
		$this->assertTrue( $reflection->hasMethod( 'init' ), 'BaseCommand should have init method for dependency injection' );

		// Should have protected properties for dependencies
		$this->assertTrue( $reflection->hasProperty( 'credential_manager' ), 'BaseCommand should have credential_manager property' );
		$this->assertTrue( $reflection->hasProperty( 'platform_registry' ), 'BaseCommand should have platform_registry property' );

		$credential_property = $reflection->getProperty( 'credential_manager' );
		$registry_property = $reflection->getProperty( 'platform_registry' );

		$this->assertTrue( $credential_property->isProtected(), 'credential_manager should be protected' );
		$this->assertTrue( $registry_property->isProtected(), 'platform_registry should be protected' );
	}
}
