<?php
/**
 * Postcode validation rules used before the expanded set in postcodes.php.
 *
 * Shortcode checkout uses these only when the `woocommerce_experimental_extensive_postcode_rules`
 * filter returns false. Do not add countries here.
 *
 * @package WooCommerce\i18n
 */

defined( 'ABSPATH' ) || exit;

return array(
	'AT' => array( 'pattern' => '[0-9]{4}' ),
	'BA' => array( 'pattern' => '[7-8][0-9]{4}' ),
	'BE' => array( 'pattern' => '\\d{4}' ),
	'BR' => array( 'pattern' => '\\d{5}[\\-]?\\d{3}' ),
	'CA' => array(
		'pattern' => '[ABCEGHJKLMNPRSTVXY][0-9][ABCEGHJKLMNPRSTVWXYZ] ?[0-9][ABCEGHJKLMNPRSTVWXYZ][0-9]',
		'flags'   => 'i',
	),
	'CH' => array( 'pattern' => '\\d{4}' ),
	'CZ' => array( 'pattern' => '(?:CZ-)?[0-9]{3}[ ]?[0-9]{2}' ),
	'DE' => array( 'pattern' => '(?:0[1-9]|[1-9][0-9])[0-9]{3}' ),
	'DK' => array(
		'pattern' => '(?:DK[- ]?)?(?:[1-24-9]\\d{3}|3[0-8]\\d{2})',
		'flags'   => 'i',
	),
	'EE' => array( 'pattern' => '\\d{5}' ),
	'ES' => array( 'pattern' => '[0-9]{5}' ),
	'FI' => array( 'pattern' => '[0-9]{5}' ),
	'FR' => array(
		'pattern' => '[0-9]{5}',
		'flags'   => 'i',
	),
	'GB' => array(
		'pattern'       => '(?:[abcdefghijklmnoprstuwyz][abcdefghklmnopqrstuvwxy]?[0-9]{1,2}[0-9][abdefghjlnpqrstuwxyz]{2}|[abcdefghijklmnoprstuwyz][0-9][abcdefghjkpstuw][0-9][abdefghjlnpqrstuwxyz]{2}|[abcdefghijklmnoprstuwyz][abcdefghklmnopqrstuvwxy][0-9][abehmnprvwxy][0-9][abdefghjlnpqrstuwxyz]{2}|gir0aa|bfpo[0-9]{1,4}|bfpoc\\/o[0-9]{1,3})',
		'flags'         => 'i',
		'normalization' => 'removeSpaces',
	),
	'HU' => array( 'pattern' => '\\d{4}' ),
	'IE' => array(
		'pattern'       => '(?:[AC-FHKNPRTV-Y][0-9]{2}|D6W)[0-9AC-FHKNPRTV-Y]{4}',
		'flags'         => 'i',
		'normalization' => 'removeSpacesAndHyphens',
	),
	'IN' => array( 'pattern' => '[1-9][0-9]{2}[ ]?[0-9]{3}' ),
	'IT' => array( 'pattern' => '\\d{5}' ),
	'JP' => array( 'pattern' => '[0-9]{3}-?[0-9]{4}' ),
	'KH' => array( 'pattern' => '[0-9]{6}' ),
	'LI' => array( 'pattern' => '94[8-9][0-9]' ),
	'LV' => array(
		'pattern' => '(?:LV[- ]?)?[1-9][0-9]{3}',
		'flags'   => 'i',
	),
	'MN' => array( 'pattern' => '[0-9]{5}(?:-[0-9]{4})?' ),
	'NI' => array( 'pattern' => '[1-9][0-9]{4}' ),
	'NL' => array(
		'pattern' => '[1-9][0-9]{3}[ ]?(?!SA|SD|SS)[A-Z]{2}',
		'flags'   => 'i',
	),
	'NO' => array( 'pattern' => '\\d{4}' ),
	'PL' => array( 'pattern' => '\\d{2}-\\d{3}' ),
	'PR' => array(
		'pattern' => '[0-9]{5}(?:-[0-9]{4})?',
		'flags'   => 'i',
	),
	'PT' => array( 'pattern' => '[0-9]{4}-[0-9]{3}' ),
	'SE' => array( 'pattern' => '(?:SE-)?[0-9]{3}[ ]?[0-9]{2}' ),
	'SI' => array( 'pattern' => '[1-9][0-9]{3}' ),
	'SK' => array( 'pattern' => '(?:SK-)?[0-9]{3}[ ]?[0-9]{2}' ),
	'US' => array( 'pattern' => '([0-9]{5})(?:-([0-9]{4}))?' ),
);
