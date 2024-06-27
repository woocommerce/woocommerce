<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

class ExportShipping implements ExportsStepSchema {
	public function export() {
		global $wpdb;
		$classes = $wpdb->get_results("
			SELECT *
			FROM {$wpdb->prefix}term_taxonomy
			where taxonomy = 'product_shipping_class'
		");

		$settings = array();

		$term_ids = array();
		foreach ($classes as $term) {
			$term_ids[] = (int) $term->term_id;
		}

		$term_ids = implode(", ", $term_ids);

		if (!empty($term_ids)) {
			$terms = $wpdb->get_results( "
			SELECT *
			from {$wpdb->prefix}terms
			where term_id in ($term_ids)
		" );
		} else {
			$terms = array();
		}

		$settings['classes'] = $classes;
		$settings['terms'] = $terms;

		$settings['local_pickup'] = array(
			'general' => get_option('woocommerce_pickup_location_settings', array()),
			'locations' => get_option('pickup_location_pickup_locations', array())
		);

		$zones = $wpdb->get_results("
			SELECT *
			FROM {$wpdb->prefix}woocommerce_shipping_zones
		");

		$methods = $wpdb->get_results("
			SELECT *
			FROM {$wpdb->prefix}woocommerce_shipping_zone_methods
		");


		$methods_by_zone_id = array();
		foreach ($methods as $method) {
			if (!isset($methods_by_zone_id[$method->zone_id])) {
				$methods_by_zone_id[$method->zone_id] = array();
			}
			$methods_by_zone_id[$method->zone_id][] = $method->method_id;
		}

		$locations = $wpdb->get_results("
			SELECT *
			FROM {$wpdb->prefix}woocommerce_shipping_zone_locations
		");

		$locations_by_zone_id = array();
		foreach ($locations as $location) {
			if (!isset($locations_by_zone_id[$location->zone_id])) {
				$locations_by_zone_id[$location->zone_id] = array();
			}
			$locations_by_zone_id[$location->zone_id][] = $location->location_id;
		}

		$settings['shipping_methods'] = $methods;
		$settings['shipping_locations'] = $locations;
		$settings['shipping_zones'] = $zones;

	    return $settings;
	}

	public function export_step_schema() {
		return array(
			'step' => $this->get_step_name(),
			'values' => $this->export()
		);
	}

	public function get_step_name() {
		return 'configureShipping';
	}
}
