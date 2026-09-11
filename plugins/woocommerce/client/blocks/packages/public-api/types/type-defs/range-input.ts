/**
 * Internal dependencies
 */
import type { HTMLElementEvent } from './utils';

export interface RangeInputContext {
	min: number;
	max: number;
	currentMin: number;
	currentMax: number;
	step?: number;
	storeNamespace: string;
	isLoading?: boolean;
}

export type RangeInputBlockContext = {
	// eslint-disable-next-line @typescript-eslint/naming-convention
	'woocommerce/rangeInput': RangeInputContext;
};

/**
 * Contract every parent store referenced by `storeNamespace` MUST satisfy.
 * Use with `satisfies` to get compile-time enforcement:
 *
 *   myRangeStore satisfies RangeInputParentStore;
 *
 * Inner blocks call `actions.setMin` / `actions.setMax` on input change.
 * The parent derives its own semantics (currency, step, labels) from `context.min`/`max` etc.
 */
export interface RangeInputParentStore {
	actions: {
		setMin: ( event: HTMLElementEvent< HTMLInputElement > ) => void;
		setMax: ( event: HTMLElementEvent< HTMLInputElement > ) => void;
	};
}
