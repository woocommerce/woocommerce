<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;

class ConfigureSettingsSiteVisibility extends MapFieldsToOptions implements StepProcessor {
	protected array $options_map = array(
		'visibility' => 'woocommerce_coming_soon',
		'coming_soon_restrict_to_store_pages_only' => 'woocommerce_store_pages_only',
	);

	protected function provide_fields( $schema ): array {
		$fields = parent::provide_fields( $schema );
		if (isset($fields['visibility'])) {
			$fields['visibility'] = $fields['visibility'] === 'coming_soon' ? 'yes' : 'no';
		}

		return $fields;
	}
}
