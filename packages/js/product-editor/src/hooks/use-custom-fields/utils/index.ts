/**
 * Internal dependencies
 */
import type { Metadata } from '../../../types';
import type { DisjoinMetas } from '../types';

export function isCustomField< T extends Metadata< string > >(
	customField: T
) {
	return ! customField.key.startsWith( '_' ) && customField.value !== null;
}

export function disjoinMetas< T extends Metadata< string > >(
	state: DisjoinMetas< T >,
	meta: T
): DisjoinMetas< T > {
	if ( isCustomField( meta ) ) {
		state.customFields.push( meta );
	} else {
		state.otherMetas.push( meta );
	}
	return state;
}
