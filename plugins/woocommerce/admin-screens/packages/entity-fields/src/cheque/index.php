<?php
/**
 * The `cheque` collection: the fields of the cheque settings page.
 *
 * Each field folder's `field.php` holds its data. `instructions/field.ts` adds what PHP can't
 * describe, through the `@woocommerce/entity-fields/cheque` script module.
 *
 * @package WooCommerce\Admin
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'origin' => 'woocommerce',
	'kind'   => 'woo_settings_payments',
	'name'   => 'cheque',
	'module' => '@woocommerce/entity-fields/cheque',
);
