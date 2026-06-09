/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

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
import { getClosestColor } from '../../utils/get-closest-color';

type ChipsItem = SelectableItem< {
	visual?: VisualAttributeTerm;
	index?: number;
} >;

const DEFAULT_DISPLAY_LIMIT = 15;
const CHIP_BACKGROUND_VAR = '--wc-product-filter-chips-background';
const CHIP_TEXT_VAR = '--wc-product-filter-chips-text';

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
	callbacks: {
		initColors: () => void;
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

function getCurrentItem(): ChipsItem | undefined {
	const context = getContext< { item?: ChipsItem } >();
	return context.item;
}

function getCssVariable( element: HTMLElement, property: string ): string {
	return (
		element.style.getPropertyValue( property ) ||
		window.getComputedStyle( element ).getPropertyValue( property )
	).trim();
}

function initChipColors( element: HTMLElement ): void {
	const style = element.style;

	if ( ! getCssVariable( element, CHIP_BACKGROUND_VAR ) ) {
		const backgroundColor = getClosestColor( element, 'backgroundColor' );
		if ( backgroundColor ) {
			style.setProperty( CHIP_BACKGROUND_VAR, backgroundColor );
		}
	}

	if ( ! getCssVariable( element, CHIP_TEXT_VAR ) ) {
		const textColor = getClosestColor( element, 'color' );
		if ( textColor ) {
			style.setProperty( CHIP_TEXT_VAR, textColor );
		}
	}
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
				return isVisualAttributeTermEmpty( item?.visual );
			},
			get swatchStyle(): string {
				const item = getCurrentItem();
				return getVisualAttributeTermStyleString( item?.visual );
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
		callbacks: {
			initColors: () => {
				const el = getElement();
				if ( ! el.ref ) {
					return;
				}

				initChipColors( el.ref );
			},
		},
	},
	{ lock: true }
);

export type { ChipsStore };
export { state as chipsState };
