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
import type { ProductBulkEditFormData } from '../../product-edit/bulk-edit';
import { isBulkNumericPercentEdit } from '../../product-edit/bulk-edit';

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
	disabled?: boolean;
	placeholder?: string;
	hideLabelFromVision?: boolean;
	showPercentAdornment?: boolean;
};

export function CurrencyControl( {
	id,
	label,
	value,
	onChange,
	customValidity,
	disabled = false,
	placeholder,
	hideLabelFromVision,
	showPercentAdornment = false,
}: CurrencyControlProps ) {
	const prefix =
		! showPercentAdornment && isCurrencyLeft ? (
			<InputControlPrefixWrapper>{ symbol }</InputControlPrefixWrapper>
		) : undefined;
	let suffix;

	if ( showPercentAdornment ) {
		suffix = <InputControlSuffixWrapper>%</InputControlSuffixWrapper>;
	} else if ( ! isCurrencyLeft ) {
		suffix = (
			<InputControlSuffixWrapper>{ symbol }</InputControlSuffixWrapper>
		);
	}

	return (
		// eslint-disable-next-line @typescript-eslint/no-unsafe-call -- ValidatedInputControl is a private API
		<ValidatedInputControl
			id={ id }
			label={ label }
			hideLabelFromVision={ hideLabelFromVision }
			value={ value }
			onChange={ onChange }
			placeholder={ placeholder }
			type="number"
			min={ 0 }
			step={ step }
			customValidity={ customValidity }
			disabled={ disabled }
			prefix={ prefix }
			suffix={ suffix }
		/>
	);
}

/**
 * Shared Edit component for currency fields.
 * Renders a number input with min=0 and currency prefix/suffix.
 *
 * @param root0                     Props from DataForm.
 * @param root0.data                Current product entity record.
 * @param root0.field               Normalized field definition.
 * @param root0.hideLabelFromVision Whether to visually hide the control label.
 * @param root0.onChange            Callback to update entity values.
 * @param root0.validity            Per-rule validation state from useFormValidity.
 */
export function CurrencyInput( {
	data,
	field,
	hideLabelFromVision,
	onChange,
	validity,
}: DataFormControlProps< ProductEntityRecord > ) {
	const fieldId = field.id as CurrencyField;
	const disabled = field.isDisabled( { item: data, field } );

	return (
		<CurrencyControl
			id={ `currency-input-${ fieldId }` }
			label={ field.label }
			hideLabelFromVision={ hideLabelFromVision }
			value={ data[ fieldId ] ?? '' }
			placeholder={ field.placeholder }
			onChange={ ( newValue: string ) => {
				onChange( { [ fieldId ]: newValue } );
			} }
			customValidity={ validity?.custom }
			disabled={ disabled }
			showPercentAdornment={ isBulkNumericPercentEdit(
				data as ProductBulkEditFormData,
				fieldId
			) }
		/>
	);
}
