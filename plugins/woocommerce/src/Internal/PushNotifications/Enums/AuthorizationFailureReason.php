<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications\Enums;

/**
 * Reasons a loopback request failed authorization.
 */
final class AuthorizationFailureReason {

	/**
	 * Neither the Authorization header nor the token query parameter carried a token.
	 *
	 * @var string
	 */
	public const CREDENTIAL_MISSING = 'credential_missing';

	/**
	 * The token did not validate against the site's auth salt.
	 *
	 * @var string
	 */
	public const TOKEN_INVALID = 'token_invalid';

	/**
	 * The token was issued for a different site.
	 *
	 * @var string
	 */
	public const ISSUER_INVALID = 'issuer_invalid';

	/**
	 * The token's body hash does not match the request body.
	 *
	 * @var string
	 */
	public const BODY_HASH_MISMATCH = 'body_hash_mismatch';
}
