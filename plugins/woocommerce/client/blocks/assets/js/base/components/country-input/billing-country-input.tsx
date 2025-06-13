/**
 * External dependencies
 */
import { COUNTRIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CountryInput from './country-input';
import type { CountryInputProps } from './CountryInputProps';

const BillingCountryInput = ( props: CountryInputProps ): JSX.Element => {
	const { ...restOfProps } = props;

	return <CountryInput countries={ COUNTRIES } { ...restOfProps } />;
};

export default BillingCountryInput;
