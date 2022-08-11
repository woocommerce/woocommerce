/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';

export const ProductDetailsSection: React.FC = () => {
	const formContext = useFormContext< Product >();
	return (
		<ProductSectionLayout
			title={ __( 'Product details', 'woocommerce' ) }
			description={ __(
				'This info will be displayed on the product page, category pages, social media, and search results.',
				'woocommerce'
			) }
		>
			<TextControl
				label={ __( 'Name', 'woocommerce' ) }
				name="name"
				{ ...formContext.getInputProps< string >( 'name' ) }
			/>
		</ProductSectionLayout>
	);
};
