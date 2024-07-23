/**
 * External dependencies
 */
import type { ForwardedRef } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { useDebounce } from '@wordpress/compose';
import {
	createElement,
	forwardRef,
	useCallback,
	useMemo,
	useState,
} from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { ComboboxControl, ComboboxControlProps } from '../../combobox-control';
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

export const CustomFieldNameControl = forwardRef(
	function ForwardedCustomFieldNameControl(
		{ value, onBlur, ...props }: CustomFieldNameControlProps,
		ref: ForwardedRef< HTMLInputElement >
	) {
		const [ customFieldNames, setCustomFieldNames ] = useState<
			ComboboxControlProps[ 'options' ]
		>( [] );

		const options = useMemo(
			/**
			 * Prepend the selected value as an option to let
			 * the Combobox know which option is the selected
			 * one even when an async request is being performed
			 *
			 * @return The combobox options.
			 */
			function prependSelectedValueAsOption() {
				if ( value ) {
					const isExisting = customFieldNames.some(
						( customFieldName ) => customFieldName.value === value
					);
					if ( ! isExisting ) {
						return [ { label: value, value }, ...customFieldNames ];
					}
				}
				return customFieldNames;
			},
			[ customFieldNames, value ]
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

		function handleBlur( event: React.FocusEvent< HTMLInputElement > ) {
			setCustomFieldNames( [] );
			onBlur?.( event );
		}

		return (
			<ComboboxControl
				{ ...props }
				ref={ ref }
				value={ value }
				options={ options }
				onFilterValueChange={ handleFilterValueChange }
				onBlur={ handleBlur }
			/>
		);
	}
);
