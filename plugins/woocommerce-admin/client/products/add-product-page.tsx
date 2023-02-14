/**
 * External dependencies
 */
import { Editor } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';

const dummyProduct = {
	name: 'Example product',
} as Product;

const AddProductPage: React.FC = () => {
	return <Editor product={ dummyProduct } settings={ {} } />;
};

export default AddProductPage;
