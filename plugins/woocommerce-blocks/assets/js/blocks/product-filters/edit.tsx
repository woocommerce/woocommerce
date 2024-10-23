/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './editor.scss';
import { type BlockAttributes } from './types';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/heading',
		{
			level: 3,
			style: { typography: { fontSize: '24px' } },
			content: __( 'Filters', 'woocommerce' ),
		},
	],
	[ 'woocommerce/product-filter-active' ],
	[ 'woocommerce/product-filter-attribute' ],
	[ 'woocommerce/product-filter-stock-status' ],
	[
		'core/buttons',
		{ layout: { type: 'flex' } },
		[
			[
				'core/button',
				{
					text: __( 'Apply', 'woocommerce' ),
					className: 'wc-block-product-filters__apply-button',
					style: {
						border: {
							width: '0px',
							style: 'none',
						},
						typography: {
							textDecoration: 'none',
						},
						outline: 'none',
						fontSize: 'medium',
					},
				},
			],
		],
	],
];

export const Edit = ( {}: BlockEditProps< BlockAttributes > ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InnerBlocks templateLock={ false } template={ TEMPLATE } />
		</div>
	);
};
