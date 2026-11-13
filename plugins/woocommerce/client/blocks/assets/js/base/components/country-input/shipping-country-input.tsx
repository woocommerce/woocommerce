/**
 * External dependencies
 */
import { SHIPPING_COUNTRIES } from '@woocommerce/block-settings';
import type { ReactElement } from 'react';

/**
 * Internal dependencies
 */
import CountryInput from './country-input';
import { CountryInputProps } from './CountryInputProps';

const ShippingCountryInput = ( props: CountryInputProps ): ReactElement => {
	return <CountryInput countries={ SHIPPING_COUNTRIES } { ...props } />;
};

export default ShippingCountryInput;
