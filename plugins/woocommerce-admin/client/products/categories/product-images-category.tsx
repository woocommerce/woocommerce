/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProductCategoryLayout } from '../layout/product-category-layout';
import { ProductFieldLayout } from '../layout/product-field-layout';

export const ProductImagesCategory: React.FC = () => {
	return (
		<ProductCategoryLayout
			title={ __( 'Images', 'woocommerce' ) }
			description={ __(
				'For best results, use JPEG files that are 1000 by 1000 pixels or larger..',
				'woocommerce'
			) }
		>
			<TextControl
				label={ __( 'Images', 'woocommerce' ) }
				name="images"
				value={ '' }
				onChange={ () => {} }
			/>
		</ProductCategoryLayout>
	);
};
