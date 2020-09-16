/** @typedef { import('@woocommerce/type-defs/address-fields').CountryAddressFields } CountryAddressFields */
/** @typedef { import('@woocommerce/type-defs/address-fields').AddressFieldKey } AddressFieldKey */
/** @typedef { import('@woocommerce/type-defs/address-fields').AddressField } AddressField */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Used to render postcode before the city field.
 *
 * @type {Object <AddressFieldKey, AddressField>}
 */
const postcodeBeforeCity = {
	city: {
		index: 9,
	},
	postcode: {
		index: 7,
	},
};

/**
 * Used to make the state field optional.
 *
 * @type {Object <AddressFieldKey, AddressField>}
 */
const optionalState = {
	state: {
		required: false,
	},
};

/**
 * Used to hide the state field.
 *
 * @type {Object <AddressFieldKey, AddressField>}
 */
const hiddenState = {
	state: {
		required: false,
		hidden: true,
	},
};

/**
 * Used to hide the postcode field.
 *
 * @type {Object <AddressFieldKey, AddressField>}
 */
const hiddenPostcode = {
	postcode: {
		required: false,
		hidden: true,
	},
};

/**
 * Country specific address field properties.
 *
 * @type {CountryAddressFields}
 */
const countryAddressFields = {
	AE: {
		...hiddenPostcode,
		...optionalState,
	},
	AF: hiddenState,
	AO: {
		...hiddenPostcode,
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	AT: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	AU: {
		city: {
			label: __( 'Suburb', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Suburb (optional)',
				'woo-gutenberg-products-block'
			),
		},
		postcode: {
			label: __( 'Postcode', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Postcode (optional)',
				'woo-gutenberg-products-block'
			),
		},
		state: {
			label: __( 'State', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'State (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	AX: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	BD: {
		postcode: {
			required: false,
		},
		state: {
			label: __( 'District', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'District (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	BE: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	BH: {
		postcode: {
			required: false,
		},
		...hiddenState,
	},
	BI: hiddenState,
	BO: hiddenPostcode,
	BS: hiddenPostcode,
	CA: {
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	CH: {
		...postcodeBeforeCity,
		state: {
			label: __( 'Canton', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Canton (optional)',
				'woo-gutenberg-products-block'
			),
			required: false,
		},
	},
	CL: {
		city: {
			require: true,
		},
		postcode: {
			required: false,
		},
		state: {
			label: __( 'Region', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Region (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	CN: {
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	CO: {
		postcode: {
			required: false,
		},
	},
	CZ: hiddenState,
	DE: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	DK: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	EE: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	ES: {
		...postcodeBeforeCity,
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	FI: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	FR: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	GB: {
		postcode: {
			label: __( 'Postcode', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Postcode (optional)',
				'woo-gutenberg-products-block'
			),
		},
		state: {
			label: __( 'County', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'County (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	GP: hiddenState,
	GF: hiddenState,
	GR: optionalState,
	HK: {
		postcode: {
			required: false,
		},
		city: {
			label: __( 'Town/District', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Town/District (optional)',
				'woo-gutenberg-products-block'
			),
		},
		state: {
			label: __( 'Region', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Region (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	HU: {
		state: {
			label: __( 'County', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'County (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	ID: {
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	IE: {
		postcode: {
			label: __( 'Eircode', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Eircode (optional)',
				'woo-gutenberg-products-block'
			),
			required: false,
		},
		state: {
			label: __( 'County', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'County (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	IS: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	IL: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	IM: hiddenState,
	IT: {
		...postcodeBeforeCity,
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	JP: {
		first_name: {
			index: 2,
		},
		last_name: {
			index: 1,
		},
		address_1: {
			index: 7,
		},
		address_2: {
			index: 8,
		},
		postcode: {
			index: 4,
		},
		city: {
			index: 6,
		},
		state: {
			label: __( 'Prefecture', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Prefecture (optional)',
				'woo-gutenberg-products-block'
			),
			index: 5,
		},
	},
	KR: hiddenState,
	KW: hiddenState,
	LB: hiddenState,
	LI: {
		...postcodeBeforeCity,
		state: {
			label: __( 'Municipality', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Municipality (optional)',
				'woo-gutenberg-products-block'
			),
			required: false,
		},
	},
	LK: hiddenState,
	LU: hiddenState,
	LV: {
		state: {
			label: __( 'Municipality', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Municipality (optional)',
				'woo-gutenberg-products-block'
			),
			required: false,
		},
	},
	MQ: hiddenState,
	MT: hiddenState,
	MZ: {
		...hiddenPostcode,
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	NL: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	NG: {
		...hiddenPostcode,
		state: {
			label: __( 'State', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'State (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	NO: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	NP: {
		postcode: {
			required: false,
		},
		state: {
			label: __( 'State', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'State (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	NZ: {
		postcode: {
			label: __( 'Postcode', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Postcode (optional)',
				'woo-gutenberg-products-block'
			),
		},
		state: {
			label: __( 'Region', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Region (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	PL: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	PT: hiddenState,
	RE: hiddenState,
	RO: {
		state: {
			label: __( 'County', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'County (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	RS: hiddenState,
	SE: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	SG: {
		city: {
			required: false,
		},
		...hiddenState,
	},
	SK: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	SI: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	SR: {
		...hiddenPostcode,
	},
	ST: {
		...hiddenPostcode,
		state: {
			label: __( 'District', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'District (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	MD: {
		state: {
			label: __(
				'Municipality/District',
				'woo-gutenberg-products-block'
			),
			optionalLabel: __(
				'Municipality/District (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	TR: {
		...postcodeBeforeCity,
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	UG: {
		...hiddenPostcode,
		city: {
			label: __( 'Town/Village', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Town/Village (optional)',
				'woo-gutenberg-products-block'
			),
		},
		state: {
			label: __( 'District', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'District (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	US: {
		postcode: {
			label: __( 'ZIP', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'ZIP (optional)',
				'woo-gutenberg-products-block'
			),
		},
		state: {
			label: __( 'State', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'State (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	VN: {
		city: {
			index: 8,
		},
		postcode: {
			index: 7,
			required: false,
		},
		...hiddenState,
	},
	WS: hiddenPostcode,
	YT: hiddenState,
	ZA: {
		state: {
			label: __( 'Province', 'woo-gutenberg-products-block' ),
			optionalLabel: __(
				'Province (optional)',
				'woo-gutenberg-products-block'
			),
		},
	},
	ZW: hiddenPostcode,
};

export default countryAddressFields;
