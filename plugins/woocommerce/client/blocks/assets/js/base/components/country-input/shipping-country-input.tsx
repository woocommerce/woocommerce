/**
 * External dependencies
 */
import { COUNTRIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CountryInput from './country-input';
import { CountryInputProps } from './CountryInputProps';

const ShippingCountryInput = ( props: CountryInputProps ): JSX.Element => {
	return <CountryInput countries={ COUNTRIES } { ...props } />;
};

export default ShippingCountryInput;
