<?php
/**
 * Fake MCP Adapter session manager class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

/**
 * Stands in for WP\MCP\Transport\Infrastructure\SessionManager from the MCP Adapter plugin, which isn't installed in tests.
 */
class FakeMcpSessionManager {

	/**
	 * Sessions by user ID and session ID.
	 *
	 * @var array<int, array<string, array>>
	 */
	public static array $sessions = array();

	/**
	 * Get a session, like the MCP Adapter does.
	 *
	 * @param int    $user_id    The user ID.
	 * @param string $session_id The session ID.
	 * @return array|false
	 */
	public static function get_session( int $user_id, string $session_id ) {
		return self::$sessions[ $user_id ][ $session_id ] ?? false;
	}
}
