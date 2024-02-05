/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { Link, useFormContext } from '@woocommerce/components';
import { Product, ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import { Variations } from '../fields/variations';

export const ProductVariationsSection: React.FC = () => {
	const {
		getInputProps,
		values: { id: productId },
	} = useFormContext< Product >();

	const { value: attributes }: { value: ProductAttribute[] } = getInputProps(
		'attributes',
		{
			productId,
		}
	);

	const options = attributes
		? attributes.filter(
				( attribute: ProductAttribute ) => attribute.variation
		  )
		: [];

	if ( options.length === 0 ) {
		return null;
	}

	return (
		<ProductSectionLayout
			title={ __( 'Variations', 'woocommerce' ) }
			description={
				<>
					<span>
						{ __(
							'Manage individual product combinations created from options.',
							'woocommerce'
						) }
					</span>
					<Link
						className="woocommerce-form-section__header-link"
						href="https://woo.com/posts/product-variations-display/"
						target="_blank"
						type="external"
						onClick={ () => {
							recordEvent( 'add_product_variation_help' );
						} }
					>
						{ __(
							'How to make variations work for you',
							'woocommerce'
						) }
					</Link>
				</>
			}
		>
			<Variations />
		</ProductSectionLayout>
	);
};
