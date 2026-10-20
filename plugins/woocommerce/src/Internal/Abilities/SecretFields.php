<?php
/**
 * Secret fields class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Recognizes settings that hold credentials, so abilities leave them out.
 * A text field under a harmless name still gets through, so abilities that
 * can allowlist fields should do that instead.
 */
class SecretFields {

	/**
	 * Name parts that mark a credential, matched as whole words after camelCase and punctuation become underscores.
	 */
	private const SECRET_WORDS = '(password|passwd|passphrase|secret|signature|credentials?|authorization|private_key|api_key|access_key|consumer_key|token|webhook_key)';

	/**
	 * Whether a setting holds a credential.
	 *
	 * @param string $id   Setting ID or option name.
	 * @param string $type Setting field type, such as `password` or `text`.
	 * @return bool
	 */
	public static function is_secret( string $id, string $type = '' ): bool {
		if ( in_array( $type, array( 'password', 'secret' ), true ) ) {
			return true;
		}

		$normalized = strtolower( (string) preg_replace( array( '/([a-z])([A-Z])/', '/[^A-Za-z0-9]+/' ), array( '$1_$2', '_' ), $id ) );

		return 1 === preg_match( '/(^|_)' . self::SECRET_WORDS . '(_|$)/', $normalized );
	}
}
