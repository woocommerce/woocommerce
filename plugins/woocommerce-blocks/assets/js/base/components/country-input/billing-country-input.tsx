/**
 * External dependencies
 */
import { ALLOWED_COUNTRIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CountryInput from './country-input';
import type { CountryInputProps } from './CountryInputProps';

const BillingCountryInput = ( props: CountryInputProps ): JSX.Element => {
	//  TODO - are errorMessage and errorId still relevant when select always has a value?
	const { errorMessage: _, errorId: __, ...restOfProps } = props;

	return (
		<>
			<CountryInput countries={ ALLOWED_COUNTRIES } { ...restOfProps } />
		</>
	);
};

export default BillingCountryInput;
