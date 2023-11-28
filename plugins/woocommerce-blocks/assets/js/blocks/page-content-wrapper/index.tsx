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
import { CHECKOUT_PAGE_ID, CART_PAGE_ID } from '@woocommerce/block-settings';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './editor.scss';

const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attrs: BlockAttributes ) => void;
} ) => {
	const TEMPLATE: InnerBlockTemplate[] = [
		[ 'core/post-title', { align: 'wide', level: 1 } ],
		[ 'core/post-content', { align: 'wide' } ],
	];

	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-page-content-wrapper',
	} );

	useEffect( () => {
		if ( ! attributes.postId && attributes.page ) {
			let postId = 0;

			if ( attributes.page === 'checkout' ) {
				postId = CHECKOUT_PAGE_ID;
			}

			if ( attributes.page === 'cart' ) {
				postId = CART_PAGE_ID;
			}

			if ( postId ) {
				setAttributes( { postId, postType: 'page' } );
			}
		}
	}, [ attributes, setAttributes ] );

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
			title: __( 'Checkout Page', 'woo-gutenberg-products-block' ),
			attributes: {
				page: 'checkout',
			},
			isActive: ( blockAttributes, variationAttributes ) =>
				blockAttributes.page === variationAttributes.page,
		},
		{
			name: 'cart-page',
			title: __( 'Cart Page', 'woo-gutenberg-products-block' ),
			attributes: {
				page: 'cart',
			},
			isActive: ( blockAttributes, variationAttributes ) =>
				blockAttributes.page === variationAttributes.page,
		},
	],
} );
