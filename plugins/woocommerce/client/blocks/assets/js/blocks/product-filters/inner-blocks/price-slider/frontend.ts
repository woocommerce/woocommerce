/**
 * External dependencies
 */
import {
	store,
	getContext,
	getElement,
	withScope,
} from '@wordpress/interactivity';
import type { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductFiltersStore } from '../../frontend';
import type {
	ProductFilterPriceContext,
	ProductFilterPriceStore,
} from '../price-filter/frontend';

function debounceWithScope< Args extends unknown[] >(
	func: ( ...args: Args ) => void,
	timeout = 300
) {
	let timer: ReturnType< typeof setTimeout > | null;
	return function ( this: unknown, ...args: Args ) {
		if ( timer ) clearTimeout( timer );
		timer = setTimeout(
			withScope( () => {
				func.apply( this, args );
			} ),
			timeout
		);
	};
}

const productFilterPriceSliderStore = {
	state: {
		rangeStyle: () => {
			const context = getContext< ProductFilterPriceContext >();
			return `--low: ${
				( 100 * ( state.minPrice - context.minRange ) ) /
				( context.maxRange - context.minRange )
			}%; --high: ${
				( 100 * ( state.maxPrice - context.minRange ) ) /
				( context.maxRange - context.minRange )
			}%;`;
		},
	},
	actions: {
		selectInputContent: () => {
			const element = getElement();
			if ( element?.ref instanceof HTMLInputElement ) {
				element.ref.select();
			}
		},
		debounceSetMinPrice: debounceWithScope(
			( e: HTMLElementEvent< HTMLInputElement > ) => {
				actions.setMinPrice( e );
				actions.navigate();
			},
			1000
		),
		debounceSetMaxPrice: debounceWithScope(
			( e: HTMLElementEvent< HTMLInputElement > ) => {
				actions.setMaxPrice( e );
				actions.navigate();
			},
			1000
		),
	},
};
const { state, actions } = store<
	ProductFiltersStore &
		ProductFilterPriceStore &
		typeof productFilterPriceSliderStore
>( 'woocommerce/product-filters', productFilterPriceSliderStore );
