/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { createElement, useState, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import type { FocusEvent } from 'react';

/**
 * Internal dependencies
 */
import { TextControl } from '../../text-control';
import type { Metadata } from '../../../types';
import type { EditModalProps } from './types';

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
	const [ customField, setCustomField ] =
		useState< Metadata< string > >( initialValue );
	const [ validationError, setValidationError ] = useState< string >();
	const nameTextRef = useRef< HTMLInputElement >( null );

	function renderTitle() {
		return sprintf(
			/* translators: %s: the name of the custom field */
			__( 'Edit %s', 'woocommerce' ),
			customField.key
		);
	}

	function handleNameChange( key: string ) {
		setCustomField( ( current ) => ( { ...current, key } ) );
	}

	function handleNameBlur( event: FocusEvent< HTMLInputElement > ) {
		const error = validateName( event.target.value );
		setValidationError( error );
	}

	function handleValueChange( value: string ) {
		setCustomField( ( current ) => ( { ...current, value } ) );
	}

	function handleUpdateButtonClick() {
		const error = validateName( customField.key );
		if ( error ) {
			setValidationError( error );
			nameTextRef.current?.focus();
			return;
		}

		onUpdate( customField );
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
				ref={ nameTextRef }
				label={ __( 'Name', 'woocommerce' ) }
				error={ validationError }
				value={ customField.key }
				onChange={ handleNameChange }
				onBlur={ handleNameBlur }
			/>

			<TextControl
				label={ __( 'Value', 'woocommerce' ) }
				value={ customField.value }
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
