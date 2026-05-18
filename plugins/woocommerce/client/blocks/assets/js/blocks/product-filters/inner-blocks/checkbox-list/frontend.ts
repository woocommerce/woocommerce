/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type {
	SelectableItem,
	SelectableItemsParentStore,
} from '../../../../types/type-defs/selectable-items';

type CheckboxListItem = SelectableItem< { color?: string; index?: number } >;

type CheckboxListContext = {
	storeNamespace: string;
	displayLimit: number;
	isExpanded: boolean;
};

type CheckboxListStore = {
	state: {
		items: CheckboxListItem[];
		itemHidden: boolean;
		ratingStyle: string;
		colorSwatchStyle: string;
		isColorSwatchEmpty: boolean;
	};
	actions: {
		toggle: () => void;
		showAll: () => void;
	};
};

function getParentStore( storeNamespace: string ) {
	return store< SelectableItemsParentStore< { color?: string } > >(
		storeNamespace
	);
}

function getCurrentItem(): CheckboxListItem | undefined {
	const context = getContext< { item?: CheckboxListItem } >();
	return context.item;
}

const { state }: CheckboxListStore = store< CheckboxListStore >(
	'woocommerce/product-filter-checkbox-list',
	{
		state: {
			get items(): CheckboxListItem[] {
				const { storeNamespace } = getContext< CheckboxListContext >();
				return getParentStore(
					storeNamespace
				).state.selectableItems.map( ( item, index ) => ( {
					...item,
					index,
				} ) );
			},
			get itemHidden(): boolean {
				const { isExpanded, displayLimit } =
					getContext< CheckboxListContext >();
				if ( isExpanded ) return false;
				const item = getCurrentItem();
				if ( ! item ) return false;
				if ( item.selected ) return false;
				if ( item.index === undefined ) return false;
				return item.index >= displayLimit;
			},
			get ratingStyle(): string {
				const item = getCurrentItem();
				if ( ! item ) return '';
				return `width: ${ Number( item.value ) * 20 }%`;
			},
			get colorSwatchStyle(): string {
				const item = getCurrentItem();
				if ( ! item?.color ) return '';
				return `background-color: ${ item.color }`;
			},
			get isColorSwatchEmpty(): boolean {
				const item = getCurrentItem();
				return ! item?.color;
			},
		},
		actions: {
			toggle() {
				const { storeNamespace } = getContext< CheckboxListContext >();
				getParentStore( storeNamespace ).actions.toggle(
					getCurrentItem()
				);
			},
			showAll() {
				const context = getContext< CheckboxListContext >();
				context.isExpanded = true;
			},
		},
	},
	{ lock: true }
);

export type { CheckboxListStore };
export { state as checkboxListState };
