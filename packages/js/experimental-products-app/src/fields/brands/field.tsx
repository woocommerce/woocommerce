/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	type: 'array',
	label: __( 'Brands', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => {
		return ( item.brands ?? [] ).map( ( { id } ) => id.toString() );
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
};
