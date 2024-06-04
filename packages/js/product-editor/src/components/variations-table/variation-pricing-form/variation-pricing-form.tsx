/**
 * External dependencies
 */
import type { FormEvent } from 'react';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
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

	function handleSubmit( event: FormEvent< HTMLFormElement > ) {
		event.preventDefault();

		onSubmit?.( value );
	}

	return (
		<form
			onSubmit={ handleSubmit }
			className="woocommerce-variation-pricing-form"
			aria-label={ __( 'Variation pricing form', 'woocommerce' ) }
		>
			<div className="woocommerce-variation-pricing-form__controls">
				<InputControl
					name="regular_price"
					label={ __( 'Regular price', 'woocommerce' ) }
					value={ value.regular_price }
					onChange={ ( regular_price: string ) =>
						setValue( ( current ) => ( {
							...current,
							regular_price,
						} ) )
					}
				/>

				<InputControl
					name="sale_price"
					label={ __( 'Sale price', 'woocommerce' ) }
					value={ value.sale_price }
					onChange={ ( sale_price: string ) =>
						setValue( ( current ) => ( {
							...current,
							sale_price,
						} ) )
					}
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
