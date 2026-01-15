/**
 * External dependencies
 */
import { useProductDataContext } from '@woocommerce/shared-context';
import { Disabled, Spinner } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { isProductResponseItem } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import QuantityStepper from '../../components/quantity-stepper';

const CTA = () => {
	const { isLoading, product } = useProductDataContext();

	if ( isLoading || ! isProductResponseItem( product ) ) {
		return <Spinner />;
	}

	const {
		permalink,
		add_to_cart: productCartDetails,
		has_options: hasOptions,
		is_purchasable: isPurchasable,
		is_in_stock: isInStock,
		sold_individually: soldIndividually,
	} = product;

	if ( ! hasOptions && isPurchasable && isInStock ) {
		if ( soldIndividually ) {
			return (
				<input
					type="checkbox"
					value="1"
					className="wc-grouped-product-add-to-cart-checkbox"
				/>
			);
		}
		return <QuantityStepper />;
	}

	return (
		<a
			aria-label={ productCartDetails?.description || '' }
			className="button wp-element-button add_to_cart_button wc-block-components-product-button__button"
			href={ permalink }
		>
			{ productCartDetails?.text || __( 'Add to Cart', 'woocommerce' ) }
		</a>
	);
};

export default function ProductItemCTAEdit() {
	const blockProps = useBlockProps( {
		className:
			'wc-block-add-to-cart-with-options-grouped-product-item-selector',
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<CTA />
			</Disabled>
		</div>
	);
}
