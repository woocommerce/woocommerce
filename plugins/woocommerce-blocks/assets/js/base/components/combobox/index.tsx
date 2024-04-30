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

/**
 * Internal dependencies
 */
import './style.scss';
import { normalizeTextString } from '../../utils/string';

interface ComboboxControlOption {
	label: string;
	value: string;
}

type WCComboboxProps = Omit< AriakitComboboxProps, 'onChange' > & {
	errorId: string | null;
	errorMessage?: string | undefined;
	instanceId?: string;
	onChange: ( filterValue: string ) => void;
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
	// Not the native onChange, a custom onChange that is called when the filter value changes.
	onChange,
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

	const matchingSuggestions = useMemo( () => {
		const startsWithMatch: ComboboxControlOption[] = [];
		const containsMatch: ComboboxControlOption[] = [];
		const match = normalizeTextString( searchTerm );

		options.forEach( ( option ) => {
			const index = normalizeTextString( option.label ).indexOf( match );
			if ( index === 0 ) {
				startsWithMatch.push( option );
			} else if ( index > 0 ) {
				containsMatch.push( option );
			}
		} );

		return startsWithMatch.concat( containsMatch );
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
		restOfProps.className || '',
		{
			'is-active': value,
			'has-error': error?.message && ! error?.hidden,
		}
	);

	const innerWrapperClasses = classnames(
		'components-base-control',
		'wc-block-components-combobox-control',
		'components-combobox-control'
	);

	const comboboxClasses = classnames(
		'components-combobox-control__input',
		'components-form-token-field__input'
	);

	const ariaInvalid = error?.message && ! error?.hidden ? 'true' : 'false';

	const store = useComboboxStore();

	const onClose = useCallback( () => {
		const { selectedValue } = store.getState();

		// If a value is not selected and there is no search term, fire an onChange to ensure we update
		// the value in the parent component.
		if ( ! searchTerm && ! selectedValue ) {
			onChange( '' );
			// If there is no search term but a selected value, set the search to match the selected value.
		} else if ( ! searchTerm && selectedValue ) {
			const opt = options.find(
				( option ) => option.value === selectedValue
			);

			if ( opt ) {
				setSearchTerm( opt.label );
			}
			// Otherwise if there is a search term see if it matches the selected value, (or label fallback),
			// if not reset it to the selected value.
		} else if ( searchTerm ) {
			const opt = options.find(
				( option ) => option.value === searchTerm
			);

			// Fallback to a label match
			const labelFallback = options.find( ( foundOpt ) => {
				return normalizeTextString( foundOpt.label ).startsWith(
					normalizeTextString( searchTerm )
				);
			} );

			if ( opt ) {
				setSearchTerm( opt.label );
				onChange( opt.value );
			} else if ( labelFallback ) {
				setSearchTerm( labelFallback.label );
				onChange( labelFallback.value );
			} else {
				const lastOpt = options.find(
					( option ) => option.value === selectedValue
				);
				setSearchTerm( lastOpt?.label || '' );
			}
		}
	}, [ searchTerm, store, onChange, options ] );

	return (
		<div className={ outerWrapperClasses }>
			<div className={ innerWrapperClasses } ref={ controlRef }>
				<ComboboxProvider
					store={ store }
					value={ searchTerm }
					selectedValue={ initialOption?.value || '' }
					setValue={ ( val ) => {
						startTransition( () => {
							setSearchTerm( val );
						} );

						const option = options.find(
							( opt ) =>
								normalizeTextString( opt.label ) ===
								normalizeTextString( val )
						);

						// If we find an exact match, change the selected value on behalf of user
						if ( option ) {
							store.setState( 'selectedValue', option.value );
							onChange( option.value );
						} else if ( val.length ) {
							// Fallback to a label match
							const foundOption = options.find( ( foundOpt ) => {
								return normalizeTextString(
									foundOpt.label
								).startsWith( normalizeTextString( val ) );
							} );

							if ( foundOption ) {
								store.setState(
									'selectedValue',
									foundOption.value
								);
								onChange( foundOption.value );
							}
						}
					} }
					setSelectedValue={ ( val ) => {
						const option = options.find(
							( opt ) => opt.label === val
						);

						if ( option ) {
							setSearchTerm( option.label );
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
								className={ comboboxClasses }
								autoComplete={ autoComplete }
								aria-invalid={ ariaInvalid }
								aria-errormessage={ validationErrorId }
								type="text"
							/>
							<ComboboxPopover
								className="components-form-token-field__suggestions-list"
								sameWidth
								flip={ false }
								onClose={ onClose }
							>
								{ matchingSuggestions.map( ( option ) => (
									<ComboboxItem
										className="components-form-token-field__suggestion"
										key={ option.label }
										value={ option.label }
									/>
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
