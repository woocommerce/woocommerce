/**
 * External dependencies
 */
import {
	CartShippingRate,
	CartShippingPackageShippingRate,
} from '@woocommerce/type-defs/cart';

export const generateShippingRate = ( {
	rateId,
	name,
	price,
	instanceID,
	methodID = name.toLowerCase().split( ' ' ).join( '_' ),
	selected = false,
	// eslint-disable-next-line @typescript-eslint/naming-convention -- meta_data comes from the API response.
	meta_data = [],
}: {
	rateId: string;
	name: string;
	price: string;
	instanceID: number;
	methodID?: string;
	selected?: boolean;
	meta_data?: { key: string; value: string }[];
} ): CartShippingPackageShippingRate => {
	return {
		rate_id: rateId,
		name,
		description: '',
		delivery_time: '',
		price,
		taxes: '0',
		instance_id: instanceID,
		method_id: methodID,
		meta_data,
		selected,
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
	};
};

export const generateShippingPackage = ( {
	packageId,
	shippingRates,
}: {
	packageId: number;
	shippingRates: CartShippingPackageShippingRate[];
} ): CartShippingRate => {
	return {
		package_id: packageId,
		name: 'Shipping',
		destination: {
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
		},
		items: [],
		shipping_rates: shippingRates,
	};
};
