/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { SHIPPING_COUNTIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import StateInput from './state-input.js';

const ShippingStateInput = ( props ) => {
	return <StateInput counties={ SHIPPING_COUNTIES } { ...props } />;
};

ShippingStateInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default ShippingStateInput;
