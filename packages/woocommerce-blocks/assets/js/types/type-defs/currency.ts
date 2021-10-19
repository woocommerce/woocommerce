export interface Currency {
	code: string;
	decimalSeparator: string;
	minorUnit: number;
	prefix: string;
	suffix: string;
	symbol: string;
	thousandSeparator: string;
}

export interface CurrencyResponse {
	currency_code: string;
	currency_symbol: string;
	currency_minor_unit: number;
	currency_decimal_separator: string;
	currency_thousand_separator: string;
	currency_prefix: string;
	currency_suffix: string;
}

export type SymbolPosition = 'left' | 'left_space' | 'right' | 'right_space';
