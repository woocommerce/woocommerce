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
import {
	getVisualAttributeTermStyleString,
	isVisualAttributeTermEmpty,
} from '../../../../base/utils/visual-attribute-terms';
import type { VisualAttributeTerm } from '../../../../base/utils/visual-attribute-terms';

type CheckboxListItem = SelectableItem< {
	visual?: VisualAttributeTerm;
	index?: number;
} >;

const DEFAULT_DISPLAY_LIMIT = 15;

type CheckboxListContext = {
	storeNamespace: string;
	displayLimit: number;
	isExpanded: boolean;
};

type CheckboxListStore = {
	state: {
		items: CheckboxListItem[];
		ratingStyle: string;
		colorSwatchStyle: string;
		isColorSwatchEmpty: boolean;
	};
	actions: {
		toggle: () => void;
		showAll: () => void;
	};
};

function getParentStore( storeNamespace?: string ) {
	if ( ! storeNamespace ) return undefined;
	return store<
		SelectableItemsParentStore< { visual?: VisualAttributeTerm } >
	>( storeNamespace );
}

function normalizeDisplayLimit( displayLimit: number ): number {
	const limit = Number( displayLimit );
	if ( ! Number.isFinite( limit ) || limit < 0 ) {
		return DEFAULT_DISPLAY_LIMIT;
	}
	return Math.floor( limit );
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
				const { storeNamespace, isExpanded, displayLimit } =
					getContext< CheckboxListContext >();
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
			get ratingStyle(): string {
				const item = getCurrentItem();
				if ( ! item ) return '';
				return `width: ${ Number( item.value ) * 20 }%`;
			},
			get colorSwatchStyle(): string {
				const item = getCurrentItem();
				return getVisualAttributeTermStyleString( item?.visual );
			},
			get isColorSwatchEmpty(): boolean {
				const item = getCurrentItem();
				return isVisualAttributeTermEmpty( item?.visual );
			},
		},
		actions: {
			toggle() {
				const item = getCurrentItem();
				if ( ! item ) return;
				const { storeNamespace } = getContext< CheckboxListContext >();
				getParentStore( storeNamespace )?.actions?.toggle?.( item );
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
