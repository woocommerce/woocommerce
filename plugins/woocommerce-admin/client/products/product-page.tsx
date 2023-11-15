/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	__experimentalInitBlocks as initBlocks,
	__experimentalWooProductMoreMenuItem as WooProductMoreMenuItem,
	ProductEditorSettings,
	productApiFetchMiddleware,
	TRACKS_SOURCE,
	__experimentalProductMVPCESFooter as FeedbackBar,
	__experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer,
	ProductPageSkeleton,
} from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';
import { registerPlugin, unregisterPlugin } from '@wordpress/plugins';
import { useParams } from 'react-router-dom';
import { WooFooterItem } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { useProductEntityRecord } from './hooks/use-product-entity-record';
import BlockEditorTourWrapper from './tour/block-editor/block-editor-tour-wrapper';
import { MoreMenuFill } from './fills/product-block-editor-fills';
import './product-page.scss';

declare const productBlockEditorSettings: ProductEditorSettings;

productApiFetchMiddleware();

export default function ProductPage() {
	const { productId } = useParams();

	const product = useProductEntityRecord( productId );

	useEffect( () => {
		registerPlugin( 'wc-admin-more-menu', {
			// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
			scope: 'woocommerce-product-block-editor',
			render: () => (
				<>
					<WooProductMoreMenuItem>
						{ ( { onClose }: { onClose: () => void } ) => (
							<MoreMenuFill onClose={ onClose } />
						) }
					</WooProductMoreMenuItem>
				</>
			),
		} );

		const unregisterBlocks = initBlocks();

		return () => {
			unregisterPlugin( 'wc-admin-more-menu' );
			unregisterBlocks();
		};
	}, [] );

	useEffect(
		function trackViewEvents() {
			if ( productId ) {
				recordEvent( 'product_edit_view', {
					source: TRACKS_SOURCE,
					product_id: productId,
				} );
			} else {
				recordEvent( 'product_add_view', {
					source: TRACKS_SOURCE,
				} );
			}
		},
		[ productId ]
	);

	if ( ! product?.id ) {
		return <ProductPageSkeleton />;
	}

	return (
		<>
			<Editor
				product={ product }
				settings={ productBlockEditorSettings || {} }
			/>
			<WooFooterItem>
				<>
					<FeedbackBar productType="product" />
					<ProductMVPFeedbackModalContainer
						productId={ product.id }
					/>
				</>
			</WooFooterItem>
			<BlockEditorTourWrapper />
		</>
	);
}
