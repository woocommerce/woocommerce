/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	__experimentalInitBlocks as initBlocks,
	ProductEditorSettings,
	productApiFetchMiddleware,
	TRACKS_SOURCE,
} from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';
import { Spinner } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import './fills/product-block-editor-fills';
import './product-page.scss';
import { useProductVariationEntityRecord } from './hooks/use-product-variation-entity-record';

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
		</>
	);
}
