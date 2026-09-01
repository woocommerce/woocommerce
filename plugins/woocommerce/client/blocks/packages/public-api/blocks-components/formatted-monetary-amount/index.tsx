/**
 * External dependencies
 */
import { NumericFormat } from 'react-number-format';
import type {
	NumberFormatValues,
	NumericFormatProps,
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
	extends Omit< NumericFormatProps, 'onValueChange' | 'displayType' > {
	className?: string;
	displayType?: NumericFormatProps[ 'displayType' ] | undefined;
	allowNegative?: boolean;
	isAllowed?: ( formattedValue: NumberFormatValues ) => boolean;
	value: number | string; // Value of money amount.
	currency?: Currency | undefined; // Currency configuration object. Defaults to site currency.
	onValueChange?: ( unit: number ) => void; // Function to call when value changes.
	style?: React.CSSProperties | undefined;
	// Render prop receiving the formatted price string; forwarded to
	// NumericFormat.
	renderText?: NonNullable< NumericFormatProps[ 'renderText' ] >;
}

/**
 * Maps the currency configuration onto the props NumericFormat expects.
 */
const currencyToNumericFormatProps = ( currency: Currency ) => {
	const { prefix, suffix, thousandSeparator, decimalSeparator } = currency;
	// Decode HTML entities in separators
	const decodedThousandSeparator = decodeHtmlEntities( thousandSeparator );
	// NumericFormat throws when both separators are identical; an empty
	// decimal separator would collide with the thousand separator cleared
	// below, so fall back to '.'.
	const decodedDecimalSeparator =
		decodeHtmlEntities( decimalSeparator ) || '.';

	const hasDuplicateSeparator =
		decodedThousandSeparator === decodedDecimalSeparator;
	if ( hasDuplicateSeparator ) {
		// eslint-disable-next-line no-console
		console.warn(
			'Thousand separator and decimal separator are the same. This may cause formatting issues.'
		);
	}
	return {
		thousandSeparator: hasDuplicateSeparator
			? ''
			: decodedThousandSeparator,
		decimalSeparator: decodedDecimalSeparator,
		fixedDecimalScale: true,
		prefix: decodeHtmlEntities( prefix ),
		suffix: decodeHtmlEntities( suffix ),
		valueIsNumericString: true,
	};
};

/**
 * FormattedMonetaryAmount component.
 *
 * Takes a price and returns a formatted price using the NumericFormat component.
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
	const numericFormatProps = {
		...props,
		...currencyToNumericFormatProps( currency ),
		decimalScale,
		value: undefined,
		currency: undefined,
		onValueChange: undefined,
	};

	// Wrapper for NumericFormat onValueChange which handles subunit conversion.
	// Like v4, this also fires for `value` prop changes; kept deliberately so
	// the major bump does not change the callback's behaviour.
	const onValueChangeWrapper = onValueChange
		? ( values: NumberFormatValues ) => {
				const minorUnitValue = +values.value * 10 ** currency.minorUnit;
				onValueChange( minorUnitValue );
		  }
		: () => void 0;

	return (
		<NumericFormat
			className={ classes }
			displayType={ displayType }
			translate="no"
			{ ...numericFormatProps }
			value={ priceValue }
			onValueChange={ onValueChangeWrapper }
		/>
	);
};

export default FormattedMonetaryAmount;
