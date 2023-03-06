/**
 * External dependencies
 */
import { Editor } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './product-page.scss';

const dummyProduct = {
	name: 'Example product',
	short_description: 'Short product description content',
} as Product;

export default function ProductPage() {
	return <Editor product={ dummyProduct } settings={ {} } />;
}
