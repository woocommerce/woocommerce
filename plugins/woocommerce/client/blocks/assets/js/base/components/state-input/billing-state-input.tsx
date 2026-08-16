/**
 * External dependencies
 */
import { STATES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import StateInput from './state-input';
import type { StateInputProps } from './StateInputProps';

const BillingStateInput = ( props: StateInputProps ): JSX.Element => {
	const { ...restOfProps } = props;

	return <StateInput states={ STATES } { ...restOfProps } />;
};

export default BillingStateInput;
