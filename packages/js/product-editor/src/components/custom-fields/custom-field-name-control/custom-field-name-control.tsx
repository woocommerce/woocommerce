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
import { __ } from '@wordpress/i18n';
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
		let options: ComboboxControlOption[] = [];

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
			[ id ]
		);

		const { attrs, events } = useMemo(
			function splitAttrsAndEvents() {
				return Object.entries( props ).reduce<
					Record< string, Record< string, unknown > >
				>(
					( current, [ key, value ] ) => {
						if ( value !== undefined ) {
							if ( key.startsWith( 'on' ) ) {
								const eventName = key
									.substring( 2 )
									.toLowerCase();
								current.events[ eventName ] = value as never;
							} else {
								current.attrs[ key ] = value as never;
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
				Object.entries( attrs ).forEach( ( [ key, value ] ) => {
					comboboxRef.current?.setAttribute( key, `${ value }` );
				} );
			},
			[ attrs ]
		);

		useEffect(
			function initializeEvents() {
				Object.entries( events ).forEach( ( [ key, value ] ) => {
					comboboxRef.current?.addEventListener(
						key,
						value as () => void
					);
				} );

				return () => {
					Object.entries( events ).forEach( ( [ key, value ] ) => {
						comboboxRef.current?.removeEventListener(
							key,
							value as () => void
						);
					} );
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
