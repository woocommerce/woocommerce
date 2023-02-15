import {
	EnviaSinglePartner,
	SkydropxSinglePartner,
	SendcloudSinglePartner,
	PacklinkSinglePartner,
	ShipStationSinglePartner,
	PacklinkDualPartner,
	SendcloudDualPartner,
	ShipStationDualPartner,
	WooCommerceShippingSinglePartner,
} from './partners';

// Order is respected, left to right in the UI
const shippingProviders = {
	MX: {
		// using name instead of directly referencing the component because we want to send this over the API in the near future
		shipping_providers: [ 'Skydropx' ] as const,
	},
	CO: {
		shipping_providers: [ 'Skydropx' ] as const,
	},
	CL: {
		shipping_providers: [ 'Envia' ] as const,
	},
	AR: {
		shipping_providers: [ 'Envia' ] as const,
	},
	PE: {
		shipping_providers: [ 'Envia' ] as const,
	},
	BR: {
		shipping_providers: [ 'Envia' ] as const,
	},
	UY: {
		shipping_providers: [ 'Envia' ] as const,
	},
	GT: {
		shipping_providers: [ 'Envia' ] as const,
	},
	NL: {
		shipping_providers: [ 'Sendcloud' ] as const,
	},
	AT: {
		shipping_providers: [ 'Sendcloud' ] as const,
	},
	BE: {
		shipping_providers: [ 'Sendcloud' ] as const,
	},
	FR: {
		shipping_providers: [ 'Sendcloud', 'Packlink' ] as const,
	},
	DE: {
		shipping_providers: [ 'Sendcloud', 'Packlink' ] as const,
	},
	ES: {
		shipping_providers: [ 'Packlink', 'Sendcloud' ] as const,
	},
	IT: {
		shipping_providers: [ 'Packlink', 'Sendcloud' ] as const,
	},
	GB: {
		shipping_providers: [ 'Sendcloud', 'ShipStation' ] as const,
	},
};

const providers = {
	Envia: {
		name: 'Envia' as const,
		url: 'https://woocommerce.com/products/envia-shipping-and-fulfillment/', // no plugin yet so we link to the product page
		'single-partner-layout': EnviaSinglePartner,
	},
	Skydropx: {
		name: 'Skydropx' as const,
		url: 'https://woocommerce.com/products/skydropx/', // no plugin yet so we link to the product page
		'single-partner-layout': SkydropxSinglePartner,
	},
	Sendcloud: {
		name: 'Sendcloud' as const,
		slug: 'sendcloud-shipping', // https://wordpress.org/plugins/sendcloud-shipping/
		'single-partner-layout': SendcloudSinglePartner,
		'dual-partner-layout': SendcloudDualPartner,
	},
	Packlink: {
		name: 'Packlink' as const,
		slug: 'packlink-pro-shipping', // https://wordpress.org/plugins/packlink-pro-shipping/
		'single-partner-layout': PacklinkSinglePartner,
		'dual-partner-layout': PacklinkDualPartner,
	},
	ShipStation: {
		name: 'ShipStation' as const,
		slug: 'woocommerce-shipstation-integration', // https://wordpress.org/plugins/woocommerce-shipstation-integration/
		'single-partner-layout': ShipStationSinglePartner,
		'dual-partner-layout': ShipStationDualPartner,
	},
	WooCommerceShipping: {
		name: 'WooCommerce Shipping' as const,
		slug: 'woocommerce-services', // https://wordpress.org/plugins/woocommerce-services/
		'single-partner-layout': WooCommerceShippingSinglePartner,
	},
} as const;

/**
 * Gets the components for the shipping providers for a given country
 * @param countryCode One of the countries above
 * @returns Either a single partner component or an array containing two partner components
 */
export const getShippingProviders = ( countryCode: keyof typeof shippingProviders ) => {
	const countryProviders =
		shippingProviders[ countryCode ]?.shipping_providers;

	if ( ! countryProviders ) {
		return [];
	}

	return countryProviders.length === 1
		? providers[ countryProviders[ 0 ] ][ 'single-partner-layout' ]
		: [
				providers[ countryProviders[ 0 ] ][ 'dual-partner-layout' ],
				providers[ countryProviders[ 1 ] ][ 'dual-partner-layout' ],
		  ];
};
