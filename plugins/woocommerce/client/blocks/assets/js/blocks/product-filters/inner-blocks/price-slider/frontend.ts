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
		if ( timer ) {
			clearTimeout( timer );
		}
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
		// Keep native whole-unit slider steps separate from exact text/URL bounds.
		get minSliderPrice(): number {
			return Math.floor( state.minPrice );
		},
		get maxSliderPrice(): number {
			return Math.floor( state.maxPrice );
		},
		rangeStyle: () => {
			const { minRange, maxRange } = getServerContext
				? getServerContext< ProductFilterPriceContext >()
				: getContext< ProductFilterPriceContext >();
			return `--low: ${
				( 100 * ( state.minSliderPrice - minRange ) ) /
				( maxRange - minRange )
			}%; --high: ${
				( 100 * ( state.maxSliderPrice - minRange ) ) /
				( maxRange - minRange )
			}%;`;
		},
	},
	actions: {
		onPriceInput: ( e: HTMLElementEvent< HTMLInputElement > ) => {
			if ( e.target.getAttribute( 'aria-invalid' ) === 'true' ) {
				getContext< { priceInputError: string } >().priceInputError =
					'';
			}
			e.target.setCustomValidity( '' );
			e.target.removeAttribute( 'aria-invalid' );
			if ( e.target.classList.contains( 'min' ) ) {
				actions.debounceSetMinPrice( e );
			} else {
				actions.debounceSetMaxPrice( e );
			}
		},
		validatePriceInput: ( e: HTMLElementEvent< HTMLInputElement > ) => {
			if ( e.target.classList.contains( 'min' ) ) {
				actions.setMin( e );
			} else {
				actions.setMax( e );
			}
			getContext< { priceInputError: string } >().priceInputError =
				e.target.validationMessage;
			e.target.setAttribute(
				'aria-invalid',
				String( ! e.target.validity.valid )
			);
		},
		selectInputContent: () => {
			const element = getElement();
			if ( element?.ref instanceof HTMLInputElement ) {
				element.ref.select();
			}
		},
		debounceSetMinPrice: debounceWithScope(
			( e: HTMLElementEvent< HTMLInputElement > ) => {
				actions.setMin( e );
				if ( e.target.validity.valid ) {
					void actions.navigate();
				}
			},
			1000
		),
		debounceSetMaxPrice: debounceWithScope(
			( e: HTMLElementEvent< HTMLInputElement > ) => {
				actions.setMax( e );
				if ( e.target.validity.valid ) {
					void actions.navigate();
				}
			},
			1000
		),
	},
	callbacks: {
		updatePriceInput: () => {
			const element = getElement();
			if ( element?.ref instanceof HTMLInputElement ) {
				const input = element.ref;
				input.value = input.classList.contains( 'min' )
					? state.formattedMinPrice
					: state.formattedMaxPrice;
				input.setCustomValidity( '' );
				input.removeAttribute( 'aria-invalid' );
				getContext< { priceInputError: string } >().priceInputError =
					'';
			}
		},
	},
};
const { state, actions } = store<
	ProductFiltersStore &
		ProductFilterPriceStore &
		typeof productFilterPriceSliderStore
>( PRODUCT_FILTERS_STORE_NAME, productFilterPriceSliderStore );
