/**
 * External dependencies
 */
import { NumericFormat } from 'react-number-format';
import type {
	NumberFormatValues,
	NumericFormatProps,
	SourceInfo,
} from 'react-number-format';
import clsx from 'clsx';
import { useMemo } from '@wordpress/element';
import type { ReactElement, ReactNode } from 'react';
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
	// Hands rendering to the caller, replacing the element structure this
	// component composes; a price rendered this way loses the currency symbol
	// isolation. Prefer wrapping the component over using this.
	renderText?: NonNullable< NumericFormatProps[ 'renderText' ] >;
}

/**
 * Splits a currency prefix or suffix into the symbol and the spacing around it,
 * which the position setting bakes into the same string: "left with space"
 * gives "€ ", "left" gives "€".
 *
 * The spacing is returned separately so it can be placed outside the isolating
 * element; inside an RTL isolate a trailing space is drawn on the wrong side of
 * the symbol.
 */
const splitCurrencySymbolAndSpacing = ( currencySymbol: string ) => {
	// The pattern matches any string, including the empty one, so the match
	// cannot be null.
	const [ , before, symbol, after ] = currencySymbol.match(
		/^(\s*)(.*?)(\s*)$/
	) as RegExpMatchArray;
	return { before, symbol, after };
};

/**
 * Renders the currency symbol in its own element, mirroring the
 * `woocommerce-Price-currencySymbol` span `wc_price()` emits.
 *
 * `dir="auto"` keeps the symbol a self-contained run, so it cannot pull the
 * neighbouring digits into a right-to-left run and land on the wrong side of
 * the amount. A `<span dir="auto">` rather than a nested `<bdi>` to keep the
 * structure identical to the PHP-rendered price, which uses a span because it
 * has to survive `wp_kses_post()` (kses strips `bdi` but keeps `dir`).
 */
const renderIsolatedSymbol = ( currencySymbol: string ): ReactNode => {
	const { before, symbol, after } =
		splitCurrencySymbolAndSpacing( currencySymbol );

	// A symbol that is nothing but spacing has nothing to isolate.
	if ( ! symbol ) {
		return currencySymbol;
	}

	return (
		<>
			{ before }
			<span
				className="wc-block-components-formatted-money-amount__currency-symbol"
				dir="auto"
			>
				{ symbol }
			</span>
			{ after }
		</>
	);
};

/**
 * Maps the currency separators onto the props NumericFormat expects.
 */
const currencyToNumericFormatProps = ( currency: Currency ) => {
	const { thousandSeparator, decimalSeparator } = currency;
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
	return {
		thousandSeparator: hasDuplicateSeparator
			? ''
			: decodedThousandSeparator,
		decimalSeparator: decodedDecimalSeparator,
		fixedDecimalScale: true,
		valueIsNumericString: true,
	};
};

/**
 * Builds the NumericFormat `renderText` callback that composes a price out of
 * elements, matching the structure `wc_price()` produces.
 *
 * NumericFormat renders nothing of its own once `renderText` is set, so the
 * props it would have applied to its span arrive as the second argument and are
 * spread here instead.
 */
const createStructuredPriceRenderer =
	( {
		prefix,
		suffix,
		getInputRef,
	}: {
		prefix: string;
		suffix: string;
		getInputRef?: NumericFormatProps[ 'getInputRef' ] | undefined;
	} ): NonNullable< NumericFormatProps[ 'renderText' ] > =>
	( formattedValue, spanProps ) => {
		// `wc_price()` puts the negative sign inside the `<bdi>`, ahead of the
		// symbol, so split it off the amount.
		const isNegative = formattedValue.startsWith( '-' );

		return (
			// NumericFormat types the second argument as its own props rather
			// than as span attributes, so it is narrowed to what it is at
			// runtime: the attributes it would have put on the element.
			<span
				{ ...( spanProps as React.HTMLAttributes< HTMLSpanElement > ) }
				ref={ getInputRef as React.Ref< HTMLSpanElement > }
			>
				<bdi>
					{ isNegative ? '-' : '' }
					{ renderIsolatedSymbol( prefix ) }
					{ isNegative ? formattedValue.slice( 1 ) : formattedValue }
					{ renderIsolatedSymbol( suffix ) }
				</bdi>
			</span>
		);
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

	const decodedPrefix = decodeHtmlEntities( currency.prefix );
	const decodedSuffix = decodeHtmlEntities( currency.suffix );
	const { getInputRef } = props;

	const structuredPriceRenderer = useMemo(
		() =>
			createStructuredPriceRenderer( {
				prefix: decodedPrefix,
				suffix: decodedSuffix,
				getInputRef,
			} ),
		[ decodedPrefix, decodedSuffix, getInputRef ]
	);

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

	// Compose the price as markup so it matches the structure `wc_price()`
	// produces everywhere else in the store. An input value cannot hold
	// elements, and a consumer-supplied renderText owns its output, so both keep
	// the plain string.
	const renderAsMarkup = displayType === 'text' && ! props.renderText;

	const numericFormatProps = {
		...props,
		...currencyToNumericFormatProps( currency ),
		prefix: renderAsMarkup ? '' : decodedPrefix,
		suffix: renderAsMarkup ? '' : decodedSuffix,
		decimalScale,
		value: undefined,
		currency: undefined,
		onValueChange: undefined,
		renderText: renderAsMarkup ? structuredPriceRenderer : props.renderText,
	};

	// Wrapper for NumericFormat onValueChange which handles subunit conversion.
	const onValueChangeWrapper = onValueChange
		? ( values: NumberFormatValues, sourceInfo: SourceInfo ) => {
				// NumericFormat also fires when the `value` prop changes; only
				// user input counts as a change here. A prop-driven call echoes
				// the rounded display value back, which consumers would apply
				// as if the user had picked it. Compared as a string because
				// the SourceType enum only exists in the type declarations,
				// not in the runtime build.
				if ( ( sourceInfo.source as string ) !== 'event' ) {
					return;
				}
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
