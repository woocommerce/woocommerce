/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { SelectableItem } from '../../../../types/type-defs/selectable-items';

type ItemWithIndex = SelectableItem & { index?: number; color?: string };

type CheckboxListContext = {
	storeNamespace: string;
	displayLimit: number;
	isExpanded: boolean;
};

type ParentItemContext = {
	item?: ItemWithIndex;
};

type CheckboxListStore = {
	state: {
		itemHidden: boolean;
		ratingStyle: string;
		colorSwatchStyle: string;
		isColorSwatchEmpty: boolean;
	};
	actions: {
		showAll: () => void;
	};
};

function getParentItem( storeNamespace: string ): ItemWithIndex | undefined {
	const parentCtx = getContext< ParentItemContext >( storeNamespace );
	return parentCtx.item;
}

const { state }: CheckboxListStore = store< CheckboxListStore >(
	'woocommerce/product-filter-checkbox-list',
	{
		state: {
			get itemHidden(): boolean {
				const { isExpanded, storeNamespace, displayLimit } =
					getContext< CheckboxListContext >();
				if ( isExpanded ) return false;
				const item = getParentItem( storeNamespace );
				if ( ! item ) return false;
				if ( item.selected ) return false;
				if ( item.index === undefined ) return false;
				return item.index >= displayLimit;
			},
			get ratingStyle(): string {
				const { storeNamespace } = getContext< CheckboxListContext >();
				const item = getParentItem( storeNamespace );
				if ( ! item ) return '';
				return `width: ${ Number( item.value ) * 20 }%`;
			},
			get colorSwatchStyle(): string {
				const { storeNamespace } = getContext< CheckboxListContext >();
				const item = getParentItem( storeNamespace );
				if ( ! item?.color ) return '';
				return `background-color: ${ item.color }`;
			},
			get isColorSwatchEmpty(): boolean {
				const { storeNamespace } = getContext< CheckboxListContext >();
				const item = getParentItem( storeNamespace );
				return ! item?.color;
			},
		},
		actions: {
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
