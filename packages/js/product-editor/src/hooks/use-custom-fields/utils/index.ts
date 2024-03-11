/**
 * Internal dependencies
 */
import type { Metadata } from '../../../types';
import type { DisjoinMetas } from '../types';

export function isCustomField< T extends Metadata< string > >( value: T ) {
	return ! value.key.startsWith( '_' );
}

export function disjoinMetas< T extends Metadata< string > >(
	state: DisjoinMetas< T >,
	meta: T
): DisjoinMetas< T > {
	if ( isCustomField( meta ) ) {
		state.customFields.push( meta );
	} else {
		state.internalMetas.push( meta );
	}
	return state;
}
