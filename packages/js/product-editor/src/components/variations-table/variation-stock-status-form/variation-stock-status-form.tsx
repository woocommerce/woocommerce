/**
 * External dependencies
 */
import type { FormEvent } from 'react';
import { Button, ToggleControl } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { VariationStockStatusFormProps } from './types';

export function VariationStockStatusForm( {
	initialValue,
	onSubmit,
	onCancel,
}: VariationStockStatusFormProps ) {
	const [ value, setValue ] = useState( {
		manage_stock: Boolean( initialValue?.manage_stock ),
	} );

	function handleSubmit( event: FormEvent< HTMLFormElement > ) {
		event.preventDefault();

		onSubmit?.( value );
	}

	function handleTrackInventoryToggleChange( isChecked: boolean ) {
		setValue( ( current ) => ( { ...current, manage_stock: isChecked } ) );
	}

	return (
		<form
			onSubmit={ handleSubmit }
			className="woocommerce-variation-stock-status-form"
			aria-label={ __( 'Variation stock status form', 'woocommerce' ) }
		>
			<div className="woocommerce-variation-stock-status-form__controls">
				<ToggleControl
					label={ __( 'Track inventory', 'woocommerce' ) }
					checked={ value.manage_stock }
					onChange={ handleTrackInventoryToggleChange }
				/>
			</div>

			<div className="woocommerce-variation-stock-status-form__actions">
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
