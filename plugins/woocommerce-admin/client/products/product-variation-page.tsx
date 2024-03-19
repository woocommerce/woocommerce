/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	__experimentalInitBlocks as initBlocks,
	__experimentalWooProductMoreMenuItem as WooProductMoreMenuItem,
	productApiFetchMiddleware,
	TRACKS_SOURCE,
	__experimentalVariationSwitcherFooter as VariationSwitcherFooter,
	__experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer,
} from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';
import { WooFooterItem } from '@woocommerce/admin-layout';
import { registerPlugin, unregisterPlugin } from '@wordpress/plugins';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { MoreMenuFill } from './fills/product-block-editor-fills';
import { useProductVariationEntityRecord } from './hooks/use-product-variation-entity-record';
import { DeleteVariationMenuItem } from './fills/more-menu-items';
import './product-page.scss';

productApiFetchMiddleware();

export default function ProductPage() {
	const { productId, variationId } = useParams();

	const variation = useProductVariationEntityRecord( variationId as string );

	useEffect( () => {
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

	return (
		<>
			<Editor product={ variation } productType="product_variation" />
			<WooFooterItem order={ 0 }>
				<>
					<VariationSwitcherFooter
						parentId={ variation?.parent_id }
						variationId={ variation?.id }
					/>

					<ProductMVPFeedbackModalContainer
						productId={ variation?.parent_id }
					/>
				</>
			</WooFooterItem>
		</>
	);
}
