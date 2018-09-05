<?php
/**
 * WC_Versioned_Transients class.
 *
 * @package WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Versioned_Transients.
 *
 * There is no way in WordPress core to group transients and invalidate at once all the transients in a given group.
 * This class implements an strategy to make it possible adding a unique string (based on time()) per group to each
 * transient name. When it is necessary to invalidate a group of transients, the unique string will be changed and
 * data will be regenerated.
 *
 * Raised in issue https://github.com/woocommerce/woocommerce/issues/5777.
 * Adapted from ideas in http://tollmanz.com/invalidation-schemes/.
 *
 * @since 3.5.0
 */
class WC_Versioned_Transients {
	/**
	 * Hook in methods.
	 *
	 * @since 3.5.0
	 */
	public function init() {
		add_action( 'delete_version_transients', array( $this, 'delete_version_transients' ) );
	}

	/**
	 * Return full transient name. This includes the name passed in the first parameter
	 * prefixed by 'wc_' and the unique identifier for the given group.
	 *
	 * @param string $name Transient name.
	 * @param string $group Transient group.
	 * @return string Transient name prefixed with group unique identifier
	 */
	public function get_name( $name, $group ) {
		return 'wc_' . $this->get_group_version( $group ) . '_' . $name;
	}

	/**
	 * Invalidate the current group version, if one exists, and delete all transients
	 * from the previous group version.
	 *
	 * @since 3.5.0
	 * @param string $group Group name.
	 * @return void
	 */
	public function invalidate_group_version( $group ) {
		$this->get_group_version( $group, true );
	}

	/**
	 * Get group version. A new version is created if none exists or if $refresh is true.
	 * When refreshing the group version, all transients from the previous version are deleted.
	 *
	 * @since 3.5.0
	 * @param  string  $group   Name for the group of transients to get the version.
	 * @param  boolean $refresh If true, a new version will be generated and all transients from the previous version are deleted.
	 * @return string Group version based on time(), 10 digits.
	 */
	protected function get_group_version( $group, $refresh = false ) {
		$group_transient_name = $group . '-transient-version';
		$group_version        = get_transient( $group_transient_name );

		// Create a group version if none exists or if invalidating the previous version.
		if ( false === $group_version || true === $refresh ) {
			if ( ! empty( $group_version ) ) {
				// Delete old transients if invalidating the previous version.
				$this->delete_version_transients( $group_version );
			}

			$group_version = (string) time();
			set_transient( $group_transient_name, $group_version );
		}

		return $group_version;
	}

	/**
	 * When the transient group version changes, this method is used to remove all past transients to avoid filling the DB.
	 *
	 * Note: this only works on transients prepended with the transient group version, and when object caching is not being used.
	 *
	 * @since 3.5.0
	 * @param string $version Version of the transients to remove.
	 */
	public function delete_version_transients( $version = '' ) {
		if ( ! wp_using_ext_object_cache() && ! empty( $version ) ) {
			global $wpdb;

			$limit = apply_filters( 'woocommerce_delete_version_transients_limit', 1000 );

			if ( ! $limit ) {
				return;
			}

			$affected = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id LIMIT %d",
					'\_transient\_wc\_' . $version . '\_%',
					$limit
				)
			); // WPCS: cache ok, db call ok.

			// If affected rows is equal to limit, there are more rows to delete. Delete in 30 secs.
			if ( $affected === $limit ) {
				wp_schedule_single_event( time() + 30, 'delete_version_transients', array( $version ) );
			}
		}
	}
}
