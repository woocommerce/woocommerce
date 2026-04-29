/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { DerivedSelectableItem } from '../../../../types/type-defs/selectable-items';

type ChipsContext = {
	storeNamespace: string;
	displayLimit: number;
};

type ParentItemContext = {
	item?: DerivedSelectableItem;
};

type ChipsStore = {
	state: {
		isExpanded: boolean;
		itemHidden: boolean;
	};
	actions: {
		showAll: () => void;
	};
};

function getParentItem(
	storeNamespace: string
): DerivedSelectableItem | undefined {
	const parentCtx = getContext< ParentItemContext >( storeNamespace );
	return parentCtx.item;
}

const { state }: ChipsStore = store< ChipsStore >(
	'woocommerce/product-filter-chips',
	{
		state: {
			isExpanded: false,
			get itemHidden(): boolean {
				if ( state.isExpanded ) return false;
				const { storeNamespace, displayLimit } =
					getContext< ChipsContext >();
				const item = getParentItem( storeNamespace );
				if ( ! item ) return false;
				return item.index >= displayLimit;
			},
		},
		actions: {
			showAll() {
				state.isExpanded = true;
			},
		},
	},
	{ lock: true }
);

export type { ChipsStore };
export { state as chipsState };
