/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { DataFormControlProps, Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { TaxonomyEdit } from '../components/taxonomy-edit';

const fieldDefinition = {
	type: 'array',
	label: __( 'Tags', 'woocommerce' ),
	description: __(
		'Add descriptive tags to help customers find related items while shopping.',
		'woocommerce'
	),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
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
