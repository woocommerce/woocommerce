/**
 * External dependencies
 */
import {
	Combobox as AriakitCombobox,
	ComboboxLabel as AriakitComboboxLabel,
	ComboboxPopover as AriakitComboboxPopover,
	ComboboxProvider as AriakitComboboxProvider,
} from '@ariakit/react';
import type { ComboboxProps as AriakitComboboxProps } from '@ariakit/react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useEffect, useId, useRef } from '@wordpress/element';
import { ComboboxControl } from 'wordpress-components';
import { ValidationInputError } from '@woocommerce/blocks-components';
import { isObject } from '@woocommerce/types';
import { use, useDispatch, useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import './style.scss';

interface ComboboxControlOption {
	label: string;
	value: string;
}

interface WCComboboxProps extends AriakitComboboxProps {
	errorId: string | null;
	errorMessage?: string | undefined;
	instanceId?: string;
	//onChange: ( filterValue: string ) => void;
	options: ComboboxControlOption[];
	value: string;
	label: string;
}

/**
 * Wrapper for the Ariakit Combobox with validation support.
 */
const Combobox = ( {
	id,
	label,
	onChange,
	options,
	value,
	errorId: incomingErrorId,
	required = false,
	autoComplete = 'off',
	errorMessage = __( 'Please select a valid option', 'woocommerce' ),
	...restOfProps
}: WCComboboxProps ): JSX.Element => {
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

	const [ searchTerm, setSearchTerm ] = useState( '' );

	const matches = useMemo( () => {}, [ searchTerm ] );

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

	const wrapperClasses = classnames(
		'wc-block-components-combobox',
		restOfProps.className || '',
		{
			'is-active': value,
			'has-error': error?.message && ! error?.hidden,
		}
	);

	return (
		<div className={ wrapperClasses } ref={ controlRef }>
			<AriakitComboboxProvider
				setValue={ ( val ) => {
					setSearchTerm( val );

					// Try to match.
					const normalizedFilterValue = val.toLocaleUpperCase();

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
				} }
			>
				<AriakitComboboxLabel>{ label }</AriakitComboboxLabel>
				<AriakitCombobox
					className={ 'wc-block-components-combobox-control' }
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
					value={ value || '' }
					allowReset={ false }
					autoComplete={ autoComplete }
					aria-invalid={ error?.message && ! error?.hidden }
					aria-errormessage={ validationErrorId }
				/>
				<AriakitComboboxPopover></AriakitComboboxPopover>
			</AriakitComboboxProvider>
			<ValidationInputError propertyName={ errorId } />
		</div>
	);
};

export default Combobox;
