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

type ChipsItem = SelectableItem< {
	color?: string;
	index?: number;
} >;

const DEFAULT_DISPLAY_LIMIT = 15;

type ChipsContext = {
	storeNamespace: string;
	displayLimit: number;
	isExpanded: boolean;
};

type ChipsStore = {
	state: {
		items: ChipsItem[];
		swatchHidden: boolean;
		swatchStyle: string;
	};
	actions: {
		toggle: () => void;
		showAll: () => void;
	};
};

function getParentStore( storeNamespace?: string ) {
	if ( ! storeNamespace ) return undefined;
	return store< SelectableItemsParentStore< { color?: string } > >(
		storeNamespace
	);
}

function normalizeDisplayLimit( displayLimit: number ): number {
	const limit = Number( displayLimit );
	if ( ! Number.isFinite( limit ) || limit < 0 ) {
		return DEFAULT_DISPLAY_LIMIT;
	}
	return Math.floor( limit );
}

function getCurrentItem(): ChipsItem | undefined {
	const context = getContext< { item?: ChipsItem } >();
	return context.item;
}

const { state }: ChipsStore = store< ChipsStore >(
	'woocommerce/product-filter-chips',
	{
		state: {
			get items(): ChipsItem[] {
				const { storeNamespace, isExpanded, displayLimit } =
					getContext< ChipsContext >();
				const parentItems =
					getParentStore( storeNamespace )?.state?.selectableItems;
				if ( ! Array.isArray( parentItems ) ) return [];
				const normalizedDisplayLimit =
					normalizeDisplayLimit( displayLimit );
				return parentItems.map( ( item, index ) => ( {
					...item,
					index,
					hidden:
						item.hidden ||
						( ! isExpanded &&
							! item.selected &&
							index >= normalizedDisplayLimit ),
				} ) );
			},
			get swatchHidden(): boolean {
				const item = getCurrentItem();
				return ! item?.color;
			},
			get swatchStyle(): string {
				const item = getCurrentItem();
				if ( ! item?.color ) return '';
				return `background-color: ${ item.color }`;
			},
		},
		actions: {
			toggle() {
				const item = getCurrentItem();
				if ( ! item ) return;
				const { storeNamespace } = getContext< ChipsContext >();
				getParentStore( storeNamespace )?.actions?.toggle?.( item );
			},
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
