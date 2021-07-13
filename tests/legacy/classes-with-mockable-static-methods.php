<?php
/**
 * This array contains the names of the classes whose static methods will become mockable via StaticMockerHack
 * when running unit tests. If you need to mock a public static method of a class that isn't in the list,
 * simply add it. Please keep it sorted alphabetically.
 *
 * @package WooCommerce Tests
 */

return array(
	'WC_Admin_API_Keys',
	'WC_Admin_Settings',
	'WC_Admin_Webhooks',
	'WC_Emails',
	'WC_Payment_Gateways',
	'WC_Tax',
);
