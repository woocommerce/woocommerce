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
import { decodeHtmlEntities } from '@woocommerce/utils';

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

// Matches characters from strong RTL scripts (Hebrew, Arabic, Syriac, Thaana,
// NKo, and their presentation forms).
const RTL_SCRIPT_REGEX = /[\u0591-\u08FF\uFB1D-\uFDFD\uFE70-\uFEFC]/;

/**
 * Wraps an RTL-script currency symbol in first-strong isolate characters
 * (U+2068 FSI … U+2069 PDI) — the plain-text equivalent of `dir="auto"` — so
 * the bidi algorithm cannot move it to the wrong side of the amount.
 * Surrounding whitespace stays outside the isolate. LTR symbols are returned
 * unchanged so rendered text is identical for LTR currencies.
 */
const isolateRtlSymbol = ( affix: string ): string => {
	if ( ! RTL_SCRIPT_REGEX.test( affix ) ) {
		return affix;
	}
	return affix.replace( /^(\s*)(.*?)(\s*)$/, '$1\u2068$2\u2069$3' );
};

/**
 * Formats currency data into the expected format for NumberFormat.
 */
const currencyToNumberFormat = (
	currency: Currency,
	displayType: NumberFormatProps[ 'displayType' ]
) => {
	const { prefix, suffix, thousandSeparator, decimalSeparator } = currency;
	// Decode HTML entities in separators
	const decodedThousandSeparator = decodeHtmlEntities( thousandSeparator );
	const decodedDecimalSeparator = decodeHtmlEntities( decimalSeparator );

	const hasDuplicateSeparator =
		decodedThousandSeparator === decodedDecimalSeparator;
	if ( hasDuplicateSeparator ) {
		// eslint-disable-next-line no-console
		console.warn(
			'Thousand separator and decimal separator are the same. This may cause formatting issues.'
		);
	}

	const decodedPrefix = decodeHtmlEntities( prefix );
	const decodedSuffix = decodeHtmlEntities( suffix );
	// Isolate characters are invisible control characters; they must not leak
	// into editable input values, so only display text gets them.
	const isDisplayText = displayType === 'text';
	return {
		thousandSeparator: hasDuplicateSeparator
			? ''
			: decodedThousandSeparator,
		decimalSeparator: decodedDecimalSeparator,
		fixedDecimalScale: true,
		prefix: isDisplayText
			? isolateRtlSymbol( decodedPrefix )
			: decodedPrefix,
		suffix: isDisplayText
			? isolateRtlSymbol( decodedSuffix )
			: decodedSuffix,
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
		...currencyToNumberFormat( currency, displayType ),
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
			translate="no"
			{ ...numberFormatProps }
			value={ priceValue }
			onValueChange={ onValueChangeWrapper }
		/>
	);
};

export default FormattedMonetaryAmount;
