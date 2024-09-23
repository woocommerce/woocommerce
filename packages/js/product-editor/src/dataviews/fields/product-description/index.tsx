/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import type { Field } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProductDescriptionField } from './product-description-field';

const productDescriptionField: Field< Product > = {
	id: 'description',
	label: __( 'Description', 'woocommerce' ),
	placeholder: __( 'No title', 'woocommerce' ),
	Edit: ProductDescriptionField,
};

export default productDescriptionField;
