<?php
/**
 * Whether a user passes an ability's permission check for an input, without running the ability.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{user: string, ability: string, input: array<string, mixed>} $args
 * @return bool
 */
return static function ( array $args ): bool {
	wp_set_current_user( get_user_by( 'login', $args['user'] )->ID );

	return true === wp_get_ability( $args['ability'] )->check_permissions( $args['input'] );
};
