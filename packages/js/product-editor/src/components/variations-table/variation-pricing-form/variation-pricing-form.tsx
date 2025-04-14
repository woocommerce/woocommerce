/**
 * External dependencies
 */
import type { FormEvent } from 'react';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import {
	Button,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useCurrencyInputProps } from '../../../hooks/use-currency-input-props';
import type { VariationPricingFormProps } from './types';

export function VariationPricingForm( {
	initialValue,
	onSubmit,
	onCancel,
}: VariationPricingFormProps ) {
	const [ value, setValue ] = useState( {
		regular_price: initialValue?.regular_price ?? '',
		sale_price: initialValue?.sale_price ?? '',
	} );

	const [ errors, setErrors ] = useState< Partial< typeof value > >( {} );

	const regularPriceInputProps = useCurrencyInputProps( {
		value: value.regular_price,
		onChange( regular_price ) {
			setValue( ( current ) => ( { ...current, regular_price } ) );
		},
	} );

	const salePriceInputProps = useCurrencyInputProps( {
		value: value.sale_price,
		onChange( sale_price ) {
			setValue( ( current ) => ( { ...current, sale_price } ) );
		},
	} );

	function validateRegularPrice(): boolean {
		const validationErrors: Partial< typeof value > = {
			regular_price: undefined,
		};

		const regularPrice = Number.parseFloat( value.regular_price );

		if ( regularPrice ) {
			if ( regularPrice < 0 ) {
				validationErrors.regular_price = __(
					'Regular price must be greater than or equals to zero.',
					'woocommerce'
				);
			}

			if (
				value.sale_price &&
				regularPrice <= Number.parseFloat( value.sale_price )
			) {
				validationErrors.regular_price = __(
					'Regular price must be greater than the sale price.',
					'woocommerce'
				);
			}
		}

		setErrors( validationErrors );

		return ! validationErrors.regular_price;
	}

	function validateSalePrice(): boolean {
		const validationErrors: Partial< typeof value > = {
			sale_price: undefined,
		};

		if ( value.sale_price ) {
			const salePrice = Number.parseFloat( value.sale_price );

			if ( salePrice < 0 ) {
				validationErrors.sale_price = __(
					'Sale price must be greater than or equals to zero.',
					'woocommerce'
				);
			}

			if (
				! value.regular_price ||
				Number.parseFloat( value.regular_price ) <= salePrice
			) {
				validationErrors.sale_price = __(
					'Sale price must be lower than the regular price.',
					'woocommerce'
				);
			}
		}

		setErrors( validationErrors );

		return ! validationErrors.sale_price;
	}

	function handleSubmit( event: FormEvent< HTMLFormElement > ) {
		event.preventDefault();

		if ( validateSalePrice() && validateRegularPrice() ) {
			onSubmit?.( value );
		}
	}

	return (
		<form
			onSubmit={ handleSubmit }
			className="woocommerce-variation-pricing-form"
			aria-label={ __( 'Variation pricing form', 'woocommerce' ) }
		>
			<div className="woocommerce-variation-pricing-form__controls">
				<InputControl
					{ ...regularPriceInputProps }
					name="regular_price"
					label={ __( 'Regular price', 'woocommerce' ) }
					help={ errors.regular_price }
					className={ classNames( regularPriceInputProps.className, {
						'has-error': errors.regular_price,
					} ) }
					onBlur={ validateRegularPrice }
				/>

				<InputControl
					{ ...salePriceInputProps }
					name="sale_price"
					label={ __( 'Sale price', 'woocommerce' ) }
					help={ errors.sale_price }
					className={ classNames( salePriceInputProps.className, {
						'has-error': errors.sale_price,
					} ) }
					onBlur={ validateSalePrice }
				/>
			</div>

			<div className="woocommerce-variation-pricing-form__actions">
				<Button variant="tertiary" onClick={ onCancel }>
					Cancel
				</Button>

				<Button variant="primary" type="submit">
					Save
				</Button>
			</div>
		</form>
	);
}
