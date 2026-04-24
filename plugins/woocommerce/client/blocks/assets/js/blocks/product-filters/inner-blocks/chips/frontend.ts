/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type {
	DerivedSelectableItem,
	SelectableItem,
	SelectableItemsParentStore,
} from '../../../../types/type-defs/selectable-items';

type ChipsContext = {
	storeNamespace: string;
	displayLimit: number;
	item?: DerivedSelectableItem;
};

type ChipsItem = DerivedSelectableItem & { hidden: boolean };

type ChipsStore = {
	state: {
		isExpanded: boolean;
		items: readonly ChipsItem[];
	};
	actions: {
		showAll: () => void;
		toggle: () => void;
	};
};

const { state }: ChipsStore = store< ChipsStore >(
	'woocommerce/product-filter-chips',
	{
		state: {
			isExpanded: false,
			get items(): readonly ChipsItem[] {
				const { storeNamespace, displayLimit } =
					getContext< ChipsContext >();
				return store< SelectableItemsParentStore >(
					storeNamespace
				).state.selectableItems.map( ( item, index ) => ( {
					...item,
					hidden:
						! state.isExpanded && index >= displayLimit,
				} ) );
			},
		},
		actions: {
			showAll() {
				state.isExpanded = true;
			},
			toggle() {
				const { storeNamespace, item } = getContext< ChipsContext >();
				if ( ! item ) return;
				store< SelectableItemsParentStore >(
					storeNamespace
				).actions.toggle( item as SelectableItem );
			},
		},
	},
	{ lock: true }
);

export type { ChipsStore };
export { state as chipsState };
