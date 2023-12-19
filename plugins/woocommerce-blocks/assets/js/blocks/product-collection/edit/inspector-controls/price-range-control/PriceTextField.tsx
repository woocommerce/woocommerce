/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getCurrency } from '@woocommerce/price-format';
import { Currency } from '@woocommerce/types';
import {
	// @ts-expect-error Using experimental features
	__experimentalInputControl as InputControl,
	// @ts-expect-error Using experimental features
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
} from '@wordpress/components';

interface PriceTextFieldProps {
	value: number;
	onChange: ( value: number | undefined ) => void;
	label?: string;
	suffix?: string;
}

const formatNumber = ( val: number, currency: Currency ): string => {
	// Round the number to the correct number of decimals
	const factor = Math.pow( 10, currency.minorUnit );
	const roundedValue = `${ Math.round( val * factor ) / factor }`;

	// Split the number into whole and decimal parts
	let [ whole, decimal ] = roundedValue.split( '.' );

	// Apply the thousand separator
	whole = whole.replace(
		/\B(?=(\d{3})+(?!\d))/g,
		currency.thousandSeparator
	);

	// If there is no decimal part, we don't need to add decimal separator
	if ( ! decimal ) {
		return whole;
	}

	// Reassemble the number with the correct decimal separator
	return currency?.decimalSeparator
		? `${ whole }${ currency.decimalSeparator }${ decimal }`
		: `${ whole }.${ decimal }`;
};

const formatValueWithCurrencySymbol = (
	val: number | undefined,
	currency: Currency
) => {
	if ( val === undefined || isNaN( val ) ) {
		return undefined;
	}

	let formattedNumber = formatNumber( val, currency );

	// Append prefix and suffix if they exist
	if ( currency?.prefix ) {
		formattedNumber = `${ currency.prefix }${ formattedNumber }`;
	}
	if ( currency?.suffix ) {
		formattedNumber = `${ formattedNumber }${ currency.suffix }`;
	}

	return formattedNumber;
};

const PriceTextField: React.FC< PriceTextFieldProps > = ( {
	value,
	onChange,
	label,
} ) => {
	const currency = getCurrency();

	const convertCurrencyStringToNumber = (
		val: string
	): number | undefined => {
		// First, remove the currency symbol if any
		const valueWithoutCurrencySymbol = val
			.replace( currency.prefix, '' )
			.replace( currency.suffix, '' );

		// Replace the thousand separator with an empty string and decimal separator with a dot
		const normalizedValue = valueWithoutCurrencySymbol
			.replace(
				new RegExp( `\\${ currency.thousandSeparator }`, 'g' ),
				''
			)
			.replace( new RegExp( `\\${ currency.decimalSeparator }` ), '.' );

		const numberValue = Number( normalizedValue );
		if ( isNaN( numberValue ) ) {
			return undefined;
		}

		// Price can't be negative
		if ( numberValue < 0 ) {
			return 0;
		}

		return numberValue;
	};

	return (
		<InputControl
			value={ formatValueWithCurrencySymbol( value, currency ) }
			onChange={ ( val: string ) => {
				const numberValue = convertCurrencyStringToNumber( val );
				onChange( numberValue );
			} }
			label={ label }
			prefix={
				<InputControlPrefixWrapper>{ label }</InputControlPrefixWrapper>
			}
			placeholder={ __( 'Auto', 'woocommerce' ) }
			isPressEnterToChange
			hideLabelFromVision
			type="text"
			style={ {
				textAlign: 'right',
			} }
			__next40pxDefaultSize
		/>
	);
};

export default PriceTextField;
