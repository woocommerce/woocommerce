/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { SelectableItem } from '../../../../types/type-defs/selectable-items';

type ChipsItem = SelectableItem< { color?: string } > & { index?: number };

type ChipsContext = {
	storeNamespace: string;
	displayLimit: number;
	isExpanded: boolean;
};

type ParentItemContext = {
	item?: ChipsItem;
};

type ChipsStore = {
	state: {
		itemHidden: boolean;
		swatchHidden: boolean;
		swatchStyle: string;
	};
	actions: {
		showAll: () => void;
	};
};

function getParentItem( storeNamespace: string ): ChipsItem | undefined {
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
				if ( item.index === undefined ) return false;
				return item.index >= displayLimit;
			},
			get swatchHidden(): boolean {
				const { storeNamespace } = getContext< ChipsContext >();
				const item = getParentItem( storeNamespace );
				return ! item?.color;
			},
			get swatchStyle(): string {
				const { storeNamespace } = getContext< ChipsContext >();
				const item = getParentItem( storeNamespace );
				if ( ! item?.color ) return '';
				return `background-color: ${ item.color }`;
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
