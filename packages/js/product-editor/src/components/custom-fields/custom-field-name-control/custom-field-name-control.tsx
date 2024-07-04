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

/**
 * Since the Combobox does not support an arbitrary value, the
 * way to make it behave as an autocomplete, is by converting
 * the arbitrary value into an option so it can be selected as
 * a valid value
 *
 * @param search The seraching criteria.
 * @return The list of filtered custom field names as a Promise.
 */
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

/**
 * This is a wrapper + a work around the Combobox to
 * expose important properties and events from the
 * internal input element that are required when
 * validating the field in the context of a form
 */
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
			/**
			 * The Combobox component does not expose the ref to the
			 * internal native input element removing the ability to
			 * focus the element when validating it in the context
			 * of a form
			 */
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
			/**
			 * The Combobox component does not expose any attribute
			 * of the internal native input element removing the ability
			 * to set attribute's values like name important in the
			 * context of form submission
			 */
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
			/**
			 * The Combobox component does not expose any event
			 * of the internal native input element removing the ability
			 * to focus the element and other things related also to
			 * validations
			 */
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
				/**
				 * The Combobox component clear the value of its internal
				 * input control when losing the focus, even when the
				 * selected value is set, afecting the validation behavior
				 * on bluring
				 */
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
