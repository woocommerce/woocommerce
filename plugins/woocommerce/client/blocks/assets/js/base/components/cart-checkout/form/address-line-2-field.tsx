/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { useState, Fragment, useCallback, useEffect } from '@wordpress/element';
import { usePrevious, useToggleCollapse } from '@woocommerce/base-hooks';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@ariakit/react';
import { getFieldLabel } from '@woocommerce/blocks-checkout';
/**
 * Internal dependencies
 */
import { AddressLineFieldProps } from './types';
import './style.scss';

const AddressLine2Field = ( {
	field,
	props,
	onChange,
	value,
}: AddressLineFieldProps ): JSX.Element => {
	const isFieldRequired = field?.required ?? false;
	const previousIsFieldRequired = usePrevious( isFieldRequired );

	// Display the input field if it has a value or if it is required.
	const [ isFieldVisible, setIsFieldVisible ] = useState(
		() => Boolean( value ) || isFieldRequired
	);

	const fieldLabel = getFieldLabel( field.label );
	// Re-render if the isFieldVisible prop changes.
	useEffect( () => {
		if ( previousIsFieldRequired !== isFieldRequired ) {
			setIsFieldVisible( Boolean( value ) || isFieldRequired );
		}
	}, [ value, previousIsFieldRequired, isFieldRequired ] );

	const handleHiddenInputChange = useCallback(
		( newValue: string ) => {
			onChange( newValue );
			setIsFieldVisible( true );
		},
		[ onChange ]
	);

	// Rerender if value changes to anything non-empty.
	useEffect( () => {
		if ( value ) {
			setIsFieldVisible( true );
		}
	}, [ value ] );

	// The field grows from the height of the "+ Add" link that a click replaced, so that the content below moves in one
	// direction.
	const fieldCollapse = useToggleCollapse(
		Boolean( isFieldVisible ),
		() =>
			( props?.id &&
				document
					.getElementById( props.id )
					?.closest< HTMLElement >(
						'.wc-block-components-address-form__address_2'
					) ) ||
			null,
		{ growFromTrigger: true }
	);

	return (
		<Fragment>
			{ isFieldVisible ? (
				<ValidatedTextInput
					{ ...props }
					type={ field.type }
					label={
						isFieldRequired ? field.label : field.optionalLabel
					}
					className={ `wc-block-components-address-form__address_2` }
					value={ value }
					onChange={ ( newValue: string ) => onChange( newValue ) }
				/>
			) : (
				<>
					<Button
						render={ <span /> }
						className={
							'wc-block-components-address-form__address_2-toggle'
						}
						onClick={ ( event ) => {
							fieldCollapse.onToggle( event );
							setIsFieldVisible( true );
						} }
					>
						{ sprintf(
							// translators: %s: address 2 field label.
							__( '+ Add %s', 'woocommerce' ),
							fieldLabel
						) }
					</Button>
					<input
						type="text"
						tabIndex={ -1 }
						className="wc-block-components-address-form__address_2-hidden-input"
						aria-hidden="true"
						aria-label={ field.label }
						autoComplete={ props?.autoComplete }
						id={ props?.id }
						value={ value }
						onChange={ ( event ) =>
							handleHiddenInputChange( event.target.value )
						}
					/>
				</>
			) }
		</Fragment>
	);
};

export default AddressLine2Field;
