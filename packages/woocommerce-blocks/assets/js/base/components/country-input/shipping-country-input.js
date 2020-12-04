/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { SHIPPING_COUNTRIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CountryInput from './country-input.js';

const ShippingCountryInput = ( props ) => {
	return <CountryInput countries={ SHIPPING_COUNTRIES } { ...props } />;
};

ShippingCountryInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default ShippingCountryInput;
