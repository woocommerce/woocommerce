/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { SHIPPING_STATES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import StateInput from './state-input.js';

const ShippingStateInput = ( props ) => {
	return <StateInput states={ SHIPPING_STATES } { ...props } />;
};

ShippingStateInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default ShippingStateInput;
