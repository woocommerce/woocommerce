/**
 * External dependencies
 */
import { productFilterAttribute } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import { registerBlockType } from '@wordpress/blocks';
import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import type { TaxonomyItem } from './types';

const taxonomies = getSetting< TaxonomyItem[] >(
	'filterableProductTaxonomies',
	[]
);

registerBlockType( metadata, {
	edit: Edit,
	icon: productFilterAttribute,
	save: () => {
		return (
			<div
				{ ...useBlockProps.save() }
				{ ...useInnerBlocksProps.save() }
			/>
		);
	},
	variations: taxonomies.map( ( item, index ) => {
		return {
			name: `product-filter-taxonomy-${ item.name }`,
			title: sprintf(
				// translators: %s is the taxonomy label.
				__( '%s Filter', 'woocommerce' ),
				item.label
			),
			description: sprintf(
				// translators: %s is the taxonomy label.
				__(
					`Enable customers to filter the product collection by selecting one or more %s terms.`,
					'woocommerce'
				),
				item.label
			),
			attributes: {
				taxonomy: item.name,
			},
			isActive: [ 'taxonomy' ],
			isDefault: index === 0,
		};
	} ),
} );
