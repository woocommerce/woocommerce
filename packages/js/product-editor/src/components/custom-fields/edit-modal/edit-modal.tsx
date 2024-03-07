/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { TextControl } from '../../text-control';
import type { Metadata } from '../../../types';
import type { EditModalProps } from './types';
import { FocusEvent } from 'react';

function validateName( value: string ) {
	if ( value.startsWith( '_' ) ) {
		return __(
			'The name cannot begin with the underscore (_) character.',
			'woocommerce'
		);
	}
}

export function EditModal( {
	initialValue,
	onUpdate,
	onCancel,
	...props
}: EditModalProps ) {
	const [ value, setValue ] = useState< Metadata< string > >( initialValue );
	const [ validationError, setValidationError ] = useState< string >();

	function renderTitle() {
		return sprintf( __( 'Edit %s', 'woocommerce' ), value.key );
	}

	function handleNameChange( key: string ) {
		setValue( ( current ) => ( { ...current, key } ) );
	}

	function handleNameBlur( event: FocusEvent< HTMLInputElement > ) {
		setValidationError( validateName( event.target.value ) );
	}

	function handleValueChange( value: string ) {
		setValue( ( current ) => ( { ...current, value } ) );
	}

	function handleUpdateButtonClick() {
		const error = validateName( value.key );
		if ( error ) {
			setValidationError( error );
			return;
		}

		onUpdate( value );
	}

	return (
		<Modal
			shouldCloseOnClickOutside={ false }
			{ ...props }
			title={ renderTitle() }
			onRequestClose={ onCancel }
			className={ classNames(
				'woocommerce-product-custom-fields__edit-modal',
				props.className
			) }
		>
			<TextControl
				label={ __( 'Name', 'woocommerce' ) }
				error={ validationError }
				value={ value.key }
				onChange={ handleNameChange }
				onBlur={ handleNameBlur }
			/>

			<TextControl
				label={ __( 'Value', 'woocommerce' ) }
				value={ value.value }
				onChange={ handleValueChange }
			/>

			<div className="woocommerce-product-custom-fields__edit-modal-actions">
				<Button variant="secondary" onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>

				<Button variant="primary" onClick={ handleUpdateButtonClick }>
					{ __( 'Update', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}
