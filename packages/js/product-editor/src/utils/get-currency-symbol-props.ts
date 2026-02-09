type CurrencyConfig = {
	code: string;
	symbol: string;
	symbolPosition: string;
	decimalSeparator: string;
	priceFormat: string;
	thousandSeparator: string;
	precision: number;
};

/**
 * Get input props for currency related values and symbol positions.
 *
 * @param {Object} currencyConfig - Currency context
 * @return {Object} Props.
 */
export const getCurrencySymbolProps = ( currencyConfig: CurrencyConfig ) => {
	const { symbol, symbolPosition } = currencyConfig;
	const currencyPosition = symbolPosition.includes( 'left' )
		? 'prefix'
		: 'suffix';

	return {
		[ currencyPosition ]: symbol,
	};
};
