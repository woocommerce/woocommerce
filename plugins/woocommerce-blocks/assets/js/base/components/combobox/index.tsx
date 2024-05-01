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
import {
	findBestMatchByLabel,
	findExactMatchByLabel,
	findMatchingSuggestions,
} from './util';

export interface ComboboxControlOption {
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
		restOfProps.className || '',
		{
			'is-active': value,
			'has-error': error?.message && ! error?.hidden,
		}
	);

	const ariaInvalid = error?.message && ! error?.hidden ? 'true' : 'false';
	const store = useComboboxStore();
	const activeValue = store.useState( 'activeValue' );

	return (
		<div className={ outerWrapperClasses }>
			<div
				className="components-combobox-control components-base-control wc-block-components-combobox-control"
				ref={ controlRef }
			>
				<ComboboxProvider
					store={ store }
					value={ searchTerm }
					selectedValue={ selectedOption?.value || '' }
					setValue={ ( val ) => {
						console.log( 'set value happen', val );
						startTransition( () => {
							setSearchTerm( val );

							if ( val && val.length ) {
								const bestMatch =
									findExactMatchByLabel( val, options ) ||
									findBestMatchByLabel( val, options );

								if (
									bestMatch &&
									bestMatch.value !== selectedOption?.value
								) {
									store.setState(
										'selectedValue',
										bestMatch.value
									);
									setSelectedOption( bestMatch );
									onChange( bestMatch.value );
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
								autoComplete={ autoComplete }
								aria-invalid={ ariaInvalid }
								aria-errormessage={ validationErrorId }
								type="text"
								onFocus={ () => {
									setSearchTerm( '' );
								} }
							/>
							<ComboboxPopover
								className="components-form-token-field__suggestions-list"
								sameWidth
								flip={ false }
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
