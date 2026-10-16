/**
 * External dependencies
 */
import { FieldLocaleOverrides, FormFields } from '@woocommerce/settings';

export type CountryData = {
	allowBilling: boolean;
	allowShipping: boolean;
	states: Record< string, string >;
	locale: Record< keyof FormFields, FieldLocaleOverrides >;
	format?: string;
};
