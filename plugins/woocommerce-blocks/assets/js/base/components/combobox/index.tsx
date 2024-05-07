/**
 * External dependencies
 */
import {
	Combobox as AriakitCombobox,
	ComboboxLabel,
	ComboboxPopover,
	ComboboxProvider,
	ComboboxItem,
	useComboboxStore,
} from '@ariakit/react';
import type { ComboboxProps as AriakitComboboxProps } from '@ariakit/react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import {
	useCallback,
	useEffect,
	useId,
	useMemo,
	useRef,
	useState,
	useTransition,
} from '@wordpress/element';
import { ValidationInputError } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	findBestMatchByLabel,
	findExactMatchByLabel,
	findMatchingSuggestions,
} from './util';

export interface ComboboxControlOption {
	label: string;
	value: string;
}

export type ComboboxProps = Omit< AriakitComboboxProps, 'onChange' > & {
	errorId: string | null;
	errorMessage?: string | undefined;
	instanceId?: string;
	onChange: ( newValue: string ) => void;
	options: ComboboxControlOption[];
	value: string;
	label: string;
};

/**
 * Wrapper for the Ariakit Combobox with validation support.
 */
const Combobox = ( {
	id,
	label,
	options,
	value,
	// Not the native onChange, a custom onChange that is called when the selected value changes.
	onChange,
	errorId: incomingErrorId,
	required = false,
	autoComplete = 'list',
	errorMessage = __( 'Please select a valid option', 'woocommerce' ),
	className,
	...restOfProps
}: ComboboxProps ): JSX.Element => {
	const controlRef = useRef< HTMLDivElement >( null );
	const fallbackId = useId();
	const controlId = id || 'control-' + fallbackId;
	const errorId = incomingErrorId || controlId;

	let autoCompleteValue = autoComplete;

	if (
		! [ 'list', 'inline', 'both', 'none' ].includes( autoCompleteValue )
	) {
		deprecated( `Passing browser autocomplete hints to combobox`, {
			since: '9.0.0',
			plugin: '@woocommerce/components',
			hint: 'Passing autocomplete hints to combobox has no effect, please use the values supported by Ariakit combobox: https://ariakit.org/reference/combobox#autocomplete',
		} );

		// Reset the value to the default.
		autoCompleteValue = 'list';
	}

	const { setValidationErrors, clearValidationError } =
		useDispatch( VALIDATION_STORE_KEY );

	const [ , startTransition ] = useTransition();

	const { error, validationErrorId } = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return {
			error: store.getValidationError( errorId ),
			validationErrorId: store.getValidationErrorId( errorId ),
		};
	} );

	const initialOption = options.find( ( option ) => option.value === value );

	const [ searchTerm, setSearchTerm ] = useState(
		initialOption?.label || ''
	);

	useEffect( () => {
		setSearchTerm( initialOption?.label || '' );
	}, [ initialOption ] );

	const [ selectedOption, setSelectedOption ] = useState( initialOption );

	const matchingSuggestions = useMemo( () => {
		return findMatchingSuggestions( searchTerm, options );
	}, [ searchTerm, options ] );

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

	const outerWrapperClasses = classnames(
		'wc-block-components-combobox',
		className || '',
		{
			'is-active': selectedOption?.value,
			'has-error': error?.message && ! error?.hidden,
		}
	);

	const ariaInvalid = error?.message && ! error?.hidden ? 'true' : 'false';
	const store = useComboboxStore();
	const activeValue = store.useState( 'activeValue' );

	const onClose = useCallback( () => {
		if ( searchTerm.length ) {
			const bestMatch = findBestMatchByLabel( searchTerm, options );

			if ( bestMatch ) {
				setSearchTerm( bestMatch.label );
				setSelectedOption( bestMatch );
				onChange( bestMatch.value );
			} else {
				setSearchTerm( selectedOption?.label || '' );
			}
			// Search term was cleared, reselect the last selected option
		} else {
			setSearchTerm( selectedOption?.label || '' );
		}
	}, [
		searchTerm,
		options,
		onChange,
		setSelectedOption,
		setSearchTerm,
		selectedOption,
	] );

	return (
		<div
			ref={ controlRef }
			id={ controlId }
			className={ outerWrapperClasses }
		>
			<div className="components-combobox-control components-base-control wc-block-components-combobox-control">
				<ComboboxProvider
					store={ store }
					value={ searchTerm }
					selectedValue={ selectedOption?.value || '' }
					setValue={ ( val: string ) => {
						startTransition( () => {
							setSearchTerm( val );

							if ( val?.length ) {
								const exactMatch = findExactMatchByLabel(
									val,
									options
								);

								if (
									exactMatch &&
									exactMatch.value !== selectedOption?.value
								) {
									store.setState(
										'selectedValue',
										exactMatch.value
									);
									setSelectedOption( exactMatch );
									onChange( exactMatch.value );
								}
							}
						} );
					} }
					setSelectedValue={ ( val ) => {
						const option = options.find(
							( opt ) => opt.value === val
						);

						if ( option ) {
							setSearchTerm( option.label );
							setSelectedOption( option );
							onChange( option.value );
						}
					} }
				>
					<div className="components-base-control__field">
						<ComboboxLabel className="components-base-control__label">
							{ label }
						</ComboboxLabel>

						<div className="components-combobox-control__suggestions-container">
							<AriakitCombobox
								className="components-combobox-control__input components-form-token-field__input"
								autoComplete={ autoCompleteValue }
								aria-invalid={ ariaInvalid }
								aria-errormessage={ validationErrorId }
								type="text"
								onFocus={ () => {
									setSearchTerm( '' );
								} }
								{ ...restOfProps }
							/>
							<ComboboxPopover
								className="components-form-token-field__suggestions-list"
								sameWidth
								flip={ false }
								onClose={ onClose }
							>
								{ matchingSuggestions.map( ( option ) => (
									<ComboboxItem
										// For backwards compatibility we retain the is-selected class, in future we could target aria or data attributes in CSS instead.
										className={ `components-form-token-field__suggestion ${
											activeValue === option.value
												? 'is-selected'
												: ''
										}` }
										key={ option.label }
										value={ option.value }
										setValueOnClick={ () => {
											setSearchTerm( option.label );

											return false;
										} }
									>
										{ option.label }
									</ComboboxItem>
								) ) }
							</ComboboxPopover>
						</div>
					</div>
				</ComboboxProvider>
			</div>
			<ValidationInputError propertyName={ errorId } />
		</div>
	);
};

export default Combobox;
