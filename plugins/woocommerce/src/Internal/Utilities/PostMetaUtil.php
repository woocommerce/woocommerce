<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * A class of utilities for dealing with post meta.
 *
 * @since 10.4.0
 */
class PostMetaUtil {

	/**
	 * Check if a value is an incomplete object.
	 *
	 * @param mixed $value The value to check.
	 * @return bool TRUE if the value is an incomplete object, FALSE otherwise.
	 */
	private static function is_incomplete_object( $value ): bool {
		return is_object( $value ) && '__PHP_Incomplete_Class' === get_class( $value );
	}

	/**
	 * Add a post meta value safely, ensuring incomplete objects are handled gracefully.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $key     The meta key.
	 * @param mixed  $value   The meta value.
	 * @param bool   $unique  Whether the meta value should be unique.
	 * @return bool
	 */
	public static function add_post_meta_safe( int $post_id, string $key, $value, bool $unique = false ): bool {
		global $wpdb;

		if ( ! self::is_incomplete_object( $value ) ) {
			return (bool) add_post_meta( $post_id, $key, $value, $unique );
		}

		if ( $unique && metadata_exists( 'post', $post_id, $key ) ) {
			return false;
		}

		$value  = maybe_serialize( $value );
		$result = $wpdb->insert(
			_get_meta_table( 'post' ),
			array(
				'post_id'    => $post_id,
				'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			),
			array( '%d', '%s', '%s' )
		);
		wp_cache_delete( $post_id, 'post_meta' );

		$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
		$logger->warning( sprintf( 'encountered a post meta value of type __PHP_Incomplete_Class during `add_post_meta_safe` in post with ID %d and key %s: "%s"', $post_id, $key, var_export( $value, true ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

		return (bool) $result;
	}

	/**
	 * Delete a post meta value safely, ensuring incomplete objects are handled gracefully.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $key     The meta key.
	 * @param mixed  $value   The meta value.
	 * @return bool
	 */
	public static function delete_post_meta_safe( int $post_id, string $key, $value ): bool {
		global $wpdb;

		if ( ! self::is_incomplete_object( $value ) ) {
			return delete_post_meta( $post_id, $key, $value );
		}

		$value = maybe_serialize( $value );

		$result = $wpdb->delete(
			_get_meta_table( 'post' ),
			array(
				'post_id'    => $post_id,
				'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			),
			array( '%d', '%s', '%s' )
		);
		wp_cache_delete( $post_id, 'post_meta' );

		$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
		$logger->warning( sprintf( 'encountered a post meta value of type __PHP_Incomplete_Class during `delete_post_meta_safe` in post with ID %d and key %s: "%s"', $post_id, $key, var_export( $value, true ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

		return (bool) $result;
	}

	/**
	 * Update a post meta value safely, ensuring incomplete objects are handled gracefully.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $key     The meta key.
	 * @param mixed  $value   The meta value.
	 * @return bool
	 */
	public static function update_post_meta_safe( int $post_id, string $key, $value ): bool {
		global $wpdb;

		if ( ! self::is_incomplete_object( $value ) ) {
			return (bool) update_post_meta( $post_id, $key, $value );
		}

		$value = maybe_serialize( $value );

		$result = $wpdb->update(
			_get_meta_table( 'post' ),
			array(
				'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			),
			array(
				'post_id'  => $post_id,
				'meta_key' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			),
			array( '%s' ),
			array( '%d', '%s' )
		);
		wp_cache_delete( $post_id, 'post_meta' );

		$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
		$logger->warning( sprintf( 'encountered a post meta value of type __PHP_Incomplete_Class during `update_post_meta_safe` in post with ID %d and key %s: "%s"', $post_id, $key, var_export( $value, true ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

		return (bool) $result;
	}
}
