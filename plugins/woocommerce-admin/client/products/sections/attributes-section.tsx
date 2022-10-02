/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { Link, useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import { AttributeField } from '../fields/attribute-field';

export const AttributesSection: React.FC = () => {
	const { getInputProps, values } = useFormContext< Product >();

	// TODO: remove https://github.com/woocommerce/woocommerce/issues/34333 is done.
	if ( values.attributes && values.attributes.length > 0 ) {
		return null;
	}

	return (
		<ProductSectionLayout
			title={ __( 'Attributes', 'woocommerce' ) }
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
			<Card>
				<CardBody>
					<AttributeField { ...getInputProps( 'attributes' ) } />
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
