/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { EnrichedLabel, Link, useFormContext } from '@woocommerce/components';
import { useContext } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import { getTextControlProps } from './utils';
import { ADMIN_URL } from '../../utils/admin-settings';
import {
	CurrencyContext,
	getFilteredCurrencyInstance,
} from '../../lib/currency-context';

const PRICING_SLUG = 'pricing';

export const PricingSection: React.FC = () => {
	const { getInputProps } = useFormContext< Product >();
	const context = useContext( CurrencyContext );
	return (
		<ProductSectionLayout
			title={ __( 'Pricing', 'woocommerce' ) }
			description={
				<>
					<span>
						{ __(
							'Set a competitive price, put the product on sale, and manage tax calculations.',
							'woocommerce'
						) }
					</span>
					<Link
						href="https://woocommerce.com/posts/how-to-price-products-strategies-expert-tips/"
						target="_blank"
						type="external"
						onClick={ () => {
							recordEvent( 'add_product_pricing_help' );
						} }
					>
						How to price your product: expert tips
					</Link>
				</>
			}
		>
			<InputControl
				type="number"
				prefix="$"
				label={ __( 'List price', 'woocommerce' ) }
				placeholder={ __( '10.59', 'woocommerce' ) }
				{ ...getTextControlProps( getInputProps( 'regular_price' ) ) }
			/>
			<span>
				Per your&nbsp;
				<Link
					href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=tax` }
					target="_blank"
					type="external"
					onClick={ () => {
						recordEvent( 'add_product_pricing_list_price_help' );
					} }
				>
					store settings
				</Link>
				, tax is <strong>included</strong> in the price.
			</span>
			<InputControl
				type="number"
				prefix="$"
				label={ __( 'Sale price (optional)', 'woocommerce' ) }
				placeholder={ __( '10.59', 'woocommerce' ) }
				{ ...getTextControlProps( getInputProps( 'sale_price' ) ) }
			/>
		</ProductSectionLayout>
	);
};
