export interface RemovableItem {
	id: string;
	type: string;
	value: string;
	label: string;
}

export interface RemovableItemsContext {
	items: RemovableItem[];
	storeNamespace: string;
}

export type RemovableItemsBlockContext = {
	// eslint-disable-next-line @typescript-eslint/naming-convention
	'woocommerce/removableItems': RemovableItemsContext;
};

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
