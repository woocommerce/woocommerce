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
	__experimentalVariationSwitcherFooter as VariationSwitcherFooter,
	__experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer,
} from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';
import { Spinner } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { WooFooterItem } from '@woocommerce/admin-layout';
import { registerPlugin } from '@wordpress/plugins';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { MoreMenuFill } from './fills/product-block-editor-fills';
import { useProductVariationEntityRecord } from './hooks/use-product-variation-entity-record';
import { DeleteVariationMenuItem } from './fills/more-menu-items';
import './product-page.scss';

declare const productBlockEditorSettings: ProductEditorSettings;

productApiFetchMiddleware();

export default function ProductPage() {
	const { productId, variationId } = useParams();

	const product = useProductVariationEntityRecord( variationId as string );

	useEffect( () => {
		return initBlocks();
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
		return <Spinner />;
	}

	return (
		<>
			<Editor
				product={ product }
				productType="product_variation"
				settings={ productBlockEditorSettings || {} }
			/>
			{ productId && variationId && (
				<WooFooterItem order={ 0 }>
					<VariationSwitcherFooter
						parentId={ parseInt( productId, 10 ) }
						variationId={ parseInt( variationId, 10 ) }
					/>
					<ProductMVPFeedbackModalContainer
						productId={ product.id }
					/>
				</WooFooterItem>
			) }
		</>
	);
}

registerPlugin( 'wc-admin-more-menu', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-block-editor',
	render: () => (
		<>
			<WooProductMoreMenuItem>
				{ ( { onClose }: { onClose: () => void } ) => (
					<>
						<DeleteVariationMenuItem onClose={ onClose } />
						<MoreMenuFill
							productType="product_variation"
							onClose={ onClose }
						/>
					</>
				) }
			</WooProductMoreMenuItem>
		</>
	),
} );
