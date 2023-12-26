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
		'woocommerce/collection-active-filters',
		{},
		[
			[
				'core/heading',
				{ content: __( 'Active Filters', 'woocommerce' ), level: 3 },
			],
		],
	],
	[
		'woocommerce/collection-price-filter',
		{},
		[
			[
				'core/heading',
				{ content: __( 'Filter by Price', 'woocommerce' ), level: 3 },
			],
		],
	],
	[
		'woocommerce/collection-stock-filter',
		{},
		[
			[
				'core/heading',
				{
					content: __( 'Filter by Stock Status', 'woocommerce' ),
					level: 3,
				},
			],
		],
	],
	[
		'woocommerce/collection-rating-filter',
		{},
		[
			[
				'core/heading',
				{
					content: __( 'Filter by Rating', 'woocommerce' ),
					level: 3,
				},
			],
		],
	],
];

const firstAttribute = ATTRIBUTES.find( Boolean );

if ( firstAttribute ) {
	template.push( [
		'woocommerce/collection-attribute-filter',
		{
			attributeId: parseInt( firstAttribute?.attribute_id, 10 ),
		},
		[
			[
				'core/heading',
				{
					content: sprintf(
						// translators: %s is the attribute label.
						__( 'Filter by %s', 'woocommerce' ),
						firstAttribute.attribute_label
					),
					level: 3,
				},
			],
		],
	] );
}

const Edit = () => {
	const blockProps = useBlockProps();
	const innerBlockProps = useInnerBlocksProps( blockProps, {
		template,
	} );

	return <nav { ...innerBlockProps } />;
};

export default Edit;
