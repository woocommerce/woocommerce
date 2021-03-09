/* eslint-disable camelcase*/

/**
 * External dependencies
 */
import type { ReactElement } from 'react';

export interface Rate {
	currency_code: string;
	currency_decimal_separator: string;
	currency_minor_unit: number;
	currency_prefix: string;
	currency_suffix: string;
	currency_symbol: string;
	currency_thousand_separator: string;
	delivery_time: string;
	description: string;
	id: number;
	meta_data: [ { key: 'Items'; value: 'Beanie &times; 2' } ];
	method_id: string;
	name: string;
	price: string;
	rate_id: string;
	selected: boolean;
	taxes: string;
}

export interface PackageRateOption {
	label: string;
	value: string;
	description?: string | ReactElement;
	secondaryLabel?: string | ReactElement;
	secondaryDescription?: string;
	id?: string;
}
