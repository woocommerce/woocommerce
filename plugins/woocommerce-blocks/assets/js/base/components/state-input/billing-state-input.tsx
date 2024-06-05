/**
 * External dependencies
 */
import { ALLOWED_STATES } from '@woocommerce/block-settings';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import StateInput from './state-input';
import type { StateInputProps } from './StateInputProps';
import { Select } from '../select';

const BillingStateInput = ( props: StateInputProps ): JSX.Element => {
	const countryStates = ALLOWED_STATES[ props.country ];
	const options = Object.keys( countryStates ).map( ( key ) => ( {
		value: key,
		label: decodeEntities( countryStates[ key ] ),
		'data-alternate-values': countryStates[ key ],
	} ) );

	const {
		errorMessage: _,
		errorId: __,
		country: ___,
		...restOfProps
	} = props;

	console.log( options );

	return (
		<>
			{ /* <StateInput states={ ALLOWED_STATES } { ...props } /> */ }
			{ options.length && (
				<Select { ...restOfProps } options={ options } />
			) }
		</>
	);
};

export default BillingStateInput;
