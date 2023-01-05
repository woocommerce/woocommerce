/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { Link, useFormContext } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	Product,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import { Variations } from '../fields/variations';

export const ProductVariationsSection: React.FC = () => {
	const { values } = useFormContext< Product >();
	const productId = values.id;

	const { totalCount } = useSelect(
		( select ) => {
			const { getProductVariationsTotalCount } = select(
				EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
			);
			const requestParams = {
				product_id: productId,
			};
			return {
				totalCount:
					getProductVariationsTotalCount< number >( requestParams ),
			};
		},
		[ productId ]
	);

	if ( totalCount === 0 || isNaN( totalCount ) ) {
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
						href="https://woocommerce.com/posts/product-variations-display/"
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
