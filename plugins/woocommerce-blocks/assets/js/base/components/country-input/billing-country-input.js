/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import CountryInput from './country-input.js';

const BillingCountryInput = ( props ) => {
	return (
		<CountryInput
			countries={ getSetting( 'allowedCountries', {} ) }
			{ ...props }
		/>
	);
};

BillingCountryInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default BillingCountryInput;
