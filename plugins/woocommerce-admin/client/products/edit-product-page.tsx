/**
 * Internal dependencies
 */
import { ProductDetailsCategory } from './categories/product-details-category';
import { ProductImagesCategory } from './categories/product-images-category';
import { ProductFormLayout } from './layout/product-form-layout';

const categories = [
	{
		id: 'product_details',
		title: 'Product details',
		description:
			'This info will be displayed on the product page, category pages, social media, and search results.',
		fields: [
			{
				label: 'Name',
				key: 'name',
				autoComplete: 'address-line1',
				value: '',
				onChange: () => {},
			},
			{
				label: 'Category',
				key: 'category',
				autoComplete: 'address-line1',
				value: '',
				onChange: () => {},
			},
		],
	},
	{
		id: 'product_images',
		title: 'Images',
		description:
			'For best results, use JPEG files that are 1000 by 1000 pixels or larger.',
		fields: [
			{
				label: 'Images',
				key: 'images',
				autoComplete: 'address-line1',
				value: '',
				onChange: () => {},
			},
		],
	},
];

const EditProductPage: React.FC = () => {
	return (
		<ProductFormLayout>
			<ProductDetailsCategory />
			<ProductImagesCategory />
		</ProductFormLayout>
	);
};

export default EditProductPage;
