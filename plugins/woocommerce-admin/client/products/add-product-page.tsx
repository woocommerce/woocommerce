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
} as Product;

const AddProductPage: React.FC = () => {
	return <Editor product={ dummyProduct } settings={ {} } />;
};

export default AddProductPage;
