/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { DerivedSelectableItem } from '../../../../types/type-defs/selectable-items';

type CountedSelectableItem = DerivedSelectableItem< {
	count?: unknown;
} >;

type CheckboxListContext = {
	storeNamespace: string;
	displayLimit: number;
};

type ParentItemContext = {
	item?: CountedSelectableItem;
};

type CheckboxListStore = {
	state: {
		isExpanded: boolean;
		itemHidden: boolean;
		itemCountHidden: boolean;
		itemCountText: string;
		ratingStyle: string;
	};
	actions: {
		showAll: () => void;
	};
};

function getParentItem(
	storeNamespace: string
): CountedSelectableItem | undefined {
	const parentCtx = getContext< ParentItemContext >( storeNamespace );
	return parentCtx.item;
}

function getItemCount( item?: CountedSelectableItem ): unknown {
	if ( ! item || ! Object.prototype.hasOwnProperty.call( item, 'count' ) ) {
		return undefined;
	}
	if (
		item.count === null ||
		typeof item.count === 'undefined' ||
		item.count === ''
	) {
		return undefined;
	}
	return item.count;
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
			get itemCountHidden(): boolean {
				const { storeNamespace } = getContext< CheckboxListContext >();
				return (
					typeof getItemCount( getParentItem( storeNamespace ) ) ===
					'undefined'
				);
			},
			get itemCountText(): string {
				const { storeNamespace } = getContext< CheckboxListContext >();
				const count = getItemCount( getParentItem( storeNamespace ) );
				return typeof count === 'undefined'
					? ''
					: `(${ String( count ) })`;
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
