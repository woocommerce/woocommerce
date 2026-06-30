/**
 * External dependencies
 */
import {
	registerBlockType,
	InnerBlockTemplate,
	BlockAttributes,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	BlockContextProvider,
	InnerBlocks,
	useBlockProps,
} from '@wordpress/block-editor';
import { page } from '@wordpress/icons';
import { CHECKOUT_PAGE_ID, CART_PAGE_ID } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './editor.scss';

const Edit = ( { attributes }: { attributes: BlockAttributes } ) => {
	const TEMPLATE: InnerBlockTemplate[] = [
		[ 'core/post-title', { align: 'wide', level: 1 } ],
		[ 'core/post-content', { align: 'wide' } ],
	];

	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-page-content-wrapper',
	} );

	// Resolve the page being previewed so the inner post-title/post-content
	// render the right content in the editor canvas. We provide this as block
	// context rather than writing it to attributes: mutating attributes on
	// mount makes the Site Editor flag the template as dirty (#48936). On the
	// frontend the queried post supplies this context, so it's editor-only.
	let postId = 0;
	if ( attributes.page === 'checkout' ) {
		postId = CHECKOUT_PAGE_ID;
	} else if ( attributes.page === 'cart' ) {
		postId = CART_PAGE_ID;
	}

	const innerBlocks = <InnerBlocks template={ TEMPLATE } />;

	return (
		<div { ...blockProps }>
			{ postId ? (
				<BlockContextProvider value={ { postId, postType: 'page' } }>
					{ innerBlocks }
				</BlockContextProvider>
			) : (
				innerBlocks
			) }
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
