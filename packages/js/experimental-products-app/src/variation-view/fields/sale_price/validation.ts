/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import { toNumberOrNaN, validatePrice } from '../utils/price';

export function validateSalePrice( item: ProductEntityRecord ): string | null {
	const numericError = validatePrice( item.sale_price );
	if ( numericError ) {
		return numericError;
	}

	const regularPrice = toNumberOrNaN( item.regular_price );

	if ( Number.isNaN( regularPrice ) ) {
		return null;
	}

	const salePrice = toNumberOrNaN( item.sale_price );
	if ( salePrice >= regularPrice ) {
		return __(
			'Sale price must be lower than the regular price.',
			'woocommerce'
		);
	}

	return null;
}
