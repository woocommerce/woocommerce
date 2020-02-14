/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { SHIPPING_COUNTIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CountyInput from './county-input.js';

const ShippingCountyInput = ( props ) => {
	return <CountyInput counties={ SHIPPING_COUNTIES } { ...props } />;
};

ShippingCountyInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default ShippingCountyInput;
