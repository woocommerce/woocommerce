/**
 * External dependencies
 */
import type { SetStateAction } from 'react';
import { useEntityProp } from '@wordpress/core-data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { Metadata } from '../../types';
import { disjoinMetas } from './utils';

export function useCustomFields<
	T extends Metadata< string > = Metadata< string >
>() {
	const [ metas, setMetas ] = useEntityProp< T[] >(
		'postType',
		'product',
		'meta_data'
	);

	const { customFields, otherMetas } = useMemo(
		function extractCustomFieldsFromMetas() {
			return metas.reduce( disjoinMetas< T >, {
				customFields: [],
				otherMetas: [],
			} );
		},
		[ metas ]
	);

	function setCustomFields( value: SetStateAction< T[] > ) {
		const newValue =
			typeof value === 'function' ? value( customFields ) : value;

		setMetas( [ ...otherMetas, ...newValue ] );
	}

	function addCustomFields( value: T[] ) {
		setCustomFields( ( current ) => [ ...current, ...value ] );
	}

	function updateCustomField( customField: T ) {
		setCustomFields( ( current ) =>
			current.map( ( field ) => {
				if ( customField.id && field.id === customField.id ) {
					return customField;
				}
				return field;
			} )
		);
	}

	function removeCustomField( customField: T ) {
		setCustomFields( ( current ) => {
			// If the id is undefined then it is a local copy.
			if ( customField.id === undefined ) {
				return current.filter( function isNotEquals( field ) {
					return ! (
						field.key === customField.key &&
						field.value === customField.value
					);
				} );
			}

			return current.map( ( field ) => {
				if ( field.id === customField.id ) {
					return {
						...field,
						value: null,
					};
				}

				return field;
			} );
		} );
	}

	return {
		customFields,
		addCustomFields,
		setCustomFields,
		updateCustomField,
		removeCustomField,
	};
}
