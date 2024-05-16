<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use WC_Tax;

class ConfigureShipping implements StepProcessor {
	public function process($schema): StepProcessorResult {
		$result = StepProcessorResult::success('ConfigureTaxRaes');

		$fields = array(
			'terms'=>array('terms',  array('%d', '%s', '%s', '%d') ),
			'classes'=>array('term_taxonomy', array('%d', '%d', '%s', '%s', '%d', '%d') ),
			'shipping_zones' => array('woocommerce_shipping_zones', array('%d', '%s', '%d')),
			'shipping_methods' => array('woocommerce_shipping_zone_methods', array('%d', '%d', '%s', '%d', '%d')),
			'shipping_locations' => array('woocommerce_shipping_zone_locations', array('%d', '%d', '%s', '%s')),
		);

		foreach ($fields as $name => $data) {
			if (isset($schema->values->{$name})) {
				$this->insert($data[0], $data[1], $schema->values->{$name});
			}
		}

		isset($schema->values->local_pickup) && $this->add_local_pickup($schema->values->local_pickup);
		return $result;
	}

	protected function insert($table, $format, $rows) {
		global $wpdb;
		$inserted_ids = array();
		$table = $wpdb->prefix.$table;
		$format = implode(', ', $format);
		foreach ($rows as $row) {
			$row = (array) $row;
			$columns = implode(", ", array_keys($row));
			$sql = $wpdb->prepare("REPLACE INTO $table ($columns) VALUES ($format)", $row);
			$result = $wpdb->query($sql);
		}
		return $inserted_ids;
	}

	private function add_local_pickup( $local_pickup ) {
		if (isset($local_pickup->general)) {
			update_option('woocommerce_pickup_location_settings', (array) $local_pickup->general);
		}

		if (isset($local_pickup->locations)) {
			$local_pickup->locations = json_decode(json_encode($local_pickup->locations), true);
			update_option('pickup_location_pickup_locations', $local_pickup->locations);
		}
	}

	public function get_supported_step(): string {
		return 'configureShipping';
	}
}
