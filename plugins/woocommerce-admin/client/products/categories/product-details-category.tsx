/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProductCategoryLayout } from '../layout/product-category-layout';

export const ProductDetailsCategory: React.FC = () => {
	return (
		<ProductCategoryLayout
			title={ __( 'Product details', 'woocommerce' ) }
			description={ __(
				'This info will be displayed on the product page, category pages, social media, and search results.',
				'woocommerce'
			) }
		>
			<TextControl
				label={ __( 'Name', 'woocommerce' ) }
				value={ '' }
				onChange={ () => {} }
			/>
		</ProductCategoryLayout>
	);
};
