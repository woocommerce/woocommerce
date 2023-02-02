/**
 * External dependencies
 */
import { useFormContext2 } from '@woocommerce/components';
import type { ProductVariation } from '@woocommerce/data';
import { useWatch } from 'react-hook-form';

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
	{ variationsOrder }: ProductVariationsOrder
) {
	if ( ! variationsOrder || ! variationsOrder[ currentPage ] )
		return variations;

	const currentPageVariationsOrder = variationsOrder[ currentPage ];

	return [ ...variations ].sort( ( a, b ) => {
		if (
			! currentPageVariationsOrder[ a.id ] ||
			! currentPageVariationsOrder[ b.id ]
		)
			return 0;
		return (
			currentPageVariationsOrder[ a.id ] -
			currentPageVariationsOrder[ b.id ]
		);
	} );
}

export default function useVariationsOrder( {
	variations,
	currentPage,
}: UseVariationsOrderInput ): UseVariationsOrderOutput {
	const { setValue, control } = useFormContext2< ProductVariationsOrder >();

	const variationsOrder = useWatch( { name: 'variationsOrder', control } );

	function onOrderChange( items: JSX.Element[] ) {
		const minOrder = Math.min( ...items.map( getVariationOrder ) );

		setValue( 'variationsOrder', {
			...variationsOrder,
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
		sortedVariations: sort( variations, currentPage, { variationsOrder } ),
		getVariationKey,
		onOrderChange,
	};
}

export type UseVariationsOrderInput = {
	variations: ProductVariation[];
	currentPage: number;
};

export type UseVariationsOrderOutput = {
	sortedVariations: ProductVariation[];
	getVariationKey( variation: ProductVariation ): string;
	onOrderChange( items: JSX.Element[] ): void;
};

export type ProductVariationsOrder = {
	variationsOrder?: {
		[ page: number ]: {
			[ variationId: number ]: number;
		};
	};
};
