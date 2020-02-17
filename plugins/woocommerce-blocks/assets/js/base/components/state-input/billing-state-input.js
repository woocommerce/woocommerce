/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { ALLOWED_COUNTIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import StateInput from './state-input.js';

const BillingStateInput = ( props ) => {
	return <StateInput counties={ ALLOWED_COUNTIES } { ...props } />;
};

BillingStateInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default BillingStateInput;
