/**
 * External dependencies
 */
import type { ForwardedRef } from 'react';
import { ComboboxControl as CoreCombobox } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import {
	createElement,
	forwardRef,
	useEffect,
	useLayoutEffect,
	useRef,
} from '@wordpress/element';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import type { ComboboxControlProps } from './types';

/*
 * Create an alias for the ComboboxControl core component,
 * but with the custom ComboboxControlProps interface.
 */
const Combobox = CoreCombobox as React.ComponentType< ComboboxControlProps >;

/**
 * This is a wrapper + a work around the Combobox to
 * expose important properties and events from the
 * internal input element that are required when
 * validating the field in the context of a form
 */
export const ComboboxControl = forwardRef( function ForwardedComboboxControl(
	{
		id,
		name,
		allowReset,
		className,
		help,
		hideLabelFromVision,
		label,
		messages,
		value,
		options,
		onFilterValueChange,
		onChange,
		onBlur,
		__experimentalRenderItem,
	}: ComboboxControlProps,
	ref: ForwardedRef< HTMLInputElement >
) {
	const inputElementRef = useRef< HTMLInputElement >();
	const generatedId = useInstanceId(
		ComboboxControl,
		'woocommerce-combobox-control'
	) as string;
	const currentId = id ?? generatedId;

	useLayoutEffect(
		/**
		 * The Combobox component does not expose the ref to the
		 * internal native input element removing the ability to
		 * focus the element when validating it in the context
		 * of a form
		 */
		function initializeRefs() {
			inputElementRef.current = document.querySelector(
				`.${ currentId } [role="combobox"]`
			) as HTMLInputElement;

			if ( name ) {
				inputElementRef.current?.setAttribute( 'name', name );
			}

			if ( ref ) {
				if ( typeof ref === 'function' ) {
					ref( inputElementRef.current );
				} else {
					ref.current = inputElementRef.current;
				}
			}
		},
		[ currentId, name, ref ]
	);

	useEffect(
		function overrideBlur() {
			/**
			 * The Combobox component clear the value of its internal
			 * input control when losing the focus, even when the
			 * selected value is set, affecting the validation behavior
			 * on bluring
			 */
			function handleBlur( event: FocusEvent ) {
				onBlur?.( {
					...event,
					target: {
						...event.target,
						value,
					},
				} as never );
			}

			inputElementRef.current?.addEventListener( 'blur', handleBlur );

			return () => {
				inputElementRef.current?.removeEventListener(
					'blur',
					handleBlur
				);
			};
		},
		[ value, onBlur ]
	);

	return (
		<Combobox
			allowReset={ allowReset }
			help={ help }
			hideLabelFromVision={ hideLabelFromVision }
			label={ label }
			messages={ messages }
			value={ value }
			options={ options }
			onChange={ onChange }
			onFilterValueChange={ onFilterValueChange }
			className={ classNames(
				'woocommerce-combobox-control',
				currentId,
				className
			) }
			__experimentalRenderItem={ __experimentalRenderItem }
		/>
	);
} );
