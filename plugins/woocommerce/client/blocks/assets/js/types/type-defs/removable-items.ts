export interface RemovableItem {
	type: string;
	value: string;
	label: string;
}

export interface RemovableItemsContext {
	items: RemovableItem[];
	storeNamespace: string;
}

/* eslint-disable @typescript-eslint/naming-convention -- WordPress block context key format */
export type RemovableItemsBlockContext = {
	'woocommerce/removableItems': RemovableItemsContext;
};
/* eslint-enable @typescript-eslint/naming-convention */

/**
 * Contract every parent store referenced by `storeNamespace` MUST satisfy.
 * Use with `satisfies` to get compile-time enforcement:
 *
 *   myStore satisfies RemovableItemsParentStore;
 *
 * `remove` removes a single item (reads `getContext().item`).
 * `removeAll` clears every item.
 */
export interface RemovableItemsParentStore {
	state: {
		removableItems: readonly RemovableItem[];
	};
	actions: {
		remove: () => void;
		removeAll: () => void;
	};
}
