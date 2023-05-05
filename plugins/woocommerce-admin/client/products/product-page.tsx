/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	ProductEditorSettings,
	productApiFetchMiddleware,
} from '@woocommerce/product-editor';

import { Spinner } from '@wordpress/components';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { useProductEntityRecord } from './hooks/use-product-entity-record';

import './fills/product-block-editor-fills';

declare const productBlockEditorSettings: ProductEditorSettings;

productApiFetchMiddleware();

export default function ProductPage() {
	const { productId } = useParams();

	const product = useProductEntityRecord( productId );

	if ( ! product?.id ) {
		return <Spinner />;
	}

	return (
		<Editor
			product={ product }
			settings={ productBlockEditorSettings || {} }
		/>
	);
}
