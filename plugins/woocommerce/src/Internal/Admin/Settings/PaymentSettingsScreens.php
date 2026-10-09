<?php
/**
 * Registry of experimental payment settings screens.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Collects the settings screens that payment gateways register.
 *
 * @internal
 */
final class PaymentSettingsScreens {

	/**
	 * Entity kind shared by every screen. The entity name is the screen ID.
	 */
	public const ENTITY_KIND = 'woo_settings';

	/**
	 * Get the valid registered screens, keyed by screen ID.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, array{id: string, title: string, rest_path: string, scripts: string[]}>
	 */
	public function get_screens(): array {
		/**
		 * Filters the payment settings screens. Experimental: the format can change in any release.
		 *
		 * Each screen is keyed by its ID and has a `title`, the `rest_path` of its settings entity,
		 * and optional `scripts` (script handles to load on the screen).
		 *
		 * @since 11.3.0
		 *
		 * @param array $screens The registered screens.
		 */
		$screens = apply_filters( 'woocommerce_experimental_payment_settings_screens', array() );
		if ( ! is_array( $screens ) ) {
			return array();
		}

		$valid = array();
		foreach ( $screens as $id => $screen ) {
			$normalized = self::normalize_screen( $id, $screen );
			if ( null !== $normalized ) {
				$valid[ $normalized['id'] ] = $normalized;
			}
		}

		return $valid;
	}

	/**
	 * Get one registered screen.
	 *
	 * @since 11.3.0
	 *
	 * @param string $id The screen ID.
	 * @return array{id: string, title: string, rest_path: string, scripts: string[]}|null
	 */
	public function get_screen( string $id ): ?array {
		return $this->get_screens()[ $id ] ?? null;
	}

	/**
	 * Validate a registered screen, or return null when it can't be used.
	 *
	 * @param mixed $id     The screen ID.
	 * @param mixed $screen The screen arguments.
	 * @return array{id: string, title: string, rest_path: string, scripts: string[]}|null
	 */
	private static function normalize_screen( $id, $screen ): ?array {
		if ( ! is_string( $id ) || sanitize_key( $id ) !== $id || '' === $id || ! is_array( $screen ) ) {
			return null;
		}

		$title     = $screen['title'] ?? null;
		$rest_path = $screen['rest_path'] ?? null;
		if ( ! is_string( $title ) || '' === $title || ! is_string( $rest_path ) || 0 !== strpos( $rest_path, '/' ) ) {
			return null;
		}

		$scripts = $screen['scripts'] ?? array();
		$scripts = is_array( $scripts ) ? array_values( array_filter( $scripts, 'is_string' ) ) : array();

		return array(
			'id'        => $id,
			'title'     => $title,
			'rest_path' => $rest_path,
			'scripts'   => $scripts,
		);
	}
}
