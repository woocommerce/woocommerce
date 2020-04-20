<?php
/**
 * The code hacker won't work with hacks defined for files that are loaded during the WooCommerce initialization
 * that is triggered in the unit testing bootstrap (files that are already loaded by the time the code hacker
 * is enabled during test hooks aren't hacked).
 *
 * As a workaround, a StaticWrapper class is defined (and configured via StaticMockerHack) for each class having
 * static methods that need to be replaced in tests but are used from code already loaded; this happens
 * right before WooCommerce initialization.
 *
 * This file simply returns the list of files that require a StaticWrapper to be registered during bootstrap.
 *
 * @package WooCommerce Tests
 */

return array(
	'WC_Admin_Settings',
);
