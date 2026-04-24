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

type CheckboxListContext = {
	storeNamespace: string;
	displayLimit: number;
	item?: DerivedSelectableItem;
};

type CheckboxListItem = DerivedSelectableItem & { hidden: boolean };

type CheckboxListStore = {
	state: {
		isExpanded: boolean;
		items: readonly CheckboxListItem[];
		ratingStyle: string;
	};
	actions: {
		showAll: () => void;
		toggle: () => void;
	};
};

const { state }: CheckboxListStore = store< CheckboxListStore >(
	'woocommerce/product-filter-checkbox-list',
	{
		state: {
			isExpanded: false,
			get items(): readonly CheckboxListItem[] {
				const { storeNamespace, displayLimit } =
					getContext< CheckboxListContext >();
				return store< SelectableItemsParentStore >(
					storeNamespace
				).state.selectableItems.map( ( item, index ) => ( {
					...item,
					hidden:
						! state.isExpanded && index >= displayLimit,
				} ) );
			},
			get ratingStyle() {
				const { item } = getContext< CheckboxListContext >();
				if ( ! item ) return '';
				return `width: ${ Number( item.value ) * 20 }%`;
			},
		},
		actions: {
			showAll() {
				state.isExpanded = true;
			},
			toggle() {
				const { storeNamespace, item } =
					getContext< CheckboxListContext >();
				if ( ! item ) return;
				store< SelectableItemsParentStore >(
					storeNamespace
				).actions.toggle( item as SelectableItem );
			},
		},
	},
	{ lock: true }
);

export type { CheckboxListStore };
export { state as checkboxListState };
