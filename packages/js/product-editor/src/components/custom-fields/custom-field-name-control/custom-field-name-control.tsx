/**
 * External dependencies
 */
import type { Ref } from 'react';
import { ComboboxControl } from '@wordpress/components';
import {
	createElement,
	forwardRef,
	useEffect,
	useLayoutEffect,
	useMemo,
	useRef,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CustomFieldNameControlProps } from './types';
import { useInstanceId } from '@wordpress/compose';
import classNames from 'classnames';

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
			options,
			onChange,
			onFilterValueChange,
			...props
		}: CustomFieldNameControlProps,
		ref: Ref< HTMLInputElement >
	) {
		const comboboxRef = useRef< HTMLInputElement >();
		const id = useInstanceId(
			CustomFieldNameControl,
			'woocommerce-custom-field-name'
		);

		useLayoutEffect(
			function initializeRefs() {
				comboboxRef.current = document.querySelector(
					`.${ id } [role="combobox"]`
				) as HTMLInputElement;

				if ( typeof ref === 'function' ) {
					ref( comboboxRef.current );
				}
			},
			[ id ]
		);

		const {
			attrs,
			events,
		}: Record< string, Record< string, unknown > > = useMemo(
			function splitAttrsAndEvents() {
				return Object.entries( props ).reduce(
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
					{ attrs: {}, events: {} } as Record<
						string,
						Record< string, unknown >
					>
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
				console.log( events );
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

		return (
			<ComboboxControl
				allowReset={ allowReset }
				help={ help }
				hideLabelFromVision={ hideLabelFromVision }
				label={ label }
				messages={ messages }
				value={ value }
				options={ options }
				onChange={ onChange }
				onFilterValueChange={ onFilterValueChange }
				className={ classNames( id, className ) }
			/>
		);
	}
);
