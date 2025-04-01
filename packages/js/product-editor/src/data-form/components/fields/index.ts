/**
 * Internal dependencies
 */
import { initNameField } from './name';
import { initRegularPriceField } from './regular-price';
import { initSalePriceField } from './sale-price';

export function initFields() {
	initNameField();
	initRegularPriceField();
	initSalePriceField();
}

export * from './registration';
