/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

import { store as coreStore, type Term } from '@wordpress/core-data';

import { decodeEntities } from '@wordpress/html-entities';

import { useMemo } from '@wordpress/element';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const OrganizationSummary = ( { item }: { item: ProductEntityRecord } ) => {
	const ids = useMemo(
		() =>
			( item.categories ?? [] )
				.map( ( { id } ) => {
					const numericId = Number( id );
					return Number.isFinite( numericId ) ? numericId : null;
				} )
				.filter( ( idValue ): idValue is number => idValue !== null ),
		[ item.categories ]
	);
	const terms = useSelect(
		( select ) => {
			if ( ids.length === 0 ) {
				return [];
			}
			return (
				select( coreStore ).getEntityRecords(
					'taxonomy',
					'product_cat',
					{
						include: ids,
						per_page: ids.length,
					}
				) ?? []
			);
		},
		[ ids ]
	) as Term[];

	if ( ! terms || terms.length === 0 ) {
		return null;
	}

	const termNamesById = new Map(
		terms.map( ( term ) => [ term.id, decodeEntities( term.name ) ] )
	);

	const orderedNames = ids
		.map( ( termId ) => termNamesById.get( termId ) )
		.filter( ( label ): label is string => Boolean( label ) );

	if ( orderedNames.length === 0 ) {
		return null;
	}

	return <span>{ orderedNames.join( ', ' ) }</span>;
};

const fieldDefinition = {
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( props ) => <OrganizationSummary { ...props } />,
};
