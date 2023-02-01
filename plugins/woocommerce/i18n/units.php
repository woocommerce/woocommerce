<?php
/**
 * Units
 *
 * Returns a multidimensional array of measurement units and their labels.
 * Unit labels should be defined in English and translated native through localization files.
 *
 * @package WooCommerce\i18n
 * @version
 */

defined( 'ABSPATH' ) || exit;

return array(
	'weight'     => array(
		'kg'  => _x( 'kg', 'weight unit kilograms', 'woocommerce' ),
		'g'   => _x( 'g', 'weight unit grams', 'woocommerce' ),
		'lbs' => _x( 'lbs', 'weight unit pounds', 'woocommerce' ),
		'oz'  => _x( 'oz', 'weight unit ounces', 'woocommerce' ),
	),
	'dimensions' => array(
		'm'  => _x( 'm', 'dimensions unit meters', 'woocommerce' ),
		'cm' => _x( 'cm', 'dimensions unit centimeters', 'woocommerce' ),
		'mm' => _x( 'mm', 'dimensions unit millimeters', 'woocommerce' ),
		'in' => _x( 'in', 'dimensions unit inches', 'woocommerce' ),
		'yd' => _x( 'yd', 'dimensions unit yards', 'woocommerce' ),
	),
);
