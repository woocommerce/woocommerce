/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	__experimentalInitBlocks as initBlocks,
	__experimentalWooProductMoreMenuItem as WooProductMoreMenuItem,
	productApiFetchMiddleware,
	TRACKS_SOURCE,
	__experimentalProductMVPCESFooter as FeedbackBar,
	__experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer,
	__experimentalEditorLoadingContext as EditorLoadingContext,
} from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';
import { useContext, useEffect } from '@wordpress/element';
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

productApiFetchMiddleware();

let productId: string | undefined;
declare global {
	interface Window {
		_wpLoadBlockEditor?: Promise< {
			postId: number;
		} >;
	}
}
// eslint-disable-next-line @typescript-eslint/no-explicit-any
if ( window._wpLoadBlockEditor ) {
	window._wpLoadBlockEditor.then( ( data ) => {
		productId = data.postId.toString();
	} );
}
export default function ProductPage() {
	const params = useParams();
	productId = productId || params.productId;
	const product = useProductEntityRecord( productId );

	useEffect( () => {
		document.body.classList.add( 'is-product-editor' );
		registerPlugin( 'wc-admin-product-editor', {
			// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
			scope: 'woocommerce-product-block-editor',
			render: () => {
				// eslint-disable-next-line react-hooks/rules-of-hooks
				const isEditorLoading = useContext( EditorLoadingContext );

				if ( isEditorLoading ) {
					return null;
				}

				return (
					<>
						<WooProductMoreMenuItem>
							{ ( { onClose }: { onClose: () => void } ) => (
								<MoreMenuFill onClose={ onClose } />
							) }
						</WooProductMoreMenuItem>

						<WooFooterItem>
							<>
								<FeedbackBar productType="product" />
								<ProductMVPFeedbackModalContainer
									productId={
										productId
											? parseInt( productId, 10 )
											: undefined
									}
								/>
							</>
						</WooFooterItem>

						<BlockEditorTourWrapper />
					</>
				);
			},
		} );

		const unregisterBlocks = initBlocks();

		return () => {
			document.body.classList.remove( 'is-product-editor' );
			unregisterPlugin( 'wc-admin-more-menu' );
			unregisterBlocks();
		};
	}, [ productId ] );

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

	return <>{ product && <Editor product={ product } /> }</>;
}
