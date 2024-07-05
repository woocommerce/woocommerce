/**
 * External dependencies
 */
import {
	registerBlockType,
	InnerBlockTemplate,
	BlockAttributes,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { page } from '@wordpress/icons';
/**
 * Internal dependencies
 */
import metadata from './block.json';
import './editor.scss';

const Edit = () => {
	const TEMPLATE: InnerBlockTemplate[] = [
		[ 'core/post-title', { align: 'wide', level: 1 } ],
		[ 'core/post-content', { align: 'wide' } ],
	];

	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-page-content-wrapper',
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks template={ TEMPLATE } />
		</div>
	);
};

registerBlockType( metadata, {
	icon: {
		src: page,
	},
	edit: Edit,
	save: () => <InnerBlocks.Content />,
	variations: [
		{
			name: 'checkout-page',
			title: __( 'Checkout Page', 'woocommerce' ),
			attributes: {
				page: 'checkout',
			},
			isActive: ( blockAttributes, variationAttributes ) =>
				blockAttributes.page === variationAttributes.page,
		},
		{
			name: 'cart-page',
			title: __( 'Cart Page', 'woocommerce' ),
			attributes: {
				page: 'cart',
			},
			isActive: ( blockAttributes, variationAttributes ) =>
				blockAttributes.page === variationAttributes.page,
		},
	],
} );
