/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link, useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useContext } from '@wordpress/element';
import {
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import { getInputControlProps } from './utils';
import { ADMIN_URL } from '../../utils/admin-settings';
import { CurrencyContext } from '../../lib/currency-context';

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
				label={ __( 'List price', 'woocommerce' ) }
				placeholder={ __( '10.59', 'woocommerce' ) }
				{ ...getInputControlProps( {
					...getInputProps( 'regular_price' ),
					context,
				} ) }
			/>
			<span className="woocommerce-product-form__secondary-text">
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

			<div className="woocommerce-product-form__custom-label-input">
				<label htmlFor="sale_price">
					Sale price&nbsp;
					<span className="woocommerce-product-form__optional-input">
						(optional)
					</span>
				</label>
				<InputControl
					hideLabelFromVision={ true }
					id="sale_price"
					placeholder={ __( '8.59', 'woocommerce' ) }
					{ ...getInputControlProps( {
						...getInputProps( 'sale_price' ),
						context,
					} ) }
				/>
			</div>
		</ProductSectionLayout>
	);
};
