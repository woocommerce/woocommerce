/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { resolveSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import type { DataFormControlProps, Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { TaxonomyEdit } from '../components/taxonomy-edit';

const fieldDefinition = {
	type: 'array',
	label: __( 'Tags', 'woocommerce' ),
	enableSorting: false,
	filterBy: {
		operators: [ 'isAny', 'isNone' ],
	},
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => {
		return item.tags.map( ( { id } ) => id.toString() );
	},
	setValue: ( { value }: { value: string[] } ) => {
		return {
			tags: value.map( ( v ) => ( {
				id: parseInt( v, 10 ),
			} ) ),
		};
	},
	getElements: async () => {
		const records = ( await resolveSelect( coreStore ).getEntityRecords(
			'taxonomy',
			'product_tag',
			{ per_page: -1 }
		) ) as Array< { id: number; name: string } > | null;
		return ( records ?? [] ).map( ( { id, name } ) => ( {
			value: id.toString(),
			label: decodeEntities( name ),
		} ) );
	},
	render: ( { item } ) => {
		return ( item.tags ?? [] )
			.map( ( { name } ) => decodeEntities( name ?? '' ) )
			.join( ', ' );
	},
	Edit: ( props: DataFormControlProps< ProductEntityRecord > ) => (
		<TaxonomyEdit
			{ ...props }
			taxonomy="product_tag"
			fieldProperty="tags"
			searchPlaceholder={ __( 'Search or create tags', 'woocommerce' ) }
			serverSearchThreshold={ 100 }
			// @ts-expect-error wcSettings is a global variable injected by Woo core, and it doesn't have proper typings.
			termCount={ window.wcSettings?.tagCount }
		/>
	),
};
