/**
 * External dependencies
 */
import { FieldLocaleOverrides, FormFields } from '@woocommerce/settings';

export type CountryData = {
	allowBilling: boolean;
	allowShipping: boolean;
	states: Record< string, string >;
	/**
	 * PHP-defined ordering of state keys. Use this when iterating states because
	 * JavaScript reorders integer-like string keys numerically when using
	 * Object.keys/Object.entries, which would otherwise lose the merchant-defined
	 * order set via the `woocommerce_states` filter.
	 */
	stateOrder?: string[];
	locale: Record< keyof FormFields, FieldLocaleOverrides >;
	format?: string;
};
