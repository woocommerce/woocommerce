/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { DerivedSelectableItem } from '../../../../types/type-defs/selectable-items';

type ElementsContext = {
	storeNamespace: string;
	displayLimit: number;
};

type ParentItemContext = {
	item?: DerivedSelectableItem;
};

type ElementsStore = {
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

const { state }: ElementsStore = store< ElementsStore >(
	'woocommerce/product-filter-elements',
	{
		state: {
			isExpanded: false,
			get itemHidden(): boolean {
				if ( state.isExpanded ) return false;
				const { storeNamespace, displayLimit } =
					getContext< ElementsContext >();
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

export type { ElementsStore };
export { state as elementsState };
