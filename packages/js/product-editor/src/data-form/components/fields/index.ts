/**
 * Internal dependencies
 */
import { initNameField } from './name';
import { initRegularPriceField } from './regular-price';
export function initFields() {
	initNameField();
	initRegularPriceField();
}

export * from './registration';
