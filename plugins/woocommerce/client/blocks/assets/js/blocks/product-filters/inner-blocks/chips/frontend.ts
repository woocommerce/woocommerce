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

type ChipsContext = {
	storeNamespace: string;
	displayLimit: number;
};

type ParentItemContext = {
	item?: CountedSelectableItem;
};

type ChipsStore = {
	state: {
		isExpanded: boolean;
		itemHidden: boolean;
		itemCountHidden: boolean;
		itemCountText: string;
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
			get itemCountHidden(): boolean {
				const { storeNamespace } = getContext< ChipsContext >();
				return (
					typeof getItemCount( getParentItem( storeNamespace ) ) ===
					'undefined'
				);
			},
			get itemCountText(): string {
				const { storeNamespace } = getContext< ChipsContext >();
				const count = getItemCount( getParentItem( storeNamespace ) );
				return typeof count === 'undefined'
					? ''
					: `(${ String( count ) })`;
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
