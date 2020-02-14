/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { ALLOWED_COUNTIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CountyInput from './county-input.js';

const BillingCountyInput = ( props ) => {
	return <CountyInput counties={ ALLOWED_COUNTIES } { ...props } />;
};

BillingCountyInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default BillingCountyInput;
