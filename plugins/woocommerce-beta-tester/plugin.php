<?php
add_action(
	'admin_menu',
	function() {
		add_management_page(
			'WooCommerce Admin Test Helper',
			'WCA Test Helper',
			'install_plugins',
			'woocommerce-admin-test-helper',
			function() {
				?><div id="woocommerce-admin-test-helper-app-root"></div>
				<?php
			}
		);
	}
);

add_action(
	'wp_loaded',
	function() {
		require_once __DIR__ . '/vendor/autoload.php';
		require 'api/api.php';
	}
);

