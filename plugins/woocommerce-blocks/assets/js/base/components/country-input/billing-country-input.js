/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { ALLOWED_COUNTRIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CountryInput from './country-input.js';

const BillingCountryInput = ( props ) => {
	return <CountryInput countries={ ALLOWED_COUNTRIES } { ...props } />;
};

BillingCountryInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default BillingCountryInput;
