/* eslint-disable @wordpress/no-unsafe-wp-apis -- Currency inputs use wrappers for the private ValidatedInputControl API. */
/**
 * External dependencies
 */
import {
	privateApis,
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
	__experimentalInputControlSuffixWrapper as InputControlSuffixWrapper,
} from '@wordpress/components';

import type { DataFormControlProps } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';

import type { ProductEntityRecord } from '../types';

import { getCurrencyObject } from '../utils/currency';

const { ValidatedInputControl } = unlock( privateApis );

const { symbol, symbolPosition, precision } = getCurrencyObject();
const isCurrencyLeft =
	symbolPosition === 'left' || symbolPosition === 'left_space';
// Step matches store decimal precision (e.g. precision=2 → step=0.01).
const step = Math.pow( 10, -precision );

type CurrencyField = 'regular_price' | 'sale_price';

type CurrencyControlProps = {
	id: string;
	label: string;
	value: string | number;
	onChange: ( newValue: string ) => void;
	customValidity?: NonNullable<
		DataFormControlProps< ProductEntityRecord >[ 'validity' ]
	>[ 'custom' ];
};

export function CurrencyControl( {
	id,
	label,
	value,
	onChange,
	customValidity,
}: CurrencyControlProps ) {
	return (
		// eslint-disable-next-line @typescript-eslint/no-unsafe-call -- ValidatedInputControl is a private API
		<ValidatedInputControl
			id={ id }
			label={ label }
			value={ value }
			onChange={ onChange }
			type="number"
			min={ 0 }
			step={ step }
			customValidity={ customValidity }
			prefix={
				isCurrencyLeft ? (
					<InputControlPrefixWrapper>
						{ symbol }
					</InputControlPrefixWrapper>
				) : undefined
			}
			suffix={
				! isCurrencyLeft ? (
					<InputControlSuffixWrapper>
						{ symbol }
					</InputControlSuffixWrapper>
				) : undefined
			}
		/>
	);
}

/**
 * Shared Edit component for currency fields.
 * Renders a number input with min=0 and currency prefix/suffix.
 *
 * @param root0          Props from DataForm.
 * @param root0.data     Current product entity record.
 * @param root0.field    Normalized field definition.
 * @param root0.onChange Callback to update entity values.
 * @param root0.validity Per-rule validation state from useFormValidity.
 */
export function CurrencyInput( {
	data,
	field,
	onChange,
	validity,
}: DataFormControlProps< ProductEntityRecord > ) {
	const fieldId = field.id as CurrencyField;

	return (
		<CurrencyControl
			id={ `currency-input-${ fieldId }` }
			label={ field.label }
			value={ data[ fieldId ] ?? '' }
			onChange={ ( newValue: string ) => {
				onChange( { [ fieldId ]: newValue } );
			} }
			customValidity={ validity?.custom }
		/>
	);
}
