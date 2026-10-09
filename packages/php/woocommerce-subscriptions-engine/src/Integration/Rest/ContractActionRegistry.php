<?php
/**
 * ContractActionRegistry - the contract actions extensions register for the action endpoint.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest;

use UnexpectedValueException;
use WP_REST_Request;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;

defined( 'ABSPATH' ) || exit;

/**
 * Registered contract actions, keyed by extension slug then action, and their per-request
 * resolution (permission, availability, args schema) against a contract.
 *
 * @internal Written through {@see \Automattic\WooCommerce\SubscriptionsEngine\Api\ContractActions::register()} only, which validates every definition.
 *
 * @phpstan-type ContractActionDefinition array{extension_slug: string, action: string, callback: callable, permission: 'manager'|'customer'|callable, description: string, args: array<string, array<string, mixed>>|callable, is_available: callable|null}
 */
final class ContractActionRegistry {

	/**
	 * Permission preset: the user can `manage_woocommerce`.
	 */
	public const PERMISSION_MANAGER = 'manager';

	/**
	 * Permission preset: the user is the contract's customer.
	 */
	public const PERMISSION_CUSTOMER = 'customer';

	/**
	 * Registered actions keyed by extension slug, then action.
	 *
	 * @var array<string, array<string, ContractActionDefinition>>
	 */
	private static $actions = array();

	/**
	 * Store a validated action definition.
	 *
	 * @param array $definition Action definition.
	 * @phpstan-param ContractActionDefinition $definition
	 */
	public static function add( array $definition ): void {
		self::$actions[ $definition['extension_slug'] ][ $definition['action'] ] = $definition;
	}

	/**
	 * Whether the extension registered the action.
	 *
	 * @param string $extension_slug Extension slug.
	 * @param string $action         Action slug.
	 */
	public static function has( string $extension_slug, string $action ): bool {
		return isset( self::$actions[ $extension_slug ][ $action ] );
	}

	/**
	 * The extension's action definition, or null when not registered.
	 *
	 * @param string $extension_slug Extension slug.
	 * @param string $action         Action slug.
	 * @return ContractActionDefinition|null
	 */
	public static function get( string $extension_slug, string $action ): ?array {
		return self::$actions[ $extension_slug ][ $action ] ?? null;
	}

	/**
	 * The extension's action definitions, in registration order.
	 *
	 * @param string $extension_slug Extension slug.
	 * @return array<int, ContractActionDefinition>
	 */
	public static function get_for_extension( string $extension_slug ): array {
		return array_values( self::$actions[ $extension_slug ] ?? array() );
	}

	/**
	 * Whether the current user may run the action on the contract.
	 *
	 * @param array           $definition Action definition.
	 * @param ContractView    $contract   Contract.
	 * @param WP_REST_Request $request    Request.
	 * @phpstan-param ContractActionDefinition $definition
	 */
	public static function is_permitted( array $definition, ContractView $contract, WP_REST_Request $request ): bool {
		$permission = $definition['permission'];
		if ( self::PERMISSION_MANAGER === $permission ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers manage_woocommerce.
			return current_user_can( 'manage_woocommerce' );
		}

		if ( self::PERMISSION_CUSTOMER === $permission ) {
			return is_user_logged_in() && get_current_user_id() === $contract->get_customer_id();
		}

		return true === $permission( $contract, $request );
	}

	/**
	 * Whether the action is available for the contract now; actions without `is_available` always are.
	 *
	 * @param array        $definition Action definition.
	 * @param ContractView $contract   Contract.
	 * @phpstan-param ContractActionDefinition $definition
	 */
	public static function is_available( array $definition, ContractView $contract ): bool {
		return null === $definition['is_available'] || true === ( $definition['is_available'] )( $contract );
	}

	/**
	 * The `action_args` property schemas for the contract, resolving a callable `args`.
	 *
	 * @param array        $definition Action definition.
	 * @param ContractView $contract   Contract.
	 * @phpstan-param ContractActionDefinition $definition
	 * @return array<string, array<string, mixed>>
	 * @throws UnexpectedValueException If a callable `args` returns something other than an array of schemas.
	 */
	public static function get_args_schema( array $definition, ContractView $contract ): array {
		$args = $definition['args'];
		if ( ! is_callable( $args ) ) {
			return $args;
		}

		$resolved_args = $args( $contract );
		if ( ! self::is_args_schema( $resolved_args ) ) {
			throw new UnexpectedValueException( sprintf( 'The "args" callback of contract action "%s" must return an array of property schemas.', esc_html( $definition['action'] ) ) );
		}

		return $resolved_args;
	}

	/**
	 * Whether the value is an `args` schema map: string property names to schema arrays.
	 *
	 * @param mixed $value Value.
	 * @phpstan-assert-if-true array<string, array<string, mixed>> $value
	 */
	public static function is_args_schema( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $name => $schema ) {
			if ( ! is_string( $name ) || ! is_array( $schema ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Clear every registration.
	 *
	 * @internal Public only so tests can isolate per-test state.
	 */
	public static function reset(): void {
		self::$actions = array();
	}
}
