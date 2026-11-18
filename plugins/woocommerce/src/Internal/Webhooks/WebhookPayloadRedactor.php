<?php
/**
 * Redacts internal fields from custom webhook arguments.
 *
 * @package WooCommerce\Internal\Webhooks
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Webhooks;

use WC_Data;

/**
 * Prepares the stored and outgoing copies of custom webhook arguments.
 *
 * @internal
 */
final class WebhookPayloadRedactor {

	public const REDACTED = '[redacted]';

	/**
	 * Core resources whose payload starts with an ID or a data object.
	 */
	private const ID_RESOURCES = array( 'coupon', 'customer', 'order', 'product' );

	/**
	 * Core hooks that pass a bare checkout password.
	 */
	private const PASSWORD_HOOKS = array(
		'woocommerce_process_checkout_field_account_password',
		'woocommerce_process_checkout_password_field',
	);

	/**
	 * Hook that passes checkout form data.
	 */
	private const FORM_ARGUMENT_HOOK = 'woocommerce_checkout_update_order_review';

	/**
	 * Hook that passes a Helper API request with a query string.
	 */
	private const REQUEST_QUERY_HOOK = 'woocommerce_helper_api_request_args';

	/**
	 * Fields to redact, matched case-insensitively in structured data.
	 */
	private const REDACTED_FIELDS = array(
		'account_password'                   => true,
		'activation_key'                     => true,
		'access_token'                       => true,
		'access_token_secret'                => true,
		'api_key'                            => true,
		'authorization'                      => true,
		'consumer_key'                       => true,
		'consumer_secret'                    => true,
		'nonce'                              => true,
		'password'                           => true,
		'payment_token'                      => true,
		'refresh_token'                      => true,
		'request_hash'                       => true,
		'reset_key'                          => true,
		'secret'                             => true,
		'set_password_url'                   => true,
		'token'                              => true,
		'user_activation_key'                => true,
		'user_pass'                          => true,
		'user_password'                      => true,
		'verification_key'                   => true,
		'verify_email_key'                   => true,
		'verify_url'                         => true,
		'woocommerce-login-nonce'            => true,
		'woocommerce-process-checkout-nonce' => true,
		'x-woo-signature'                    => true,
	);

	/**
	 * Prepare an argument for a payload. Core resource payloads must use an ID or a data object.
	 *
	 * @since 11.4.0
	 *
	 * @param string $topic         Webhook topic.
	 * @param mixed  $resource_name Resource name supplied by the webhook resource filter.
	 * @param mixed  $arg           First hook argument.
	 * @return mixed
	 */
	public static function redact_for_payload( string $topic, $resource_name, $arg ) {
		$arg = self::redact( $topic, $arg );
		if ( ! in_array( $resource_name, self::ID_RESOURCES, true ) || is_numeric( $arg ) || $arg instanceof WC_Data ) {
			return $arg;
		}

		return self::REDACTED;
	}

	/**
	 * Redact a custom topic's first argument without changing the original.
	 *
	 * @since 11.4.0
	 *
	 * @param string $topic Webhook topic.
	 * @param mixed  $arg First hook argument.
	 * @return mixed
	 */
	public static function redact( string $topic, $arg ) {
		$parts = explode( '.', $topic, 3 );
		if ( 'action' !== $parts[0] ) {
			return $arg;
		}

		$hook = $parts[1] ?? '';
		if ( is_string( $arg ) ) {
			return self::redact_string( $hook, $arg );
		}

		if ( is_array( $arg ) || is_object( $arg ) ) {
			return self::redact_structure( $hook, $arg );
		}

		return $arg;
	}

	/**
	 * Redact a password or form data passed as a string.
	 *
	 * @param string $hook Hook name.
	 * @param string $arg  First hook argument.
	 * @return string
	 */
	private static function redact_string( string $hook, string $arg ): string {
		if ( in_array( $hook, self::PASSWORD_HOOKS, true ) ) {
			return '' === $arg ? $arg : self::REDACTED;
		}

		if ( self::FORM_ARGUMENT_HOOK === $hook ) {
			return self::redact_form_parameters( $arg );
		}

		return $arg;
	}

	/**
	 * Check the data as JSON encodes it. Keep the original when no fields need to change.
	 *
	 * @param string       $hook Hook name.
	 * @param array|object $arg  First hook argument.
	 * @return mixed
	 */
	private static function redact_structure( string $hook, $arg ) {
		$json = wp_json_encode( $arg );
		if ( false === $json ) {
			return null;
		}

		$changed = false;
		$value   = self::redact_request_query( $hook, json_decode( $json ), $changed );
		$clean   = self::redact_fields( $value, $changed );

		if ( ! $changed ) {
			return $arg;
		}

		return is_array( $arg ) ? (array) $clean : $clean;
	}

	/**
	 * Redact the query string on a copy of a Helper API request.
	 *
	 * @param string $hook    Hook name.
	 * @param mixed  $value   Data decoded from JSON.
	 * @param bool   $changed Whether any field was redacted.
	 * @return mixed The decoded data with its query string redacted.
	 */
	private static function redact_request_query( string $hook, $value, bool &$changed ) {
		if ( self::REQUEST_QUERY_HOOK !== $hook || ! is_object( $value ) || ! is_string( $value->query_string ?? null ) ) {
			return $value;
		}

		$query_string = self::redact_form_parameters( $value->query_string );
		if ( $query_string !== $value->query_string ) {
			$value->query_string = $query_string;
			$changed             = true;
		}

		return $value;
	}

	/**
	 * Redact the listed fields in JSON-shaped data.
	 *
	 * @param mixed $value Decoded data.
	 * @param bool  $changed Whether any field was redacted.
	 * @return mixed
	 */
	private static function redact_fields( $value, bool &$changed ) {
		if ( ! is_array( $value ) && ! is_object( $value ) ) {
			return $value;
		}

		$fields = (array) $value;
		foreach ( $fields as $key => $item ) {
			if ( isset( self::REDACTED_FIELDS[ strtolower( (string) $key ) ] ) && null !== $item && '' !== $item && self::REDACTED !== $item ) {
				$fields[ $key ] = self::REDACTED;
				$changed        = true;
			} else {
				$fields[ $key ] = self::redact_fields( $item, $changed );
			}
		}

		return is_array( $value ) ? $fields : (object) $fields;
	}

	/**
	 * Redact the listed fields in known form or query parameters.
	 *
	 * @param string $form Posted form data.
	 * @return string
	 */
	private static function redact_form_parameters( string $form ): string {
		return preg_replace_callback(
			'/(^\??|&)([^=&]+)=([^&]*)/',
			static function ( array $matches ): string {
				$key = strtolower( urldecode( $matches[2] ) );
				if ( isset( self::REDACTED_FIELDS[ $key ] ) && '' !== $matches[3] ) {
					return $matches[1] . $matches[2] . '=' . self::REDACTED;
				}
				return $matches[0];
			},
			$form
		) ?? self::REDACTED;
	}
}
