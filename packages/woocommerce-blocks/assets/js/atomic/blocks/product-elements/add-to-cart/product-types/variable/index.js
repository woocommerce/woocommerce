/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useAddToCartFormContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import {
	AddToCartButton,
	QuantityInput,
	ProductUnavailable,
} from '../../shared';
import VariationAttributes from './variation-attributes';

/**
 * Variable Product Add To Cart Form
 */
const Variable = () => {
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
			<VariationAttributes product={ product } />
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

export default Variable;
