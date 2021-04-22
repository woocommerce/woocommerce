/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import StateInput from './state-input.js';

const BillingStateInput = ( props ) => {
	return (
		<StateInput states={ getSetting( 'allowedStates', {} ) } { ...props } />
	);
};

BillingStateInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default BillingStateInput;
