/**
 * External dependencies
 */
import { ALLOWED_STATES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import StateInput from './state-input';
import type { StateInputProps } from './StateInputProps';

const BillingStateInput = ( props: StateInputProps ): JSX.Element => {
	// TODO - are errorMessage and errorId still relevant when select always has a value?
	const { errorMessage: _, errorId: __, ...restOfProps } = props;

	return <StateInput states={ ALLOWED_STATES } { ...restOfProps } />;
};

export default BillingStateInput;
