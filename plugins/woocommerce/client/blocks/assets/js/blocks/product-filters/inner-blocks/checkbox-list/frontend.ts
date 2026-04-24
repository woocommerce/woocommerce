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
	item?: DerivedSelectableItem & { index?: number };
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

const { state }: CheckboxListStore = store< CheckboxListStore >(
	'woocommerce/product-filter-checkbox-list',
	{
		state: {
			isExpanded: false,
			get itemHidden(): boolean {
				if ( state.isExpanded ) return false;
				const { storeNamespace, displayLimit } =
					getContext< CheckboxListContext >();
				const parentCtx =
					getContext< ParentItemContext >( storeNamespace );
				if ( ! parentCtx.item ) return false;
				const { index } = parentCtx.item;
				if ( typeof index !== 'number' ) return false;
				return index >= displayLimit;
			},
			get ratingStyle(): string {
				const { storeNamespace } =
					getContext< CheckboxListContext >();
				const parentCtx =
					getContext< ParentItemContext >( storeNamespace );
				if ( ! parentCtx.item ) return '';
				return `width: ${ Number( parentCtx.item.value ) * 20 }%`;
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
