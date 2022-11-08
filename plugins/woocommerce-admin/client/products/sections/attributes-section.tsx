/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link, useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './attributes-section.scss';
import { ProductSectionLayout } from '../layout/product-section-layout';
import { AttributeField } from '../fields/attribute-field';

export const AttributesSection: React.FC = () => {
	const {
		getInputProps,
		values: { id: productId },
	} = useFormContext< Product >();

	return (
		<ProductSectionLayout
			title={ __( 'Attributes', 'woocommerce' ) }
			className="woocommerce-product-attributes-section"
			description={
				<>
					<span>
						{ __(
							'Add descriptive pieces of information that customers can use to filter and search for this product.',
							'woocommerce'
						) }
					</span>
					<Link
						className="woocommerce-form-section__header-link"
						href="https://woocommerce.com/document/managing-product-taxonomies/#product-attributes"
						target="_blank"
						type="external"
						onClick={ () => {
							recordEvent( 'learn_more_about_attributes_help' );
						} }
					>
						{ __( 'Learn more about attributes', 'woocommerce' ) }
					</Link>
				</>
			}
		>
			<AttributeField
				{ ...getInputProps( 'attributes', { productId } ) }
			/>
		</ProductSectionLayout>
	);
};
