/**
 * Internal dependencies
 */
import { initNameField } from './name';
import { initRegularPriceField } from './regular-price';
import { initSalePriceField } from './sale-price';
import { initScheduleSaleField } from './schedule-sale';
import { initTextAreaField } from './text-area';

export function initFields() {
	initNameField();
	initRegularPriceField();
	initSalePriceField();
	initScheduleSaleField();
	initTextAreaField();
}

export * from './registration';
