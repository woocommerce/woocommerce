/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import type { TemplateArray } from '@wordpress/blocks';
import { useEditorContext } from '@woocommerce/base-context';
import { SHOP_URL } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import {
	useForcedLayout,
	getAllowedBlocks,
} from '../../../cart-checkout-shared';
import newArrivals from '../../../product-collection/collections/new-arrivals';
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../../../product-collection/constants';
import { CoreCollectionNames } from '../../../product-collection/types';

const returnToShopTemplate = SHOP_URL
	? [
			'core/buttons',
			{ layout: { type: 'flex', justifyContent: 'center' } },
			[
				[
					'core/button',
					{
						text: __( 'Return to shop', 'woocommerce' ),
						url: SHOP_URL,
					},
				],
			],
		]
	: null;

// Recent WordPress versions center headings through the typography support and no longer
// have a textAlign attribute; older ones only know textAlign. Unknown attributes are dropped
// on insert, so passing both keeps the heading centered on every supported version.
const centeredHeading = {
	textAlign: 'center',
	style: { typography: { textAlign: 'center' } },
};

const defaultTemplate = [
	[
		'core/heading',
		{
			...centeredHeading,
			content: __( 'Your cart is empty', 'woocommerce' ),
			level: 2,
			className: 'wc-block-cart__empty-cart__title',
		},
	],
	returnToShopTemplate,
	[
		'core/spacer',
		{
			height: '40px',
		},
	],
	[
		'core/heading',
		{
			...centeredHeading,
			content: __( 'New in store', 'woocommerce' ),
			level: 2,
		},
	],
	[
		'woocommerce/product-collection',
		{
			...newArrivals.attributes,
			displayLayout: {
				...newArrivals.attributes.displayLayout,
				columns: 4,
			},
			query: {
				// Copy all properties from newArrivals.attributes.query except timeFrame
				...( ( { timeFrame, ...rest } ) => rest )(
					newArrivals.attributes.query
				),
				isProductCollectionBlock: true,
				postType: 'product',
				perPage: 4,
				woocommerceStockStatus: [ 'instock' ],
			},
			collection: CoreCollectionNames.NEW_ARRIVALS,
		},
		[ INNER_BLOCKS_PRODUCT_TEMPLATE ],
	],
].filter( Boolean ) as unknown as TemplateArray;

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const { currentView } = useEditorContext();
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.EMPTY_CART );
	// Product Collection is used for the New Arrivals block.
	// We don't want to set a parent on the Product Collection block
	// so we add it here manually.
	allowedBlocks.push( 'woocommerce/product-collection' );

	useForcedLayout( {
		clientId,
		registeredBlocks: allowedBlocks,
		defaultTemplate,
	} );

	return (
		<div
			{ ...blockProps }
			hidden={ currentView !== 'woocommerce/empty-cart-block' }
		>
			<InnerBlocks
				template={ defaultTemplate }
				templateLock={ false }
				renderAppender={ InnerBlocks.ButtonBlockAppender }
			/>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
