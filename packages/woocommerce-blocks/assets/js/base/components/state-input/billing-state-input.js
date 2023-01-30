/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { ALLOWED_STATES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import StateInput from './state-input.js';

const BillingStateInput = ( props ) => {
	return <StateInput states={ ALLOWED_STATES } { ...props } />;
};

BillingStateInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default BillingStateInput;
