/**
 * External dependencies
 */
import * as iAPI from '@wordpress/interactivity';
import type { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductFiltersStore } from '../../frontend';
import type {
	ProductFilterPriceContext,
	ProductFilterPriceStore,
} from '../price-filter/frontend';
import { PRODUCT_FILTERS_STORE_NAME } from '../../constants';

const { store, getContext, getElement, withScope, getServerContext } = iAPI;

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
			const { minRange, maxRange } = getServerContext
				? getServerContext< ProductFilterPriceContext >()
				: getContext< ProductFilterPriceContext >();
			return `--low: ${
				( 100 * ( state.minPrice - minRange ) ) /
				( maxRange - minRange )
			}%; --high: ${
				( 100 * ( state.maxPrice - minRange ) ) /
				( maxRange - minRange )
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
				actions.setMin( e );
				void actions.navigate();
			},
			1000
		),
		debounceSetMaxPrice: debounceWithScope(
			( e: HTMLElementEvent< HTMLInputElement > ) => {
				actions.setMax( e );
				void actions.navigate();
			},
			1000
		),
	},
};
const { state, actions } = store<
	ProductFiltersStore &
		ProductFilterPriceStore &
		typeof productFilterPriceSliderStore
>( PRODUCT_FILTERS_STORE_NAME, productFilterPriceSliderStore );
