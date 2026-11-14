export type ShippingExtensionId =
	| 'woocommerce-shipping'
	| 'shipstation'
	| 'packlink';

export const COUNTRY_EXTENSIONS_MAP: Record< string, ShippingExtensionId[] > = {
	US: [ 'woocommerce-shipping', 'shipstation' ],
	CA: [ 'shipstation' ],
	FR: [ 'packlink' ],
	ES: [ 'packlink' ],
	IT: [ 'packlink' ],
	DE: [ 'packlink' ],
	GB: [ 'shipstation' ],
	NL: [ 'packlink' ],
	AT: [ 'packlink' ],
	BE: [ 'packlink' ],
	AU: [ 'shipstation' ],
	NZ: [ 'shipstation' ],
	IE: [ 'packlink' ],
	PT: [ 'packlink' ],
};

export const EXTENSION_PLUGIN_SLUGS: Record< ShippingExtensionId, string > = {
	'woocommerce-shipping': 'woocommerce-shipping',
	shipstation: 'woocommerce-shipstation-integration',
	packlink: 'packlink-pro-shipping',
};

export const getShippingExtensionsForCountry = (
	countryCode?: string | null
): ShippingExtensionId[] => COUNTRY_EXTENSIONS_MAP[ countryCode ?? '' ] ?? [];
