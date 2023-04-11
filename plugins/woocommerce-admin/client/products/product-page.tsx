/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	ProductEditorSettings,
} from '@woocommerce/product-editor';

import { Spinner } from '@wordpress/components';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { useProductEntityRecord } from './hooks/use-product-entity-record';

import './product-page.scss';
import './product-block-page.scss';
import './fills/product-block-editor-fills';

declare const productBlockEditorSettings: ProductEditorSettings;

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
