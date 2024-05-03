/**
 * External dependencies
 */
import classnames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
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
	autoComplete?: string;
	className?: string;
	errorId: string | null;
	errorMessage?: string | undefined;
	id: string;
	instanceId?: string;
	label: string;
	onChange: ( filterValue: string ) => void;
	options: ComboboxControlOption[];
	required?: boolean;
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
	errorMessage = sprintf(
		/* translators: %s select label */
		__( 'Please select a valid %s', 'woocommerce' ),
		label.toLowerCase()
	),
}: ComboboxProps ): JSX.Element => {
	const controlRef = useRef< HTMLDivElement >( null );
	const fallbackId = useId();
	const controlId = id || 'control-' + fallbackId;
	const errorId = incomingErrorId || controlId;

	const { setValidationErrors, clearValidationError } =
		useDispatch( VALIDATION_STORE_KEY );
	const error = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return store.getValidationError( errorId );
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
			className={ classnames( 'wc-block-components-combobox', className, {
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
				aria-invalid={ error?.message && ! error?.hidden }
			/>
			<ValidationInputError propertyName={ errorId } />
		</div>
	);
};

export default Combobox;
