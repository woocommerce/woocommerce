<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\Steps\SetWCShipping;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;

/**
 * Class ExportWCShipping
 *
 * This class exports WooCommerce shipping settings and implements the StepExporter interface.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCShipping implements StepExporter {
	/**
	 * Export WooCommerce shipping settings.
	 *
	 * @return SetWCShipping
	 */
	public function export() {
		global $wpdb;

		// Fetch shipping classes from the database.
		$classes = $wpdb->get_results(
			"
            SELECT *
            FROM {$wpdb->prefix}term_taxonomy
            WHERE taxonomy = 'product_shipping_class'
        "
		);

		$term_ids = array();

		// Collect term IDs.
		foreach ( $classes as $term ) {
			$term_ids[] = (int) $term->term_id;
		}

		$term_ids = implode( ', ', $term_ids );

		// Fetch terms based on term IDs.
		if ( ! empty( $term_ids ) ) {
			$terms = $wpdb->get_results(
				$wpdb->prepare(
					"
                SELECT *
                FROM {$wpdb->prefix}terms
                WHERE term_id IN (%s)
      	  		",
					$term_ids
				)
			);
		} else {
			$terms = array();
		}

		// Fetch local pickup settings.
		$local_pickup = array(
			'general'   => get_option( 'woocommerce_pickup_location_settings', array() ),
			'locations' => get_option( 'pickup_location_pickup_locations', array() ),
		);

		if ( empty( $local_pickup['general'] ) ) {
			$local_pickup['general'] = new \stdClass();
		}

		// Fetch shipping zones from the database.
		$zones = $wpdb->get_results(
			"
            SELECT *
            FROM {$wpdb->prefix}woocommerce_shipping_zones
        "
		);

		// Fetch shipping zone methods from the database.
		$methods = $wpdb->get_results(
			"
            SELECT *
            FROM {$wpdb->prefix}woocommerce_shipping_zone_methods
        "
		);

		$methods_by_zone_id = array();

		// Organize methods by zone ID.
		foreach ( $methods as $method ) {
			if ( ! isset( $methods_by_zone_id[ $method->zone_id ] ) ) {
				$methods_by_zone_id[ $method->zone_id ] = array();
			}
			$methods_by_zone_id[ $method->zone_id ][] = $method->method_id;
		}

		// Fetch shipping zone locations from the database.
		$locations = $wpdb->get_results(
			"
            SELECT *
            FROM {$wpdb->prefix}woocommerce_shipping_zone_locations
        "
		);

		$locations_by_zone_id = array();

		// Organize locations by zone ID.
		foreach ( $locations as $location ) {
			if ( ! isset( $locations_by_zone_id[ $location->zone_id ] ) ) {
				$locations_by_zone_id[ $location->zone_id ] = array();
			}
			$locations_by_zone_id[ $location->zone_id ][] = $location->location_id;
		}


		// Create a new SetWCShipping step with the fetched data.
		$step = new SetWCShipping( $methods, $locations, $zones, $terms, $classes, $local_pickup );
		$step->set_meta_values(
			array(
				'plugin' => 'woocommerce',
			)
		);

		return $step;
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string
	 */
	public function get_step_name() {
		return SetWCShipping::get_step_name();
	}
}
