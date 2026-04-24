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
};

type ParentItemContext = {
	item?: DerivedSelectableItem & { index?: number };
};

type ChipsStore = {
	state: {
		isExpanded: boolean;
		itemHidden: boolean;
	};
	actions: {
		showAll: () => void;
	};
};

const { state }: ChipsStore = store< ChipsStore >(
	'woocommerce/product-filter-chips',
	{
		state: {
			isExpanded: false,
			get itemHidden(): boolean {
				if ( state.isExpanded ) return false;
				const { storeNamespace, displayLimit } =
					getContext< ChipsContext >();
				const parentCtx =
					getContext< ParentItemContext >( storeNamespace );
				if ( ! parentCtx.item ) return false;
				const { index } = parentCtx.item;
				if ( typeof index !== 'number' ) return false;
				return index >= displayLimit;
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
