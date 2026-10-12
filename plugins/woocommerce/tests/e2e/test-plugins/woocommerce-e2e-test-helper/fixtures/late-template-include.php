<?php
/**
 * Fixture template returned by a late `template_include` filter in the Coming Soon E2E tests.
 *
 * It stands in for a page builder assigning its own template. If any of this markup reaches a
 * visitor on a Coming Soon page, a later `template_include` callback replaced or appended the page.
 *
 * @package Automattic\WooCommerce\E2EPlaywright
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html>
<body>
	<h1>Late template_include fixture: this should be hidden by Coming Soon</h1>
</body>
</html>
