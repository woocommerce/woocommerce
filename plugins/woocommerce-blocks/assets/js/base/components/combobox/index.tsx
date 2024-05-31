/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useEffect, useId, useRef } from '@wordpress/element';
import { ComboboxControl } from 'wordpress-components';
import { ValidationInputError } from '@woocommerce/blocks-components';
import { isObject } from '@woocommerce/types';
import { useDispatch, useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import './style.scss';

export interface ComboboxControlOption {
	label: string;
	value: string;
}

export interface ComboboxProps {
	autoComplete?: string | undefined;
	className?: string;
	errorId: string | null;
	errorMessage?: string | undefined;
	id: string;
	instanceId?: string;
	label?: string | undefined;
	onChange: ( filterValue: string ) => void;
	options: ComboboxControlOption[];
	required?: boolean | undefined;
	value: string;
}

/**
 * Wrapper for the WordPress ComboboxControl which supports validation.
 */
const Combobox = ( {
	id,
	className,
	label,
	onChange,
	options,
	value,
	required = false,
	errorId: incomingErrorId,
	autoComplete = 'off',
	errorMessage = __( 'Please select a valid option', 'woocommerce' ),
}: ComboboxProps ): JSX.Element => {
	const controlRef = useRef< HTMLDivElement >( null );
	const fallbackId = useId();
	const controlId = id || 'control-' + fallbackId;
	const errorId = incomingErrorId || controlId;

	const { setValidationErrors, clearValidationError } =
		useDispatch( VALIDATION_STORE_KEY );

	const { error, validationErrorId } = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return {
			error: store.getValidationError( errorId ),
			validationErrorId: store.getValidationErrorId( errorId ),
		};
	} );

	useEffect( () => {
		if ( ! required || value ) {
			clearValidationError( errorId );
		} else {
			setValidationErrors( {
				[ errorId ]: {
					message: errorMessage,
					hidden: true,
				},
			} );
		}
		return () => {
			clearValidationError( errorId );
		};
	}, [
		clearValidationError,
		value,
		errorId,
		errorMessage,
		required,
		setValidationErrors,
	] );

	// @todo Remove patch for ComboboxControl once https://github.com/WordPress/gutenberg/pull/33928 is released
	// Also see https://github.com/WordPress/gutenberg/pull/34090
	return (
		<div
			id={ controlId }
			className={ clsx( 'wc-block-components-combobox', className, {
				'is-active': value,
				'has-error': error?.message && ! error?.hidden,
			} ) }
			ref={ controlRef }
		>
			<ComboboxControl
				className={ 'wc-block-components-combobox-control' }
				label={ label }
				onChange={ onChange }
				onFilterValueChange={ ( filterValue: string ) => {
					if ( filterValue.length ) {
						// If we have a value and the combobox is not focussed, this could be from browser autofill.
						const activeElement = isObject( controlRef.current )
							? controlRef.current.ownerDocument.activeElement
							: undefined;

						if (
							activeElement &&
							isObject( controlRef.current ) &&
							controlRef.current.contains( activeElement )
						) {
							return;
						}

						// Try to match.
						const normalizedFilterValue =
							filterValue.toLocaleUpperCase();

						// Try to find an exact match first using values.
						const foundValue = options.find(
							( option ) =>
								option.value.toLocaleUpperCase() ===
								normalizedFilterValue
						);

						if ( foundValue ) {
							onChange( foundValue.value );
							return;
						}

						// Fallback to a label match.
						const foundOption = options.find( ( option ) =>
							option.label
								.toLocaleUpperCase()
								.startsWith( normalizedFilterValue )
						);

						if ( foundOption ) {
							onChange( foundOption.value );
						}
					}
				} }
				options={ options }
				value={ value || '' }
				allowReset={ false }
				autoComplete={ autoComplete }
				// Note these aria properties are ignored by ComboboxControl. When we replace ComboboxControl we should support them.
				aria-invalid={ error?.message && ! error?.hidden }
				aria-errormessage={ validationErrorId }
			/>
			<ValidationInputError propertyName={ errorId } />
		</div>
	);
};

export default Combobox;
