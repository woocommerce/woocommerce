/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { sprintf, __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import type { AttributeSetting } from '@woocommerce/types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

const template = [
	[
		'core/heading',
		{
			content: __( 'Filter by Price', 'woo-gutenberg-products-block' ),
			level: 3,
		},
	],
	[ 'woocommerce/collection-price-filter', {} ],
	[
		'core/heading',
		{
			content: __(
				'Filter by Stock status',
				'woo-gutenberg-products-block'
			),
			level: 3,
		},
	],
	[ 'woocommerce/collection-stock-filter', {} ],
];

const firstAttribute = ATTRIBUTES.find( Boolean );

if ( firstAttribute ) {
	template.push(
		[
			'core/heading',
			{
				content: sprintf(
					// translators: %s is the attribute label.
					__( 'Filter by %s', 'woo-gutenberg-products-block' ),
					firstAttribute.attribute_label
				),
				level: 3,
			},
		],
		[
			'woocommerce/collection-attribute-filter',
			{
				attributeId: parseInt( firstAttribute?.attribute_id, 10 ),
			},
		]
	);
}

const Edit = () => {
	const blockProps = useBlockProps();
	const innerBlockProps = useInnerBlocksProps( blockProps, {
		template,
	} );

	return <nav { ...innerBlockProps } />;
};

export default Edit;
