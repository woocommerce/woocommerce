<?php
/**
 * A customer of its own per `key`, so orders created for it leave the other fixture customers alone.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{key: string} $args
 * @return array{id: int, email: string}
 */
return static function ( array $args ): array {
	$login = "mcp-{$args['key']}";
	$email = "{$login}@fixtures.mcp.test";
	$user  = get_user_by( 'login', $login );
	$id    = $user ? $user->ID : wp_create_user( $login, wp_generate_password(), $email );

	wp_update_user(
		array(
			'ID'   => $id,
			'role' => 'customer',
		)
	);

	return array(
		'id'    => $id,
		'email' => $email,
	);
};
