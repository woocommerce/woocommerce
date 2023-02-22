/**
 * External dependencies
 */
import { Editor } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { AUTO_DRAFT_NAME } from './utils/get-product-title';
import './product-page.scss';

const AddProductPage: React.FC = () => {
	const { saveEntityRecord } = useDispatch( 'core' );
	const [ product, setProduct ] = useState< Product | undefined >(
		undefined
	);

	useEffect( () => {
		saveEntityRecord( 'postType', 'product', {
			title: AUTO_DRAFT_NAME,
		} )
			// @ts-ignore
			.then( ( autoDraftProduct: Product ) => {
				setProduct( autoDraftProduct );
			} );
	}, [] );

	return <Editor product={ product } settings={ {} } />;
};

export default AddProductPage;
