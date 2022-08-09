/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';

export const ProductImagesSection: React.FC = () => {
	return (
		<ProductSectionLayout
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
		</ProductSectionLayout>
	);
};
