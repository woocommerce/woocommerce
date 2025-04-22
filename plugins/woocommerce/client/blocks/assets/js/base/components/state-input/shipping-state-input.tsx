/**
 * External dependencies
 */
import { STATES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import StateInput from './state-input';
import type { StateInputProps } from './StateInputProps';

const ShippingStateInput = ( props: StateInputProps ): JSX.Element => {
	return <StateInput states={ STATES } { ...props } />;
};

export default ShippingStateInput;
