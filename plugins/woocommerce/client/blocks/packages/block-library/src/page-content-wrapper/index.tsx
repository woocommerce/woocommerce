/**
 * External dependencies
 */
import {
	BlockConfiguration,
	registerBlockType,
	InnerBlockTemplate,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { page } from '@wordpress/icons';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { CHECKOUT_PAGE_ID, CART_PAGE_ID } from './utils';

type Attributes = {
	page?: string;
	postId?: number;
	postType?: string;
};

const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: Attributes;
	setAttributes: ( attrs: Partial< Attributes > ) => void;
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

registerBlockType( metadata as BlockConfiguration< Attributes >, {
	icon: {
		src: page,
	},
	edit: Edit,
	save() {
		return <InnerBlocks.Content />;
	},
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
