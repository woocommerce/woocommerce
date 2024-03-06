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

	const { customFields, internalMetas } = useMemo(
		function extractCustomFieldsFromMetas() {
			return metas.reduce( disjoinMetas< T >, {
				customFields: [],
				internalMetas: [],
			} );
		},
		[ metas ]
	);

	function setCustomFields( value: SetStateAction< T[] > ) {
		const newValue =
			typeof value === 'function' ? value( customFields ) : value;

		setMetas( [ ...internalMetas, ...newValue ] );
	}

	function removeCustomField( key: T[ 'key' ] ) {
		setCustomFields( ( current ) =>
			current.filter( ( customField ) => customField.key !== key )
		);
	}

	return { customFields, removeCustomField };
}
