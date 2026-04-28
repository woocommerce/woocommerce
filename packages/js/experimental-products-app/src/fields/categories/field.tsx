/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';

import { store as coreStore } from '@wordpress/core-data';

import { decodeEntities } from '@wordpress/html-entities';

import { __ } from '@wordpress/i18n';

import type { DataFormControlProps, Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { TaxonomyEdit } from '../components/taxonomy-edit';

type CategoryImage = {
	id: number;
	src?: string;
	alt?: string;
};

type ProductCategory = {
	id: number;
	name: string;
	image?: CategoryImage;
};

const fieldDefinition = {
	type: 'array',
	label: __( 'Categories', 'woocommerce' ),
	description: __(
		'Add one or more categories to help customers find this product on your store.',
		'woocommerce'
	),
	enableSorting: false,
	filterBy: {
		operators: [ 'isAny', 'isNone' ],
	},
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => {
		return item.categories.map( ( { id } ) => id.toString() );
	},
	setValue: ( { value }: { value: string[] } ) => {
		return {
			categories: value.map( ( v ) => ( {
				id: parseInt( v, 10 ),
			} ) ),
		};
	},
	render: ( { item } ) => {
		return ( item.categories ?? [] )
			.map( ( { name } ) => decodeEntities( name ?? '' ) )
			.join( ', ' );
	},
	getElements: async () => {
		const records: ProductCategory[] | null = await resolveSelect(
			coreStore
		).getEntityRecords( 'taxonomy', 'product_cat', { per_page: -1 } );
		return ( records ?? [] ).map( ( { id, name, image } ) => ( {
			value: id.toString(),
			label: decodeEntities( name ),
			image: image?.src ? { src: image.src, alt: image.alt } : undefined,
		} ) );
	},
	Edit: ( props: DataFormControlProps< ProductEntityRecord > ) => (
		<TaxonomyEdit
			{ ...props }
			taxonomy="product_cat"
			fieldProperty="categories"
			searchPlaceholder={ __(
				'Search or create categories',
				'woocommerce'
			) }
		/>
	),
};
