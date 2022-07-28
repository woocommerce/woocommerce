/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductFormLayout } from './layout/product-form-layout';

const EditProductPage: React.FC = () => {
	return (
		<div>
			<ProductFormLayout
				categories={ [
					{
						id: 'product_details',
						title: 'Product details',
						description:
							'This info will be displayed on the product page, category pages, social media, and search results.',
						fields: [
							<TextControl
								label="Name"
								key="name"
								autoComplete="address-line1"
								value=""
								onChange={ () => {} }
							/>,
							<TextControl
								label="Category"
								key="category"
								autoComplete="address-line1"
								value=""
								onChange={ () => {} }
							/>,
						],
					},
					{
						id: 'product_images',
						title: 'Images',
						description:
							'For best results, use JPEG files that are 1000 by 1000 pixels or larger.',
						fields: [
							<TextControl
								label="Images"
								key="images"
								autoComplete="address-line1"
								value=""
								onChange={ () => {} }
							/>,
						],
					},
				] }
			/>
		</div>
	);
};

export default EditProductPage;
