/**
 * External dependencies
 */
import {
	__experimentalEditor as Editor,
	AUTO_DRAFT_NAME,
} from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './product-page.scss';

export default function ProductPage() {
	const { saveEntityRecord } = useDispatch( 'core' );
	const [ product, setProduct ] = useState< Product | undefined >(
		undefined
	);

	useEffect( () => {
		saveEntityRecord( 'postType', 'product', {
			title: AUTO_DRAFT_NAME,
		} ).then( ( autoDraftProduct: Product ) => {
			setProduct( autoDraftProduct );
		} );
	}, [] );

	if ( ! product ) {
		return <Spinner />;
	}

	return <Editor product={ product } settings={ {} } />;
}
