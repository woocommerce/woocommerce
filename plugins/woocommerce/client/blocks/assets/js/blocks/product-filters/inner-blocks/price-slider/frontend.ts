/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import type { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	ProductFilterPriceContext,
	ProductFilterPriceStore,
} from '../price-filter/frontend';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type DebouncedFunction< T extends ( ...args: any[] ) => any > = ( (
	...args: Parameters< T >
) => void ) & { flush: () => void };

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const debounce = < T extends ( ...args: any[] ) => any >(
	func: T,
	wait: number,
	immediate?: boolean
): DebouncedFunction< T > => {
	let timeout: ReturnType< typeof setTimeout > | null;
	let latestArgs: Parameters< T > | null = null;

	const debounced = ( ( ...args: Parameters< T > ) => {
		latestArgs = args;
		if ( timeout ) clearTimeout( timeout );
		timeout = setTimeout( () => {
			timeout = null;
			if ( ! immediate && latestArgs ) func( ...latestArgs );
		}, wait );
		if ( immediate && ! timeout ) func( ...args );
	} ) as DebouncedFunction< T >;

	debounced.flush = () => {
		if ( timeout && latestArgs ) {
			func( ...latestArgs );
			clearTimeout( timeout );
			timeout = null;
		}
	};

	return debounced;
};

store( 'woocommerce/product-filter-price-slider', {
	state: {
		rangeStyle: () => {
			const { minRange, maxRange } =
				getContext< ProductFilterPriceContext >(
					'woocommerce/product-filter-price'
				);
			const productFilterPriceStore = store< ProductFilterPriceStore >(
				'woocommerce/product-filter-price'
			);
			const { minPrice, maxPrice } = productFilterPriceStore.state;

			return `--low: ${
				( 100 * ( minPrice - minRange ) ) / ( maxRange - minRange )
			}%; --high: ${
				( 100 * ( maxPrice - minRange ) ) / ( maxRange - minRange )
			}%;`;
		},
	},
	actions: {
		selectInputContent: () => {
			const element = getElement();
			if ( element && element.ref ) {
				element.ref.select();
			}
		},
		debounceSetPrice: debounce(
			( e: HTMLElementEvent< HTMLInputElement > ) => {
				e.target.dispatchEvent( new Event( 'change' ) );
			},
			1000
		),
		limitRange: ( e: HTMLElementEvent< HTMLInputElement > ) => {
			const productFilterPriceStore = store< ProductFilterPriceStore >(
				'woocommerce/product-filter-price'
			);
			const { minPrice, maxPrice } = productFilterPriceStore.state;
			if ( e.target.classList.contains( 'min' ) ) {
				e.target.value = Math.min(
					parseInt( e.target.value, 10 ),
					maxPrice - 1
				).toString();
			} else {
				e.target.value = Math.max(
					parseInt( e.target.value, 10 ),
					minPrice + 1
				).toString();
			}
		},
	},
} );
