/**
 * External dependencies
 */
import { ALLOWED_COUNTRIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CountryInput from './country-input';
import type { CountryInputProps } from './CountryInputProps';
import { Select } from '../select';

const BillingCountryInput = ( props: CountryInputProps ): JSX.Element => {
	console.log( props );

	return (
		<>
			<CountryInput countries={ ALLOWED_COUNTRIES } { ...props } />
			<Select { ...props } />
		</>
	);
};

export default BillingCountryInput;
