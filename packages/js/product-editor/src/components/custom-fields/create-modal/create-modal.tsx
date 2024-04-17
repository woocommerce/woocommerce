/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { createElement, useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';
import classNames from 'classnames';
import type { FocusEvent } from 'react';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { TextControl } from '../../text-control';
import {
	ValidationError,
	validate,
	type ValidationErrors,
} from '../utils/validations';
import type { Metadata } from '../../../types';
import type { CreateModalProps } from './types';

const DEFAULT_CUSTOM_FIELD = {
	id: 1,
	key: '',
	value: '',
} satisfies Metadata< string >;

export function CreateModal( {
	values,
	onCreate,
	onCancel,
	...props
}: CreateModalProps ) {
	const [ customFields, setCustomFields ] = useState< Metadata< string >[] >(
		[ DEFAULT_CUSTOM_FIELD ]
	);
	const [ validationError, setValidationError ] =
		useState< ValidationErrors >( {} );
	const inputRefs = useRef<
		Record<
			string,
			Record< keyof Metadata< string >, HTMLInputElement | null >
		>
	>( {} );

	useEffect( function focusFirstNameInputOnMount() {
		const firstRef = inputRefs.current[ DEFAULT_CUSTOM_FIELD.id ];
		firstRef?.key?.focus();
	}, [] );

	function getRef(
		customField: Metadata< string >,
		prop: keyof Metadata< string >
	) {
		return function setRef( element: HTMLInputElement ) {
			const id = String( customField.id );
			inputRefs.current[ id ] = {
				...inputRefs.current[ id ],
				[ prop ]: element,
			};
		};
	}

	function getValidationError(
		customField: Metadata< string >,
		prop: keyof Metadata< string >
	) {
		return validationError[ String( customField.id ) ]?.[ prop ];
	}

	function changeHandler(
		customField: Metadata< string >,
		prop: keyof Metadata< string >
	) {
		return function handleChange( value: string ) {
			setCustomFields( ( current ) =>
				current.map( ( field ) =>
					field.id === customField.id
						? { ...field, [ prop ]: value }
						: field
				)
			);
		};
	}

	function blurHandler(
		customField: Metadata< string >,
		prop: keyof Metadata< string >
	) {
		return function handleBlur( event: FocusEvent< HTMLInputElement > ) {
			const error = validate(
				{
					...customField,
					[ prop ]: event.target.value,
				},
				[ ...customFields, ...values ]
			);
			const id = String( customField.id );
			setValidationError( ( current ) => ( {
				...current,
				[ id ]: {
					...( current[ id ] as ValidationError ),
					[ prop ]: error[ prop ],
				},
			} ) );
		};
	}

	function removeCustomFieldButtonClickHandler(
		customField: Metadata< string >
	) {
		if ( customFields.length <= 1 ) {
			return undefined;
		}

		return function handleRemoveCustomFieldButtonClick() {
			setCustomFields( ( current ) =>
				current.filter( ( { id } ) => customField.id !== id )
			);
			setValidationError( ( current ) => ( {
				...current,
				[ `${ customField.id }` ]: undefined,
			} ) );
		};
	}

	function handleAddAnotherButtonClick() {
		setCustomFields( ( current ) => {
			const lastField = current[ current.length - 1 ];
			return [
				...current,
				{ ...DEFAULT_CUSTOM_FIELD, id: ( lastField.id ?? 0 ) + 1 },
			];
		} );

		recordEvent( 'product_custom_fields_add_another_button_click', {
			source: TRACKS_SOURCE,
		} );
	}

	function handleAddButtonClick() {
		const { errors, hasErrors } = customFields.reduce(
			( prev, customField ) => {
				const _errors = validate( customField, [
					...customFields,
					...values,
				] );
				prev.errors[ String( customField.id ) ] = _errors;

				if ( _errors.key ) {
					if ( ! prev.hasErrors ) {
						inputRefs.current[
							String( customField.id )
						]?.key?.focus();
					}
					prev.hasErrors = true;
				}

				if ( _errors.value ) {
					if ( ! prev.hasErrors ) {
						inputRefs.current[
							String( customField.id )
						]?.value?.focus();
					}
					prev.hasErrors = true;
				}

				return prev;
			},
			{ errors: {} as ValidationErrors, hasErrors: false }
		);

		if ( hasErrors ) {
			setValidationError( errors );
			return;
		}

		onCreate(
			customFields.map( ( { id, ...customField } ) => ( {
				key: customField.key.trim(),
				value: customField.value?.trim(),
			} ) )
		);

		recordEvent( 'product_custom_fields_add_new_button_click', {
			source: TRACKS_SOURCE,
			custom_field_names: customFields.map(
				( customField ) => customField.key
			),
			total: customFields.length,
		} );
	}

	return (
		<Modal
			shouldCloseOnClickOutside={ false }
			title={ __( 'Add custom fields', 'woocommerce' ) }
			onRequestClose={ onCancel }
			{ ...props }
			className={ classNames(
				'woocommerce-product-custom-fields__create-modal',
				props.className
			) }
		>
			<ul className="woocommerce-product-custom-fields__create-modal-list">
				{ customFields.map( ( customField ) => (
					<li
						key={ customField.id }
						className="woocommerce-product-custom-fields__create-modal-list-item"
					>
						<TextControl
							ref={ getRef( customField, 'key' ) }
							label={ __( 'Name', 'woocommerce' ) }
							error={ getValidationError( customField, 'key' ) }
							value={ customField.key }
							onChange={ changeHandler( customField, 'key' ) }
							onBlur={ blurHandler( customField, 'key' ) }
						/>

						<TextControl
							ref={ getRef( customField, 'value' ) }
							label={ __( 'Value', 'woocommerce' ) }
							error={ getValidationError( customField, 'value' ) }
							value={ customField.value }
							onChange={ changeHandler( customField, 'value' ) }
							onBlur={ blurHandler( customField, 'value' ) }
						/>

						<Button
							icon={ closeSmall }
							disabled={ customFields.length <= 1 }
							aria-label={ __(
								'Remove custom field',
								'woocommerce'
							) }
							onClick={ removeCustomFieldButtonClickHandler(
								customField
							) }
						/>
					</li>
				) ) }
			</ul>

			<div className="woocommerce-product-custom-fields__create-modal-add-another">
				<Button
					variant="tertiary"
					onClick={ handleAddAnotherButtonClick }
				>
					{ __( '+ Add another', 'woocommerce' ) }
				</Button>
			</div>

			<div className="woocommerce-product-custom-fields__create-modal-actions">
				<Button variant="secondary" onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>

				<Button variant="primary" onClick={ handleAddButtonClick }>
					{ __( 'Add', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}
