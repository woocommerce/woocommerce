/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useAddToCartFormContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { AddToCartButton, QuantityInput, ProductUnavailable } from '../shared';

/**
 * Simple Product Add To Cart Form
 */
const Simple = () => {
	const {
		product,
		quantity,
		minQuantity,
		maxQuantity,
		setQuantity,
		formDisabled,
	} = useAddToCartFormContext();

	if ( product.id && ! product.is_purchasable ) {
		return <ProductUnavailable />;
	}

	if ( product.id && ! product.is_in_stock ) {
		return (
			<ProductUnavailable
				reason={ __(
					'This product is currently out of stock and cannot be purchased.',
					'woocommerce'
				) }
			/>
		);
	}

	return (
		<>
			<QuantityInput
				value={ quantity }
				min={ minQuantity }
				max={ maxQuantity }
				disabled={ formDisabled }
				onChange={ setQuantity }
			/>
			<AddToCartButton />
		</>
	);
};

export default Simple;
