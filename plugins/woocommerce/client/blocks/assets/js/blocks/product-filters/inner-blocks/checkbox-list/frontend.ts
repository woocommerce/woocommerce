/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { DerivedSelectableItem } from '../../../../types/type-defs/selectable-items';

type CheckboxListContext = {
	storeNamespace: string;
	displayLimit: number;
};

type ParentItemContext = {
	item?: DerivedSelectableItem;
};

type CheckboxListStore = {
	state: {
		isExpanded: boolean;
		itemHidden: boolean;
		ratingStyle: string;
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

const { state }: CheckboxListStore = store< CheckboxListStore >(
	'woocommerce/product-filter-checkbox-list',
	{
		state: {
			isExpanded: false,
			get itemHidden(): boolean {
				if ( state.isExpanded ) return false;
				const { storeNamespace, displayLimit } =
					getContext< CheckboxListContext >();
				const item = getParentItem( storeNamespace );
				if ( ! item ) return false;
				return item.index >= displayLimit;
			},
			get ratingStyle(): string {
				const { storeNamespace } = getContext< CheckboxListContext >();
				const item = getParentItem( storeNamespace );
				if ( ! item ) return '';
				return `width: ${ Number( item.value ) * 20 }%`;
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

export type { CheckboxListStore };
export { state as checkboxListState };
