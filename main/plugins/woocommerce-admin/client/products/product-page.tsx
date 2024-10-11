/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	__experimentalInitBlocks as initBlocks,
	__experimentalWooProductMoreMenuItem as WooProductMoreMenuItem,
	productApiFetchMiddleware,
	productEditorHeaderApiFetchMiddleware,
	TRACKS_SOURCE,
	__experimentalProductMVPCESFooter as FeedbackBar,
	__experimentalEditorLoadingContext as EditorLoadingContext,
} from '@woocommerce/product-editor';
import { Spinner } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import React, { lazy, Suspense, useContext, useEffect } from 'react';
import { registerPlugin, unregisterPlugin } from '@wordpress/plugins';
import { useParams } from 'react-router-dom';
import { WooFooterItem } from '@woocommerce/admin-layout';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useProductEntityRecord } from './hooks/use-product-entity-record';
import { MoreMenuFill } from './fills/product-block-editor-fills';
import './product-page.scss';

productEditorHeaderApiFetchMiddleware();
productApiFetchMiddleware();

// Lazy load components
const BlockEditorTourWrapper = lazy(
	() => import( './tour/block-editor/block-editor-tour-wrapper' )
);
const ProductMVPFeedbackModalContainer = lazy( () =>
	import( '@woocommerce/product-editor' ).then( ( module ) => ( {
		default: module.__experimentalProductMVPFeedbackModalContainer,
	} ) )
);

export default function ProductPage() {
	const { productId: productIdSearchParam } = useParams();

	/**
	 * Only register blocks and unregister them when the product page is being rendered or unmounted.
	 * Note: Dependency array should stay empty.
	 */
	useEffect( () => {
		document.body.classList.add( 'is-product-editor' );

		const unregisterBlocks = initBlocks();

		return () => {
			document.body.classList.remove( 'is-product-editor' );
			unregisterBlocks();
		};
	}, [] );

	useEffect( () => {
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
								<Suspense fallback={ <Spinner /> }>
									<ProductMVPFeedbackModalContainer
										productId={
											productIdSearchParam
												? Number.parseInt(
														productIdSearchParam,
														10
												  )
												: undefined
										}
									/>
								</Suspense>
							</>
						</WooFooterItem>

						<Suspense fallback={ <Spinner /> }>
							<BlockEditorTourWrapper />
						</Suspense>
					</>
				);
			},
		} );

		return () => {
			unregisterPlugin( 'wc-admin-product-editor' );
		};
	}, [ productIdSearchParam ] );

	useEffect(
		function trackViewEvents() {
			if ( productIdSearchParam ) {
				recordEvent( 'product_edit_view', {
					source: TRACKS_SOURCE,
					product_id: productIdSearchParam,
				} );
			} else {
				recordEvent( 'product_add_view', {
					source: TRACKS_SOURCE,
				} );
			}
		},
		[ productIdSearchParam ]
	);

	const productId = useProductEntityRecord( productIdSearchParam );

	if ( ! productId ) {
		return (
			<div className="woocommerce-layout__loading">
				<Spinner
					aria-label={ __( 'Creating the product', 'woocommerce' ) }
				/>
			</div>
		);
	}

	return <Editor productId={ productId } />;
}
