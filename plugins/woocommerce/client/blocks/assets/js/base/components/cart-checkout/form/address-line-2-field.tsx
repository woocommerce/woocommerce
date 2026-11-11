/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import {
	useState,
	Fragment,
	useCallback,
	useEffect,
	useLayoutEffect,
	useRef,
} from '@wordpress/element';
import { animateCollapse } from '@woocommerce/base-utils';
import { usePrevious } from '@woocommerce/base-hooks';
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

	// The height of the "+ Add" link that a click replaced, so that the field grows from it, and the content below
	// moves in one direction.
	const growFromRef = useRef< number | null >( null );
	useLayoutEffect( () => {
		if ( isFieldVisible && growFromRef.current !== null && props?.id ) {
			animateCollapse(
				document
					.getElementById( props.id )
					?.closest< HTMLElement >(
						'.wc-block-components-address-form__address_2'
					) ?? null,
				true,
				true,
				{ height: `${ growFromRef.current }px`, opacity: '0' }
			);
		}
		growFromRef.current = null;
	}, [ isFieldVisible, props?.id ] );

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
							// A click that a key press starts has no click count, and applies immediately.
							growFromRef.current =
								event.detail === 0
									? null
									: event.currentTarget.offsetHeight;
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
