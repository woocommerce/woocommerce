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
	isExpanded: boolean;
};

type ParentItemContext = {
	item?: DerivedSelectableItem;
};

type ChipsStore = {
	state: {
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
			get itemHidden(): boolean {
				const { isExpanded, storeNamespace, displayLimit } =
					getContext< ChipsContext >();
				if ( isExpanded ) return false;
				const item = getParentItem( storeNamespace );
				if ( ! item ) return false;
				if ( item.selected ) return false;
				return item.index >= displayLimit;
			},
		},
		actions: {
			showAll() {
				const context = getContext< ChipsContext >();
				context.isExpanded = true;
			},
		},
	},
	{ lock: true }
);

export type { ChipsStore };
export { state as chipsState };
