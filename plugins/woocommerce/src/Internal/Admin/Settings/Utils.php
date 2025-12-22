<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Admin\Features\Features;
use WP_Error;

defined( 'ABSPATH' ) || exit;
/**
 * Payments settings utilities class.
 *
 * @internal
 */
class Utils {
	/**
	 * Apply order mappings to a base order map.
	 *
	 * @param array $base_map     The base order map.
	 * @param array $new_mappings The order mappings to apply.
	 *                            This can be a full or partial list of the base one,
	 *                            but it can also contain (only) new IDs and their orders.
	 *
	 * @return array The updated base order map, normalized.
	 */
	public static function order_map_apply_mappings( array $base_map, array $new_mappings ): array {
		// Make sure the base map is sorted ascending by their order values.
		// We don't normalize first because the order values have meaning.
		asort( $base_map );

		$updated_map = $base_map;
		// Apply the new mappings in the order they were given.
		foreach ( $new_mappings as $id => $order ) {
			// If the ID is not in the base map, we ADD it at the desired order. Otherwise, we MOVE it.
			if ( ! isset( $base_map[ $id ] ) ) {
				$updated_map = self::order_map_add_at_order( $updated_map, $id, $order );
				continue;
			}

			$updated_map = self::order_map_move_at_order( $updated_map, $id, $order );
		}

		return self::order_map_normalize( $updated_map );
	}

	/**
	 * Move an id at a specific order in an order map.
	 *
	 * This method is used to simulate the behavior of a drag&drop sorting UI:
	 * - When moving an id down, all the ids with an order equal or lower than the desired order
	 *   but equal or higher than the current order are decreased by 1.
	 * - When moving an id up, all the ids with an order equal or higher than the desired order
	 *   but equal or lower than the current order are increased by 1.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to place.
	 * @param int    $order     The order at which to place the id.
	 *
	 * @return array The updated order map. This map is not normalized.
	 */
	public static function order_map_move_at_order( array $order_map, string $id, int $order ): array {
		// If the id is not in the order map, return the order map as is.
		if ( ! isset( $order_map[ $id ] ) ) {
			return $order_map;
		}

		// If the id is already at the desired order, return the order map as is.
		if ( $order_map[ $id ] === $order ) {
			return $order_map;
		}

		// If there is no id at the desired order, just place the id there.
		if ( ! in_array( $order, $order_map, true ) ) {
			$order_map[ $id ] = $order;

			return $order_map;
		}

		// We apply the normal behavior of a drag&drop sorting UI.
		$existing_order = $order_map[ $id ];
		if ( $order > $existing_order ) {
			// Moving down.
			foreach ( $order_map as $key => $value ) {
				if ( $value <= $order && $value >= $existing_order ) {
					--$order_map[ $key ];
				}
			}
		} else {
			// Moving up.
			foreach ( $order_map as $key => $value ) {
				if ( $value >= $order && $value <= $existing_order ) {
					++$order_map[ $key ];
				}
			}
		}

		// Place the id at the desired order.
		$order_map[ $id ] = $order;

		return $order_map;
	}

	/**
	 * Place an id at a specific order in an order map.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to place.
	 * @param int    $order     The order at which to place the id.
	 *
	 * @return array The updated order map.
	 */
	public static function order_map_place_at_order( array $order_map, string $id, int $order ): array {
		// If the id is already at the desired order, return the order map as is.
		if ( isset( $order_map[ $id ] ) && $order_map[ $id ] === $order ) {
			return $order_map;
		}

		// If there is no id at the desired order, just place the id there.
		if ( ! in_array( $order, $order_map, true ) ) {
			$order_map[ $id ] = $order;

			return $order_map;
		}

		// Bump the order of everything with an order equal or higher than the desired order.
		foreach ( $order_map as $key => $value ) {
			if ( $value >= $order ) {
				++$order_map[ $key ];
			}
		}

		// Place the id at the desired order.
		$order_map[ $id ] = $order;

		return $order_map;
	}

	/**
	 * Add an id to a specific order in an order map.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to move.
	 * @param int    $order     The order to move the id to.
	 *
	 * @return array The updated order map. If the id is already in the order map, the order map is returned as is.
	 */
	public static function order_map_add_at_order( array $order_map, string $id, int $order ): array {
		// If the id is in the order map, return the order map as is.
		if ( isset( $order_map[ $id ] ) ) {
			return $order_map;
		}

		return self::order_map_place_at_order( $order_map, $id, $order );
	}

	/**
	 * Normalize an order map.
	 *
	 * Sort the order map by the order and ensure the order values start from 0 and are consecutive.
	 *
	 * @param array $order_map The order map.
	 *
	 * @return array The normalized order map.
	 */
	public static function order_map_normalize( array $order_map ): array {
		asort( $order_map );

		return array_flip( array_keys( $order_map ) );
	}

	/**
	 * Change the minimum order of an order map.
	 *
	 * @param array $order_map     The order map.
	 * @param int   $new_min_order The new minimum order.
	 *
	 * @return array The updated order map.
	 */
	public static function order_map_change_min_order( array $order_map, int $new_min_order ): array {
		// Sanity checks.
		if ( empty( $order_map ) ) {
			return array();
		}

		$updated_map = array();
		$bump        = $new_min_order - min( $order_map );
		foreach ( $order_map as $id => $order ) {
			$updated_map[ $id ] = $order + $bump;
		}

		asort( $updated_map );

		return $updated_map;
	}

	/**
	 * Get the list of plugin slug suffixes used for handling non-standard testing slugs.
	 *
	 * @return string[] The list of plugin slug suffixes used for handling non-standard testing slugs.
	 */
	public static function get_testing_plugin_slug_suffixes(): array {
		return array( '-dev', '-rc', '-test', '-beta', '-alpha' );
	}

	/**
	 * Generate a list of testing plugin slugs from a standard/official plugin slug.
	 *
	 * @param string $slug             The standard/official plugin slug. Most likely the WPORG slug.
	 * @param bool   $include_original Optional. Whether to include the original slug in the list.
	 *                                 If true, the original slug will be the first item in the list.
	 *
	 * @return string[] The list of testing plugin slugs generated from the standard/official plugin slug.
	 */
	public static function generate_testing_plugin_slugs( string $slug, bool $include_original = false ): array {
		$slugs = array();
		if ( $include_original ) {
			$slugs[] = $slug;
		}

		foreach ( self::get_testing_plugin_slug_suffixes() as $suffix ) {
			$slugs[] = $slug . $suffix;
		}

		return $slugs;
	}

	/**
	 * Normalize a plugin slug to a standard/official slug.
	 *
	 * This is a best-effort approach.
	 * It will remove beta testing suffixes and lowercase the slug.
	 * It will NOT convert plugin titles to slugs or sanitize the slug like sanitize_title() does.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return string The normalized plugin slug.
	 */
	public static function normalize_plugin_slug( string $slug ): string {
		// If the slug is empty or contains anything other than alphanumeric and dash characters, it will be left as is.
		if ( empty( $slug ) || ! preg_match( '/^[\w-]+$/', $slug, $matches ) ) {
			return $slug;
		}

		// Lowercase the slug.
		$slug = strtolower( $slug );
		// Remove testing suffixes.
		foreach ( self::get_testing_plugin_slug_suffixes() as $suffix ) {
			$slug = str_ends_with( $slug, $suffix ) ? substr( $slug, 0, -strlen( $suffix ) ) : $slug;
		}

		return $slug;
	}

	/**
	 * Trim the .php file extension from a path.
	 *
	 * @param string $path The path to trim.
	 *
	 * @return string The trimmed path. If the path does not end with .php, it will be returned as is.
	 */
	public static function trim_php_file_extension( string $path ): string {
		if ( ! empty( $path ) && str_ends_with( $path, '.php' ) ) {
			$path = substr( $path, 0, - 4 );
		}

		return $path;
	}

	/**
	 * Truncate a text to a target character length while preserving whole words.
	 *
	 * We take a greedy approach: if some characters of a word fit in the target length, the whole word is included.
	 * This means we might exceed the target length by a few characters.
	 * The append string length is not included in the character count.
	 *
	 * @param string $text          The text to truncate.
	 *                              It will not be sanitized, stripped of HTML tags, or modified in any way before truncation.
	 * @param int    $target_length The target character length of the truncated text.
	 * @param string $append        Optional. The string to append to the truncated text, if there is any truncation.
	 *
	 * @return string The truncated text.
	 */
	public static function truncate_with_words( string $text, int $target_length, string $append = '' ): string {
		// First, deal with locale that doesn't have words separated by spaces, but instead deals with characters.
		// Borrowed from wp_trim_words().
		if ( str_starts_with( wp_get_word_count_type(), 'characters' ) && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
			$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
			preg_match_all( '/./u', $text, $words_array );

			// Nothing to do if the text is already short enough.
			if ( count( $words_array[0] ) <= $target_length ) {
				return $text;
			}

			$words_array = array_slice( $words_array[0], 0, $target_length );
			$truncated   = implode( '', $words_array );
			if ( $append ) {
				$truncated .= $append;
			}

			return $truncated;
		}

		// Deal with locale that has words separated by spaces.
		if ( strlen( $text ) <= $target_length ) {
			return $text;
		}

		$words_array = preg_split( "/[\n\r\t ]+/", $text, - 1, PREG_SPLIT_NO_EMPTY );
		$sep         = ' ';

		// Include words until the target length is reached.
		$truncated        = '';
		$remaining_length = $target_length;
		while ( $remaining_length > 0 && ! empty( $words_array ) ) {
			$word              = array_shift( $words_array );
			$truncated        .= $word . $sep;
			$remaining_length -= strlen( $word . $sep );
		}

		// Remove the last separator.
		$truncated = rtrim( $truncated, $sep );

		if ( null !== $append ) {
			$truncated .= $append;
		}

		return $truncated;
	}

	/**
	 * Retrieves a URL to relative path inside WooCommerce admin Payments settings with
	 * the provided query parameters.
	 *
	 * @param string|null $path  Relative path of the desired page.
	 * @param array       $query Query parameters to append to the path.
	 *
	 * @return string       Fully qualified URL pointing to the desired path.
	 */
	public static function wc_payments_settings_url( ?string $path = null, array $query = array() ): string {
		$path = $path ? '&path=' . $path : '';

		$query_string = '';
		if ( ! empty( $query ) ) {
			$query_string = '&' . http_build_query( $query );
		}

		return admin_url( 'admin.php?page=wc-settings&tab=checkout' . $path . $query_string );
	}

	/**
	 * Get data from a WooCommerce API endpoint.
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $params   Params to pass with request query.
	 *
	 * @return array|\WP_Error The response data or a WP_Error object.
	 */
	public static function rest_endpoint_get_request( string $endpoint, array $params = array() ) {
		$request = new \WP_REST_Request( 'GET', $endpoint );
		if ( $params ) {
			$request->set_query_params( $params );
		}

		// Do the internal request.
		// This has minimal overhead compared to an external request.
		$response = rest_do_request( $request );

		$server        = rest_get_server();
		$response_data = json_decode( wp_json_encode( $server->response_to_data( $response, false ) ), true );

		// Handle non-200 responses.
		if ( 200 !== $response->get_status() ) {
			return new \WP_Error(
				'woocommerce_settings_payments_rest_error',
				sprintf(
					/* translators: 1: the endpoint relative URL, 2: error code, 3: error message */
					esc_html__( 'REST request GET %1$s failed with: (%2$s) %3$s', 'woocommerce' ),
					$endpoint,
					$response_data['code'] ?? 'unknown_error',
					$response_data['message'] ?? esc_html__( 'Unknown error', 'woocommerce' )
				),
				$response_data
			);
		}

		// If the response is 200, return the data.
		return $response_data;
	}

	/**
	 * Post data to a WooCommerce API endpoint and return the response data.
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $params   Params to pass with request body.
	 *
	 * @return array|\WP_Error The response data or a WP_Error object.
	 */
	public static function rest_endpoint_post_request( string $endpoint, array $params = array() ) {
		$request = new \WP_REST_Request( 'POST', $endpoint );
		if ( $params ) {
			$request->set_body_params( $params );
		}

		// Do the internal request.
		// This has minimal overhead compared to an external request.
		$response = rest_do_request( $request );

		$server        = rest_get_server();
		$response_data = json_decode( wp_json_encode( $server->response_to_data( $response, false ) ), true );

		// Handle non-200 responses.
		if ( 200 !== $response->get_status() ) {
			return new \WP_Error(
				'woocommerce_settings_payments_rest_error',
				sprintf(
				/* translators: 1: the endpoint relative URL, 2: error code, 3: error message */
					esc_html__( 'REST request POST %1$s failed with: (%2$s) %3$s', 'woocommerce' ),
					$endpoint,
					$response_data['code'] ?? 'unknown_error',
					$response_data['message'] ?? esc_html__( 'Unknown error', 'woocommerce' )
				),
				$response_data
			);
		}

		// If the response is 200, return the data.
		return $response_data;
	}

	/**
	 * Get the details to authorize a connection to WordPress.com.
	 *
	 * The most important part of the result is the URL to redirect to for authorization.
	 *
	 * @param string $return_url The URL to redirect to after the connection is authorized.
	 *
	 * @return array {
	 *               'success' => bool Whether the request was successful.
	 *               'errors' => array An array of error messages, if any.
	 *               'url' => string The URL to redirect to for authorization. In case of an error, this will be an empty string.
	 * }
	 */
	public static function get_wpcom_connection_authorization( string $return_url ): array {
		$connection_manager = new JetpackConnectionManager( 'woocommerce' );
		$errors             = new WP_Error();

		// If the site is not registered with WPCOM, try to register it.
		if ( ! $connection_manager->is_connected() ) {
			$result = $connection_manager->try_registration();
			if ( is_wp_error( $result ) ) {
				$errors->add( $result->get_error_code(), $result->get_error_message() );
			}
		}

		// Bail if we are not connected to WPCOM by now.
		if ( ! $connection_manager->is_connected() ) {
			$errors->add(
				'woocommerce_settings_payments_connection_error',
				esc_html__( 'Could not connect to WordPress.com. Please try again later.', 'woocommerce' )
			);

			return array(
				'success' => false,
				'errors'  => $errors->get_error_messages(),
				'url'     => '',
			);
		}

		$calypso_env = defined( 'WOOCOMMERCE_CALYPSO_ENVIRONMENT' ) && in_array( WOOCOMMERCE_CALYPSO_ENVIRONMENT, array(
			'development',
			'wpcalypso',
			'horizon',
			'stage',
		), true ) ? WOOCOMMERCE_CALYPSO_ENVIRONMENT : 'production';
		if ( Features::is_enabled( 'use-wp-horizon' ) ) {
			$calypso_env = 'horizon';
		}

		$authorization_url = $connection_manager->get_authorization_url( null, $return_url );
		$authorization_url = add_query_arg( 'locale', self::get_wpcom_locale(), $authorization_url );

		return array(
			'success' => ! $errors->has_errors(),
			'errors'  => $errors->get_error_messages(),
			'url'     => add_query_arg(
				array(
					// We use the new WooDNA value.
					'from'        => 'woocommerce-onboarding',
					// We inform Calypso that this is a WooPayments onboarding flow.
					'plugin_name' => 'woocommerce-payments',
					'calypso_env' => $calypso_env,
				),
				$authorization_url,
			),
		);
	}

	/**
	 * Return a locale string for wpcom.
	 *
	 * @return string
	 */
	public static function get_wpcom_locale(): string {
		// List of locales that should be used with region code.
		$locale_to_lang = array(
			'bre'   => 'br',
			'de_AT' => 'de-at',
			'de_CH' => 'de-ch',
			'de'    => 'de_formal',
			'el'    => 'el-po',
			'en_GB' => 'en-gb',
			'es_CL' => 'es-cl',
			'es_MX' => 'es-mx',
			'fr_BE' => 'fr-be',
			'fr_CA' => 'fr-ca',
			'nl_BE' => 'nl-be',
			'nl'    => 'nl_formal',
			'pt_BR' => 'pt-br',
			'sr'    => 'sr_latin',
			'zh_CN' => 'zh-cn',
			'zh_HK' => 'zh-hk',
			'zh_SG' => 'zh-sg',
			'zh_TW' => 'zh-tw',
		);

		$system_locale = get_locale();
		if ( isset( $locale_to_lang[ $system_locale ] ) ) {
			// Return the locale with region code if it's in the list.
			return $locale_to_lang[ $system_locale ];
		}

		// If the locale is not in the list, return the language code only.
		return explode( '_', $system_locale )[0];
	}
}
