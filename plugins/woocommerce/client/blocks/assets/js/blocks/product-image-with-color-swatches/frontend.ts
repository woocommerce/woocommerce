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
} from '../../types/type-defs/selectable-items';

export type SwatchImage = {
	id: number;
	src: string;
	srcset: string;
	sizes: string;
	alt: string;
	width: string;
	height: string;
};

type SwatchItemFields = {
	color?: string;
	image: SwatchImage;
};

export type SwatchItem = SelectableItem< SwatchItemFields >;

type Context = {
	items?: SwatchItem[];
	selectedItemId?: string | null;
	defaultImage?: SwatchImage;
	item?: SwatchItem;
};

type ProductImageWithColorSwatchesStore = {
	state: {
		selectableItems: readonly SwatchItem[];
		currentImage: SwatchImage;
	};
	actions: {
		toggle: ( item?: SwatchItem | Event ) => void;
	};
};

const EMPTY_IMAGE: SwatchImage = {
	id: 0,
	src: '',
	srcset: '',
	sizes: '',
	alt: '',
	width: '',
	height: '',
};

function isEvent( value: unknown ): value is Event {
	return typeof Event !== 'undefined' && value instanceof Event;
}

function getCurrentItem( itemArg?: SwatchItem | Event ): SwatchItem | undefined {
	if ( itemArg && ! isEvent( itemArg ) ) {
		return itemArg;
	}

	return getContext< Context >().item;
}

const { state, actions } = store< ProductImageWithColorSwatchesStore >(
	'woocommerce/product-image-with-color-swatches',
	{
		state: {
			get selectableItems(): readonly SwatchItem[] {
				const context = getContext< Context >();
				const items = Array.isArray( context.items ) ? context.items : [];
				return items.map( ( item ) => ( {
					...item,
					selected: context.selectedItemId === item.id,
				} ) );
			},
			get currentImage(): SwatchImage {
				const context = getContext< Context >();
				const defaultImage = context.defaultImage || EMPTY_IMAGE;

				if ( ! context.selectedItemId ) {
					return defaultImage;
				}

				return (
					state.selectableItems.find(
						( item ) => item.id === context.selectedItemId
					)?.image || defaultImage
				);
			},
		},
		actions: {
			toggle( itemArg?: SwatchItem | Event ) {
				const item = getCurrentItem( itemArg );
				if ( ! item || item.disabled || item.hidden ) {
					return;
				}

				const context = getContext< Context >();
				context.selectedItemId =
					context.selectedItemId === item.id ? null : item.id;
			},
		},
	} satisfies ProductImageWithColorSwatchesStore &
		SelectableItemsParentStore< SwatchItemFields >
);

export type { ProductImageWithColorSwatchesStore };
export {
	actions as productImageWithColorSwatchesActions,
	state as productImageWithColorSwatchesState,
};
