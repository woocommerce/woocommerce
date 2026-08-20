/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import type { DataFormControlProps, Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import { TaxonomyEdit } from '../components/taxonomy-edit';

const fieldDefinition = {
	type: 'array',
	label: __( 'Brands', 'woocommerce' ),
	enableSorting: false,
	filterBy: {
		operators: [ 'isAny' ],
	},
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => {
		return ( item.brands ?? [] ).map( ( { id } ) => id.toString() );
	},
	setValue: ( { value }: { value: string[] } ) => {
		return {
			brands: value.map( ( v ) => ( {
				id: parseInt( v, 10 ),
			} ) ),
		};
	},
	render: ( { item } ) => {
		const names = ( item.brands ?? [] )
			.map( ( { name } ) => decodeEntities( name ?? '' ) )
			.filter( Boolean );

		if ( names.length === 0 ) {
			return <span>{ '—' }</span>;
		}

		return <span>{ names.join( ', ' ) }</span>;
	},
	getElements: async () => {
		const records = ( await resolveSelect( coreStore ).getEntityRecords(
			'taxonomy',
			'product_brand',
			{ per_page: -1 }
		) ) as Array< { id: number; name: string } > | null;
		return ( records ?? [] ).map( ( { id, name } ) => ( {
			value: id.toString(),
			label: decodeEntities( name ),
		} ) );
	},
	Edit: ( props: DataFormControlProps< ProductEntityRecord > ) => (
		<TaxonomyEdit
			{ ...props }
			taxonomy="product_brand"
			fieldProperty="brands"
			searchPlaceholder={ __( 'Search or create brands', 'woocommerce' ) }
		/>
	),
};
