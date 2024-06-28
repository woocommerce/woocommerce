/**
 * External dependencies
 */
import type { ForwardedRef } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { ComboboxControl } from '@wordpress/components';
import { useDebounce, useInstanceId } from '@wordpress/compose';
import {
	createElement,
	forwardRef,
	useCallback,
	useEffect,
	useLayoutEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import type { ComboboxControlOption } from '../../attribute-combobox-field/types';
import type { CustomFieldNameControlProps } from './types';

async function searchCustomFieldNames( search?: string ) {
	return apiFetch< string[] >( {
		path: addQueryArgs( '/wc/v3/products/custom-fields/names', {
			search,
		} ),
	} ).then( ( customFieldNames = [] ) => {
		const options: ComboboxControlOption[] = [];

		if ( search && customFieldNames.indexOf( search ) === -1 ) {
			options.push( { value: search, label: search } );
		}

		customFieldNames.forEach( ( customFieldName ) => {
			options.push( {
				value: customFieldName,
				label: customFieldName,
			} );
		} );

		return options;
	} );
}

export const CustomFieldNameControl = forwardRef(
	function ForwardedCustomFieldNameControl(
		{
			allowReset,
			className,
			help,
			hideLabelFromVision,
			label,
			messages,
			value,
			onChange,
			onBlur,
			...props
		}: CustomFieldNameControlProps,
		ref: ForwardedRef< HTMLInputElement >
	) {
		const comboboxRef = useRef< HTMLInputElement >();
		const id = useInstanceId(
			CustomFieldNameControl,
			'woocommerce-custom-field-name'
		);

		const [ customFieldNames, setCustomFieldNames ] = useState<
			ComboboxControl.Props[ 'options' ]
		>( [] );

		useLayoutEffect(
			function initializeRefs() {
				comboboxRef.current = document.querySelector(
					`.${ id } [role="combobox"]`
				) as HTMLInputElement;

				if ( ref ) {
					if ( typeof ref === 'function' ) {
						ref( comboboxRef.current );
					} else {
						ref.current = comboboxRef.current;
					}
				}
			},
			[ id, ref ]
		);

		const { attrs, events } = useMemo(
			function splitAttrsAndEvents() {
				return Object.entries( props ).reduce<
					Record< string, Record< string, unknown > >
				>(
					( current, [ propName, propValue ] ) => {
						if ( propValue !== undefined ) {
							if ( propName.startsWith( 'on' ) ) {
								const eventName = propName
									.substring( 2 )
									.toLowerCase();
								current.events[ eventName ] =
									propValue as never;
							} else {
								current.attrs[ propName ] = propValue as never;
							}
						}

						return current;
					},
					{ attrs: {}, events: {} }
				);
			},
			[ props ]
		);

		useEffect(
			function initializeAttrs() {
				Object.entries( attrs ).forEach(
					( [ propName, propValue ] ) => {
						comboboxRef.current?.setAttribute(
							propName,
							`${ propValue }`
						);
					}
				);
			},
			[ attrs ]
		);

		useEffect(
			function initializeEvents() {
				Object.entries( events ).forEach(
					( [ propName, propValue ] ) => {
						comboboxRef.current?.addEventListener(
							propName,
							propValue as () => void
						);
					}
				);

				return () => {
					Object.entries( events ).forEach(
						( [ propName, propValue ] ) => {
							comboboxRef.current?.removeEventListener(
								propName,
								propValue as () => void
							);
						}
					);
				};
			},
			[ events ]
		);

		const handleFilterValueChange = useDebounce(
			useCallback(
				function onFilterValueChange( search: string ) {
					searchCustomFieldNames(
						search === '' ? value : search
					).then( setCustomFieldNames );
				},
				[ value ]
			),
			250
		);

		useEffect(
			function overrideBlur() {
				function handleBlur( event: FocusEvent ) {
					if ( comboboxRef.current ) {
						comboboxRef.current.value = value;
					}
					onBlur?.( event as never );
				}

				comboboxRef.current?.addEventListener( 'blur', handleBlur );

				return () => {
					comboboxRef.current?.removeEventListener(
						'blur',
						handleBlur
					);
				};
			},
			[ value, onBlur ]
		);

		return (
			<ComboboxControl
				allowReset={ allowReset }
				help={ help }
				hideLabelFromVision={ hideLabelFromVision }
				label={ label }
				messages={ messages }
				value={ value }
				options={ customFieldNames }
				onChange={ onChange }
				onFilterValueChange={ handleFilterValueChange }
				className={ classNames(
					id,
					'woocommerce-custom-field-name-control',
					className
				) }
			/>
		);
	}
);
