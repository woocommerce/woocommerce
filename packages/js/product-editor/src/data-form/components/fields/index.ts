/**
 * Internal dependencies
 */
import { initNameField } from './name';
import { initRegularPriceField } from './regular-price';
import { initSalePriceField } from './sale-price';
import { initScheduleSaleField } from './schedule-sale';

export function initFields() {
	initNameField();
	initRegularPriceField();
	initSalePriceField();
	initScheduleSaleField();
}

export * from './registration';
