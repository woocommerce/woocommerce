<?php
register_woocommerce_admin_test_helper_rest_route(
	'/tools/trigger-wca-install/v1',
	'tools_trigger_wca_install'
);

function tools_trigger_wca_install() {
	Automattic\WooCommerce\Admin\Install::install();

	return true;
}
