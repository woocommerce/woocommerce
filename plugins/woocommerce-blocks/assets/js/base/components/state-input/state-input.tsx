/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { useCallback, useMemo, useEffect, useRef } from '@wordpress/element';
import {
	ValidatedTextInput,
	ValidationInputError,
} from '@woocommerce/blocks-components';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { clsx } from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';
import type { StateInputWithStatesProps } from './StateInputProps';
import { Select, SelectOption } from '../select';

const optionMatcher = (
	value: string,
	options: { label: string; value: string }[]
): string => {
	const foundOption = options.find(
		( option ) =>
			option.label.toLocaleUpperCase() === value.toLocaleUpperCase() ||
			option.value.toLocaleUpperCase() === value.toLocaleUpperCase()
	);
	return foundOption ? foundOption.value : '';
};

const StateInput = ( {
	className,
	id,
	states,
	country,
	label,
	onChange,
	autoComplete = 'off',
	value = '',
	required = false,
	errorId,
}: StateInputWithStatesProps ): JSX.Element => {
	const countryStates = states[ country ];
	const options = useMemo< SelectOption[] >( () => {
		if ( countryStates && Object.keys( countryStates ).length > 0 ) {
			const emptyStateOption: SelectOption = {
				value: '',
				label: sprintf(
					/* translators: %s will be the type of province depending on country, e.g "state" or "state/county" or "department" */
					__( 'Select a %s', 'woocommerce' ),
					label?.toLowerCase()
				),
				disabled: true,
			};

			return [
				emptyStateOption,
				...Object.keys( countryStates ).map( ( key ) => ( {
					value: key,
					label: decodeEntities( countryStates[ key ] ),
				} ) ),
			];
		}
		return [];
	}, [ countryStates, label ] );

	const validationError = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return (
			store.getValidationError( errorId || '' ) || {
				hidden: true,
			}
		);
	} );

	/**
	 * Handles state selection onChange events. Finds a matching state by key or value.
	 */
	const onChangeState = useCallback(
		( stateValue: string ) => {
			const newValue =
				options.length > 0
					? optionMatcher( stateValue, options )
					: stateValue;
			if ( newValue !== value ) {
				onChange( newValue );
			}
		},
		[ onChange, options, value ]
	);

	/**
	 * Track value changes.
	 */
	const valueRef = useRef< string >( value );

	useEffect( () => {
		if ( valueRef.current !== value ) {
			valueRef.current = value;
		}
	}, [ value ] );

	/**
	 * If given a list of options, ensure the value matches those options or trigger change.
	 */
	useEffect( () => {
		if ( options.length > 0 && valueRef.current ) {
			const match = optionMatcher( valueRef.current, options );
			if ( match !== valueRef.current ) {
				onChangeState( match );
			}
		}
	}, [ options, onChangeState ] );

	if ( options.length > 0 ) {
		return (
			<div
				className={ clsx(
					className,
					'wc-block-components-state-input',
					{
						'has-error': ! validationError.hidden,
					}
				) }
			>
				<Select
					options={ options }
					label={ label || '' }
					className={ `${ className || '' }` }
					id={ id }
					onChange={ ( newValue ) => {
						if ( required ) {
						}
						onChangeState( newValue );
					} }
					value={ value }
					autoComplete={ autoComplete }
					required={ required }
				/>
				{ validationError && validationError.hidden !== true && (
					<ValidationInputError
						errorMessage={ validationError.message }
					/>
				) }
			</div>
		);
	}

	return (
		<ValidatedTextInput
			className={ className }
			id={ id }
			label={ label }
			onChange={ onChangeState }
			autoComplete={ autoComplete }
			value={ value }
			required={ required }
		/>
	);
};

export default StateInput;
