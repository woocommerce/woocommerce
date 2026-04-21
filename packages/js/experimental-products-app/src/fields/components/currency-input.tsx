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

type PriceField = 'regular_price' | 'sale_price';

/**
 * Shared Edit component for currency price fields (regular_price, sale_price).
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
	const fieldId = field.id as PriceField;
	const value = data[ fieldId ] ?? '';

	return (
		// eslint-disable-next-line @typescript-eslint/no-unsafe-call -- ValidatedInputControl is a private API
		<ValidatedInputControl
			id={ `currency-input-${ fieldId }` }
			label={ field.label }
			value={ value }
			onChange={ ( newValue: string ) => {
				onChange( { [ fieldId ]: newValue } );
			} }
			type="number"
			min={ 0 }
			step={ step }
			customValidity={ validity?.custom }
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
