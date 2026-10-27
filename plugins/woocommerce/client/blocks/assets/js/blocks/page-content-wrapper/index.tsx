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
import { useMemo } from '@wordpress/element';

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

	// Provide the previewed page as block context rather than writing it to
	// attributes on mount, which flagged the template dirty on open (#48936).
	// On the frontend the queried post supplies this context.
	let postId = 0;
	if ( attributes.page === 'checkout' ) {
		postId = CHECKOUT_PAGE_ID;
	} else if ( attributes.page === 'cart' ) {
		postId = CART_PAGE_ID;
	}

	const context = useMemo(
		() => ( postId ? { postId, postType: 'page' } : {} ),
		[ postId ]
	);

	return (
		<div { ...blockProps }>
			<BlockContextProvider value={ context }>
				<InnerBlocks template={ TEMPLATE } />
			</BlockContextProvider>
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
