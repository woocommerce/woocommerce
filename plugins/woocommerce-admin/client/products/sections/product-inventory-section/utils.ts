/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ProductStatus, ProductType, Product } from '@woocommerce/data';
import type { FormErrors } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { isValidSlug } from '../utils';

export const validate = (
	values: Partial< Product< ProductStatus, ProductType > >,
	errors: FormErrors< typeof values >
) => {
	const nextErrors = { ...errors };
	if ( values.sku && values.sku.length > 0 && ! isValidSlug( values.sku ) ) {
		nextErrors.sku = __(
			'!!SKU can only contain alphanumeric characters, hyphens, and underscores.',
			'woocommerce'
		);
	}

	if ( values.sku && values.sku.length > 160 ) {
		nextErrors.sku = __(
			'SKU must be a maximum of 160 characters.',
			'woocommerce'
		);
	}

	if ( values.sku && values.sku.length > 160 ) {
		nextErrors.sku = __(
			'SKU must be a maximum of 160 characters.',
			'woocommerce'
		);
	}

	if ( values.stock_quantity && values.stock_quantity < 0 ) {
		nextErrors.stock_quantity = __(
			'Stock quantity must be a positive number.',
			'woocommerce'
		);
	}

	if ( values.low_stock_amount && values.low_stock_amount < 0 ) {
		nextErrors.low_stock_amount = __(
			'Stock quantity must be a positive number.',
			'woocommerce'
		);
	}

	return nextErrors;
};
