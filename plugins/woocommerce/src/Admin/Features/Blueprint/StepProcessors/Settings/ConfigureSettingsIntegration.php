<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;

class ConfigureSettingsIntegration extends MapFieldsToOptions implements StepProcessor {
	protected array $options_map = array(
		'maxmind_license_key'=> 'woocommerce_maxmind_geolocation_license_key'
	);

}
