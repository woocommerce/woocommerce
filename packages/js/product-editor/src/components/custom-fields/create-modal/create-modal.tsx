/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { createElement, useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import classNames from 'classnames';
import type { FocusEvent } from 'react';

/**
 * Internal dependencies
 */
import { TextControl } from '../../text-control';
import type { Metadata } from '../../../types';
import type { CreateModalProps } from './types';

function validateName( value: string ) {
	if ( ! value ) {
		return __( 'The name is required.', 'woocommerce' );
	}

	if ( value.startsWith( '_' ) ) {
		return __(
			'The name cannot begin with the underscore (_) character.',
			'woocommerce'
		);
	}
}

function validateValue( value: string ) {
	if ( ! value ) {
		return __( 'The value is required.', 'woocommerce' );
	}
}

const DEFAULT_CUSTOM_FIELD = {
	id: 1,
	key: '',
	value: '',
} satisfies Metadata< string >;

type ValidationErrors = {
	[ id: string ]:
		| {
				name?: string;
				value?: string;
		  }
		| undefined;
};

export function CreateModal( {
	onCreate,
	onCancel,
	...props
}: CreateModalProps ) {
	const [ customFields, setCustomFields ] = useState< Metadata< string >[] >(
		[ DEFAULT_CUSTOM_FIELD ]
	);
	const [ validationError, setValidationError ] =
		useState< ValidationErrors >( {} );
	const inputRefs = useRef< Record< string, HTMLInputElement | null > >( {} );

	useEffect( function focusFirstNameInputOnMount() {
		inputRefs.current[ `name${ DEFAULT_CUSTOM_FIELD.id }` ]?.focus();
	}, [] );

	function nameChangeHandler( customField: Metadata< string > ) {
		return function handleNameChange( key: string ) {
			setCustomFields( ( current ) =>
				current.map( ( field ) =>
					field.id === customField.id ? { ...field, key } : field
				)
			);
		};
	}

	function nameBlurHandler( customField: Metadata< string > ) {
		return function handleNameBlur(
			event: FocusEvent< HTMLInputElement >
		) {
			const error = validateName( event.target.value );
			setValidationError( ( current ) => ( {
				...current,
				[ `${ customField.id }` ]: {
					name: error,
					value: current?.[ `${ customField.id }` ]?.value,
				},
			} ) );
		};
	}

	function valueChangeHandler( customField: Metadata< string > ) {
		return function handleValueChange( value: string ) {
			setCustomFields( ( current ) =>
				current.map( ( field ) =>
					field.id === customField.id ? { ...field, value } : field
				)
			);
		};
	}

	function valueBlurHandler( customField: Metadata< string > ) {
		return function handleValueBlur(
			event: FocusEvent< HTMLInputElement >
		) {
			const error = validateValue( event.target.value );
			setValidationError( ( current ) => ( {
				[ `${ customField.id }` ]: {
					name: current?.[ `${ customField.id }` ]?.name,
					value: error,
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
	}

	function handleAddButtonClick() {
		const { errors, hasErrors } = customFields.reduce(
			( prev, customField ) => {
				const errors = {
					name: validateName( customField.key ),
					value: validateValue( customField.value ?? '' ),
				};
				prev.errors[ `${ customField.id }` ] = errors;

				if ( errors.name ) {
					if ( ! prev.hasErrors ) {
						inputRefs.current[ `name${ customField.id }` ]?.focus();
					}
					prev.hasErrors = true;
				}

				if ( errors.value ) {
					if ( ! prev.hasErrors ) {
						inputRefs.current[
							`value${ customField.id }`
						]?.focus();
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
			customFields.map( ( { id, ...customField } ) => customField )
		);
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
							ref={ ( element ) => {
								inputRefs.current[ `name${ customField.id }` ] =
									element;
							} }
							label={ __( 'Name', 'woocommerce' ) }
							error={
								validationError[ `${ customField.id }` ]?.name
							}
							value={ customField.key }
							onChange={ nameChangeHandler( customField ) }
							onBlur={ nameBlurHandler( customField ) }
						/>

						<TextControl
							ref={ ( element ) => {
								inputRefs.current[
									`value${ customField.id }`
								] = element;
							} }
							label={ __( 'Value', 'woocommerce' ) }
							error={
								validationError[ `${ customField.id }` ]?.value
							}
							value={ customField.value }
							onChange={ valueChangeHandler( customField ) }
							onBlur={ valueBlurHandler( customField ) }
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
