/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	__experimentalInitBlocks as initBlocks,
	ProductEditorSettings,
	productApiFetchMiddleware,
} from '@woocommerce/product-editor';
import { Spinner } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { useParams } from 'react-router-dom';
import { TourKit } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useProductEntityRecord } from './hooks/use-product-entity-record';
import './fills/product-block-editor-fills';
import './product-page.scss';

declare const productBlockEditorSettings: ProductEditorSettings;

productApiFetchMiddleware();

export default function ProductPage() {
	const { productId } = useParams();

	const product = useProductEntityRecord( productId );

	useEffect( () => {
		return initBlocks();
	}, [] );

	if ( ! product?.id ) {
		return <Spinner />;
	}

	return (
		<>
			<Editor
				product={ product }
				settings={ productBlockEditorSettings || {} }
			/>
			<TourKit config={ { steps: [{
				meta: {
					name: 'todo',
					primaryButton: {
						text: __( 'View highlights' , 'woocommerce' ),
					},
					descriptions: {
						desktop: __( 'We designed a brand new product editing experience to let you focus on what\'s important.' , 'woocommerce' ),
					},
					heading: __( 'Meet a streamlined product form' , 'woocommerce' ),
				},
			}], closeHandler: () => {}} } />
		</>
	);
}
