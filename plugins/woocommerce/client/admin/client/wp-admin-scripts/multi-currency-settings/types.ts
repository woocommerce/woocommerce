export interface MultiCurrencyCurrency {
	id: string;
	code: string;
	name: string;
	rate: number;
	symbol: string;
	symbol_position: string;
	is_zero_decimal: boolean;
	is_default: boolean;
	charm: number;
	rounding: string;
	last_updated: number | null;
}

export interface StoreCurrenciesResponse {
	available: Record< string, MultiCurrencyCurrency >;
	enabled: Record< string, MultiCurrencyCurrency >;
	default: MultiCurrencyCurrency;
}
