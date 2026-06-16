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

export type StoreSettingsBoolean = boolean | 'yes' | 'no';
export type RenderingMode = 'speed' | 'cache';

export interface StoreSettingsResponse {
	wcpay_multi_currency_enable_auto_currency: StoreSettingsBoolean;
	wcpay_multi_currency_enable_storefront_switcher: StoreSettingsBoolean;
	wcpay_multi_currency_rendering_mode: RenderingMode;
	is_cache_optimized_feature_enabled: boolean;
	site_theme: string;
	date_format: string;
	time_format: string;
	store_url: string;
}

export interface StoreSettingsState {
	enableAutoCurrency: boolean;
	enableStorefrontSwitcher: boolean;
	renderingMode: RenderingMode;
	isCacheOptimizedFeatureEnabled: boolean;
	siteTheme: string;
}
