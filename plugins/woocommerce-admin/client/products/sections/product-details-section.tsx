/**
 * External dependencies
 */
import { CheckboxControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import EnrichedLabel from '../fields/enriched-label';

const PRODUCT_DETAILS_SLUG = 'product-details';

export const ProductDetailsSection: React.FC = () => {
	const { getInputProps } = useFormContext< Product >();
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
				name={ `${ PRODUCT_DETAILS_SLUG }-name` }
				placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
				{ ...getInputProps< string >( 'name' ) }
			/>
			<CheckboxControl
				label={
					<EnrichedLabel
						label="Feature this product"
						helpDescription="Include this product in a featured section on your website with a widget or shortcode."
						moreUrl="https://woocommerce.com/document/woocommerce-shortcodes/#products"
						slug={ PRODUCT_DETAILS_SLUG }
					/>
				}
				{ ...getInputProps< string >( 'featured' ) }
				className={ classnames(
					'woocommerce-add-product__checkbox',
					getInputProps< string >( 'featured' ).className
				) }
			/>
		</ProductSectionLayout>
	);
};
