/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { useCallback, useMemo, useEffect, useRef } from '@wordpress/element';
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { STATE_ORDER } from '@woocommerce/block-settings';
import { clsx } from 'clsx';

/**
 * Internal dependencies
 */
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
}: StateInputWithStatesProps ): JSX.Element => {
	const countryStates = states[ country ];
	const options = useMemo< SelectOption[] >( () => {
		if ( countryStates && Object.keys( countryStates ).length > 0 ) {
			// Prefer the PHP-defined ordering. Object.keys reorders integer-like
			// string keys (e.g. "81") numerically, which loses the
			// merchant-defined order set via the `woocommerce_states` filter for
			// countries whose state codes are numeric. Fall back to Object.keys
			// when no explicit order is available (older payloads or
			// non-standard state lists injected by extensions).
			const orderedKeys = (
				STATE_ORDER[ country ]?.filter(
					( key ) => key in countryStates
				) ?? []
			).concat(
				Object.keys( countryStates ).filter(
					( key ) => ! STATE_ORDER[ country ]?.includes( key )
				)
			);
			const keys =
				orderedKeys.length > 0
					? orderedKeys
					: Object.keys( countryStates );
			return keys.map( ( key ) => ( {
				value: key,
				label: decodeEntities( countryStates[ key ] ),
			} ) );
		}
		return [];
	}, [ countryStates, country ] );

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
			<Select
				className={ clsx(
					className,
					'wc-block-components-state-input'
				) }
				options={ options }
				label={ label || '' }
				id={ id }
				onChange={ onChangeState }
				value={ value }
				autoComplete={ autoComplete }
				required={ required }
			/>
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
