/**
 * External dependencies
 */
import { ALLOWED_COUNTRIES } from '@woocommerce/block-settings';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import CountryInput from './country-input';
import type { CountryInputProps } from './CountryInputProps';
import { Select } from '../select';

const BillingCountryInput = ( props: CountryInputProps ): JSX.Element => {
	const options = Object.entries( ALLOWED_COUNTRIES ).map(
		( [ countryCode, countryName ] ) => ( {
			value: countryCode,
			label: decodeEntities( countryName ),
		} )
	);

	const { errorMessage: _, errorId: __, ...restOfProps } = props;

	return (
		<>
			{ /* <CountryInput countries={ ALLOWED_COUNTRIES } { ...props } /> */ }
			<Select { ...restOfProps } options={ options } />
		</>
	);
};

export default BillingCountryInput;
