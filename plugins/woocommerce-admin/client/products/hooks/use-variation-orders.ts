/**
 * External dependencies
 */
import { useFormContext } from '@woocommerce/components';
import type { ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */

const KEY_SEPARATOR = ':';

function getVariationKey( variation: ProductVariation ) {
	return `${ variation.id }${ KEY_SEPARATOR }${ variation.menu_order }`;
}

function getVariationId( { key }: JSX.Element ) {
	return typeof key === 'string'
		? Number.parseInt( key.split( KEY_SEPARATOR )[ 0 ], 10 )
		: 0;
}

function getVariationOrder( { key }: JSX.Element ) {
	return typeof key === 'string'
		? Number.parseInt( key.split( KEY_SEPARATOR )[ 1 ], 10 )
		: Number.MAX_SAFE_INTEGER;
}

function sort(
	variations: ProductVariation[],
	currentPage: number,
	{ variationOrders }: ProductVariationOrders
) {
	if ( ! variationOrders || ! variationOrders[ currentPage ] )
		return variations;

	const currentPageOrders = variationOrders[ currentPage ];

	return [ ...variations ].sort( ( a, b ) => {
		if ( ! currentPageOrders[ a.id ] || ! currentPageOrders[ b.id ] )
			return 0;
		return currentPageOrders[ a.id ] - currentPageOrders[ b.id ];
	} );
}

export default function useVariationOrders( {
	variations,
	currentPage,
}: UseVariationOrdersInput ): UseVariationOrdersOutput {
	const { setValue, values } = useFormContext< ProductVariationOrders >();

	function onOrderChange( items: JSX.Element[] ) {
		const minOrder = Math.min( ...items.map( getVariationOrder ) );

		setValue( 'variationOrders', {
			...values.variationOrders,
			[ currentPage ]: items.reduce( ( prev, item, index ) => {
				const id = getVariationId( item );
				return {
					...prev,
					[ id ]: minOrder + index,
				};
			}, {} ),
		} );
	}

	return {
		sortedVariations: sort( variations, currentPage, values ),
		getVariationKey,
		onOrderChange,
	};
}

export type UseVariationOrdersInput = {
	variations: ProductVariation[];
	currentPage: number;
};

export type UseVariationOrdersOutput = {
	sortedVariations: ProductVariation[];
	getVariationKey( variation: ProductVariation ): string;
	onOrderChange( items: JSX.Element[] ): void;
};

export type ProductVariationOrders = {
	variationOrders?: {
		[ page: number ]: {
			[ variationId: number ]: number;
		};
	};
};
