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
			if ( in_array( $hook, self::PASSWORD_HOOKS, true ) ) {
				return '' === $arg ? $arg : self::REDACTED;
			}
			if ( self::FORM_ARGUMENT_HOOK === $hook ) {
				return self::redact_form_parameters( $arg, self::get_redacted_fields( $hook ) );
			}
		}

		if ( is_array( $arg ) || is_object( $arg ) ) {
			return self::redact_structure( $arg, self::get_redacted_fields( $hook ), self::REQUEST_QUERY_HOOK === $hook );
		}

		return $arg;
	}

	/**
	 * Redact a structured argument for the Scheduled Actions screen.
	 *
	 * Older rows may have been queued under a different topic, so check query parameters regardless of the current topic.
	 *
	 * @since 11.4.0
	 * @param string       $topic Current webhook topic.
	 * @param array|object $arg   Queued argument.
	 * @return mixed
	 */
	public static function redact_queued_structure( string $topic, $arg ) {
		$parts = explode( '.', $topic, 3 );
		return self::redact_structure( $arg, self::get_redacted_fields( $parts[1] ?? '' ), true );
	}

	/**
	 * Combine Core and plugin field names into a case-insensitive lookup.
	 *
	 * @param string $hook Hook name.
	 * @return array<string, bool>
	 */
	private static function get_redacted_fields( string $hook ): array {
		/**
		 * Filters the additional field names to hide from a custom action webhook's argument.
		 *
		 * WooCommerce applies these names in addition to its built-in list. Matching is case-insensitive.
		 *
		 * @since 11.4.0
		 * @param string[] $additional_fields Field names to hide from structured data and recognized form or query parameters.
		 * @param string   $hook              Hook name from the webhook topic.
		 */
		$additional_fields = apply_filters( 'woocommerce_webhook_redact_additional_fields', array(), $hook );
		$fields            = self::REDACTED_FIELDS;
		if ( is_array( $additional_fields ) ) {
			foreach ( $additional_fields as $field ) {
				if ( is_string( $field ) && '' !== $field ) {
					$fields[ strtolower( $field ) ] = true;
				}
			}
		}

		return $fields;
	}

	/**
	 * Check the data as JSON encodes it. Keep the original when no fields need to change.
	 *
	 * @param array|object        $arg                First hook argument.
	 * @param array<string, bool> $fields             Field names to hide.
	 * @param bool                $check_query_string Whether to check the top-level query string.
	 * @return mixed
	 */
	private static function redact_structure( $arg, array $fields, bool $check_query_string ) {
		$json = wp_json_encode( $arg );
		if ( false === $json ) {
			return null;
		}

		$changed = false;
		$value   = json_decode( $json );
		if ( $check_query_string ) {
			$value = self::redact_request_query( $value, $fields, $changed );
		}
		$clean = self::redact_fields( $value, $fields, $changed );

		if ( ! $changed ) {
			return $arg;
		}

		return is_array( $arg ) ? (array) $clean : $clean;
	}

	/**
	 * Redact the query string on a copy of the argument.
	 *
	 * @param mixed               $value   Data decoded from JSON.
	 * @param array<string, bool> $fields  Field names to hide.
	 * @param bool                $changed Whether any field was redacted.
	 * @return mixed The decoded data with its query string redacted.
	 */
	private static function redact_request_query( $value, array $fields, bool &$changed ) {
		if ( ! is_object( $value ) || ! is_string( $value->query_string ?? null ) ) {
			return $value;
		}

		$query_string = self::redact_form_parameters( $value->query_string, $fields );
		if ( $query_string !== $value->query_string ) {
			$value->query_string = $query_string;
			$changed             = true;
		}

		return $value;
	}

	/**
	 * Redact the listed fields in JSON-shaped data.
	 *
	 * @param mixed               $value   Decoded data.
	 * @param array<string, bool> $fields  Field names to hide.
	 * @param bool                $changed Whether any field was redacted.
	 * @return mixed
	 */
	private static function redact_fields( $value, array $fields, bool &$changed ) {
		if ( ! is_array( $value ) && ! is_object( $value ) ) {
			return $value;
		}

		$items = (array) $value;
		foreach ( $items as $key => $item ) {
			if ( isset( $fields[ strtolower( (string) $key ) ] ) && null !== $item && '' !== $item && self::REDACTED !== $item ) {
				$items[ $key ] = self::REDACTED;
				$changed       = true;
			} else {
				$items[ $key ] = self::redact_fields( $item, $fields, $changed );
			}
		}

		return is_array( $value ) ? $items : (object) $items;
	}

	/**
	 * Redact the listed fields in known form or query parameters.
	 *
	 * @param string              $form   Posted form data or query string.
	 * @param array<string, bool> $fields Field names to hide.
	 * @return string
	 */
	private static function redact_form_parameters( string $form, array $fields ): string {
		$prefix = '';
		if ( 0 === strpos( $form, '?' ) ) {
			$prefix = '?';
			$form   = substr( $form, 1 );
		}

		$pairs = explode( '&', $form );
		foreach ( $pairs as $index => $pair ) {
			$parts = explode( '=', $pair, 2 );
			$key   = strtolower( urldecode( $parts[0] ) );
			if ( isset( $fields[ $key ] ) && '' !== ( $parts[1] ?? '' ) ) {
				$pairs[ $index ] = $parts[0] . '=' . self::REDACTED;
			}
		}

		return $prefix . implode( '&', $pairs );
	}
}
