/**
 * External dependencies
 */
import {
	Combobox as AriakitCombobox,
	ComboboxLabel as AriakitComboboxLabel,
	ComboboxPopover as AriakitComboboxPopover,
	ComboboxProvider as AriakitComboboxProvider,
	ComboboxItem as AriakitComboboxItem,
} from '@ariakit/react';
import type { ComboboxProps as AriakitComboboxProps } from '@ariakit/react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import {
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

	const popoverClasses = classnames(
		'components-form-token-field__suggestions-list'
	);

	const suggestionClasses = classnames(
		'components-form-token-field__suggestion'
	);

	const labelClasses = classnames( 'components-base-control__label' );

	const ariaInvalid = error?.message && ! error?.hidden ? 'true' : 'false';

	return (
		<div className={ outerWrapperClasses }>
			<div className={ innerWrapperClasses } ref={ controlRef }>
				<AriakitComboboxProvider
					value={ searchTerm }
					selectedValue={ initialOption?.label || '' }
					setValue={ ( val ) => {
						startTransition( () => {
							setSearchTerm( val );
						} );
					} }
					setSelectedValue={ ( val ) => {
						const option = options.find(
							( opt ) => opt.label === val
						);

						if ( option ) {
							onChange( option.value );
						}
					} }
				>
					<div className="components-base-control__field">
						<AriakitComboboxLabel className={ labelClasses }>
							{ label }
						</AriakitComboboxLabel>

						<div className="components-combobox-control__suggestions-container">
							<AriakitCombobox
								className={ comboboxClasses }
								autoComplete={ autoComplete }
								aria-invalid={ ariaInvalid }
								aria-errormessage={ validationErrorId }
								type="text"
							/>
							<AriakitComboboxPopover
								className={ popoverClasses }
								sameWidth
								flip={ false }
							>
								{ matchingSuggestions.map( ( option ) => (
									<AriakitComboboxItem
										className={ suggestionClasses }
										key={ option.label }
										value={ option.label }
									/>
								) ) }
							</AriakitComboboxPopover>
						</div>
					</div>
				</AriakitComboboxProvider>
			</div>
			<ValidationInputError propertyName={ errorId } />
		</div>
	);
};

export default Combobox;
