<?php
/**
 * Regulatory fields
 *
 * Fields a country's rules require a store to collect with an order, keyed by country code and then by field ID.
 * A country's fields are asked for at checkout when the order is delivered to that country.
 *
 * Each field has:
 * - label:    Shown next to the input and wherever the value is displayed.
 * - required: Whether the order can be placed without a value.
 * - format:   Class that cleans, validates and formats the value. Must implement FormatInterface.
 *
 * A field ID is stored with every order that has a value for it, so never rename or reuse one.
 *
 * @package WooCommerce\i18n
 */

use Automattic\WooCommerce\Internal\RegulatoryFields\Formats\BrazilTaxId;

defined( 'ABSPATH' ) || exit;

return array(
	'BR' => array(
		'br_tax_id' => array(
			'label'    => __( 'CPF/CNPJ', 'woocommerce' ),
			'required' => true,
			'format'   => BrazilTaxId::class,
		),
	),
);
