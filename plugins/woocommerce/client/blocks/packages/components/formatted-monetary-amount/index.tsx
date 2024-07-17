/**
 * External dependencies
 */
import NumberFormat from 'react-number-format';
import type {
	NumberFormatValues,
	NumberFormatProps,
} from 'react-number-format';
import clsx from 'clsx';
import type { ReactElement } from 'react';
import type { Currency } from '@woocommerce/types';
import { SITE_CURRENCY } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './style.scss';

export interface FormattedMonetaryAmountProps
	extends Omit< NumberFormatProps, 'onValueChange' | 'displayType' > {
	className?: string;
	displayType?: NumberFormatProps[ 'displayType' ] | undefined;
	allowNegative?: boolean;
	isAllowed?: ( formattedValue: NumberFormatValues ) => boolean;
	value: number | string; // Value of money amount.
	currency?: Currency | undefined; // Currency configuration object. Defaults to site currency.
	onValueChange?: ( unit: number ) => void; // Function to call when value changes.
	style?: React.CSSProperties | undefined;
	renderText?: ( value: string ) => JSX.Element;
}

/**
 * Formats currency data into the expected format for NumberFormat.
 */
const currencyToNumberFormat = ( currency: Currency ) => {
	const { prefix, suffix, thousandSeparator, decimalSeparator } = currency;
	const hasDuplicateSeparator = thousandSeparator === decimalSeparator;
	if ( hasDuplicateSeparator ) {
		// eslint-disable-next-line no-console
		console.warn(
			'Thousand separator and decimal separator are the same. This may cause formatting issues.'
		);
	}
	return {
		thousandSeparator: hasDuplicateSeparator ? '' : thousandSeparator,
		decimalSeparator,
		fixedDecimalScale: true,
		prefix,
		suffix,
		isNumericString: true,
	};
};

/**
 * FormattedMonetaryAmount component.
 *
 * Takes a price and returns a formatted price using the NumberFormat component.
 *
 * More detailed docs on the additional props can be found here:https://s-yadav.github.io/react-number-format/docs/intro
 */
const FormattedMonetaryAmount = ( {
	className,
	value: rawValue,
	currency: rawCurrency = SITE_CURRENCY,
	onValueChange,
	displayType = 'text',
	...props
}: FormattedMonetaryAmountProps ): ReactElement | null => {
	// Merge currency configuration with site currency.
	const currency = {
		...SITE_CURRENCY,
		...rawCurrency,
	};

	// Convert values to int.
	const value =
		typeof rawValue === 'string' ? parseInt( rawValue, 10 ) : rawValue;

	if ( ! Number.isFinite( value ) ) {
		return null;
	}

	const priceValue = value / 10 ** currency.minorUnit;

	if ( ! Number.isFinite( priceValue ) ) {
		return null;
	}

	const classes = clsx(
		'wc-block-formatted-money-amount',
		'wc-block-components-formatted-money-amount',
		className
	);
	const decimalScale = props.decimalScale ?? currency?.minorUnit;
	const numberFormatProps = {
		...props,
		...currencyToNumberFormat( currency ),
		decimalScale,
		value: undefined,
		currency: undefined,
		onValueChange: undefined,
	};

	// Wrapper for NumberFormat onValueChange which handles subunit conversion.
	const onValueChangeWrapper = onValueChange
		? ( values: NumberFormatValues ) => {
				const minorUnitValue = +values.value * 10 ** currency.minorUnit;
				onValueChange( minorUnitValue );
		  }
		: () => void 0;

	return (
		<NumberFormat
			className={ classes }
			displayType={ displayType }
			{ ...numberFormatProps }
			value={ priceValue }
			onValueChange={ onValueChangeWrapper }
		/>
	);
};

export default FormattedMonetaryAmount;
