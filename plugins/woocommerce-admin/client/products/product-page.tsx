/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	AUTO_DRAFT_NAME,
	ProductEditorSettings,
} from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { useDispatch, useSelect, select as WPSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import './product-page.scss';

declare const productBlockEditorSettings: ProductEditorSettings;

const ProductEditor: React.FC< { product: Product | undefined } > = ( {
	product,
} ) => {
	if ( ! product?.id ) {
		return <Spinner />;
	}

	return (
		<Editor
			product={ product }
			settings={ productBlockEditorSettings || {} }
		/>
	);
};

const EditProductEditor: React.FC< { productId: string } > = ( {
	productId,
} ) => {
	const { product } = useSelect(
		( select: typeof WPSelect ) => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore Missing types.
			const { getEditedEntityRecord } = select( 'core' );

			return {
				product: getEditedEntityRecord(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	return <ProductEditor product={ product } />;
};

const AddProductEditor = () => {
	const { saveEntityRecord } = useDispatch( 'core' );
	const [ product, setProduct ] = useState< Product | undefined >(
		undefined
	);

	useEffect( () => {
		saveEntityRecord( 'postType', 'product', {
			title: AUTO_DRAFT_NAME,
			status: 'auto-draft',
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore Incorrect types.
		} ).then( ( autoDraftProduct: Product ) => {
			setProduct( autoDraftProduct );
		} );
	}, [] );

	return <ProductEditor product={ product } />;
};

export default function ProductPage() {
	const { productId } = useParams();

	if ( productId ) {
		return <EditProductEditor productId={ productId } />;
	}

	return <AddProductEditor />;
}
