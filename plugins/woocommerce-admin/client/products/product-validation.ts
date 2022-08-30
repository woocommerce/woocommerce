/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ProductStatus, ProductType, Product } from '@woocommerce/data';

export const validate = (
	values: Partial< Product< ProductStatus, ProductType > >
) => {
	const errors: {
		[ key: string ]: string;
	} = {};
	if ( ! values.name?.length ) {
		errors.name = __( 'This field is required.', 'woocommerce' );
	}

	if ( values.name && values.name.length > 120 ) {
		errors.name = __(
			'Please enter a product name shorter than 120 characters.',
			'woocommerce'
		);
	}

	if ( values.regular_price && ! /^[0-9.,]+$/.test( values.regular_price ) ) {
		errors.regular_price = __(
			'Please enter a price with one monetary decimal point without thousand separators and currency symbols.',
			'woocommerce'
		);
	}

	if ( values.sale_price && ! /^[0-9.,]+$/.test( values.sale_price ) ) {
		errors.sale_price = __(
			'Please enter a price with one monetary decimal point without thousand separators and currency symbols.',
			'woocommerce'
		);
	}
	return errors;
};
