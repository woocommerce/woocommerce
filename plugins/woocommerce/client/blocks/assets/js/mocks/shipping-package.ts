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
	selected = false,
}: {
	rateId: string;
	name: string;
	price: string;
	instanceID: number;
	selected?: boolean;
} ): CartShippingPackageShippingRate => {
	return {
		rate_id: rateId,
		name,
		description: '',
		delivery_time: '',
		price,
		taxes: '0',
		instance_id: instanceID,
		method_id: name.toLowerCase().split( ' ' ).join( '_' ),
		meta_data: [],
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
