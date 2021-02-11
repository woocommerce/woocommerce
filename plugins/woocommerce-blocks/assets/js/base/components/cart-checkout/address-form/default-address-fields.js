/** @typedef { import('@woocommerce/type-defs/address-fields').AddressField } AddressField */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Default address field properties.
 *
 * @property {AddressField} first_name Customer first name.
 * @property {AddressField} last_name  Customer last name.
 * @property {AddressField} company    Company name.
 * @property {AddressField} address_1  Street address.
 * @property {AddressField} address_2  Second line of address.
 * @property {AddressField} country    Country code.
 * @property {AddressField} city       City name.
 * @property {AddressField} state      State name or code.
 * @property {AddressField} postcode   Postal code.
 */
const AddressFields = {
	first_name: {
		label: __( 'First name', 'woo-gutenberg-products-block' ),
		optionalLabel: __(
			'First name (optional)',
			'woo-gutenberg-products-block'
		),
		autocomplete: 'given-name',
		autocapitalize: 'sentences',
		required: true,
		hidden: false,
		index: 10,
	},
	last_name: {
		label: __( 'Last name', 'woo-gutenberg-products-block' ),
		optionalLabel: __(
			'Last name (optional)',
			'woo-gutenberg-products-block'
		),
		autocomplete: 'family-name',
		autocapitalize: 'sentences',
		required: true,
		hidden: false,
		index: 20,
	},
	company: {
		label: __( 'Company', 'woo-gutenberg-products-block' ),
		optionalLabel: __(
			'Company (optional)',
			'woo-gutenberg-products-block'
		),
		autocomplete: 'organization',
		autocapitalize: 'sentences',
		required: false,
		hidden: false,
		index: 30,
	},
	address_1: {
		label: __( 'Address', 'woo-gutenberg-products-block' ),
		optionalLabel: __(
			'Address (optional)',
			'woo-gutenberg-products-block'
		),
		autocomplete: 'address-line1',
		autocapitalize: 'sentences',
		required: true,
		hidden: false,
		index: 40,
	},
	address_2: {
		label: __( 'Apartment, suite, etc.', 'woo-gutenberg-products-block' ),
		optionalLabel: __(
			'Apartment, suite, etc. (optional)',
			'woo-gutenberg-products-block'
		),
		autocomplete: 'address-line2',
		autocapitalize: 'sentences',
		required: false,
		hidden: false,
		index: 50,
	},
	country: {
		label: __( 'Country/Region', 'woo-gutenberg-products-block' ),
		optionalLabel: __(
			'Country/Region (optional)',
			'woo-gutenberg-products-block'
		),
		autocomplete: 'country',
		required: true,
		hidden: false,
		index: 60,
	},
	city: {
		label: __( 'City', 'woo-gutenberg-products-block' ),
		optionalLabel: __( 'City (optional)', 'woo-gutenberg-products-block' ),
		autocomplete: 'address-level2',
		autocapitalize: 'sentences',
		required: true,
		hidden: false,
		index: 70,
	},
	state: {
		label: __( 'State/County', 'woo-gutenberg-products-block' ),
		optionalLabel: __(
			'State/County (optional)',
			'woo-gutenberg-products-block'
		),
		autocomplete: 'address-level1',
		autocapitalize: 'sentences',
		required: true,
		hidden: false,
		index: 80,
	},
	postcode: {
		label: __( 'Postal code', 'woo-gutenberg-products-block' ),
		optionalLabel: __(
			'Postal code (optional)',
			'woo-gutenberg-products-block'
		),
		autocomplete: 'postal-code',
		autocapitalize: 'characters',
		required: true,
		hidden: false,
		index: 90,
	},
};

export default AddressFields;
