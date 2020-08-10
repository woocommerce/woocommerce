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
			label: __( 'Province', 'woocommerce' ),
			optionalLabel: __(
				'Province (optional)',
				'woocommerce'
			),
		},
	},
	AT: {
		...postcodeBeforeCity,
		...hiddenState,
	},
	AU: {
		city: {
			label: __( 'Suburb', 'woocommerce' ),
			optionalLabel: __(
				'Suburb (optional)',
				'woocommerce'
			),
		},
		postcode: {
			label: __( 'Postcode', 'woocommerce' ),
			optionalLabel: __(
				'Postcode (optional)',
				'woocommerce'
			),
		},
		state: {
			label: __( 'State', 'woocommerce' ),
			optionalLabel: __(
				'State (optional)',
				'woocommerce'
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
			label: __( 'District', 'woocommerce' ),
			optionalLabel: __(
				'District (optional)',
				'woocommerce'
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
			label: __( 'Province', 'woocommerce' ),
			optionalLabel: __(
				'Province (optional)',
				'woocommerce'
			),
		},
	},
	CH: {
		...postcodeBeforeCity,
		state: {
			label: __( 'Canton', 'woocommerce' ),
			optionalLabel: __(
				'Canton (optional)',
				'woocommerce'
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
			label: __( 'Region', 'woocommerce' ),
			optionalLabel: __(
				'Region (optional)',
				'woocommerce'
			),
		},
	},
	CN: {
		state: {
			label: __( 'Province', 'woocommerce' ),
			optionalLabel: __(
				'Province (optional)',
				'woocommerce'
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
			label: __( 'State', 'woocommerce' ),
			optionalLabel: __(
				'State (optional)',
				'woocommerce'
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
			label: __( 'Postcode', 'woocommerce' ),
			optionalLabel: __(
				'Postcode (optional)',
				'woocommerce'
			),
		},
		state: {
			label: __( 'County', 'woocommerce' ),
			optionalLabel: __(
				'County (optional)',
				'woocommerce'
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
			label: __( 'Town/District', 'woocommerce' ),
			optionalLabel: __(
				'Town/District (optional)',
				'woocommerce'
			),
		},
		state: {
			label: __( 'Region', 'woocommerce' ),
			optionalLabel: __(
				'Region (optional)',
				'woocommerce'
			),
		},
	},
	HU: {
		state: {
			label: __( 'County', 'woocommerce' ),
			optionalLabel: __(
				'County (optional)',
				'woocommerce'
			),
		},
	},
	ID: {
		state: {
			label: __( 'Province', 'woocommerce' ),
			optionalLabel: __(
				'Province (optional)',
				'woocommerce'
			),
		},
	},
	IE: {
		postcode: {
			label: __( 'Eircode', 'woocommerce' ),
			optionalLabel: __(
				'Eircode (optional)',
				'woocommerce'
			),
			required: false,
		},
		state: {
			label: __( 'County', 'woocommerce' ),
			optionalLabel: __(
				'County (optional)',
				'woocommerce'
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
			label: __( 'Province', 'woocommerce' ),
			optionalLabel: __(
				'Province (optional)',
				'woocommerce'
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
			label: __( 'Prefecture', 'woocommerce' ),
			optionalLabel: __(
				'Prefecture (optional)',
				'woocommerce'
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
			label: __( 'Municipality', 'woocommerce' ),
			optionalLabel: __(
				'Municipality (optional)',
				'woocommerce'
			),
			required: false,
		},
	},
	LK: hiddenState,
	LU: hiddenState,
	LV: {
		state: {
			label: __( 'Municipality', 'woocommerce' ),
			optionalLabel: __(
				'Municipality (optional)',
				'woocommerce'
			),
			required: false,
		},
	},
	MQ: hiddenState,
	MT: hiddenState,
	MZ: {
		...hiddenPostcode,
		state: {
			label: __( 'Province', 'woocommerce' ),
			optionalLabel: __(
				'Province (optional)',
				'woocommerce'
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
			label: __( 'State', 'woocommerce' ),
			optionalLabel: __(
				'State (optional)',
				'woocommerce'
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
			label: __( 'State', 'woocommerce' ),
			optionalLabel: __(
				'State (optional)',
				'woocommerce'
			),
		},
	},
	NZ: {
		postcode: {
			label: __( 'Postcode', 'woocommerce' ),
			optionalLabel: __(
				'Postcode (optional)',
				'woocommerce'
			),
		},
		state: {
			label: __( 'Region', 'woocommerce' ),
			optionalLabel: __(
				'Region (optional)',
				'woocommerce'
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
			label: __( 'County', 'woocommerce' ),
			optionalLabel: __(
				'County (optional)',
				'woocommerce'
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
			label: __( 'District', 'woocommerce' ),
			optionalLabel: __(
				'District (optional)',
				'woocommerce'
			),
		},
	},
	MD: {
		state: {
			label: __(
				'Municipality/District',
				'woocommerce'
			),
			optionalLabel: __(
				'Municipality/District (optional)',
				'woocommerce'
			),
		},
	},
	TR: {
		...postcodeBeforeCity,
		state: {
			label: __( 'Province', 'woocommerce' ),
			optionalLabel: __(
				'Province (optional)',
				'woocommerce'
			),
		},
	},
	UG: {
		...hiddenPostcode,
		city: {
			label: __( 'Town/Village', 'woocommerce' ),
			optionalLabel: __(
				'Town/Village (optional)',
				'woocommerce'
			),
		},
		state: {
			label: __( 'District', 'woocommerce' ),
			optionalLabel: __(
				'District (optional)',
				'woocommerce'
			),
		},
	},
	US: {
		postcode: {
			label: __( 'ZIP', 'woocommerce' ),
			optionalLabel: __(
				'ZIP (optional)',
				'woocommerce'
			),
		},
		state: {
			label: __( 'State', 'woocommerce' ),
			optionalLabel: __(
				'State (optional)',
				'woocommerce'
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
			label: __( 'Province', 'woocommerce' ),
			optionalLabel: __(
				'Province (optional)',
				'woocommerce'
			),
		},
	},
	ZW: hiddenPostcode,
};

export default countryAddressFields;
