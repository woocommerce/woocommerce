/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ProductStatus,
	ProductType,
	Product,
	ProductDimensions,
} from '@woocommerce/data';
import type { FormErrors } from '@woocommerce/components';
import moment from 'moment';

const validateInventory = (
	values: Partial< Product< ProductStatus, ProductType > >,
	errors: FormErrors< typeof values >
) => {
	const nextErrors = { ...errors };

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

function validateScheduledSaleFields(
	values: Partial< Product< ProductStatus, ProductType > >
): FormErrors< typeof values > {
	const errors: FormErrors< typeof values > = {};

	const dateOnSaleFrom = moment(
		values.date_on_sale_from_gmt,
		moment.ISO_8601,
		true
	);
	const dateOnSaleTo = moment(
		values.date_on_sale_to_gmt,
		moment.ISO_8601,
		true
	);

	if ( values.date_on_sale_from_gmt && ! dateOnSaleFrom.isValid() ) {
		errors.date_on_sale_from_gmt = __(
			'Please enter a valid date.',
			'woocommerce'
		);
	}

	if ( values.date_on_sale_to_gmt && ! dateOnSaleTo.isValid() ) {
		errors.date_on_sale_to_gmt = __(
			'Please enter a valid date.',
			'woocommerce'
		);
	}

	if ( dateOnSaleFrom.isAfter( dateOnSaleTo ) ) {
		errors.date_on_sale_from_gmt = __(
			'The start date of the sale must be before the end date.',
			'woocommerce'
		);
	}

	if ( dateOnSaleTo.isBefore( dateOnSaleFrom ) ) {
		errors.date_on_sale_to_gmt = __(
			'The end date of the sale must be after the start date.',
			'woocommerce'
		);
	}

	return errors;
}

export const validate = (
	values: Partial< Product< ProductStatus, ProductType > >
) => {
	let errors: FormErrors< typeof values > = {};
	errors = validateInventory( values, errors );

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

	if (
		values.sale_price &&
		( ! values.regular_price ||
			parseFloat( values.sale_price ) >=
				parseFloat( values?.regular_price ) )
	) {
		errors.sale_price = __(
			'Sale price cannot be equal to or higher than list price.',
			'woocommerce'
		);
	}

	errors = {
		...errors,
		...validateScheduledSaleFields( values ),
	};

	if ( values.dimensions?.width && +values.dimensions.width <= 0 ) {
		errors.dimensions = {
			width: __( 'Width must be higher than zero.', 'woocommerce' ),
		};
	}

	if ( values.dimensions?.length && +values.dimensions.length <= 0 ) {
		errors.dimensions = {
			...( ( errors.dimensions as FormErrors< ProductDimensions > ) ??
				{} ),
			length: __( 'Length must be higher than zero.', 'woocommerce' ),
		};
	}

	if ( values.dimensions?.height && +values.dimensions.height <= 0 ) {
		errors.dimensions = {
			...( ( errors.dimensions as FormErrors< ProductDimensions > ) ??
				{} ),
			height: __( 'Height must be higher than zero.', 'woocommerce' ),
		};
	}

	if ( values.weight && +values.weight <= 0 ) {
		errors.weight = __( 'Weight must be higher than zero.', 'woocommerce' );
	}

	return errors;
};
