<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Logger;

/**
 * Registry of block email templates that participate in template update propagation (sync).
 *
 * Walks the set of emails registered with the block editor via
 * {@see WCTransactionalEmails::get_transactional_emails()}, resolves each email's
 * block template file, parses its `@version` header and records an entry for every
 * email whose template exposes a parseable version. Emails without a parseable header
 * are silently skipped with a warning log.
 *
 * The resolved registry is cached in a static property for the lifetime of the request.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 */
class WCEmailTemplateSyncRegistry {
	/**
	 * Email IDs that are considered first-party (core + POS + Fulfillments).
	 *
	 * Mirrors the first-party union in
	 * {@see WCTransactionalEmails::get_transactional_emails()} — this list is only
	 * used to classify the `source` field on registry entries, not to gate sync
	 * participation. The gate is {@see WCTransactionalEmails::get_transactional_emails()}
	 * (which already runs through `woocommerce_transactional_emails_for_block_editor`)
	 * combined with the presence of a parseable `@version` header.
	 *
	 * @var string[]|null
	 */
	private static $first_party_ids = null;

	/**
	 * Request-scoped cache of the resolved registry.
	 *
	 * @var array<string, array{version: string, template_path: string, source: string}>|null
	 */
	private static $registry_cache = null;

	/**
	 * Logger instance. Lazily instantiated on first use; overridable for tests.
	 *
	 * @var Email_Editor_Logger_Interface|null
	 */
	private static $logger = null;

	/**
	 * Return the registry of emails participating in template sync.
	 *
	 * @return array<string, array{version: string, template_path: string, source: string}>
	 *         Map keyed by email ID. Each entry holds the parsed `@version`, the absolute
	 *         template path, and a `source` classification (`core` for first-party emails,
	 *         `third_party` otherwise).
	 *
	 * @since 10.8.0
	 */
	public static function get_sync_enabled_emails(): array {
		if ( null === self::$registry_cache ) {
			self::$registry_cache = self::resolve();
		}

		return self::$registry_cache;
	}

	/**
	 * Return the sync config for a single email ID, or null when not in the registry.
	 *
	 * @param string $email_id The email ID.
	 * @return array{version: string, template_path: string, source: string}|null
	 *
	 * @since 10.8.0
	 */
	public static function get_email_sync_config( string $email_id ): ?array {
		$registry = self::get_sync_enabled_emails();

		return $registry[ $email_id ] ?? null;
	}

	/**
	 * Whether the given email ID participates in template sync.
	 *
	 * @param string $email_id The email ID.
	 * @return bool
	 *
	 * @since 10.8.0
	 */
	public static function is_enabled( string $email_id ): bool {
		$registry = self::get_sync_enabled_emails();

		return isset( $registry[ $email_id ] );
	}

	/**
	 * Reset the request-scoped registry cache.
	 *
	 * Intended for tests and for call sites that mutate the underlying inputs
	 * (e.g. toggling a feature flag) within the same request.
	 *
	 * @internal
	 *
	 * @since 10.8.0
	 */
	public static function reset_cache(): void {
		self::$registry_cache  = null;
		self::$first_party_ids = null;
	}

	/**
	 * Override the logger implementation. Intended for tests only.
	 *
	 * @internal
	 *
	 * @param Email_Editor_Logger_Interface|null $logger The logger implementation, or null to restore the default.
	 */
	public static function set_logger( ?Email_Editor_Logger_Interface $logger ): void {
		self::$logger = $logger;
	}

	/**
	 * Resolve the registry from scratch.
	 *
	 * @return array<string, array{version: string, template_path: string, source: string}>
	 */
	private static function resolve(): array {
		$eligible_ids = WCTransactionalEmails::get_transactional_emails();
		if ( empty( $eligible_ids ) ) {
			return array();
		}

		$emails_by_id = WCTransactionalEmailPostsManager::get_instance()->get_emails_by_id();

		$registry = array();

		foreach ( $eligible_ids as $email_id ) {
			if ( ! is_string( $email_id ) || '' === $email_id ) {
				continue;
			}

			$email = $emails_by_id[ $email_id ] ?? null;
			if ( null === $email ) {
				self::get_logger()->notice(
					sprintf(
						'Email template sync skipped for email "%s": no WC_Email subclass registered.',
						$email_id
					),
					array(
						'email_id' => $email_id,
						'context'  => 'email_template_sync_registry',
					)
				);
				continue;
			}

			$source        = self::classify_source( $email_id );
			$template_path = WCTransactionalEmailPostsGenerator::resolve_block_template_path( $email );

			if ( '' === $template_path || ! is_readable( $template_path ) ) {
				self::get_logger()->notice(
					sprintf(
						'Email template sync skipped for email "%s": template path not resolvable. source=%s',
						$email_id,
						$source
					),
					array(
						'email_id'      => $email_id,
						'source'        => $source,
						'template_path' => $template_path,
						'context'       => 'email_template_sync_registry',
					)
				);
				continue;
			}

			$version = self::parse_version_header( $template_path );

			if ( '' === $version ) {
				self::get_logger()->warning(
					sprintf(
						'Email template sync skipped for email "%s": missing @version header in %s. source=%s',
						$email_id,
						$template_path,
						$source
					),
					array(
						'email_id'      => $email_id,
						'source'        => $source,
						'template_path' => $template_path,
						'context'       => 'email_template_sync_registry',
					)
				);
				continue;
			}

			$registry[ $email_id ] = array(
				'version'       => $version,
				'template_path' => $template_path,
				'source'        => $source,
			);
		}

		return $registry;
	}

	/**
	 * Classify an email ID as either first-party (core / POS / Fulfillments) or third-party.
	 *
	 * @param string $email_id The email ID.
	 * @return string Either `core` or `third_party`.
	 */
	private static function classify_source( string $email_id ): string {
		if ( null === self::$first_party_ids ) {
			self::$first_party_ids = array_values( WCTransactionalEmails::get_core_transactional_emails() );
		}

		return in_array( $email_id, self::$first_party_ids, true ) ? 'core' : 'third_party';
	}

	/**
	 * Parse the `@version` header from a block email template file.
	 *
	 * Mirrors {@see \WC_Admin_Status::get_file_version()}: WordPress' native
	 * {@see get_file_data()} only understands `Name: Value` headers, but the
	 * email-editor templates (and the wider WooCommerce template contract)
	 * document their version as a PHPDoc `@version X.Y.Z` tag, which is
	 * whitespace-separated. We reuse the exact regex used by the existing
	 * helper so core and third-party templates are parsed consistently.
	 *
	 * @param string $file Absolute path to the template file.
	 * @return string The parsed version, or an empty string if none is declared.
	 *
	 * @since 10.8.0
	 */
	public static function parse_version_header( string $file ): string {
		if ( ! is_readable( $file ) ) {
			return '';
		}

		// Only read the first 8KiB — headers are always near the top of the file.
		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			return '';
		}

		$contents = fread( $handle, 8192 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( false === $contents ) {
			return '';
		}

		// Normalize CR-only line endings so the multi-line regex behaves consistently.
		$contents = str_replace( "\r", "\n", $contents );

		/*
		 * Matches a PHPDoc-style `@version X.Y.Z` tag allowing the usual
		 * comment-leader characters (` * `, `#`, `@`, tabs) before it, on any
		 * line in the header block. Identical to the long-standing pattern in
		 * WC_Admin_Status::get_file_version() — see docblock above for why
		 * get_file_data() isn't a drop-in replacement here.
		 */
		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $contents, $match ) && ! empty( $match[1] ) ) {
			return trim( _cleanup_header_comment( $match[1] ) );
		}

		return '';
	}

	/**
	 * Return the logger instance, lazily creating it the first time.
	 *
	 * @return Email_Editor_Logger_Interface
	 */
	private static function get_logger(): Email_Editor_Logger_Interface {
		if ( null === self::$logger ) {
			self::$logger = new Logger( wc_get_logger() );
		}

		return self::$logger;
	}
}
