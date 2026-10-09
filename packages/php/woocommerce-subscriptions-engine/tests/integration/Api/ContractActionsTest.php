<?php
/**
 * Integration tests for the contract actions registration facade.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractActions;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest\ContractActionRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\ContractActions
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest\ContractActionRegistry
 */
class ContractActionsTest extends EngineIntegrationTestCase {

	private const EXTENSION_SLUG = 'test-extension';

	private const REGISTER = 'Automattic\WooCommerce\SubscriptionsEngine\Api\ContractActions::register';

	public function set_up(): void {
		parent::set_up();
		ContractActionRegistry::reset();
	}

	public function tear_down(): void {
		ContractActionRegistry::reset();
		parent::tear_down();
	}

	/**
	 * A valid registration is stored with its defaults.
	 */
	public function test_registers_an_action_with_defaults(): void {
		$callback = array( $this, 'return_contract' );

		ContractActions::register(
			self::EXTENSION_SLUG,
			'pause',
			array(
				'callback'   => $callback,
				'permission' => 'manage_subscription_contract',
			)
		);

		$definition = ContractActionRegistry::get( self::EXTENSION_SLUG, 'pause' );
		$this->assertNotNull( $definition );
		$this->assertSame( self::EXTENSION_SLUG, $definition['extension_slug'] );
		$this->assertSame( 'pause', $definition['action'] );
		$this->assertSame( $callback, $definition['callback'] );
		$this->assertSame( 'manage_subscription_contract', $definition['permission'] );
		$this->assertSame( '', $definition['description'] );
		$this->assertSame( array(), $definition['args'] );
		$this->assertNull( $definition['is_available'] );
	}

	/**
	 * Actions are scoped to the registering extension.
	 */
	public function test_actions_are_scoped_to_the_extension(): void {
		ContractActions::register( self::EXTENSION_SLUG, 'pause', $this->valid_args() );
		ContractActions::register( 'other-extension', 'pause', $this->valid_args() );
		ContractActions::register( self::EXTENSION_SLUG, 'resume', $this->valid_args() );

		$this->assertSame(
			array( 'pause', 'resume' ),
			array_column( ContractActionRegistry::get_for_extension( self::EXTENSION_SLUG ), 'action' )
		);
		$this->assertCount( 1, ContractActionRegistry::get_for_extension( 'other-extension' ) );
		$this->assertSame( array(), ContractActionRegistry::get_for_extension( 'unknown' ) );
	}

	/**
	 * A duplicate registration raises a notice and keeps the first one.
	 */
	public function test_duplicate_registration_keeps_the_first(): void {
		$this->setExpectedIncorrectUsage( self::REGISTER );

		ContractActions::register( self::EXTENSION_SLUG, 'pause', $this->valid_args() + array( 'description' => 'First' ) );
		ContractActions::register( self::EXTENSION_SLUG, 'pause', $this->valid_args() + array( 'description' => 'Second' ) );

		$definition = ContractActionRegistry::get( self::EXTENSION_SLUG, 'pause' );
		$this->assertNotNull( $definition );
		$this->assertSame( 'First', $definition['description'] );
	}

	/**
	 * Unknown keys raise a notice and are ignored; the action still registers.
	 */
	public function test_unknown_keys_are_ignored(): void {
		$this->setExpectedIncorrectUsage( self::REGISTER );

		ContractActions::register( self::EXTENSION_SLUG, 'pause', $this->valid_args() + array( 'label' => 'Pause' ) );

		$this->assertTrue( ContractActionRegistry::has( self::EXTENSION_SLUG, 'pause' ) );
	}

	/**
	 * Invalid registrations raise a notice and are not registered.
	 *
	 * @dataProvider invalid_registrations
	 *
	 * @param string               $extension_slug Extension slug.
	 * @param string               $action         Action slug.
	 * @param array<string, mixed> $overrides      Registration args to replace.
	 */
	public function test_invalid_registration_is_rejected( string $extension_slug, string $action, array $overrides ): void {
		$this->setExpectedIncorrectUsage( self::REGISTER );

		// A null override removes the key.
		$args = array_filter(
			$overrides + $this->valid_args(),
			static function ( $value ): bool {
				return null !== $value;
			}
		);

		ContractActions::register( $extension_slug, $action, $args );

		$this->assertSame( array(), ContractActionRegistry::get_for_extension( $extension_slug ) );
	}

	/**
	 * Invalid registration cases.
	 *
	 * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
	 */
	public function invalid_registrations(): array {
		return array(
			'empty extension slug'      => array( ' ', 'pause', array() ),
			'action with capitals'      => array( self::EXTENSION_SLUG, 'Pause', array() ),
			'action with a slash'       => array( self::EXTENSION_SLUG, 'pause/now', array() ),
			'action with a newline'     => array( self::EXTENSION_SLUG, "pause\n", array() ),
			'empty action'              => array( self::EXTENSION_SLUG, '', array() ),
			'missing callback'          => array( self::EXTENSION_SLUG, 'pause', array( 'callback' => null ) ),
			'non-callable callback'     => array( self::EXTENSION_SLUG, 'pause', array( 'callback' => 'not_a_function_anywhere' ) ),
			'missing permission'        => array( self::EXTENSION_SLUG, 'pause', array( 'permission' => null ) ),
			'empty permission'          => array( self::EXTENSION_SLUG, 'pause', array( 'permission' => ' ' ) ),
			'non-callable permission'   => array( self::EXTENSION_SLUG, 'pause', array( 'permission' => 42 ) ),
			'non-string description'    => array( self::EXTENSION_SLUG, 'pause', array( 'description' => 5 ) ),
			'args not a schema map'     => array( self::EXTENSION_SLUG, 'pause', array( 'args' => array( 'type' => 'boolean' ) ) ),
			'args a list'               => array( self::EXTENSION_SLUG, 'pause', array( 'args' => array( array( 'type' => 'boolean' ) ) ) ),
			'non-callable is_available' => array( self::EXTENSION_SLUG, 'pause', array( 'is_available' => true ) ),
		);
	}

	/**
	 * Callable `args` resolve per contract; a non-array result is an error.
	 */
	public function test_callable_args_resolve_per_contract(): void {
		ContractActions::register(
			self::EXTENSION_SLUG,
			'pause',
			$this->valid_args() + array(
				'args' => static function ( ContractView $contract ): array {
					return array(
						'reason' => array(
							'type'        => 'string',
							'description' => $contract->get_status(),
						),
					);
				},
			)
		);
		ContractActions::register(
			self::EXTENSION_SLUG,
			'broken',
			$this->valid_args() + array(
				'args' => static function (): string {
					return 'nope';
				},
			)
		);
		$contract = $this->create_contract();

		$pause = ContractActionRegistry::get( self::EXTENSION_SLUG, 'pause' );
		$this->assertNotNull( $pause );
		$this->assertSame(
			array(
				'reason' => array(
					'type'        => 'string',
					'description' => 'active',
				),
			),
			ContractActionRegistry::get_args_schema( $pause, $contract )
		);

		$broken = ContractActionRegistry::get( self::EXTENSION_SLUG, 'broken' );
		$this->assertNotNull( $broken );
		$this->expectException( \UnexpectedValueException::class );
		ContractActionRegistry::get_args_schema( $broken, $contract );
	}

	/**
	 * Valid registration args.
	 *
	 * @return array<string, mixed>
	 */
	private function valid_args(): array {
		return array(
			'callback'   => array( $this, 'return_contract' ),
			'permission' => 'manage_woocommerce',
		);
	}

	/**
	 * An active contract owned by the test extension.
	 */
	private function create_contract(): ContractView {
		return Contracts::create(
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'status'         => 'active',
			)
		);
	}

	/**
	 * Action callback that returns the contract unchanged.
	 *
	 * @param ContractView $contract Contract.
	 */
	public function return_contract( ContractView $contract ): ContractView {
		return $contract;
	}
}
